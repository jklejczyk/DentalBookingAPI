# Dental Booking API

![Tests](https://github.com/jklejczyk/DentalBookingAPI/actions/workflows/tests.yaml/badge.svg)
[![codecov](https://codecov.io/gh/jklejczyk/DentalBookingAPI/graph/badge.svg)](https://codecov.io/gh/jklejczyk/DentalBookingAPI)
![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen)

REST API systemu rezerwacji wizyt w gabinecie stomatologicznym — zbudowane w Laravel 12 z pełnym pokryciem testami, analizą statyczną PHPStan level 5 i kompletnym pipeline'em CI/CD (7 workflow'ów GitHub Actions)."
Aplikacja symuluje system rezerwacji wizyt w gabinecie stomatologicznym — obsługuje pacjentów, dentystów, typy wizyt, blokowanie terminów oraz zarządzanie statusami wizyt.

## Spis tresci

- [Czego się nauczylem](#czego-się-nauczylem)
- [Stack technologiczny](#stack-technologiczny)
- [Uruchomienie projektu](#uruchomienie-projektu)
- [Struktura API](#struktura-api)
- [Autoryzacja i role](#autoryzacja-i-role)
- [Testowanie](#testowanie)
- [Analiza statyczna](#analiza-statyczna)
- [CI/CD Pipelines](#cicd-pipelines)
- [Deploy](#deploy)
- [Dokumentacja API](#dokumentacja-api)

## Czego się nauczylem

Celem tego projektu było ćwiczenie i pogłębienie umiejętności testowania kodu oraz konfigurowania CI/CD. Ponizej lista rzeczy, ktore zbudowalem tutaj po raz pierwszy lub znaczaco poglabilem.

### Testy i jakosc kodu

- **Pest** — ćwiczenie pisania testów feature (HTTP request → response + asercje na bazie i w response)
- **PHPStan level 5 + Larastan** — pierwsza praca z analizą statyczna kodu.
- **TDD** — pierwszy projekt w podejściu test-first (najpierw test, potem implementacja). Zmienia sposobu myślenia o projektowaniu kodu.

### CI/CD i DevOps

- **GitHub Actions** — 7 workflow-ow napisanych od zera. Nauczyłem się komponować joba ze stawianiem PostgreSQL, Redis, cache, composera i raportowaniem pokrycia.
- **Automatyczny deploy** — push do `main` → SSH na DigitalOcean Droplet → pull, migrate, cache. Pierwszy w pełni zautomatyzowany pipeline deploy.

### Architektura API

- **RESTful design** — poglebienie umiejetnosci projektowania API w oparciu o JSON:API specification

## Stack technologiczny

- **PHP 8.3** + **Laravel 12**
- **PostgreSQL** — baza danych
- **Redis** — kolejki i cache
- **Laravel Sanctum** — uwierzytelnianie tokenami
- **Pest** — framework testowy
- **PHPStan + Larastan** — analiza statyczna (level 5)
- **Laravel Pint** — formatowanie kodu
- **Scramble** — automatyczna dokumentacja OpenAPI
- **Laravel Sail** — srodowisko Docker

## Uruchomienie projektu

### Wymagania

- Docker + Docker Compose

### Instalacja z Laravel Sail

```bash
git clone https://github.com/jklejczyk/DentalBookingAPI.git
cd DentalBookingAPI
```

Skopiuj plik srodowiskowy i zainstaluj zaleznosci:

```bash
cp .env.example .env
composer install
```

Uruchom Sail:

```bash
./vendor/bin/sail up -d
```

Wygeneruj klucz aplikacji i uruchom migracje:

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

Opcjonalnie zaseeduj baze danymi testowymi:

```bash
./vendor/bin/sail artisan db:seed
```

Aplikacja dostepna pod `http://localhost:8090`.

### Dane logowania (po seedowaniu)

| Email | Haslo | Rola |
|-------|-------|------|
| `test@example.com` | `demo123` | admin |

```bash
curl -X POST http://localhost:8090/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "test@example.com", "password": "demo123"}'
```

W odpowiedzi otrzymasz token Bearer, ktorego uzywasz w naglowku `Authorization` do kolejnych zapytan.

## Struktura API

API jest wersjonowane (`/api/v1/`) i oparte o konwencje RESTful.

### Endpointy

| Metoda | Endpoint | Opis |
|--------|----------|------|
| POST | `/api/login` | Logowanie, zwraca token |
| POST | `/api/logout` | Wylogowanie |
| GET | `/api/v1/appointment` | Lista wizyt |
| POST | `/api/v1/appointment` | Utworzenie wizyty |
| GET | `/api/v1/appointment/{id}` | Szczegoly wizyty |
| PATCH | `/api/v1/appointment/{id}` | Aktualizacja wizyty |
| DELETE | `/api/v1/appointment/{id}` | Usuniecie wizyty |
| POST | `/api/v1/appointment/{id}/confirm` | Potwierdzenie wizyty |
| POST | `/api/v1/appointment/{id}/complete` | Zakonczenie wizyty |
| POST | `/api/v1/appointment/{id}/cancel` | Anulowanie wizyty |
| GET | `/api/v1/dentist` | Lista dentystow |
| POST | `/api/v1/dentist` | Dodanie dentysty |
| GET | `/api/v1/dentist/{id}` | Szczegoly dentysty |
| GET | `/api/v1/dentist/{id}/availability` | Dostepnosc dentysty |
| GET | `/api/v1/dentist/{id}/blocked-slots` | Zablokowane terminy |
| POST | `/api/v1/dentist/{id}/blocked-slots` | Zablokowanie terminu |
| GET | `/api/v1/patient` | Lista pacjentow |
| POST | `/api/v1/patient` | Dodanie pacjenta |
| GET | `/api/v1/appointment-type` | Typy wizyt |
| POST | `/api/v1/appointment-type` | Dodanie typu wizyty |

### Statusy wizyt

Wizyty przechodza przez cykl zycia:

```
BOOKED -> CONFIRMED -> COMPLETED
                    -> CANCELLED
```

## Autoryzacja i role

System oparty o RBAC (Role-Based Access Control) z trzema rolami:

| Rola | Uprawnienia |
|------|------------|
| **admin** | Pelny dostep — zarzadzanie dentystami, typami wizyt, pacjentami |
| **receptionist** | Zarzadzanie pacjentami i wizytami |
| **dentist** | Dostep do swoich wizyt i dostepnosci |

Uwierzytelnianie realizowane przez Laravel Sanctum (tokeny Bearer).

## Testowanie

Projekt wykorzystuje **Pest** jako framework testowy. Aktualne pokrycie kodu: **81%**.

```bash
# Uruchomienie testow
./vendor/bin/sail artisan test

# Testy z pokryciem kodu
./vendor/bin/sail artisan test --coverage
```

### Zakres testow

- **AuthTest** — logowanie i wylogowanie
- **AppointmentTest** — CRUD wizyt, filtrowanie, sortowanie
- **AppointmentTypeTest** — zarzadzanie typami wizyt
- **DentistTest** — CRUD dentystow
- **DentistAvailabilityTest** — sprawdzanie dostepnosci
- **DentistBlockedSlotTest** — blokowanie terminow
- **PatientTest** — CRUD pacjentow
- **RbacTest** — testy kontroli dostepu
- **SendAppointmentRemindersTest** — powiadomienia o wizytach

## Analiza statyczna

PHPStan z Larastan na poziomie 5:

```bash
composer analyse
```

## CI/CD Pipelines

Projekt posiada 7 workflow GitHub Actions:

### 1. Testy (`tests.yaml`)
- Uruchamia się przy push/PR do `main`
- Stawia PostgreSQL i Redis
- Wykonuje analize PHPStan
- Uruchamia testy z pokryciem kodu
- Wysyła raport do Codecov

### 2. Formatowanie kodu (`laravel-pint.yaml`)
- Uruchamia się przy push/PR do `main`
- Uruchamia Laravel Pint
- Automatycznie commituje poprawki formatowania

### 3. Audyt zaleznosci (`security-audit.yaml`)
- Uruchamia się codziennie o 8:00 UTC oraz przy push/PR
- Sprawdza `composer audit` pod kątem znanych podatności (CVE)

### 4. Audyt bezpieczenstwa API (`api-audit.yaml`)
- Uruchamia się raz w tygodniu (poniedziałki o 8:00 UTC)
- Możliwość ręcznego uruchomienia (`workflow_dispatch`)
- Stawia aplikacje i skanuje endpointy OWASP ZAP
- Generuje raport bezpieczeństwa jako artifact

### 5. Dokumentacja API (`api-docs.yaml`)
- Uruchamia się przy push/PR do `main`
- Eksportuje specyfikacje OpenAPI przez Scramble
- Deployuje dokumentacje na GitHub Pages

### 6. Release (`release.yaml`)
- Uruchamia się przy pushu taga `v*`
- Tworzy GitHub Release z automatycznym changelogiem

### 7. Deploy (`deploy.yaml`)
- Uruchamia się przy pushu do `main`
- Laczy się przez SSH z DigitalOcean Droplet
- Wykonuje `git pull`, instaluje zależnożci, migracje, cache

## Deploy

Aplikacja jest wdrożona na DigitalOcean Droplet pod adresem https://dentalbookingapi.rozwiazaniawebowe.pl/

- **Serwer:** Nginx + PHP-FPM
- **Baza danych:** PostgreSQL
- **Cache/Kolejki:** Redis
- **CI/CD:** Automatyczny deploy po pushu do `main`

UWAGA: Na serwerze wyłączona jest możliwość wysyłania maili.

## Dokumentacja API

Interaktywna dokumentacja API generowana przez Scramble (OpenAPI):

- **GitHub Pages:** [https://jklejczyk.github.io/DentalBookingAPI/](https://jklejczyk.github.io/DentalBookingAPI/)
