# jTech Nepal Portfolio Guide

Laravel 12 site for browsing Nepal investment portfolio records and getting ranked, explainable matches from a short questionnaire. Results can be selected and saved in a batch (`/saved`). No login, npm or Vite needed.

## Requirements

- PHP 8.2 or newer with extensions: `mbstring`, `intl`, `pdo_sqlite`, `openssl`, `ctype`, `json`, `fileinfo`
- [Composer](https://getcomposer.org/) 2
- Git

Check with `php -v` and `composer -V`. No database server is required: the default is a local SQLite file.

## Quick start (Windows, macOS, Linux)

```bash
git clone https://github.com/YOUR-ACCOUNT/YOUR-REPOSITORY.git jtech-recommender
cd jtech-recommender
composer install
composer setup
php artisan serve
```

Open http://127.0.0.1:8000

`composer setup` copies `.env.example` to `.env`, generates the app key, creates `database/database.sqlite`, runs migrations and imports the 885 portfolio rows from `database/data/nde_portfolios_clean.csv`. It is safe to run again (the data import does not duplicate rows; a new app key is generated each time).

If you see "could not find driver", enable `pdo_sqlite` (and `intl`, `mbstring`) in your `php.ini`.

## Using it

1. Click **Start** (or pick a sample profile on the home page) and answer the questions.
2. On the results page tick portfolios, or **Select all**, then **Save selected**.
3. Open **Saved** in the top nav to review, remove selected, or clear all. Saves live in the browser session.
4. `/admin` is protected with the `ADMIN_USER` / `ADMIN_PASS` values in `.env`.

## Tests

```bash
php artisan test
```

Tests use an in-memory SQLite database and do not touch your data.

## Using MySQL instead (optional)

Create an empty `utf8mb4` database, then in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jtech_recommender
DB_USERNAME=root
DB_PASSWORD=
```

Then run `php artisan migrate` and `php artisan portfolios:import`.

## Useful commands

```text
php artisan portfolios:import --dry-run   # validate the CSV, write nothing
php artisan portfolios:import --prune     # also remove rows not in the CSV
php artisan portfolios:validate           # data-quality report -> storage/app/dq_report.json
php artisan route:clear && php artisan view:clear   # clear caches if pages look stale
```

## Troubleshooting

| Problem | Fix |
|---|---|
| `No application encryption key` | `php artisan key:generate` |
| `Route [x] not defined` or stale pages | `php artisan route:clear && php artisan view:clear` |
| Empty portfolio list | `php artisan portfolios:import` |
| `SQLSTATE ... no such table` | `php artisan migrate` |
| Wrong PHP version | Use PHP 8.2+; on Windows select it in Herd or XAMPP and open a new terminal |

Never commit `.env`; each computer generates its own.

## Production

Copy `.env.production.example` to `.env`, fill in the database values, run `php artisan key:generate --force`, and see [docs/CPANEL_DEPLOYMENT.md](docs/CPANEL_DEPLOYMENT.md) and `deploy.sh`.

## Guides

- [Step 1: Database](docs/STEP_1_DATABASE.md)
- [Step 2: Layout and homepage](docs/STEP_2_HOME.md)
- [Step 3: Browse and details](docs/STEP_3_BROWSE_DETAIL.md)
- [Step 4: Questionnaire](docs/STEP_4_QUESTIONNAIRE.md)
- [Step 5: Recommender](docs/STEP_5_RECOMMENDER.md)
- [Step 6: Tests](docs/STEP_6_TESTS.md)
- [Algorithm and file guide](docs/ALGORITHM_AND_FILE_GUIDE.md)
