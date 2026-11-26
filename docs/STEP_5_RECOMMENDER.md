# Step 5: Content-based recommendations

- `app/Services/RecommenderService.php` is a plain PHP class. It scores arrays
  without Laravel facades, derives per-collection amount bands, builds reasons
  only from matches, sorts ties by matched criteria and then portfolio ID, and
  applies the SDG Indicator cap.
- `app/Http/Controllers/RecommendationController.php` reads the completed
  session, loads current portfolio rows and totals through Eloquent, and passes
  them to the service.
- `resources/views/recommendations/index.blade.php` shows up to ten results or
  a friendly no-match message.
- `config/recommender.php` is the one place to edit the four scoring weights,
  collection names, amount units, and indicator cap.

Overview rows are always excluded. Selected collections are a hard filter.
Rows without an amount do not receive amount-fit points and are not penalized.
Scores are matched weight divided by applicable weight, shown as a percentage.
The future interactions bonus is not implemented.

## Check it

Complete the questionnaire with a topic, focus area, size, and collection. Each
result should show a collection label, score, match reason, and detail link.
Skip every question to see the no-match message.
