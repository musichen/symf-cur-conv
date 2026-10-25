# Contributing to symf-cur-conv

## Setup

PHP >= 8.2 with Composer (see `composer.json`, Symfony 7.1):

```bash
composer install
```

Docker definitions (`compose.yaml`, `compose.override.yaml`) are included if you prefer containers. Configure your database in `.env` / `.env.local` and never commit real credentials.

## Checks

```bash
vendor/bin/phpunit
```

Tests live under `tests/` (Command, Controller, Service) and use `phpunit.xml.dist`.

## Layout

- `src/Controller/Api/CurrencyController.php` - `POST /api/convert`
- `src/Command/FetchExchangeRatesCommand.php` - `app:fetch-exchange-rates`
- `src/Entity/ExchangeRate.php` and `src/Repository/ExchangeRateRepository.php` - rates and latest-rate lookup

Run `php bin/console list app` to see the console commands in your checkout.

## Pull requests

Create a branch from `main` and say which tests or endpoints you exercised in the PR.
