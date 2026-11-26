# jTech Portfolio Recommender: Algorithm and File Guide

This guide describes the algorithm that is implemented in this project and
explains the purpose of each project-owned file. The recommendation engine is
content-based: it compares the visitor's questionnaire answers with fields in
the portfolio database. It does not learn from visitors or use interaction
history.

## 1. Data used by the recommendation engine

The importer loads `database/data/nde_portfolios_clean.csv` into the
`portfolios` table. Each row keeps its original `portfolio_id` and fields,
including `dataset`, `sdg_num`, `focus_group`, `focus_area`, `total`, and
`is_overview`.

At request time, the controller reads the current portfolio rows and their
non-empty totals from MySQL. Questionnaire choices such as SDGs, focus areas,
and collections are also queried from MySQL. The collection display names,
score weights, amount units, and the diversity cap are the configurable values
in `config/recommender.php`.

## 2. Answers collected

The questionnaire has four screens. Answers are kept in Laravel's session and
are not associated with a user account.

1. **Topics:** any SDG numbers that occur in the database, plus the
   `Digital sovereignty` option.
2. **Focus areas:** one or more distinct focus areas queried from the database
   and grouped under their database `focus_group`.
3. **Investment size:** `small`, `medium`, `large`, or `no_preference`.
4. **Collections:** zero or more dataset keys present in the database, shown
   with names from configuration.

Each screen can be skipped. A skipped question contributes no preference to
the score. Selecting `no_preference` has the same effect as skipping the amount
criterion.

## 3. Amount bands are calculated separately for each collection

The controller queries rows whose `total` is not `NULL`, ordered by dataset and
amount. It groups those totals by dataset and passes each group to
`RecommenderService::calculateAmountBands()`.

For a collection with `n` amounts, the service sorts its values from low to
high and calculates two cut points:

```text
jtech-recommender/
+- .editorconfig                         Editor formatting defaults
+- .env                                  Local private settings; do not publish
+- .env.example                          Safe settings template with placeholders
+- .gitattributes                        Git text and archive handling rules
+- .gitignore                            Files to keep out of source control
+- .phpunit.result.cache                 PHPUnit generated local test cache
+- artisan                               Laravel command-line entry point
+- composer.json                         PHP dependencies and PHP 8.2 platform target
+- composer.lock                         Exact resolved PHP dependency versions
+- phpunit.xml                            PHPUnit suite and test environment settings
+- README.md                              Project quick start and guide links
+- server.php                             Local router for public files and Laravel requests
+- app/
|  +- Console/Commands/ImportPortfolios.php  CSV validation and idempotent import
|  +- Http/Controllers/Controller.php        Shared Laravel controller base
|  +- Http/Controllers/HomeController.php    Homepage live counts
|  +- Http/Controllers/PortfolioController.php Browse filters and detail records
|  +- Http/Controllers/QuestionnaireController.php Session questionnaire and validation
|  +- Http/Controllers/RecommendationController.php Loads data and calls recommender
|  +- Models/Portfolio.php                   Eloquent mapping for portfolios
|  +- Models/User.php                        Unused Laravel starter model; no account flow
|  +- Providers/AppServiceProvider.php       Laravel application service provider
|  +- Services/RecommenderService.php        Pure PHP amount-band and scoring algorithm
+- bootstrap/
|  +- app.php                                Routes, middleware, exceptions, command registration
|  +- providers.php                          Application service provider list
|  +- cache/                                 Generated Laravel bootstrap/config caches
+- config/
|  +- app.php                                App name, locale, key, and core settings
|  +- auth.php                               Unused Laravel starter authentication settings
|  +- cache.php                              Cache driver settings
|  +- database.php                           Database drivers read from environment settings
|  +- filesystems.php                        Local and cloud filesystem configuration
|  +- logging.php                            Log channel and destination settings
|  +- mail.php                               Mail transport settings; not used by this app
|  +- queue.php                              Queue driver settings; no background queue used
|  +- recommender.php                        Collection names, weights, units, and cap
|  +- services.php                           Environment-based third-party service settings
|  +- session.php                            Session settings; questionnaire answers use sessions
+- database/
|  +- .gitignore                             Keeps local database files out of source control
|  +- data/nde_portfolios_clean.csv          Imported source records
|  +- factories/UserFactory.php              Unused Laravel starter user factory
|  +- migrations/0001_01_01_000000_create_users_table.php  Starter account/session tables
|  +- migrations/0001_01_01_000001_create_cache_table.php  Starter database cache schema
|  +- migrations/0001_01_01_000002_create_jobs_table.php   Starter queue schema
|  +- migrations/2026_11_20_000000_create_portfolios_table.php Portfolio schema and indexes
|  +- seeders/DatabaseSeeder.php             Default seeder entry; CSV import is used instead
+- docs/
|  +- ALGORITHM_AND_FILE_GUIDE.md             This detailed algorithm and tree guide
|  +- CPANEL_DEPLOYMENT.md                    PHP 8.2 cPanel deployment steps
|  +- STEP_1_DATABASE.md                      Database/import explanation
|  +- STEP_2_HOME.md                          Layout/home explanation and local server command
|  +- STEP_3_BROWSE_DETAIL.md                 Browse/detail explanation
|  +- STEP_4_QUESTIONNAIRE.md                 Session questionnaire explanation
|  +- STEP_5_RECOMMENDER.md                   Recommender explanation
|  +- STEP_6_TESTS.md                          Test instructions
+- public/
|  +- .htaccess                               Apache rewrites requests to Laravel
|  +- css/app.css                             The site's only hand-written stylesheet
|  +- favicon.ico                             Browser tab icon from the Laravel starter
|  +- index.php                               Laravel web front controller
|  +- robots.txt                              Basic search crawler instructions
+- resources/views/
|  +- layouts/app.blade.php                   Shared HTML, header, navigation, and footer
|  +- home.blade.php                          Homepage and live count display
|  +- pagination/clean.blade.php              Non-Tailwind previous/next pagination
|  +- portfolios/index.blade.php              Browse form and result cards
|  +- portfolios/show.blade.php               Portfolio details and similar records
|  +- questionnaire/step.blade.php             One-screen questionnaire UI
|  +- recommendations/index.blade.php         Scored results and no-match state
+- routes/
|  +- console.php                              Console route file (no custom routes currently)
|  +- web.php                                  Named website routes
+- storage/                                     Writable logs, sessions, compiled views, runtime files
+- tests/
|  +- TestCase.php                             Laravel test base class
|  +- Feature/ExampleTest.php                  Starter HTTP smoke test
|  +- Unit/ExampleTest.php                     Starter PHPUnit example
|  +- Unit/RecommenderServiceTest.php           Scoring answer sets and edge-case tests
+- vendor/                                      Composer-installed Laravel/PHP dependencies
```

A portfolio amount is classified as:

```text
small  when total <= small_max
medium when small_max < total <= medium_max
large  when total > medium_max
```

The cut points are recalculated from database values; no global amount ranges
are hardcoded. Tied amounts can make the bands unequal in size because equal
values stay on the same side of a cut point. A collection with one amount has
that amount as both cut points.

The `nap` collection has no stated amount unit. The app compares an NAP amount
only with other NAP amounts for band matching and never prints a unit beside
it. The two SDG collections use `NRs billion`, as configured.

## 4. Candidate filtering

Before scoring a portfolio, the service:

1. Excludes every row where `is_overview` is true. Overview rows remain
   available on Browse.
2. If the visitor selected collections, keeps only rows whose dataset is in
   that selection. This is a hard filter: other collections cannot appear in
   the results.

## 5. Which criteria apply to a portfolio

The default weights are equal and live in `config/recommender.php`:

| Criterion | Weight | Applies when | Matches when |
|---|---:|---|---|
| `topic` | 0.25 | The visitor chose an SDG for which this row has an `sdg_num`, or chose digital sovereignty and the row has a `focus_group` | The row's SDG is among the chosen SDGs, or the visitor chose digital sovereignty and the row's focus group is `Digital sovereignty` |
| `focus_area` | 0.25 | The visitor chose one or more areas and this row has a `focus_area` | The row's area is among the chosen areas |
| `collection` | 0.25 | The visitor chose one or more collections and the row has a dataset | The dataset is among the chosen collections |
| `amount_fit` | 0.25 | The visitor chose small, medium, or large, the row has a `total`, and its dataset has calculated cut points | The row's band in its own collection equals the chosen band |

