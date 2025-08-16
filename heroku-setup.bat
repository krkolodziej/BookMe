@echo off
echo === KONFIGURACJA HEROKU ===

echo 1. Sprawdzanie logowania do Heroku...
heroku auth:whoami

echo 2. Tworzenie aplikacji Heroku...
set /p app_name="Podaj nazwe aplikacji (np. moja-bookme-app) lub nacisnij Enter dla automatycznej nazwy: "

if "%app_name%"=="" (
    heroku create
) else (
    heroku create %app_name%
)

echo 3. Ustawianie zmiennych srodowiskowych...
heroku config:set APP_ENV=prod
heroku config:set APP_DEBUG=false
heroku config:set APP_SECRET=e98cda9639951bfe6558dc3bcb35bcfb123456789abcdef
heroku config:set DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

echo 4. Dodawanie buildpackow...
heroku buildpacks:add heroku/nodejs
heroku buildpacks:add heroku/php

echo 5. Sprawdzanie konfiguracji...
heroku config

echo.
echo === KONFIGURACJA ZAKONCZONA ===
echo Teraz mozesz wdrozyc aplikacje poleceniem:
echo git push heroku main