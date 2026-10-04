<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ChoreStatus;
use App\Enums\HouseholdRole;
use App\Enums\MealSlot;
use App\Enums\Palette;
use App\Enums\PendingReason;
use App\Models\Household;
use App\Models\Recipe;
use App\Models\ShoppingList;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;

/**
 * The Parker household — the demo data the HouseHub design was drawn against.
 */
class HouseHubSeeder extends Seeder
{
    public function run(): void
    {
        // The demo household is seeded with a fake active subscription —
        // running this in production would hand out free access. Guarded
        // here rather than in DatabaseSeeder so `--class=` can't skip it.
        if (app()->isProduction()) {
            $this->command->error('Refusing to seed demo data in production.');

            return;
        }

        $household = Household::create([
            'name' => 'The Parkers',
            'location' => 'Bristol',
            'streak_days' => 12,
            'streak_last_active_date' => today(),
            'trial_ends_at' => now()->addDays(30),
        ]);

        // Seeded as already subscribed rather than mid-trial, so the demo
        // account shows the "paid" state of the app out of the box.
        Subscription::create([
            'household_id' => $household->id,
            'type' => 'default',
            'stripe_id' => 'sub_dev_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_home_monthly_dev',
            'quantity' => 1,
        ]);

        $members = $this->seedMembers($household);
        $this->seedShopping($household);
        $this->seedChores($household, $members);
        $this->seedMeals($household, $members);
        $this->seedCalendar($household, $members);
        $this->seedBudget($household);
        $this->seedDocuments($household, $members);
    }

    /** @return array<string, User> */
    private function seedMembers(Household $household): array
    {
        $people = [
            ['sarah', 'Sarah Parker', 'SP', Palette::Mint, 'WFH today', HouseholdRole::Owner],
            ['james', 'James Parker', 'JP', Palette::Lilac, 'Office · back 6pm', HouseholdRole::Adult],
            ['mia', 'Mia Parker', 'MP', Palette::Sun, 'School · swim club', HouseholdRole::Teen],
            ['noah', 'Noah Parker', 'NP', Palette::Sky, 'School · football', HouseholdRole::Child],
        ];

        $members = [];

        foreach ($people as [$key, $name, $initials, $colour, $status, $role]) {
            $members[$key] = User::create([
                'current_household_id' => $household->id,
                'name' => $name,
                'initials' => $initials,
                'colour' => $colour,
                'status_line' => $status,
                'email' => "{$key}@househub.test",
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]);
            $members[$key]->households()->attach($household->id, ['role' => $role, 'pending' => false]);
        }

        $margaret = User::create([
            'current_household_id' => $household->id,
            'name' => 'Margaret Parker',
            'initials' => 'MP',
            'colour' => Palette::Coral,
            'status_line' => null,
            'email' => 'margaret@parkerhouse.co.uk',
            'password' => Hash::make(Str::random(40)),
        ]);
        $margaret->households()->attach($household->id, [
            'role' => HouseholdRole::Adult,
            'pending' => true,
            'pending_reason' => PendingReason::Invited,
        ]);

        return $members;
    }

    private function seedShopping(Household $household): void
    {
        $lists = [
            ['Tesco', Palette::Mint, [
                ['Chicken thighs', '1kg', false],
                ['New potatoes', '750g', false],
                ['Lemons', 'x3', true],
                ['Oat milk', 'x2', false],
                ['Frozen peas', '1 bag', false],
                ['Sourdough', '1 loaf', false],
                ['Kitchen roll', 'x4', true],
                ['Bin bags', '1 box', false],
                ['Bananas', 'x6', false],
                ['Fish fingers', 'x12', false],
            ]],
            ['Aldi', Palette::Sky, [
                ['Greek yoghurt', 'x2', false],
                ['Carrots', '1kg', false],
                ['Croissants', 'x6', true],
            ]],
            ['Costco', Palette::Lilac, [
                ['Washing tabs', 'x60', false],
                ['Coffee beans', '2kg', false],
            ]],
            ['DIY', Palette::Coral, [
                ['Masking tape', 'x2', false],
                ['Filler', '1 tub', true],
                ['Sandpaper', 'x5', false],
            ]],
            ['Christmas', Palette::Sun, [
                ['Wrapping paper', 'x4', false],
                ['Mince pies', 'x2', false],
            ]],
        ];

        foreach ($lists as $position => [$name, $colour, $items]) {
            $list = ShoppingList::create([
                'household_id' => $household->id,
                'name' => $name,
                'slug' => Str::slug($name),
                'colour' => $colour,
                'position' => $position,
            ]);

            foreach ($items as $itemPosition => [$itemName, $quantity, $done]) {
                $list->items()->create([
                    'name' => $itemName,
                    'quantity' => $quantity,
                    'completed_at' => $done ? now() : null,
                    'position' => $itemPosition,
                ]);
            }
        }
    }

