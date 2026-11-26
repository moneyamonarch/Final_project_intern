# Step 1: Portfolio database

This step stores the supplied portfolio records in MySQL. It does not add
accounts, login pages, or personal information.

Laravel's starter project also includes migrations for empty framework tables
such as `users`, `cache`, and `jobs`. This site does not add account routes or
write account data to those tables.

## Files

- `database/data/nde_portfolios_clean.csv` is the source data. Its header is
  `portfolio_id,dataset,code,title,summary,sdg_num,focus_group,focus_area,category,layer,total,is_overview,document_url`.
- `database/migrations/2026_11_20_000000_create_portfolios_table.php` creates
  the `portfolios` table, preserves each source ID as the primary key, and
  adds indexes used for browsing and filtering.
- Laravel's three `0001_01_01_...` migration files are part of the starter
  project; they create the default users, cache, and jobs tables.
- `app/Models/Portfolio.php` maps the `Portfolio` model to that table and tells
  Eloquent to use `portfolio_id` as its non-generated primary key.
- `app/Console/Commands/ImportPortfolios.php` reads the CSV, turns blank cells
  into `NULL`, validates key values, and upserts records in batches of 100.
  Running it again updates rows with the same `portfolio_id` instead of adding
  duplicates. It prints counts for each dataset.
- `bootstrap/app.php` registers commands in `app/Console/Commands` so Artisan
  can find `portfolios:import`.
- `.env` contains local database settings and the local Laravel app key. It is
  private configuration and must not be uploaded or committed. The cPanel copy
  needs its own `.env` values from the hosting account.
- `.env.example` shows which database settings deployment needs, using
  placeholders instead of real credentials. Questionnaire answers will use
  Laravel's file-backed session store.
- `composer.json` sets Composer's PHP platform version to 8.2 so dependency
  resolution stays compatible with the cPanel target.

## Run locally

In Herd, select PHP 8.2, then open a new PowerShell window and move to the
project folder:

```powershell
cd 'C:\xampp\htdocs\jtech-recommender'
php -v
```

Start MySQL in the XAMPP Control Panel, then run:

```powershell
php artisan migrate
php artisan portfolios:import
```

The import should report 885 rows total:

```text
draft: 71
nap: 64
sdg_indicator: 265
sdg_intervention: 125
sovereign: 360
Imported or updated 885 portfolio rows.
```

If MySQL is stopped, start it in XAMPP first. If the database does not exist,
create `jtech_recommender` in phpMyAdmin, then rerun the commands above.
