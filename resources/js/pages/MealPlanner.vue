<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import AiMealPlanDialog from '@/components/househub/AiMealPlanDialog.vue';
import RecipeDetailFields from '@/components/househub/RecipeDetailFields.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useHouseholdRole } from '@/composables/useHouseholdRole';
import HouseHubLayout from '@/layouts/HouseHubLayout.vue';
import { tint } from '@/lib/househub';
import type { AiPlannerOptions, Member, PlannedMeal, PlannerDay, Recipe, ShoppingList, TagOption } from '@/types/househub';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Sparkles } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    weekOf: string;
    days: PlannerDay[];
    library: Recipe[];
    recipeCount: number;
    recipes: Recipe[];
    members: Member[];
    shoppingLists: ShoppingList[];
    tagOptions: TagOption[];
    aiConfigured: boolean;
    aiPaused: boolean;
    aiAvailableFrom: string | null;
    aiSuggestedBudget: number | null;
    aiOptions: AiPlannerOptions;
}>();

const aiOpen = ref(false);

const SLOTS = ['breakfast', 'lunch', 'dinner'];

const { canManage } = useHouseholdRole();

const draggingId = ref<number | null>(null);
const dropTarget = ref<string | null>(null);

const open = ref(false);
const editing = ref<PlannedMeal | null>(null);

const form = useForm({
    recipe_id: null as number | null,
    new_recipe_name: '',
    new_recipe_description: '',
    new_recipe_duration_label: '',
    new_recipe_difficulty: '',
    new_recipe_tags: [] as string[],
    new_recipe_ingredients: [] as { name: string; quantity: string }[],
    new_recipe_tint: 0,
    new_recipe_is_favourite: false as boolean,
    new_recipe_shopping_list_id: null as number | null,
    planned_on: '',
    slot: 'dinner',
    cook_id: null as number | null,
});

const dialogTitle = computed(() => (editing.value ? 'Edit meal' : 'New meal'));
const showRecipeDetails = computed(() => form.new_recipe_name.trim() !== '' || form.recipe_id !== null);
const HANDLED_ERROR_KEYS = ['recipe_id', 'new_recipe_name', 'new_recipe_description', 'planned_on'];
const hasUnhandledErrors = computed(() => Object.keys(form.errors).some((key) => !HANDLED_ERROR_KEYS.includes(key)));

// Drops any tag not in tagOptions — e.g. legacy/seeded values that predate
// the fixed tag list — since there's no checkbox to deselect them and
// re-submitting them as-is would fail validation with no visible error.
function validTags(tags: string[]): string[] {
    return tags.filter((tag) => props.tagOptions.some((option) => option.value === tag));
}

function populateRecipeDetailFields(recipe: Recipe): void {
    form.new_recipe_description = recipe.description ?? '';
    form.new_recipe_duration_label = recipe.durationLabel ?? '';
    form.new_recipe_difficulty = recipe.difficulty ?? '';
    form.new_recipe_tags = validTags(recipe.tags);
    form.new_recipe_ingredients = recipe.ingredients.map((i) => ({ name: i.name, quantity: i.quantity ?? '' }));
    form.new_recipe_tint = recipe.tint;
    form.new_recipe_is_favourite = recipe.isFavourite;
}

function clearRecipeDetailFields(): void {
    form.new_recipe_description = '';
    form.new_recipe_duration_label = '';
    form.new_recipe_difficulty = '';
    form.new_recipe_tags = [];
    form.new_recipe_ingredients = [];
    form.new_recipe_tint = 0;
    form.new_recipe_is_favourite = false;
}

function onRecipeSelected(): void {
    form.clearErrors();
    form.new_recipe_name = '';
    const recipe = props.recipes.find((r) => r.id === form.recipe_id) ?? null;

    if (recipe) {
        populateRecipeDetailFields(recipe);
    } else {
        clearRecipeDetailFields();
    }
}

function onNewRecipeNameInput(): void {
    if (form.recipe_id !== null) {
        form.clearErrors();
        clearRecipeDetailFields();
    }

    form.recipe_id = null;
}