    /** @param array<string, User> $members */
    private function seedChores(Household $household, array $members): void
    {
        $chores = [
            ['Empty dishwasher', 'noah', ChoreStatus::Today],
            ['Put the recycling out', 'james', ChoreStatus::Today],
            ['Hoover upstairs', 'sarah', ChoreStatus::Today],
            ['Tidy bedroom', 'mia', ChoreStatus::Today],
            ['Water the plants', 'mia', ChoreStatus::Today],
            ['Change bed sheets', 'sarah', ChoreStatus::Today],
            ['Wash the car', 'james', ChoreStatus::Today],
            ['Feed the cat', 'noah', ChoreStatus::Done],
            ['Homework check', 'sarah', ChoreStatus::Done],
        ];

        foreach ($chores as $position => [$name, $who, $status]) {
            $household->chores()->create([
                'assigned_to' => $members[$who]->id,
                'name' => $name,
                'status' => $status,
                'position' => $position,
            ]);
        }
    }

    /** @param array<string, User> $members */
    private function seedMeals(Household $household, array $members): void
    {
        $recipes = [
            ['Lemon chicken traybake', '45 min', 'Easy', 0, ['Healthy'], true,
                'One tray, one oven. Chicken thighs, new potatoes, lemon and thyme. Serves four with leftovers for Mia\'s packed lunch.'],
            ['Halloumi wraps', '15 min', 'Easy', 1, ['Quick', 'Vegetarian'], false,
                'Griddled halloumi, houmous and slaw in a warm wrap. On the table before the kettle\'s even cool.'],
            ['Slow cooker chilli', '6 hrs', 'Easy', 3, ['Slow Cooker'], false,
                'Beef mince, kidney beans and a slow afternoon in the cooker. Freezes well if there\'s any left.'],
            ['Salmon & greens', '25 min', 'Medium', 2, ['Healthy'], false,
                'Pan-seared salmon with steamed greens and a lemon butter sauce. Quick enough for a school night.'],
            ['Fish finger sarnies', '12 min', 'Easy', 4, ['Quick'], false,
                'Crispy fish fingers, buttered bread, a squeeze of ketchup. The kids\' favourite, no arguments.'],
            ['Veg lasagne', '1 hr 10', 'Medium', 0, ['Vegetarian'], false,
                'Layers of roasted veg, béchamel and pasta sheets. Worth the oven time — leftovers reheat well.'],
            ['BBQ burgers', '35 min', 'Easy', 1, ['BBQ'], false,
                'Home-formed beef patties on the grill with all the usual toppings. Weather permitting.'],
            ['Roast chicken', '1 hr 45', 'Medium', 2, [], false,
                'The Sunday classic — roast chicken, potatoes and all the trimmings. Stock the carcass for soup.'],
            ['Pancakes', '20 min', 'Easy', 3, ['Desserts'], false,
                'Stacked pancakes with maple syrup and whatever fruit is in the bowl. A slow-morning breakfast.'],
            ['Katsu curry', '50 min', 'Medium', 1, [], true,
                'Crumbed chicken with a mild, sweet curry sauce over rice. A household favourite on repeat.'],
            ['Tomato orzo', '25 min', 'Easy', 0, ['Quick'], true,
                'One-pot orzo simmered in a rich tomato sauce with basil and parmesan. Ready in one pan.'],
            ['Beef stew', '3 hrs', 'Slow cooker', 4, ['Slow Cooker'], true,
                'Chunky beef, root veg and a rich gravy, left to its own devices for the afternoon.'],
            ['Chickpea curry', '30 min', 'Vegetarian', 2, ['Vegetarian'], true,
                'Chickpeas simmered in a spiced tomato and coconut sauce. Naturally vegan, always a crowd-pleaser.'],
            ['Sticky toffee pud', '55 min', 'Dessert', 3, ['Desserts'], true,
                'Warm sponge, dates and a toffee sauce poured on right before serving. Best with custard.'],
            ['Greek salad', '10 min', 'Quick', 0, ['Quick', 'Healthy'], true,
                'Cucumber, tomato, olives and feta with a simple olive oil dressing. No cooking required.'],
        ];

        $saved = [];

        foreach ($recipes as [$name, $duration, $difficulty, $tint, $tags, $favourite, $description]) {
            $saved[$name] = Recipe::create([
                'household_id' => $household->id,
                'name' => $name,
                'description' => $description,
                'duration_label' => $duration,
                'difficulty' => $difficulty,
                'tags' => $tags,
                'tint' => $tint,
                'is_favourite' => $favourite,
            ]);
        }

        $monday = CarbonImmutable::now()->startOfWeek();

        $plan = [
            ['Lemon chicken traybake', 0, MealSlot::Dinner, 'james', 0],
            ['Halloumi wraps', 0, MealSlot::Lunch, 'sarah', 2],
            ['Slow cooker chilli', 1, MealSlot::Dinner, 'sarah', 1],
            ['Salmon & greens', 2, MealSlot::Dinner, 'james', 3],
            ['Fish finger sarnies', 3, MealSlot::Dinner, 'mia', 0],
            ['Veg lasagne', 4, MealSlot::Dinner, 'sarah', 4],
            ['BBQ burgers', 5, MealSlot::Dinner, 'james', 2],
            ['Roast chicken', 6, MealSlot::Dinner, 'james', 0],
            ['Pancakes', 6, MealSlot::Breakfast, 'noah', 0],
        ];

        foreach ($plan as [$recipe, $dayOffset, $slot, $cook, $missing]) {
            $household->plannedMeals()->create([
                'recipe_id' => $saved[$recipe]->id,
                'cook_id' => $members[$cook]->id,
                'planned_on' => $monday->addDays($dayOffset)->toDateString(),
                'slot' => $slot,
                'missing_ingredients' => $missing,
            ]);
        }
    }