Each criterion contributes its weight at most once per portfolio. If the
visitor selected multiple SDGs, a match to any one of them earns the single
topic weight. Choosing both SDGs and digital sovereignty still gives the topic
criterion one weight, not two.

An unanswered criterion is omitted from that portfolio's denominator. A
portfolio with no `total` skips `amount_fit`; it receives neither amount
credit nor an amount penalty. For example, if an amount preference is chosen
but a portfolio has no amount, the amount weight is left out of both the
matched and applicable totals.

## 6. Score calculation

For each candidate, the service adds the weights of the applicable criteria to
`applicable_weight`. When an applicable criterion matches, its weight is also
added to `matched_weight`.

```text
score = matched_weight / applicable_weight * 100
```

The service rounds the score to two decimal places for ranking. The result card
displays a whole-number percentage. Candidates with no applicable weight or
with zero matched criteria are omitted. If none remain, the page shows a
no-match message and a link back to the questionnaire.

### Worked example

Assume a visitor chooses SDG 2, `Agriculture & Food`, `Medium`, and `SDG
Interventions`. Consider a hypothetical candidate that matches SDG 2 and the
focus area, belongs to SDG Interventions, and has a medium amount band. All
four criteria apply and match:

```text
matched weight    = 0.25 + 0.25 + 0.25 + 0.25 = 1.00
applicable weight = 0.25 + 0.25 + 0.25 + 0.25 = 1.00
score             = 1.00 / 1.00 * 100 = 100%
reason            = Matches SDG 2, Agriculture & Food, SDG Interventions
                    and Medium amount band within this collection.
```

Now assume the same candidate has no amount. The amount criterion is skipped:

```text
matched weight    = 0.25 + 0.25 + 0.25 = 0.75
applicable weight = 0.25 + 0.25 + 0.25 = 0.75
score             = 0.75 / 0.75 * 100 = 100%
```

This is why a missing amount does not lower a portfolio's score. These rows
are explanatory examples, not extra portfolio data.

## 7. Reasons, ordering, and diversity

For every result, the service keeps `matched_criteria`, a list of labels that
really matched. `buildReason()` creates a sentence from this list only; it
does not mention unanswered or mismatched criteria.

Candidates are ordered by:

1. Higher score.
2. More matched criterion types. A topic matching multiple selected values
   still counts as one matched criterion type.
3. Lower `portfolio_id`.

After sorting, the service builds the result list in order. It accepts at most
the configured number of `sdg_indicator` records (currently 3). Once that cap
is reached, additional indicator rows are skipped while scanning continues,
allowing the next-best rows from other collections to fill the top ten when
available.

## 8. Project file tree

The tree below covers project-owned source, configuration, data, documentation,
and test files. `.env` values are intentionally not shown. `vendor/`,
`storage/`, and generated cache contents are described as folders because they
are installed or produced by Composer and Laravel, rather than maintained as
application source files.