function openNew(date: string): void {
    editing.value = null;
    form.clearErrors();
    form.defaults({
        recipe_id: null,
        new_recipe_name: '',
        new_recipe_description: '',
        new_recipe_duration_label: '',
        new_recipe_difficulty: '',
        new_recipe_tags: [],
        new_recipe_ingredients: [],
        new_recipe_tint: 0,
        new_recipe_is_favourite: false,
        new_recipe_shopping_list_id: null,
        planned_on: date,
        slot: 'dinner',
        cook_id: null,
    });
    form.reset();
    open.value = true;
}

function openEdit(meal: PlannedMeal, date: string): void {
    editing.value = meal;
    form.clearErrors();

    const recipe = props.recipes.find((r) => r.id === meal.recipeId) ?? null;

    form.defaults({
        recipe_id: meal.recipeId,
        new_recipe_name: '',
        new_recipe_description: recipe?.description ?? '',
        new_recipe_duration_label: recipe?.durationLabel ?? '',
        new_recipe_difficulty: recipe?.difficulty ?? '',
        new_recipe_tags: recipe ? validTags(recipe.tags) : [],
        new_recipe_ingredients: recipe ? recipe.ingredients.map((i) => ({ name: i.name, quantity: i.quantity ?? '' })) : [],
        new_recipe_tint: recipe?.tint ?? 0,
        new_recipe_is_favourite: recipe?.isFavourite ?? false,
        new_recipe_shopping_list_id: null,
        planned_on: date,
        slot: meal.slotKey,
        cook_id: meal.cook?.id ?? null,
    });
    form.reset();
    open.value = true;
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    };

    form.transform((data) => ({
        ...data,
        new_recipe_ingredients: data.new_recipe_ingredients.filter((ingredient) => ingredient.name.trim() !== ''),
    }));

    if (editing.value) {
        form.patch(route('meals.update', { meal: editing.value.id }), options);
    } else {
        form.post(route('meals.store'), options);
    }
}

const confirmOpen = ref(false);

function destroy(): void {
    if (editing.value === null) {
        return;
    }

    confirmOpen.value = true;
}

