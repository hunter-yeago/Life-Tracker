<?php

namespace Database\Seeders;

use App\Models\FoodType;
use App\Models\Recipe;
use Illuminate\Database\Seeder;

class VeganFatLossRecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sources = [
            [
                'title' => 'Vegan Fat Loss Meal Plan 1600 Calories (The Vegan Gym, PDF)',
                'url' => 'https://drive.google.com/file/d/1cXmgGz44PtUfTYIcQj8kyd-k_T-71Hag/view?__s=4p62u47p8pn0fhtz0q0u',
                'type' => 'pdf',
            ],
            [
                'title' => '3 Layers of Nutritional Defense (The Vegan Gym, YouTube)',
                'url' => 'https://www.youtube.com/watch?v=4Fa9we_O3EE',
                'type' => 'video',
            ],
        ];

        $recipes = [
            [
                'name' => 'Berry Overnight Oats',
                'description' => 'A quick and easy bowl of peanut butter overnight oats topped with fresh raspberries, blueberries, and chia seeds',
                'servings' => '4 servings (recipe), 1 serving shown',
                'ingredients' => implode("\n", [
                    'Oats, dry - 1/2 cup (41 g)',
                    'Peanut butter powder - 2 tbsp (13 g)',
                    'Chia seeds - 1 tbsp (10 g)',
                    'Brazil nut, chopped or whole - 1 nut (5 g)',
                    'Soy milk, unsweetened - 1 cup (240 g)',
                    'Blueberries - 1/2 cup (74 g)',
                    'Raspberries - 1/2 cup (62 g)',
                    'Cinnamon (optional) - to taste',
                ]),
                'instructions' => implode("\n", [
                    'Lay out 4 fridge-friendly storage containers with lids.',
                    'To each container, add the dry ingredients: 1/2 cup (41 g) dry oats, 2 tbsp (13 g) peanut butter powder, 1 tbsp (10 g) chia seeds, and 1 Brazil nut chopped or whole (5 g).',
                    'Add lids to each container and store them in the pantry.',
                    'The night before you plan to eat, remove a container from the pantry and add 1 cup (240 g) of soy milk. Mix thoroughly, then place the container in the fridge to soak overnight.',
                    'In the morning, remove the container from the fridge and add 1/2 cup (74 g) fresh blueberries and 1/2 cup (62 g) fresh raspberries (if using frozen berries, add them the night before with the soy milk so they thaw by morning).',
                    'Optionally add a splash of cinnamon for extra flavor.',
                    'Your oats are ready to be eaten!',
                ]),
            ],
            [
                'name' => 'Tofu Veggie Power Bowl',
                'description' => 'A colorful bowl of quinoa, tofu, and edamame with a vegetable medley of crisp cucumber, carrots, radishes, spinach, and tangy kimchi on the side',
                'servings' => '4 servings',
                'ingredients' => implode("\n", [
                    '1 serving:',
                    'Quinoa, cooked - 1/2 cup (93 g)',
                    'Riced cauliflower, frozen - 1/2 cup (50 g)',
                    'Low-sodium soy sauce - 1/4 tbsp (4 g)',
                    'Ground ginger - 1/4 tsp (<1 g)',
                    'Tofu, super firm - 1/4 block (114 g)',
                    'Homemade Tofu Coating - 1 serving (14 g)',
                    'Cucumber, chopped - 1/2 cup (60 g)',
                    'Carrots, shredded - 1/2 cup (77 g)',
                    'Shelled edamame, frozen - 1/2 cup (78 g)',
                    'Radish, chopped - 1 small (2 g)',
                    'Spinach, chopped - 1 cup (30 g)',
                    'Kimchi, vegan - 2 tbsp (19 g)',
                    'Kombu - 1 small piece',
                    'Homemade Orange Miso Dressing - 1 serving (85 g)',
                    '',
                    'Homemade Orange Miso Dressing (4 servings):',
                    'Low-sodium miso paste - 1/2 cup (120 g)',
                    'Rice vinegar - 3 tbsp (45 g)',
                    'Maple syrup - 3 tbsp (59 g)',
                    'Low-sodium soy sauce - 1 tbsp (16 g)',
                    'Ground ginger - 1 tsp (about 2 g)',
                    'Garlic powder - 1 tsp (3 g)',
                    'Water - 6 tbsp (89 g)',
                    'Orange, peeled - 1 medium (131 g)',
                    '',
                    'Homemade Tofu Coating (4 servings):',
                    'Corn starch - 2 tbsp (16 g)',
                    'Nutritional yeast - 2 tbsp (10 g)',
                    'Garlic powder - 1 tsp (3 g)',
                    'Onion powder - 1 tbsp (7 g)',
                    'Oregano, ground - 1 tbsp (5 g)',
                    'Cumin, ground - 1 tbsp (6 g)',
                ]),
                'instructions' => implode("\n", [
                    'Make the homemade Orange Miso Dressing first, since some of it is used to coat the tofu.',
                    'Part 1 - Dressing: Blend 1/2 cup (120 g) low-sodium miso paste, 3 tbsp (45 g) rice vinegar, 3 tbsp (59 g) maple syrup, 1 tbsp (16 g) low-sodium soy sauce, 1 tsp (about 2 g) ground ginger, 1 tsp (3 g) garlic powder, 6 tbsp (89 g) water, and 1 medium peeled orange (131 g) until smooth.',
                    'Part 2 - Dividing the dressing: This yields about 2 cups total - about 2/3 cup is used to coat the tofu, and about 1 1/3 cups is split 4 ways (1/3 cup per bowl) for drizzling on top.',
                    'Part 3 - Quinoa: Prepare 2/3 cup (113 g) of quinoa with 1 1/3 cups (316 g) of water (or per package directions) to yield about 2 cups.',
                    'In the last 5 minutes of cooking, stir in 2 cups (200 g) frozen riced cauliflower, 1 tbsp (16 g) low-sodium soy sauce, and 1 tsp (about 2 g) ground ginger. Let sit to cool.',
                    'Part 4 - Tofu: Cube a 16-oz block (454 g) of super firm tofu and place it in a large bowl with a lid.',
                    'In a separate bowl, mix 2 tbsp (16 g) corn starch, 2 tbsp (10 g) nutritional yeast, 1 tsp (3 g) garlic powder, 1 tbsp (7 g) onion powder, 1 tbsp (5 g) ground oregano, and 1 tbsp (6 g) ground cumin, then add to the tofu and shake to coat thoroughly.',
                    'Drizzle on 2/3 cup of the homemade Orange Miso Dressing, then shake again to coat.',
                    'Line an air fryer basket with parchment paper, add the coated tofu cubes, and air fry at 450F (230C) for 15-20 minutes, flipping halfway through (or bake at 375F/190C for 30 minutes, turning at the 15-minute mark).',
                    'Part 5 - Vegetables: Chop 4 cups (120 g) of fresh spinach (1 cup per bowl).',
                    'Cut 1 large cucumber (301 g) in half vertically, scoop out the seeds, and slice into sticks (about 2 cups chopped).',
                    'Thinly slice 4 small radishes (8 g total) and shred 2 cups (310 g) of carrots total for the 4 servings.',
                    'Thaw 2 cups (310 g) of shelled edamame in the fridge overnight.',
                    'Part 6 - Assembling the bowls: Evenly divide the quinoa/cauliflower, air-fried tofu, and vegetables between 4 containers, starting with a bed of quinoa/cauliflower.',
                    'Add 2 tbsp (19 g) of kimchi and a small piece of kombu to each container, then refrigerate until ready to eat.',
                    'When ready to eat, drizzle about 1/3 cup of the Orange Miso Dressing into each bowl.',
                    'Enjoy a small apple on the side (adds 77 calories, already accounted for in the 1,600-calorie total).',
                ]),
            ],
            [
                'name' => 'Romesco Seitan Polenta Bowl',
                'description' => 'A vibrant plate of kale, golden polenta, and savory seitan paired with roasted chickpeas and a smoky homemade romesco sauce',
                'servings' => '4 servings',
                'ingredients' => implode("\n", [
                    'Kale, fresh - 1 bundle or about 100 g (4 cups, chopped)',
                    'Polenta, tubed - 1/2 tube (9 oz, 255 g)',
                    'Low-sodium chickpeas, canned - 15-oz can (240 g once drained and rinsed)',
                    '',
                    'Homemade Seitan:',
                    'Vital wheat gluten - 1 1/2 cup (180 g)',
                    'Chickpea flour - 3 tbsp (17 g)',
                    'Nutritional yeast - 3 tbsp (15 g)',
                    'Onion powder - 1 tsp (2 g)',
                    'Garlic powder - 1 tsp (3 g)',
                    'Thyme, dried - 1/2 tsp (1 g)',
                    'Poultry seasoning, vegan - 1 tsp (2 g)',
                    'Low-sodium vegetable broth - 3/4 cup (166 g)',
                    'Worcestershire sauce, vegan - 3 tbsp (45 g)',
                    '',
                    'Homemade Romesco Sauce:',
                    'Roasted red peppers - 12-oz jar (340 g)',
                    'Low-sodium vegetable broth - 1/4 cup (55 g)',
                    'Red wine vinegar - 1 tbsp (15 g)',
                    'Maple syrup - 1 tsp (7 g)',
                    'Low-sodium tomato paste - 1/4 cup (56 g)',
                    'Minced garlic - 1 tbsp (15 g)',
                    'Chives, fresh, chopped - 1/4 cup (12 g)',
                    'Almonds, pre-roasted - 1/3 cup (43 g)',
                    'Smoked paprika - 1 tsp (about 2 g)',
                    'Black pepper - 1/4 tsp (about 1 g)',
                    'Iodized salt (optional) - 1/2 tsp (3 g)',
                ]),
                'instructions' => implode("\n", [
                    'Part 1 - Seitan and chickpeas: Mix the dry ingredients in a bowl: 1 1/2 cups (180 g) vital wheat gluten, 3 tbsp (17 g) chickpea flour, 3 tbsp (15 g) nutritional yeast, 1 tsp (2 g) onion powder, 1 tsp (3 g) garlic powder, 1/2 tsp (1 g) dried thyme, 1 tsp (2 g) poultry seasoning, and 1/2 tsp (3 g) iodized salt (optional).',
                    'In a second bowl, mix 3/4 cup (166 g) vegetable broth and 3 tbsp (45 g) vegan worcestershire sauce.',
                    'Gradually add the wet ingredients to the dry ingredients, working the mixture with your hands until you have a well-mixed, doughy loaf.',
                    'Place the seitan on parchment paper on top of foil and wrap it up like a burrito.',
                    'Drain and rinse 1 can of chickpeas, then toss them in an all-purpose dry seasoning of your choice.',
                    'Add the chickpeas to a parchment-lined baking sheet (can share with the seitan) and bake both at 375F (190C) for 45-60 minutes.',
                    'Check the chickpeas at the 20-minute mark and continue checking until roasted to your liking. Remove once the seitan finishes baking.',
                    'When both the seitan and chickpeas are finished baking, let them cool, then chop or tear the seitan into bite-sized pieces.',
                    'Part 2 - Romesco sauce: Blend 12-oz jar (340 g) roasted red peppers, 1/4 cup (55 g) low-sodium vegetable broth, 1 tbsp (15 g) red wine vinegar, 1 tsp (7 g) maple syrup, 1/4 cup (56 g) low-sodium tomato paste, 1 tbsp (15 g) minced garlic, 1/4 cup (12 g) chopped fresh chives, 1/3 cup (43 g) pre-roasted almonds, 1 tsp (about 2 g) smoked paprika, 1/4 tsp (about 1 g) black pepper, and 1/2 tsp (3 g) iodized salt (optional).',
                    'Pour the sauce into a fridge-friendly jar and refrigerate.',
                    'Part 3 - Polenta: Add 1 cup (221 g) vegetable broth and 1 cup (237 g) water to a pot and bring to a boil.',
                    'Cut the tube of polenta in half, then slice one half into 1/2-inch slices.',
                    'Turn the heat down to medium or medium-low and slowly add the polenta slices to the simmering broth/water.',
                    'Once softened, carefully mash the polenta with a potato masher or fork, adding pepper to preference, and cook for roughly 5 minutes, stirring occasionally until it thickens.',
                    'Transfer the cooked polenta to a fridge-friendly storage container and set aside.',
                    'Part 4 - Kale: Wash and dry the kale, tear off the ribs, and cut the leaves into small strips. Place in a storage container in the fridge.',
                    'Part 5 - Assembling the bowls: Lay out 4 fridge-friendly storage containers with lids.',
                    'Add 1 cup (21 g) of chopped kale to each container.',
                    'Add 1/4 batch of the chopped seitan to each container.',
                    'Pour 1/4 batch of the polenta into each container.',
                    'Pour 1/4 batch of the romesco sauce into each container.',
                    'Sprinkle 1/4 batch of the roasted chickpeas into each container.',
                    'Your bowls are ready to be eaten!',
                ]),
            ],
        ];

        foreach ($recipes as $recipeData) {
            $foodType = FoodType::where('name', $recipeData['name'])->first();

            $recipe = Recipe::firstOrCreate(
                ['name' => $recipeData['name']],
                [
                    'food_type_id' => $foodType?->id,
                    'description' => $recipeData['description'],
                    'ingredients' => $recipeData['ingredients'],
                    'instructions' => $recipeData['instructions'],
                    'servings' => $recipeData['servings'],
                ]
            );

            if ($recipe->sources()->count() === 0) {
                foreach ($sources as $source) {
                    $recipe->sources()->create($source);
                }
            }
        }
    }
}