```text
jtech-recommender/
├── .editorconfig                         Editor formatting defaults
├── .env                                  Local private settings; never upload or commit
├── .env.example                          Safe settings template with placeholders
├── .gitattributes                        Git text and archive handling rules
├── .gitignore                            Files to keep out of source control
├── .phpunit.result.cache                 PHPUnit's generated local test cache
├── artisan                               Laravel command-line entry point
├── composer.json                         PHP dependencies and PHP 8.2 platform target
├── composer.lock                         Exact resolved PHP dependency versions
├── phpunit.xml                            PHPUnit suite and test environment settings
├── README.md                              Project quick start and guide links
├── server.php                             Local router: serve public files, then Laravel routes
├── app/
│   ├── Console/Commands/ImportPortfolios.php  CSV validation and idempotent import
│   ├── Http/Controllers/Controller.php        Shared Laravel controller base
│   ├── Http/Controllers/HomeController.php    Homepage live counts
│   ├── Http/Controllers/PortfolioController.php Browse filters and detail records
│   ├── Http/Controllers/QuestionnaireController.php Session questionnaire and validation
│   ├── Http/Controllers/RecommendationController.php Loads data and calls the recommender
│   ├── Models/Portfolio.php                   Eloquent mapping for portfolios
│   ├── Models/User.php                        Unused Laravel starter model; no account flow
│   ├── Providers/AppServiceProvider.php       Laravel application service provider
│   └── Services/RecommenderService.php        Pure PHP amount-band and scoring algorithm
├── bootstrap/
│   ├── app.php                                Routes, middleware, exceptions, command registration
│   ├── providers.php                          Application service provider list
│   └── cache/                                 Generated Laravel bootstrap/config caches
├── config/
│   ├── app.php                                App name, locale, key, and core settings
│   ├── auth.php                               Unused Laravel starter authentication settings
│   ├── cache.php                              Cache driver settings
│   ├── database.php                           Database drivers read from environment settings
│   ├── filesystems.php                        Local and cloud filesystem configuration
│   ├── logging.php                            Log channel and destination settings
│   ├── mail.php                               Mail transport settings; not used by this app
│   ├── queue.php                              Queue driver settings; no background queue used
│   ├── recommender.php                        Collection names, weights, units, and cap
│   ├── services.php                           Environment-based third-party service settings
│   └── session.php                            Session settings; questionnaire answers use sessions
├── database/
│   ├── .gitignore                             Keeps local database files out of source control
│   ├── data/nde_portfolios_clean.csv          Imported source records
│   ├── factories/UserFactory.php              Unused Laravel starter user factory
│   ├── migrations/0001_01_01_000000_create_users_table.php  Starter users/session schema
│   ├── migrations/0001_01_01_000001_create_cache_table.php  Starter database cache schema
│   ├── migrations/0001_01_01_000002_create_jobs_table.php   Starter queue schema
│   ├── migrations/2026_11_20_000000_create_portfolios_table.php Portfolio schema and indexes
│   └── seeders/DatabaseSeeder.php             Default seeder entry; CSV import is used instead
├── docs/
│   ├── ALGORITHM_AND_FILE_GUIDE.md             This detailed algorithm and tree guide
│   ├── CPANEL_DEPLOYMENT.md                    PHP 8.2 cPanel deployment steps
│   ├── STEP_1_DATABASE.md                      Database/import explanation
│   ├── STEP_2_HOME.md                          Layout/home explanation and local server command
│   ├── STEP_3_BROWSE_DETAIL.md                 Browse/detail explanation
│   ├── STEP_4_QUESTIONNAIRE.md                 Session questionnaire explanation
│   ├── STEP_5_RECOMMENDER.md                   Recommender explanation
│   └── STEP_6_TESTS.md                          Test instructions
├── public/
│   ├── .htaccess                               Apache rewrites requests to Laravel
│   ├── css/app.css                             The site's only hand-written stylesheet
│   ├── favicon.ico                             Browser tab icon from the Laravel starter
│   ├── index.php                               Laravel web front controller
│   └── robots.txt                              Basic search crawler instructions
├── resources/views/
│   ├── layouts/app.blade.php                   Shared HTML, header, navigation, and footer
│   ├── home.blade.php                          Homepage and live count display
│   ├── pagination/clean.blade.php              Non-Tailwind previous/next pagination
│   ├── portfolios/index.blade.php              Browse form and result cards
│   ├── portfolios/show.blade.php               Portfolio details and similar records
│   ├── questionnaire/step.blade.php             One-screen questionnaire UI
│   └── recommendations/index.blade.php         Scored results and no-match state
├── routes/
│   ├── console.php                              Console route file (no custom routes currently)
│   └── web.php                                  Named website routes
├── storage/                                     Writable logs, sessions, compiled views, and runtime files
├── tests/
│   ├── TestCase.php                             Laravel test base class
│   ├── Feature/ExampleTest.php                  Starter HTTP smoke test
│   └── Unit/
│       ├── ExampleTest.php                      Starter PHPUnit example
│       └── RecommenderServiceTest.php           Scoring answer sets and edge-case tests
└── vendor/                                      Composer-installed Laravel/PHP dependencies
```

### Starter account tables

Laravel's starter migration creates empty `users`, password reset, and session
tables. The cache and jobs migrations also create framework tables. This site
does not define login or registration routes and does not create or store user
accounts. Questionnaire answers use file-backed sessions.

## 9. Testing

Run the suite using Herd's PHP 8.2 from the project folder:

```powershell
php artisan test
```

`RecommenderServiceTest` covers five answer sets and checks the missing-amount
rule, overview exclusion, indicator cap/fill, and match-only reason text.
