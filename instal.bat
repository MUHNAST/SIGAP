@echo off
title INSTALL SIGAP

echo ==========================================
echo           INSTALLASI SIGAP
echo ==========================================
echo.

echo [1/7] Composer Install...
call composer install --no-dev --optimize-autoloader

echo.
echo [3/7] Storage Link...
php artisan storage:link

echo.
echo [6/7] Optimasi Laravel...
php artisan optimize

echo.
echo [7/7] Selesai.
echo ==========================================
echo SIGAP berhasil diinstall.
echo ==========================================
pause
