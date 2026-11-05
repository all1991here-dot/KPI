@echo off
chcp 65001 >nul
setlocal
cd /d "%~dp0"
echo FarDU KPI - Akademik dizayn - installer 2
if not exist "%~dp0INSTALL-ACADEMIC.php" goto incomplete
if not exist "%~dp0files.sha256" goto incomplete
if not exist "%~dp0KPI\web\css\academic.css" goto incomplete
echo Mavjud KPI dasturingizdagi start-native.bat faylini tanlang.
set "KPI_TARGET="
for /f "delims=" %%P in ('powershell -NoProfile -STA -Command "[Console]::OutputEncoding=[System.Text.UTF8Encoding]::new($false); Add-Type -AssemblyName System.Windows.Forms; $d=New-Object System.Windows.Forms.OpenFileDialog; $d.Title='Mavjud KPI papkasidagi start-native.bat faylini tanlang'; $d.Filter='KPI launcher (start-native.bat)|start-native.bat'; if($d.ShowDialog() -eq 'OK'){[System.IO.Path]::GetDirectoryName($d.FileName)}"') do set "KPI_TARGET=%%P"
if not defined KPI_TARGET goto cancelled
set "KPI_PHP="
if exist "%KPI_TARGET%\php\php.exe" set "KPI_PHP=%KPI_TARGET%\php\php.exe"
if not defined KPI_PHP if exist "C:\xampp\php\php.exe" set "KPI_PHP=C:\xampp\php\php.exe"
if not defined KPI_PHP if exist "C:\php\php.exe" set "KPI_PHP=C:\php\php.exe"
if not defined KPI_PHP for /f "delims=" %%P in ('where php.exe 2^>nul') do if not defined KPI_PHP set "KPI_PHP=%%P"
if not defined KPI_PHP goto missingphp
"%KPI_PHP%" -n "%~dp0INSTALL-ACADEMIC.php" "%KPI_TARGET%"
if errorlevel 1 goto failed
echo Dastur ochiq bolmasa, avvalgidek start-native.bat orqali oching.
pause
exit /b 0
:incomplete
echo XATO: Yangilash paketi toliq ochilmagan.
echo ZIP ustida ong tugma - Extract All / Izvlech vse ni tanlang.
echo Ochilgan papkada INSTALL-ACADEMIC.bat, INSTALL-ACADEMIC.php,
echo files.sha256 va KPI papkasi birga turishi kerak.
echo BATni ZIP ichidan ishga tushirmang.
goto failed
:cancelled
echo Fayl tanlanmadi. Dastur ozgartirilmadi.
goto failed
:missingphp
echo XATO: PHP topilmadi. Mavjud dastur papkasida php\php.exe bolishi kerak.
:failed
echo Yuqoridagi XATO xabarini tekshiring. Bu oynani yopishdan oldin rasmga oling.
pause
exit /b 1
