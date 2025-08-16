#!/bin/bash

# Wdrożenie na Heroku
echo "Wdrażanie na Heroku..."

# Zaloguj się do Heroku (jeśli nie jesteś zalogowany)
# heroku login

# Utwórz aplikację Heroku
heroku create twoja-nazwa-aplikacji

# Ustaw zmienne środowiskowe
heroku config:set APP_ENV=prod
heroku config:set APP_DEBUG=false
heroku config:set APP_SECRET=$(openssl rand -hex 32)

# Wdróż aplikację
git push heroku main

echo "Aplikacja wdrożona na Heroku!"