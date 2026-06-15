# TODO

combing workouts and workout types pages

## Model / database cleanup (from model & relationship review)

- [ ] **Remove dead `Workout` fillable/cast entries** — `app/Models/Workout.php` still
  lists `sets`, `reps`, `weight`, `duration_minutes`, `difficulty`, `left_*`, `right_*`
  in `$fillable`/`casts()`, but these columns were dropped in
  `2025_10_22_085100_migrate_legacy_workouts_to_sets.php`. Because `sets` is in
  `casts()`, `$workout->sets` (property access) silently returns `null` instead of the
  `sets()` relation — a footgun for future code. Remove the dead entries.

- [ ] **Delete `app/Models/DietPhase.php`** — maps to the `diet_phases` table, which was
  dropped in `2025_08_23_140530_drop_diet_phases_table.php` (superseded by
  `DietPeriod`/`diet_periods`). Using this model would throw a "no such table" error.

- [ ] **Drop dead `daily_data_exclusions.notes` column** — superseded by
  `food_notes`/`workout_notes`/`weight_notes` (added in
  `2025_08_24_140035_add_notes_to_daily_data_exclusions_table.php`); never read or
  written, not in `$fillable`.

- [ ] **Drop dead `food.weight` column** — in `Food`'s `$fillable`/`casts()` but never
  set by `FoodController` or `DailyDataController` (the `weight` they handle there is
  for `DailyWeight`, unrelated). Leftover from an earlier design.

- [ ] **Fill in or remove the empty `add_is_recipe_output_to_food_types_table` migration**
  — `2026_06_15_072009_add_is_recipe_output_to_food_types_table.php` has empty
  `up()`/`down()` closures and `is_recipe_output` isn't referenced anywhere in code.
  Looks like a stubbed-out migration from the in-progress Recipes feature.

- [ ] **Confirm intent of `recipes.food_type_id` `nullOnDelete`** — if a `FoodType` is
  deleted, its `Recipe` rows survive with `food_type_id = null` rather than being
  deleted. Verify this is the desired behavior for the Recipes feature.

## Food data cleanup

- [ ] **Fix `serving_size_grams`/`serving_weight_grams` typo bug** —
  `DailyDataController::storeFood` references `$foodType->serving_size_grams`, but the
  real column is `serving_weight_grams`. This means `quantity_grams` is always stored
  as null/0 when logging food via the daily-data flow.
- [ ] **Audit/backfill `serving_weight_grams`** — many `food_types` rows have
  `serving_weight_grams = null`, which blocks gram-based math (recipe nutrition calc,
  accurate `quantity_grams` logging). Worth doing before building recipe math on top.

## Recipes feature — planning notes

Notes from a planning session about extending the food-tracking system to support
recipes (e.g. the "Vegan Fat Loss Meal Plan" PDF: dishes with ingredient lists,
quantities, nutrition totals, and step-by-step instructions).

### Current system, in brief

- `food_types`: catalog of reusable items with per-serving nutrition
  (`calories_per_serving`, `protein_per_serving`, `carbs_per_serving`,
  `fat_per_serving`, `serving_size`, `serving_weight_grams`, `category`,
  `is_one_time_item`).
- `food`: a logged consumption event — `user_id`, `food_type_id`, `servings`,
  `total_calories/protein/carbs/fat` (= `servings * food_type.*_per_serving`),
  `consumed_at`, etc.
- `FoodType::booted()` has an `updated` hook: if `*_per_serving` changes, it
  runs `php artisan food:recalculate-nutrition --food-type-id=X`, which
  recalculates `total_*` on every logged `Food` row for that type. This is the
  key existing mechanism that any "recipe nutrition changed" flow should hook into.
