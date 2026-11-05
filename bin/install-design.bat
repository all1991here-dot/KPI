@echo off
chcp 65001 >nul
setlocal
cd /d "%~dp0"
echo KPI - Dizayn v2 yangilanishi
echo Bu dastur faqat ko'rinish fayllarini yangilaydi.
echo Hozir ochiladigan oynada MAVJUD KPI dasturingizdagi start-native.bat faylini tanlang.
echo.
set "KPI_TARGET="
for /f "delims=" %%P in ('powershell -NoProfile -STA -Command "[Console]::OutputEncoding=[System.Text.UTF8Encoding]::new($false); Add-Type -AssemblyName System.Windows.Forms; $d=New-Object System.Windows.Forms.OpenFileDialog; $d.Title='Mavjud KPI papkasidagi start-native.bat faylini tanlang'; $d.Filter='KPI launcher (start-native.bat)|start-native.bat'; if($d.ShowDialog() -eq 'OK'){[System.IO.Path]::GetDirectoryName($d.FileName)}"') do set "KPI_TARGET=%%P"
if not defined KPI_TARGET (
  echo Fayl tanlanmadi. Yangilash bekor qilindi.
  pause
  exit /b 1
)
if not exist "%KPI_TARGET%\views\layouts\main.php" (
  echo Tanlangan papka KPI dasturiga mos emas. Hech narsa o'zgartirilmadi.
  pause
  exit /b 1
)
set "KPI_BACKUP=%KPI_TARGET%\runtime\design-backup-%RANDOM%-%RANDOM%"
mkdir "%KPI_BACKUP%"
if errorlevel 1 goto failed
for %%F in ("services\Ui.php" "views\layouts\main.php" "views\kpi\index.php" "views\auth\login.php" "web\css\design.css" "web\js\app.js") do (
  call :copy_one "%%~F"
  if errorlevel 1 goto failed
)
echo.
echo TAYYOR. Yangi dizayn o'rnatildi.
echo Brauzeringizda Ctrl+F5 bosing.
echo Dastur ochiq bo'lmasa, avvalgidek start-native.bat orqali oching.
echo Avvalgi dizayn nusxasi: "%KPI_BACKUP%"
echo Baza, parollar va hujjatlar o'zgartirilmadi.
pause
exit /b 0
:copy_one
if not exist "%~dp0KPI\%~1" exit /b 1
for %%D in ("%KPI_TARGET%\%~1") do if not exist "%%~dpD" mkdir "%%~dpD"
if errorlevel 1 exit /b 1
if exist "%KPI_TARGET%\%~1" (
  for %%D in ("%KPI_BACKUP%\%~1") do if not exist "%%~dpD" mkdir "%%~dpD"
  copy /y "%KPI_TARGET%\%~1" "%KPI_BACKUP%\%~1" >nul
  if errorlevel 1 exit /b 1
)
copy /y "%~dp0KPI\%~1" "%KPI_TARGET%\%~1" >nul
exit /b %errorlevel%
:failed
echo Yangilash tugamadi. Avvalgi fayllar mavjud zaxiradan qaytarilmoqda.
for %%F in ("services\Ui.php" "views\layouts\main.php" "views\kpi\index.php" "views\auth\login.php" "web\css\design.css" "web\js\app.js") do if exist "%KPI_BACKUP%\%%~F" copy /y "%KPI_BACKUP%\%%~F" "%KPI_TARGET%\%%~F" >nul
echo Qora oynaning skrinshotini yordam uchun yuboring.
pause
exit /b 1
