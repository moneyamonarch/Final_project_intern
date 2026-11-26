# Step 3: Browse and portfolio details

## Files

- `app/Http/Controllers/PortfolioController.php` validates GET filters, builds
  Eloquent queries, paginates 12 records, and finds similar records for details.
- `resources/views/portfolios/index.blade.php` renders the search form and
  database-backed portfolio cards. Overview rows remain visible here.
- `resources/views/portfolios/show.blade.php` displays the source fields,
  conditionally links a valid document URL, and shows up to four similar
  non-overview portfolios.
- `resources/views/pagination/clean.blade.php` provides Previous/Next links
  without relying on Laravel's Tailwind pagination markup.
- `config/recommender.php` holds collection display names and amount units.
- `public/css/app.css` styles filters, cards, tags, details, and pagination.

Collection, focus-area, SDG, and layer filter options are queried from the
database. Search checks title and summary. Filters stay in the URL and carry
across pages. Amount sorting orders descending, which leaves MySQL's `NULL`
amounts last. SDG records display `NRs billion`; NAP amounts display no unit.

## Check it

With the local PHP server running, open `/portfolios`. Try a keyword, select a
collection or focus area, change the sort, and move to another page. Open a
card, confirm its category and fields, then use the back link. Overview rows
should appear here but will not be recommended.