    /**
     * Spread across the whole of the current month so the month grid has something
     * to show. `who` lists the members an event is for — empty means whole house.
     *
     * @param  array<string, User>  $members
     */
    private function seedCalendar(Household $household, array $members): void
    {
        $today = CarbonImmutable::today();

        // [day of month, start, end, title, who, location, colour, all day]
        $events = [
            [2, '19:30', '22:00', 'Dinner at The Ivy', [], 'The Ivy, Clifton', Palette::Coral, false],
            [3, '18:00', '19:30', 'Football practice', ['noah'], 'Beckett Park', Palette::Sky, false],
            [4, '16:00', '17:00', 'Swim club', ['mia'], 'Horfield Leisure Centre', Palette::Sun, false],
            [5, '13:00', '14:00', 'Client call', ['sarah'], null, Palette::Mint, false],
            [6, '09:00', null, 'Recycling collection', [], null, Palette::Mint, false],
            [8, '10:00', '11:00', 'Dentist', ['noah'], 'Whiteladies Dental', Palette::Sky, false],
            [9, '00:00', null, "Grandma's birthday", [], null, Palette::Coral, true],
            [10, '18:00', '19:30', 'Football practice', ['noah'], 'Beckett Park', Palette::Sky, false],
            [11, '16:00', '17:00', 'Swim club', ['mia'], 'Horfield Leisure Centre', Palette::Sun, false],
            [12, '08:45', '12:00', 'Boiler service', [], null, Palette::Lilac, false],
            [13, '19:00', null, 'Council tax due', [], null, Palette::Coral, false],
            [14, '11:00', '15:00', 'Parents evening', ['sarah', 'james'], 'Redland Green School', Palette::Lilac, false],
            [16, '09:00', null, 'Recycling collection', [], null, Palette::Mint, false],
            [17, '18:00', '19:30', 'Football practice', ['noah'], 'Beckett Park', Palette::Sky, false],
            [18, '16:00', '17:00', 'Swim club', ['mia'], 'Horfield Leisure Centre', Palette::Sun, false],
            [19, '09:30', '17:00', 'Sarah in London', ['sarah'], null, Palette::Mint, false],
            [21, '00:00', null, 'Bank holiday', [], null, Palette::Coral, true],
            [22, '14:00', '16:00', 'Mia at Lucy’s', ['mia'], null, Palette::Sun, false],
            [24, '18:00', '19:30', 'Football practice', ['noah'], 'Beckett Park', Palette::Sky, false],
            [26, '19:00', '23:00', 'James five-a-side', ['james'], 'Goals Bristol', Palette::Lilac, false],
            [28, '12:30', '15:00', 'Family lunch', [], 'Nan’s', Palette::Coral, false],
        ];

        foreach ($events as [$dayOfMonth, $start, $end, $title, $who, $location, $colour, $allDay]) {
            $day = $today->startOfMonth()->addDays($dayOfMonth - 1);

            $event = $household->calendarEvents()->create([
                'title' => $title,
                'starts_at' => $day->setTimeFromTimeString($start),
                'ends_at' => $end !== null ? $day->setTimeFromTimeString($end) : null,
                'is_all_day' => $allDay,
                'location' => $location,
                'colour' => $colour,
            ]);

            $event->attendees()->sync(array_map(fn (string $key) => $members[$key]->id, $who));
        }
    }

