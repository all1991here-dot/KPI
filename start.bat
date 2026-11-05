@echo off
setlocal
cd /d "%~dp0"
where docker >nul 2>nul
if errorlevel 1 (
  echo Avval Docker Desktop o'rnating va ishga tushiring.
  pause
  exit /b 1
)
if not exist runtime mkdir runtime
if not exist vendor\autoload.php (
  docker compose run --rm --no-deps --user 1000:1000 --entrypoint composer app install --no-dev --prefer-dist --no-interaction --no-scripts
  if errorlevel 1 goto failed
)
docker compose run --rm --no-deps --user 1000:1000 --entrypoint php app yii setup/init
if errorlevel 1 goto failed
docker compose up -d
if errorlevel 1 goto failed
docker compose exec -T app php bin/wait.php
if errorlevel 1 goto failed
echo KPI tayyor: http://localhost:8000
if exist runtime\initial-admin.txt start notepad runtime\initial-admin.txt
start http://localhost:8000
pause
exit /b 0
:failed
echo Ishga tushirish tugamadi. Yuqoridagi xabarni tekshiring.
pause
exit /b 1
