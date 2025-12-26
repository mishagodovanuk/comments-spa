#!/usr/bin/env bash
set -euo pipefail

SAIL="./vendor/bin/sail"

echo "==> Up containers"
$SAIL up -d

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

echo "==> Laravel: migrate"
$SAIL artisan migrate --force

echo "==> Elasticsearch: create index + sync comments"
$SAIL artisan elastic:comments-create-index --force
$SAIL artisan elastic:comments-sync

echo "==> Ensure queue worker is running"
$SAIL up -d queue 2>/dev/null || true

echo "==> Done"
