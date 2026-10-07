@echo off
chcp 65001 >nul
title GS Sportif Urunler - Yerel Sunucu
cd /d "%~dp0"

where php >nul 2>nul
if errorlevel 1 (
    echo.
    echo  PHP bulunamadi!
    echo  PowerShell'i acip su komutu calistirin:  winget install PHP.PHP.8.3
    echo  Kurulumdan sonra bilgisayari yeniden baslatin ve bu dosyaya tekrar cift tiklayin.
    echo.
    pause
    exit /b
)

rem PHP'nin gercekte kurulu oldugu klasoru bul (eklentiler icin)
for /f "delims=" %%i in ('php -r "echo dirname(realpath(PHP_BINARY));"') do set "PHPDIR=%%i"

rem Windows'ta kapali gelen gerekli eklentileri yalnizca eksikse ac
set "EXT="
php -m | findstr /i /x "pdo_sqlite" >nul || set "EXT=%EXT% -d extension=pdo_sqlite"
php -m | findstr /i /x "mbstring" >nul || set "EXT=%EXT% -d extension=mbstring"
php -m | findstr /i /x "gd" >nul || set "EXT=%EXT% -d extension=gd"
if defined EXT set "EXT=-d extension_dir="%PHPDIR%\ext"%EXT%"

echo.
echo  ============================================================
echo   GS Sportif Urunler calisiyor
echo.
echo   Magaza:          http://localhost:8000
echo   Yonetim paneli:  http://localhost:8000/admin
echo   Giris:           admin@gssportif.local  /  Admin123!
echo.
echo   Kapatmak icin bu pencereyi kapatin (veya Ctrl+C).
echo  ============================================================
echo.
start "" http://localhost:8000
php %EXT% -S localhost:8000 router.php
pause