- No recipes, no ingredient composition, no meal plans, no fiber/micronutrients today.
- `is_one_time_item` is the only "kind" flag on `food_types`. Adding recipes
  introduces a third "kind" (recipe-derived food type), so list/picker UIs
  (`FoodTypeController::index`, `Foods/Create.vue`, `DailyData/Index.vue`) will
  need a third bucket or a way to hide/group recipe-derived entries.

### Two designs considered

**A) Structured ingredients (richer, more work)**

- `recipes` table: `user_id`, `food_type_id` (derived FoodType, nullable),
  `name`, `description`, `instructions` (json array of steps), `servings`,
  `source`, `notes`.
- `recipe_ingredients` table: `recipe_id`, `food_type_id` (existing ingredient
  catalog), `quantity_grams`, `display_amount` (human text like "2 tbsp (13 g)"),
  `notes`, `sort_order`.
- Each Recipe maintains a **derived FoodType** ("1 serving of [Recipe]"),
  flagged `is_recipe_output = true`. `Recipe::recalculateNutrition()` sums
  `ingredient.*_per_serving / serving_weight_grams * quantity_grams` across all
  ingredients, divides by `recipe.servings`, and writes the result onto the
  derived FoodType via `update()` — which **automatically triggers** the
  existing `FoodType::booted()` → `food:recalculate-nutrition` pipeline, so any
  already-logged `Food` entries for that recipe get their totals updated too.
- "Log this recipe" = pick the derived FoodType in the normal daily-food-log
  flow (no new logging table needed).
- Pros: real per-ingredient nutrition math, "adjust an ingredient and see
  totals recalc everywhere" works for free via existing recalculation
  infrastructure, shopping-list-style ingredient data is structured.
- Cons: more schema/UI — ingredient picker needs inline food-type creation
  with `serving_weight_grams`, missing-serving-weight warnings, dynamic
  ingredient rows, etc.

**B) Free-text recipes (simpler — what's currently being built)**

- `recipes` table: `food_type_id` (nullable FK), `name`, `description`,
  `ingredients` (plain text block), `instructions` (plain text block),
  `servings` (string).
- `recipe_sources` table: `recipe_id`, `title`, `url`, `type` — citations/links.
- Optional link to a `food_types` row for nutrition (set manually, not derived).
- Pros: fast to build, fine for "recipe as a note with a link to a food type
  whose macros I already know."
- Cons: no per-ingredient math — can't "adjust one ingredient and have
  nutrition recalc." Ingredients/instructions aren't queryable/structured
  (no shopping list generation, no swapping an ingredient programmatically).

**Status as of this writing**: option (B) is actively being built in this repo
(uncommitted): `Recipe`/`RecipeSource` models, `RecipeController`,
`Recipes/{Index,Create,Edit,Show}.vue`, migrations for `recipes` +
`recipe_sources`, `VeganFatLossMealPrepSeeder` (creates 3 FoodTypes for the
example meal plan with hardcoded macros).

### Open questions for later

- [ ] Is option (B) good enough as a v1 (recipe = note + optional link to a
  FoodType for macros), with option (A)'s structured ingredients as a
  possible v2 if "adjust an ingredient and recalc" becomes a real pain point?
- [ ] If/when structured ingredients are added, should it be a new
  `recipe_ingredients` table (as in A) layered on top of (B)'s `recipes`
  table, reusing `recipes.food_type_id` as the derived-nutrition FoodType?
- [ ] "Paste a recipe into a Claude chat" workflow: once either schema exists,
  Claude can map ingredient text to `food_types` rows (creating new ones with
  `serving_weight_grams` set as needed) and create the `Recipe` (+
  `RecipeIngredient`, if option A) rows directly — no special import
  endpoint needed, the Create/Edit form's payload shape is the contract.
- [ ] Meal plans (grouping multiple recipes, like the "Vegan Fat Loss Meal Plan")
  are out of scope for now — `recipes.source`/`notes` can informally capture
  provenance ("Vegan Fat Loss Meal Plan") until/unless a real grouping table
  is wanted.
