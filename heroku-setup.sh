#!/bin/bash

echo "=== KONFIGURACJA HEROKU ==="

# 1. Sprawdź czy jesteś zalogowany
echo "1. Sprawdzanie logowania do Heroku..."
heroku auth:whoami

# 2. Utwórz aplikację (użyj swojej nazwy)
echo "2. Tworzenie aplikacji Heroku..."
echo "Podaj nazwę aplikacji (np. moja-bookme-app) lub naciśnij Enter dla automatycznej nazwy:"
read app_name

if [ -z "$app_name" ]; then
    heroku create
else
    heroku create $app_name
fi

# 3. Wygeneruj nowy APP_SECRET
echo "3. Generowanie APP_SECRET..."
app_secret=$(openssl rand -hex 32 2>/dev/null || echo "e98cda9639951bfe6558dc3bcb35bcfb")

# 4. Ustaw zmienne środowiskowe
echo "4. Ustawianie zmiennych środowiskowych..."
heroku config:set APP_ENV=prod
heroku config:set APP_DEBUG=false
heroku config:set APP_SECRET=$app_secret
heroku config:set DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"

# 5. Dodaj buildpacki dla PHP
echo "5. Dodawanie buildpacków..."
heroku buildpacks:add heroku/nodejs
heroku buildpacks:add heroku/php

# 6. Sprawdź konfigurację
echo "6. Sprawdzanie konfiguracji..."
heroku config

echo ""
echo "=== KONFIGURACJA ZAKOŃCZONA ==="
echo "Teraz możesz wdrożyć aplikację poleceniem:"
echo "git push heroku main"