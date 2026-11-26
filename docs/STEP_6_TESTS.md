# Step 6: Recommender tests

`tests/Unit/RecommenderServiceTest.php` tests five answer sets and expected top
IDs, no-amount behavior, overview exclusion, the indicator cap and diversity
fill, and match-only reason text. The tests instantiate the recommender service
directly; they do not need a web request or database connection.

Run the unit suite from the project folder with PHP 8.2:

```powershell
php artisan test --testsuite=Unit
```
