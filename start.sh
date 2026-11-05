#!/bin/sh
set -eu
cd "$(dirname "$0")"
export KPI_UID="$(id -u)"
export KPI_GID="$(id -g)"
if ! command -v docker >/dev/null 2>&1; then
  echo 'Docker / Docker Desktop o‘rnating va ishga tushiring.' >&2
  exit 1
fi
mkdir -p runtime
if [ ! -f vendor/autoload.php ]; then
  docker compose run --rm --no-deps --user "$KPI_UID:$KPI_GID" --entrypoint composer app install --no-dev --prefer-dist --no-interaction --no-scripts
fi
docker compose run --rm --no-deps --user "$KPI_UID:$KPI_GID" --entrypoint php app yii setup/init
docker compose up -d
docker compose exec -T app php bin/wait.php
echo 'KPI ishga tushdi. Brauzerda http://localhost:8000 manzilini oching.'
if [ -f runtime/initial-admin.txt ]; then
  echo 'Kirish ma’lumotlari runtime/initial-admin.txt faylida. Parolni almashtirgach faylni o‘chiring.'
fi
