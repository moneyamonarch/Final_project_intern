# Step 4: Questionnaire

`app/Http/Controllers/QuestionnaireController.php` serves one question per
screen, validates each POST against current database values, stores only the
answers in the session, and advances or skips the current question.

`resources/views/questionnaire/step.blade.php` renders all four screens,
their progress bar, labeled controls, validation messages, and skip buttons.
SDGs, focus areas, and collections come from the database. Collection names
come from `config/recommender.php`. No answer is required.

The size choices are relative bands. The recommender calculates their cut
points separately for each collection from its non-empty amounts. NAP values
are compared only within NAP because their unit is not stated.

## Check it

Start from the homepage and submit one or more choices on each screen. Skip a
question, go back, and confirm previous answers remain selected. The last
screen should open the recommendations page. Starting again clears the old
session answers.
