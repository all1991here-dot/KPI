@echo off
setlocal
cd /d "%~dp0"
set "KPI_NATIVE_PHP="
if exist "%~dp0php\php.exe" set "KPI_NATIVE_PHP=%~dp0php\php.exe"
if not defined KPI_NATIVE_PHP if exist "C:\xampp\php\php.exe" set "KPI_NATIVE_PHP=C:\xampp\php\php.exe"
if not defined KPI_NATIVE_PHP if exist "C:\php\php.exe" set "KPI_NATIVE_PHP=C:\php\php.exe"
if not defined KPI_NATIVE_PHP for /f "delims=" %%P in ('where php.exe 2^>nul') do if not defined KPI_NATIVE_PHP set "KPI_NATIVE_PHP=%%P"
if not defined KPI_NATIVE_PHP (
  echo PHP topilmadi. Docker kerak emas, lekin PHP 8.3 yoki yangiroq kerak.
  echo https://windows.php.net/download/ dan Windows x64 PHP ZIP yuklang.
  echo ZIP tarkibini shu KPI papkasi ichidagi php papkasiga oching.
  echo Natija: KPI\php\php.exe
  echo Keyin start-native.bat ni qayta oching.
  pause
  exit /b 1
)
"%KPI_NATIVE_PHP%" bin\native.php
if errorlevel 1 echo Ishga tushirish tugamadi. Yuqoridagi xabarni tekshiring.
pause