function confirmDestroy(): void {
    if (editing.value === null) {
        return;
    }

    confirmOpen.value = false;

    router.delete(route('meals.destroy', { meal: editing.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}

function startDrag(event: DragEvent, id: number): void {
    draggingId.value = id;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }
}

function endDrag(): void {
    draggingId.value = null;
    dropTarget.value = null;
}

function drop(date: string): void {
    const meal = draggingId.value;
    endDrag();

    if (meal === null) {
        return;
    }

    router.patch(route('meals.reschedule', { meal }), { planned_on: date }, { preserveScroll: true });
}
</script>

<template>
    <HouseHubLayout title="Meal Planner" :subtitle="`Week of ${weekOf}`">
        <div class="flex animate-hh-rise flex-col gap-4">
            <div v-if="canManage" class="flex flex-wrap items-center gap-3">
                <div class="hidden min-w-0 flex-1 text-[13px] text-hh-ink3 [@media(hover:hover)]:block">
                    Drag any meal card onto another day to reschedule, or click one to edit it.
                </div>
                <div class="min-w-0 flex-1 text-[13px] text-hh-ink3 [@media(hover:hover)]:hidden">
                    Tap a meal to edit it or move it to another day.
                </div>
                <button type="button" class="hh-btn ml-auto w-auto flex-none whitespace-nowrap bg-hh-ink text-hh-shell" @click="aiOpen = true">
                    <Sparkles class="mr-1.5 inline h-3.5 w-3.5" :stroke-width="2.5" />
                    Plan my week
                </button>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                <div
                    v-for="day in days"
                    :key="day.date"
                    class="group flex min-h-[112px] flex-col gap-2.5 rounded-[18px] border border-dashed p-3 transition xl:min-h-[400px]"
                    :style="{
                        borderColor: dropTarget === day.date ? 'var(--hh-mint)' : 'var(--hh-line)',
                        background: dropTarget === day.date ? 'var(--hh-soft)' : day.isToday ? 'var(--hh-sunk)' : 'transparent',
                    }"
                    @dragover.prevent="dropTarget = day.date"
                    @dragleave="dropTarget === day.date && (dropTarget = null)"
                    @drop.prevent="drop(day.date)"
                >
                    <div class="flex items-baseline gap-1.5">
                        <span class="text-[13px] font-extrabold">{{ day.dayLabel }}</span>
                        <span class="font-mono text-[11px] text-hh-ink3">{{ day.dateLabel }}</span>
                        <button
                            v-if="canManage"
                            type="button"
                            class="ml-auto grid h-8 w-8 place-items-center rounded-md text-hh-ink3 transition hover:bg-hh-soft hover:text-hh-ink [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:focus-visible:opacity-100 [@media(hover:hover)]:group-hover:opacity-100"
                            :aria-label="`Add a meal on ${day.dateLabel}`"
                            @click="openNew(day.date)"
                        >
                            <Plus class="h-3.5 w-3.5" :stroke-width="2.5" />
                        </button>
                    </div>

                    <button
                        v-for="meal in day.meals"
                        :key="meal.id"
                        type="button"
                        :draggable="canManage"
                        class="overflow-hidden rounded-2xl border border-hh-line bg-hh-card text-left transition hover:-translate-y-[3px] hover:shadow-hh"
                        :class="canManage ? 'cursor-grab' : ''"
                        :style="{ opacity: draggingId === meal.id ? 0.35 : 1 }"
                        @dragstart="startDrag($event, meal.id)"
                        @dragend="endDrag"
                        @click="canManage && openEdit(meal, day.date)"
                    >
                        <div class="flex min-h-[76px] flex-col justify-between gap-1 px-3 py-2.5" :style="{ background: tint(meal.tint) }">
                            <span class="self-end text-[10px] font-extrabold uppercase tracking-[0.08em] text-hh-ontint opacity-60">
                                {{ meal.slot }}
                            </span>
                            <span
                                class="line-clamp-3 hyphens-auto break-words text-[14px] font-extrabold leading-[1.2] tracking-[-0.02em] text-hh-ontint"
                                :title="meal.name"
                            >
                                {{ meal.name }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-[7px] px-3 py-2.5">
                            <div class="flex flex-wrap gap-1.5">
                                <span class="rounded-md bg-hh-soft px-1.5 py-[3px] font-mono text-[10.5px] text-hh-ink2">
                                    {{ meal.duration }}
                                </span>
                                <span class="rounded-md bg-hh-soft px-1.5 py-[3px] text-[10.5px] font-semibold text-hh-ink2">
                                    {{ meal.difficulty }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span
                                    v-if="meal.cook"
                                    class="grid h-[21px] w-[21px] place-items-center rounded-[7px] text-[9px] font-extrabold text-[#0E1A2B]"
                                    :style="{ background: meal.cook.colour }"
                                >
                                    {{ meal.cook.initials }}
                                </span>
                                <span class="text-[11px] text-hh-ink3">{{ meal.missingLabel }}</span>
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <div class="rounded-[22px] border border-hh-line bg-hh-panel p-4 sm:p-[22px]">
                <div class="mb-3.5 flex items-baseline gap-2.5">
                    <h3 class="text-[15px] font-extrabold">Recipe library</h3>
                    <span class="text-xs text-hh-ink3">Favourites · {{ recipeCount }} saved</span>
                    <Link :href="route('recipes.index')" class="ml-auto text-[12.5px] font-semibold text-hh-coral hover:opacity-75">
                        Manage recipes
                    </Link>
                </div>
                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                    <div v-for="recipe in library" :key="recipe.id" class="rounded-2xl border border-hh-line bg-hh-card p-3.5">
                        <div class="mb-2.5 h-[58px] rounded-xl" :style="{ background: tint(recipe.tint) }"></div>
                        <div class="text-[13.5px] font-bold leading-tight">{{ recipe.name }}</div>
                        <div class="mt-1.5 text-[11.5px] text-hh-ink3">{{ recipe.meta }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add / edit meal -->
        <Dialog :open="open" @update:open="open = $event">
            <DialogContent class="rounded-[22px] border-hh-line bg-hh-card text-hh-ink sm:rounded-[22px]">
                <DialogHeader>
                    <DialogTitle class="text-[16px] font-extrabold tracking-tight">{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="flex flex-col gap-1.5">
                        <label for="recipe_id" class="hh-label">Recipe</label>
                        <select id="recipe_id" v-model="form.recipe_id" class="hh-input" @change="onRecipeSelected">
                            <option :value="null">— Choose from the library —</option>
                            <option v-for="recipe in recipes" :key="recipe.id" :value="recipe.id">{{ recipe.name }}</option>
                        </select>
                        <p v-if="form.errors.recipe_id" class="text-[12.5px] text-hh-coral">{{ form.errors.recipe_id }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="new_recipe_name" class="hh-label">…or add a new recipe</label>
                        <input
                            id="new_recipe_name"
                            v-model="form.new_recipe_name"
                            type="text"
                            class="hh-input"
                            placeholder="Recipe name"
                            @input="onNewRecipeNameInput"
                        />
                        <p v-if="form.errors.new_recipe_name" class="text-[12.5px] text-hh-coral">{{ form.errors.new_recipe_name }}</p>
                    </div>

                    <div v-if="showRecipeDetails" class="flex flex-col gap-1.5">
                        <label for="new_recipe_description" class="hh-label">Notes</label>
                        <textarea
                            id="new_recipe_description"
                            v-model="form.new_recipe_description"
                            rows="2"
                            maxlength="2000"
                            class="hh-input h-auto py-2.5"
                            placeholder="Optional"
                        ></textarea>
                        <p v-if="form.errors.new_recipe_description" class="text-[12.5px] text-hh-coral">
                            {{ form.errors.new_recipe_description }}
                        </p>

                        <RecipeDetailFields
                            v-model:duration-label="form.new_recipe_duration_label"
                            v-model:difficulty="form.new_recipe_difficulty"
                            v-model:tags="form.new_recipe_tags"
                            v-model:ingredients="form.new_recipe_ingredients"
                            v-model:tint="form.new_recipe_tint"
                            v-model:is-favourite="form.new_recipe_is_favourite"
                            :tag-options="tagOptions"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label for="planned_on" class="hh-label">Day</label>
                            <input id="planned_on" v-model="form.planned_on" type="date" class="hh-input" required />
                            <p v-if="form.errors.planned_on" class="text-[12.5px] text-hh-coral">{{ form.errors.planned_on }}</p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="slot" class="hh-label">Slot</label>
                            <select id="slot" v-model="form.slot" class="hh-input">
                                <option v-for="slot in SLOTS" :key="slot" :value="slot">{{ slot }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="cook_id" class="hh-label">Cook</label>
                        <select id="cook_id" v-model="form.cook_id" class="hh-input">
                            <option :value="null">Unassigned</option>
                            <option v-for="member in members" :key="member.id" :value="member.id">{{ member.name }}</option>
                        </select>
                    </div>

                    <div
                        v-if="showRecipeDetails && form.new_recipe_ingredients.some((i) => i.name.trim() !== '')"
                        class="flex flex-col gap-1.5 border-t border-hh-line pt-4"
                    >
                        <label for="new_recipe_shopping_list_id" class="hh-label">Also add ingredients to a shopping list</label>
                        <select id="new_recipe_shopping_list_id" v-model="form.new_recipe_shopping_list_id" class="hh-input">
                            <option :value="null">Don't add</option>
                            <option v-for="list in shoppingLists" :key="list.id" :value="list.id">{{ list.name }}</option>
                        </select>
                    </div>

                    <p v-if="hasUnhandledErrors" class="text-[12.5px] text-hh-coral">Couldn't save — check the recipe details above.</p>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="hh-btn w-auto bg-hh-coral text-white" :disabled="form.processing">
                            {{ editing ? 'Save changes' : 'Add meal' }}
                        </button>
                        <button type="button" class="hh-btn w-auto bg-hh-soft text-hh-ink" @click="open = false">Cancel</button>
                        <button v-if="editing" type="button" class="hh-btn ml-auto w-auto bg-hh-soft text-hh-coral" @click="destroy">Remove</button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog :open="confirmOpen" message="Remove this meal from the plan?" @update:open="confirmOpen = $event" @confirm="confirmDestroy" />

        <AiMealPlanDialog
            :open="aiOpen"
            :ai-configured="aiConfigured"
            :ai-paused="aiPaused"
            :ai-available-from="aiAvailableFrom"
            :ai-suggested-budget="aiSuggestedBudget"
            :ai-options="aiOptions"
            :days="days"
            :shopping-lists="shoppingLists"
            @update:open="aiOpen = $event"
        />
    </HouseHubLayout>
</template>
