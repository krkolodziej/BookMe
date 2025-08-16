#!/bin/bash

# Przygotowanie aplikacji do produkcji
echo "Przygotowywanie aplikacji do produkcji..."

# Zainstaluj zależności produkcyjne
composer install --no-dev --optimize-autoloader

# Wyczyść cache
php bin/console cache:clear --env=prod --no-debug

# Zbuduj assety
npm install
npm run build

# Stwórz bazę danych i uruchom migracje
php bin/console doctrine:database:create --if-not-exists --env=prod
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# Załaduj fixtures (opcjonalnie)
# php bin/console doctrine:fixtures:load --no-interaction --env=prod

echo "Aplikacja gotowa do produkcji!"