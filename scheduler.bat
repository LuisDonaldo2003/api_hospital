@echo off
title Laravel Scheduler - Sistema Hospitalario
color 0A

echo ========================================================
echo     SISTEMA DE REPORTES AUTOMATICOS - HOSPITALARIO
echo ========================================================
echo.
echo 🚀 Iniciando Laravel Scheduler...
echo 📧 Reportes automaticos: ACTIVOS
echo 🕛 Envio programado: 12:00 AM diariamente
echo ⚡ Verificando reportes perdidos...
echo.

cd /d "C:\Hospital\api_hospital"

echo 🔍 Verificando reportes perdidos al iniciar el sistema...
php artisan reports:check-missed
echo.

echo ✅ Sistema de reportes completamente activo
echo 📊 Presiona Ctrl+C para detener
echo ========================================================
echo.

:loop
echo [%date% %time%] Ejecutando scheduler...
php artisan schedule:run
timeout /t 60 /nobreak >nul
goto loop
