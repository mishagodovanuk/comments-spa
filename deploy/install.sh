#!/usr/bin/env bash
set -euo pipefail

dc() { docker compose "$@"; }

echo "==> Ensure .env exists"
if [ ! -f .env ]; then
  echo "==> Copying .env.example to .env"
  cp .env.example .env
fi

echo "==> Up infra (mysql/redis/elasticsearch)"
dc up -d mysql redis elasticsearch

echo "==> Wait for MySQL (container + ready)"
# 1) wait container exists & running
for i in $(seq 1 60); do
  if dc ps --status=running --services | grep -qx mysql; then
    break
  fi
  sleep 1
done

if ! dc ps --status=running --services | grep -qx mysql; then
  echo "MySQL container is not running"
  echo "==> docker compose ps"
  dc ps || true
  echo "==> MySQL logs"
  dc logs --tail=200 mysql || true
  exit 1
fi

# 2) wait mysql daemon ready
if ! dc exec -T mysql sh -lc '
for i in $(seq 1 180); do
  mysqladmin ping -h localhost -uroot -p"${MYSQL_ROOT_PASSWORD:-}" --silent >/dev/null 2>&1 && exit 0
  sleep 1
done
exit 1
'; then
  echo "MySQL not ready"
  echo "==> MySQL logs"
  dc logs --tail=200 mysql || true
  exit 1
fi

echo "==> Wait for Redis"
if ! dc exec -T redis sh -lc '
for i in $(seq 1 60); do
  redis-cli ping 2>/dev/null | grep -q PONG && exit 0
  sleep 1
done
exit 1
'; then
  echo "Redis not ready"
  echo "==> Redis logs"
  dc logs --tail=200 redis || true
  exit 1
fi

echo "==> Wait for Elasticsearch"
if ! dc exec -T elasticsearch sh -lc '
for i in $(seq 1 180); do
  if command -v wget >/dev/null 2>&1; then
    wget -qO- --timeout=2 http://localhost:9200 >/dev/null 2>&1 && exit 0
  fi
  if command -v curl >/dev/null 2>&1; then
    curl -fsS --max-time 2 http://localhost:9200 >/dev/null 2>&1 && exit 0
  fi
  sleep 1
done
exit 1
'; then
  echo "Elasticsearch not ready"
  echo "==> Elasticsearch logs"
  dc logs --tail=200 elasticsearch || true
  exit 1
fi

echo "==> Up app (laravel.test/queue)"
dc up -d laravel.test queue

echo "==> Install Composer dependencies (inside container)"
dc exec -T laravel.test sh -lc '
cd /var/www/html
composer install --no-interaction --prefer-dist
'

echo "==> Fix permissions (storage + bootstrap/cache) + Purifier cache dir"
dc exec -T laravel.test sh -lc '
set -e
cd /var/www/html

# Laravel writable dirs
mkdir -p storage/framework/{cache,views,sessions,testing} storage/logs bootstrap/cache

# Purifier cache dir from config/purifier.php: cachePath => storage/app/purifier
mkdir -p storage/app/purifier

# Sail app user
chown -R sail:sail storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
'

echo "==> Ensure public/storage symlink"
dc exec -T laravel.test sh -lc 'cd /var/www/html && bash docker/ensure-storage-link.sh'


echo "==> Check CommentSanitizer uses mews/purifier profile (default)"
dc exec -T laravel.test sh -lc '
set -e
cd /var/www/html

FILE="app/Services/Comment/CommentSanitizer.php"

test -f "$FILE" || { echo "ERROR: $FILE not found"; exit 1; }

grep -q "Purifier::clean" "$FILE" || {
  echo "ERROR: $FILE must use mews/purifier (Purifier::clean) to honor config/purifier.php and avoid vendor cache writes."
  echo "Expected something like: return Purifier::clean(\$raw, \"default\");"
  exit 1
}

grep -q "Purifier::clean(.*default" "$FILE" || {
  echo "ERROR: Purifier::clean must be called with profile \"default\"."
  echo "Expected: Purifier::clean(\$raw, \"default\")"
  exit 1
}
'

echo "==> Ensure APP_KEY exists"
dc exec -T laravel.test sh -lc '
cd /var/www/html
php artisan key:generate --force >/dev/null 2>&1 || true
'

echo "==> Install NPM dependencies"
dc exec -T laravel.test sh -lc '
cd /var/www/html
npm ci || npm install
'

echo "==> Build frontend assets"
dc exec -T laravel.test sh -lc '
cd /var/www/html
npm run build
'

echo "==> Laravel: clear caches"
dc exec -T laravel.test sh -lc 'cd /var/www/html && php artisan optimize:clear'

echo "==> Laravel: migrate database"
# Before migrate: ensure app container resolves mysql & can connect
dc exec -T laravel.test sh -lc '
for i in $(seq 1 60); do
  getent hosts mysql >/dev/null 2>&1 && break
  sleep 1
done
getent hosts mysql >/dev/null 2>&1 || { echo "NO DNS for mysql from app container"; exit 1; }

php -r '\''exit(@fsockopen("mysql", 3306) ? 0 : 1);'\'' || { echo "MySQL not reachable from app container"; exit 1; }
'

dc exec -T laravel.test sh -lc 'cd /var/www/html && php artisan migrate --force'

echo "==> Elasticsearch: create index + sync comments"
dc exec -T laravel.test sh -lc 'cd /var/www/html && php artisan elastic:comments-create-index --force'
dc exec -T laravel.test sh -lc 'cd /var/www/html && php artisan elastic:comments-sync'

echo "==> Ensure queue worker is running"
dc up -d queue

echo "==> Health check (HTTP)"
curl -fsS --max-time 5 http://localhost/ >/dev/null || true

echo "==> Done"
