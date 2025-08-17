# BookMe - System Rezerwacji Usług

BookMe to aplikacja webowa do zarządzania rezerwacjami usług zbudowana w oparciu o framework Symfony 7. System umożliwia kompleksowe zarządzanie procesem rezerwacji od wyboru usługi przez klienta do jej realizacji przez pracowników.

## Funkcjonalności

### Dla Klientów
- **Przeglądanie usług** - kategoryzowane usługi z opisami i zdjęciami
- **Rezerwacja terminów** - wybór dostępnych terminów u konkretnych pracowników
- **Zarządzanie rezerwacjami** - przeglądanie i edycja własnych rezerwacji
- **System opinii** - wystawianie ocen i komentarzy po zakończonej usłudze
- **Powiadomienia** - informacje o statusie rezerwacji
- **Wyszukiwarka** - znajdowanie usług według kategorii i lokalizacji

### Dla Pracowników
- **Kalendarz rezerwacji** - przeglądanie przypisanych terminów
- **Zarządzanie dostępnością** - ustalanie godzin pracy
- **Historia usług** - przegląd wykonanych rezerwacji

### Panel Administracyjny
- **Zarządzanie usługami** - dodawanie, edycja kategorii i opisów usług
- **Zarządzanie pracownikami** - przypisywanie pracowników do usług
- **Zarządzanie ofertami** - tworzenie pakietów usług
- **Godziny otwarcia** - konfiguracja dostępności systemu
- **Zarządzanie użytkownikami** - administracja kontami klientów
- **System opinii** - moderacja komentarzy i ocen

## Technologie

- **Backend**: Symfony 7.0 (PHP 8.2+)
- **Database**: Doctrine ORM z SQLite
- **Frontend**: Twig templates + Vanilla JavaScript
- **Build Tools**: Webpack Encore
- **CSS Libraries**: FontAwesome 6.7, Swiper.js
- **Testing**: PHPUnit 11.5
- **Security**: Symfony Security Bundle z autentykacją formularzową

## Wymagania Systemowe

- PHP 8.2 lub wyższy
- Composer
- Node.js i npm
- SQLite (domyślnie) lub MySQL/PostgreSQL

## Instalacja

### 1. Klonowanie repozytorium
```bash
git clone <repository-url>
cd BookMe
```

### 2. Instalacja zależności PHP
```bash
composer install
```

### 3. Instalacja zależności JavaScript
```bash
npm install
```

### 4. Konfiguracja środowiska
```bash
# Skopiuj plik .env i dostosuj konfigurację
cp .env .env.local

# Edytuj .env.local i ustaw:
# APP_ENV=dev
# DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
```

### 5. Przygotowanie bazy danych
```bash
# Tworzenie bazy danych
php bin/console doctrine:database:create

# Wykonanie migracji
php bin/console doctrine:migrations:migrate

# Załadowanie danych testowych (opcjonalnie)
php bin/console doctrine:fixtures:load
```

### 6. Budowa zasobów frontend
```bash
# Dla środowiska deweloperskiego
npm run dev

# Lub tryb watch dla automatycznego przebudowywania
npm run watch

# Dla produkcji
npm run build
```

### 7. Uruchomienie serwera deweloperskiego
```bash
symfony server:start
# lub
php bin/console server:run
```

Aplikacja będzie dostępna pod adresem `http://localhost:8000`

## Struktura Projektu

```
BookMe/
├── assets/                 # Zasoby frontend (JS, CSS)
├── config/                 # Konfiguracja Symfony
├── migrations/             # Migracje bazy danych
├── public/                 # Publiczne zasoby (CSS, JS, obrazy)
├── src/
│   ├── Controller/         # Kontrolery aplikacji
│   │   └── Admin/          # Kontrolery panelu administracyjnego
│   ├── Entity/             # Encje Doctrine
│   ├── Form/               # Formularze Symfony
│   ├── Repository/         # Repozytoria Doctrine
│   ├── Security/           # Komponenty bezpieczeństwa
│   ├── Service/            # Usługi biznesowe
│   └── Twig/               # Rozszerzenia Twig
├── templates/              # Szablony Twig
├── tests/                  # Testy jednostkowe i funkcjonalne
└── var/                    # Pliki tymczasowe, logi, cache
```

## Główne Encje

- **User** - użytkownicy systemu (klienci, pracownicy, administratorzy)
- **Service** - usługi oferowane przez firmę
- **ServiceCategory** - kategorie usług
- **Employee** - pracownicy wykonujący usługi
- **Booking** - rezerwacje klientów
- **Offer** - pakiety usług
- **Opinion** - opinie i oceny klientów
- **Notification** - powiadomienia systemowe
- **OpeningHour** - godziny otwarcia

## System Autoryzacji

Aplikacja wykorzystuje wbudowany system bezpieczeństwa Symfony z następującymi rolami:
- **ROLE_USER** - podstawowi użytkownicy (klienci)
- **ROLE_EMPLOYEE** - pracownicy
- **ROLE_ADMIN** - administratorzy systemu

## Testowanie

```bash
# Uruchomienie wszystkich testów
php bin/phpunit

# Uruchomienie konkretnej grupy testów
php bin/phpunit tests/Controller/
php bin/phpunit tests/Service/

# Testowanie z pokryciem kodu
php bin/phpunit --coverage-html coverage/
```

## Wdrożenie Produkcyjne

### 1. Przygotowanie środowiska produkcyjnego
```bash
# Ustawienie środowiska produkcyjnego
APP_ENV=prod

# Budowa zasobów produkcyjnych
npm run build

# Czyszczenie i warming cache
php bin/console cache:clear --env=prod
php bin/console cache:warmup --env=prod
```

### 2. Optymalizacja
```bash
# Autoloader Composer dla produkcji
composer install --no-dev --optimize-autoloader

# Preload klas PHP (opcjonalnie)
composer dump-autoload --optimize --classmap-authoritative
```

## API Endpointy

### Główne trasy publiczne:
- `GET /` - strona główna
- `GET /service/{encodedName}` - szczegóły usługi
- `GET /service-category/{encodedName}` - kategoria usług
- `GET /search` - wyszukiwarka usług
- `GET /login` - logowanie
- `GET /register` - rejestracja

### Trasy dla zalogowanych użytkowników:
- `GET /booking` - lista rezerwacji
- `POST /booking/create` - tworzenie rezerwacji
- `GET /opinion` - zarządzanie opiniami
- `GET /notifications` - powiadomienia

### Panel administracyjny (`/admin/*`):
- Zarządzanie wszystkimi encjami systemu
- Raporty i statystyki
- Konfiguracja systemu

## Współpraca

1. Fork repozytorium
2. Utwórz branch dla nowej funkcjonalności (`git checkout -b feature/nazwa-funkcjonalności`)
3. Zcommituj zmiany (`git commit -am 'Dodano nową funkcjonalność'`)
4. Push do brancha (`git push origin feature/nazwa-funkcjonalności`)
5. Utwórz Pull Request

## Licencja

Ten projekt jest własnością prywatną i nie posiada otwartej licencji.

## Zgłaszanie Błędów

Błędy i propozycje ulepszeń można zgłaszać przez system issue w repozytorium.

## Kontakt

W przypadku pytań technicznych skontaktuj się z zespołem deweloperskim.