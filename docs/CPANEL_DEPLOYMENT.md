# cPanel deployment

This project targets PHP 8.2. `composer.json` sets Composer's platform to 8.2,
and `composer.lock` records the resolved PHP dependencies. The page styles are
served from `public/css/app.css`; no Node or frontend build step is needed.

## Prepare the cPanel account

1. Set the domain's PHP version to 8.2 in cPanel.
2. Create a MySQL database and database user in cPanel, then grant the user
   access to that database. Keep those credentials private.
3. Upload the Laravel project outside the public web root when possible. Set
   the domain document root to the project's `public` directory. Do not expose
   `.env`, `vendor`, or the application source as public files.
4. Copy `.env.example` to `.env` on the server. Set `APP_ENV=production`,
   `APP_DEBUG=false`, `APP_URL`, and the database host, name, username, and
   password supplied by the host. Never upload the local `.env` file.
5. Run Composer using the host's PHP 8.2 CLI and the project lock file:

   ```text
   composer install --no-dev --optimize-autoloader --no-interaction
   ```

6. Generate an application key on the server, then create the schema and
   import the source data:

   ```text
   php artisan key:generate
   php artisan migrate --force
   php artisan portfolios:import
   ```

7. Ensure the web-server user can write to `storage` and `bootstrap/cache`.
   The questionnaire uses Laravel's file session driver, so
   `storage/framework/sessions` must be writable.
8. Open the domain and check the home page, Browse page, questionnaire, and
   recommendation results.

Use the exact PHP 8.2 CLI command provided by the hosting account if its
`php` command points at another PHP version. Set the actual database host from
the cPanel account; it may differ from the local XAMPP host.
