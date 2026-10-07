@echo off
title GS Sportif Urunler - Yerel Sunucu
cd /d "%~dp0"
echo.
echo  GS Sportif Urunler calisiyor:  http://localhost:8000
echo  Yonetim paneli:                http://localhost:8000/admin
echo  Kapatmak icin bu pencereyi kapatin (veya Ctrl+C).
echo.
start "" http://localhost:8000
php -S localhost:8000 router.php
pause
