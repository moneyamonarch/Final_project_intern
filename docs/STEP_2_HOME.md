# Step 2: Layout and homepage

This step adds the shared page frame and the first customer-facing page. It
uses Blade and one hand-written stylesheet; it does not require Vite, npm,
Tailwind, or Bootstrap.

## Files

- `app/Http/Controllers/HomeController.php` reads the portfolio total, the
  number of distinct non-empty SDG values, and the number of datasets from the
  database. It does not store those counts in the page source.
- `routes/web.php` connects `/` to the homepage. Its primary button starts the
  questionnaire flow.
- `resources/views/layouts/app.blade.php` supplies the shared HTML document,
  header, navigation, footer, and direct link to the stylesheet.
- `resources/views/home.blade.php` contains the welcome message, primary
  questionnaire button, and three live counts.
- `public/css/app.css` contains the responsive styles, color palette, spacing,
  cards, and visible keyboard focus treatment.

## Test locally

Select PHP 8.2 in Herd, then open PowerShell and run:

```powershell
cd 'C:\xampp\htdocs\jtech-recommender'
php -v
php -S 127.0.0.1:8000 -t public server.php
```

Open `http://127.0.0.1:8000`. The homepage should show the current totals from
the database (885 portfolios, 15 SDGs covered, and 5 collections with the
imported dataset). Select **Start questionnaire** to open the first question.

The `server.php` router lets PHP serve the stylesheet and other public files
directly while sending application routes to Laravel. It is for local
development; cPanel serves files from the `public` document root.
