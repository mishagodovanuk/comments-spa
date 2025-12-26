#!/usr/bin/env bash
set -euo pipefail

SAIL="./vendor/bin/sail"

if [ ! -f .env ]; then
  echo "==> Copying .env.example to .env"
  cp .env.example .env
fi

echo "==> Up containers"
$SAIL up -d

echo "==> Install Composer dependencies"
$SAIL exec -T laravel.test bash -lc '
if [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist
fi
'

echo "==> Install NPM dependencies"
$SAIL exec -T laravel.test bash -lc '
if [ ! -d node_modules ]; then
  npm install
fi
'

echo "==> Build frontend assets"
$SAIL exec -T laravel.test bash -lc '
npm run build
'

echo "==> Wait for MySQL"
$SAIL exec -T mysql bash -lc '
for i in {1..90}; do
  mysqladmin ping -h "mysql" -p"${MYSQL_ROOT_PASSWORD:-password}" --silent && exit 0
  sleep 1
 done
exit 1
' || true

echo "==> Wait for Redis"
$SAIL exec -T redis sh -lc '
for i in {1..60}; do
  redis-cli ping | grep -q PONG && exit 0
  sleep 1
done
exit 1
'

echo "==> Wait for Elasticsearch"
$SAIL exec -T laravel.test bash -lc '
for i in {1..120}; do
  curl -fsS http://elasticsearch:9200 >/dev/null && exit 0
  sleep 1
done
echo "Elasticsearch not ready"
exit 1
'

echo "==> Laravel: clear caches"
$SAIL artisan optimize:clear

echo "==> Laravel: migrate database"
$SAIL artisan migrate --force

echo "==> Elasticsearch: create index + sync comments"
$SAIL artisan elastic:comments-create-index --force
$SAIL artisan elastic:comments-sync

echo "==> Ensure queue worker is running"
$SAIL up -d queue 2>/dev/null || true

echo "==> Done"
