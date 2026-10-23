# symf-cur-conv

Symfony currency converter service. It stores exchange rates and converts amounts through a small JSON API.

## Stack

- PHP >= 8.2 and Symfony 7.1 (see `composer.json`)
- Doctrine ORM 3 with DBAL and migrations
- An `ExchangeRate` entity (base currency, target currency, rate) with a repository method to fetch the latest rate for a pair

## Install

```bash
composer install
```

Docker definitions are included (`compose.yaml` and `compose.override.yaml`) if you prefer containers.

## API

Convert a currency pair:

```http
POST /api/convert
```

Defined in `src/Controller/Api/CurrencyController.php`.

## Console

Fetch fresh exchange rates:

```bash
php bin/console app:fetch-exchange-rates
```

The command name is defined in `src/Command/FetchExchangeRatesCommand.php`; run `php bin/console list app` to confirm it in your checkout.

## Tests

Tests live under `tests/` (Command, Controller, and Service) and are configured by `phpunit.xml.dist`. Run them with:

```bash
vendor/bin/phpunit
```

## License

GNU General Public License v3.0. See [LICENSE](LICENSE). Note that `composer.json` currently marks the project `proprietary`; align that field with the LICENSE file before publishing the package metadata.
