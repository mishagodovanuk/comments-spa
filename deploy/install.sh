#!/usr/bin/env bash
set -euo pipefail

if [ ! -f .env ]; then
  echo "==> Copying .env.example to .env"
  cp .env.example .env
fi

echo "==> Up containers (docker compose)"
docker compose up -d

echo "==> Wait for MySQL"
docker compose exec -T mysql sh -lc '
for i in $(seq 1 120); do
  # 1) ping daemon
  if mysqladmin ping -h "localhost" -uroot -p"${MYSQL_ROOT_PASSWORD:-}" --silent 2>/dev/null; then
    exit 0
  fi

  # 2) fallback: simple query (інколи ping бреше/падає без причин)
  if mysql -h "localhost" -uroot -p"${MYSQL_ROOT_PASSWORD:-}" -e "SELECT 1" >/dev/null 2>&1; then
    exit 0
  fi

  sleep 1
done

echo "MySQL not ready"
echo "--- mysql logs (tail 80) ---"
tail -n 80 /var/log/mysql/error.log 2>/dev/null || true
exit 1
'

echo "==> Wait for Redis"
docker compose exec -T redis sh -lc '
i=1
while [ $i -le 60 ]; do
  redis-cli ping | grep -q PONG && exit 0
  i=$((i+1))
  sleep 1
done
echo "Redis not ready"
exit 1
'

echo "==> Install Composer dependencies (inside container)"
docker compose exec -T laravel.test bash -lc '
cd /var/www/html
composer install --no-interaction --prefer-dist
'

echo "==> Restart app container (supervisor/web pick up vendor)"
docker compose restart laravel.test

echo "==> Ensure APP_KEY exists"
docker compose exec -T laravel.test bash -lc '
cd /var/www/html
php artisan key:generate --force >/dev/null 2>&1 || true
'

echo "==> Install NPM dependencies"
docker compose exec -T laravel.test bash -lc '
cd /var/www/html
npm ci || npm install
'

echo "==> Build frontend assets"
docker compose exec -T laravel.test bash -lc '
cd /var/www/html
npm run build
'

echo "==> Fix permissions + HTMLPurifier cache (storage)"
docker compose exec -T laravel.test sh -lc '
cd /var/www/html

grep -q "^HTMLPURIFIER_CACHE_PATH=" .env || echo "HTMLPURIFIER_CACHE_PATH=/var/www/html/storage/app/htmlpurifier" >> .env

mkdir -p storage/app/htmlpurifier

mkdir -p storage/framework/{cache,views,sessions,testing} bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
'

echo "==> Wait for Elasticsearch"
docker compose exec -T laravel.test bash -lc '
for i in $(seq 1 120); do
  curl -fsS --max-time 2 http://elasticsearch:9200 >/dev/null && exit 0
  sleep 1
done
echo "Elasticsearch not ready"
exit 1
'

echo "==> Laravel: clear caches"
docker compose exec -T laravel.test bash -lc 'cd /var/www/html && php artisan optimize:clear'

echo "==> Laravel: migrate database"
docker compose exec -T laravel.test bash -lc 'cd /var/www/html && php artisan migrate --force'

echo "==> Elasticsearch: create index + sync comments"
docker compose exec -T laravel.test bash -lc 'cd /var/www/html && php artisan elastic:comments-create-index --force'
docker compose exec -T laravel.test bash -lc 'cd /var/www/html && php artisan elastic:comments-sync'

echo "==> Ensure queue worker is running"
docker compose up -d queue

echo "==> Health check (HTTP)"
curl -fsS --max-time 5 http://localhost/ >/dev/null || true

echo "==> Done"