    private function seedBudget(Household $household): void
    {
        $month = CarbonImmutable::today()->startOfMonth();

        // [label, colour, budgeted £] — the four categories from the design.
        $categories = [
            ['Food & shopping', Palette::Coral, 900],
            ['Utilities', Palette::Mint, 600],
            ['Subscriptions', Palette::Sun, 150],
            ['Fuel', Palette::Sky, 500],
        ];

        foreach ($categories as $position => [$label, $colour, $budgeted]) {
            $household->budgetCategories()->create([
                'label' => $label,
                'colour' => $colour,
                'budgeted_pence' => $budgeted * 100,
                'month' => $month->toDateString(),
                'position' => $position,
            ]);
        }
    }

    /** @param array<string, User> $members */
    private function seedDocuments(Household $household, array $members): void
    {
        $folders = [
            ['Property', '🏠', Palette::Lilac],
            ['Insurance', '🛡', Palette::Sky],
            ['Vehicles', '🚗', Palette::Sun],
            ['Utilities', '💡', Palette::Mint],
        ];

        $created = [];

        foreach ($folders as $position => [$name, $icon, $colour]) {
            $created[$name] = $household->documentFolders()->create([
                'name' => $name,
                'icon' => $icon,
                'colour' => $colour,
                'position' => $position,
            ]);
        }

        // [folder, filename, extension, size in KB, uploader, expires in N days]
        $documents = [
            ['Property', 'Mortgage offer — Nationwide.pdf', 'pdf', 2480, 'sarah', null],
            ['Property', 'EPC certificate.pdf', 'pdf', 820, 'sarah', null],
            ['Insurance', 'Buildings & contents policy.pdf', 'pdf', 1180, 'sarah', 240],
            ['Vehicles', 'Car insurance — Golf.pdf', 'pdf', 960, 'james', 35],
            ['Vehicles', 'MOT certificate 2026.jpg', 'jpg', 1420, 'james', null],
            ['Utilities', 'Octopus annual statement.pdf', 'pdf', 540, 'sarah', null],
        ];

        $disk = Storage::disk(config('documents.disk'));

        foreach ($documents as [$folderName, $name, $extension, $sizeKb, $uploader, $expiresInDays]) {
            $folder = $created[$folderName];
            $path = "documents/{$household->id}/".Str::uuid().'.'.$extension;
            $disk->put($path, "Seeded placeholder for {$name}");

            $folder->documents()->create([
                'household_id' => $household->id,
                'added_by' => $members[$uploader]->id,
                'name' => $name,
                'path' => $path,
                'extension' => $extension,
                'size' => $sizeKb * 1024,
                'expires_at' => $expiresInDays !== null ? now()->addDays($expiresInDays) : null,
            ]);
        }
    }
}
