<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import RecipeDetailFields from '@/components/ryh/RecipeDetailFields.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useAddIngredientsToShoppingList } from '@/composables/useAddIngredientsToShoppingList';
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import { tint } from '@/lib/ryh';
import type { RecipeData, ShoppingListData } from '@/types/generated';
import type { TagOption } from '@/types/ryh';
import { router, useForm } from '@inertiajs/vue3';
import { Plus, Star } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineOptions({ layout: RyhShell });

const props = defineProps<{ recipes: RecipeData[]; tagOptions: TagOption[]; shoppingLists: ShoppingListData[] }>();

const can = useHouseholdAbilities();

// Drops any tag not in tagOptions — e.g. legacy/seeded values that predate
// the fixed tag list — since there's no checkbox to deselect them and
// re-submitting them as-is would fail validation with no visible error.
function validTags(tags: string[]): string[] {
    return tags.filter((tag) => props.tagOptions.some((option) => option.value === tag));
}

const HANDLED_ERROR_KEYS = ['name', 'description'];
const hasUnhandledErrors = computed(() => Object.keys(form.errors).some((key) => !HANDLED_ERROR_KEYS.includes(key)));

const open = ref(false);
const editing = ref<RecipeData | null>(null);

const form = useForm({
    name: '',
    description: '',
    duration_label: '',
    difficulty: '',
    tags: [] as string[],
    ingredients: [] as { name: string; quantity: string }[],
    tint: 0,
    is_favourite: false as boolean,
    shopping_list_id: null as number | null,
});

const dialogTitle = computed(() => (editing.value ? 'Edit recipe' : 'New recipe'));

function openNew(): void {
    editing.value = null;
    selectedListId.value = null;
    form.clearErrors();
    form.defaults({
        name: '',
        description: '',
        duration_label: '',
        difficulty: '',
        tags: [],
        ingredients: [],
        tint: 0,
        is_favourite: false,
        shopping_list_id: null,
    });
    form.reset();
    open.value = true;
}

function openEdit(recipe: RecipeData): void {
    editing.value = recipe;
    selectedListId.value = null;
    form.clearErrors();
    form.defaults({
        name: recipe.name,
        description: recipe.description ?? '',
        duration_label: recipe.durationLabel ?? '',
        difficulty: recipe.difficulty ?? '',
        tags: validTags(recipe.tags),
        ingredients: recipe.ingredients.map((i) => ({ name: i.name, quantity: i.quantity ?? '' })),
        tint: recipe.tint,
        is_favourite: recipe.isFavourite,
        shopping_list_id: null,
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
        ingredients: data.ingredients.filter((ingredient) => ingredient.name.trim() !== ''),
    }));

    if (editing.value) {
        form.patch(route('recipes.update', { recipe: editing.value.id }), options);
    } else {
        form.post(route('recipes.store'), options);
    }
}

const { selectedListId, addIngredientsToList } = useAddIngredientsToShoppingList();

function submitAddIngredients(): void {
    if (editing.value === null) {
        return;
    }

    addIngredientsToList(editing.value.id);
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

    router.delete(route('recipes.destroy', { recipe: editing.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <RyhLayout title="Recipe Library" :subtitle="`${recipes.length} saved`">
        <div class="flex animate-ryh-rise flex-col gap-4">
            <div v-if="can?.createRecipes" class="flex items-center gap-2">
                <button type="button" class="ryh-btn w-auto bg-ryh-coral text-white" @click="openNew">
                    <Plus class="h-4 w-4" :stroke-width="2.5" />
                    New recipe
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                <button
                    v-for="recipe in recipes"
                    :key="recipe.id"
                    type="button"
                    class="flex flex-col rounded-2xl border border-ryh-line bg-ryh-card p-3.5 text-left transition hover:-translate-y-[3px] hover:shadow-ryh"
                    @click="recipe.can.update && openEdit(recipe)"
                >
                    <div class="relative mb-2.5 h-[58px] rounded-xl" :style="{ background: tint(recipe.tint) }">
                        <Star
                            v-if="recipe.isFavourite"
                            class="absolute right-1.5 top-1.5 h-4 w-4 text-ryh-ontint"
                            :stroke-width="2.5"
                            fill="currentColor"
                        />
                    </div>
                    <div class="text-[13.5px] font-bold leading-tight">{{ recipe.name }}</div>
                    <div class="mt-1.5 text-[11.5px] text-ryh-ink3">{{ recipe.meta || 'No details yet' }}</div>
                </button>
            </div>

            <p v-if="recipes.length === 0" class="text-[13px] text-ryh-ink3">No recipes yet — add your first one above.</p>
        </div>

        <!-- Add / edit -->
        <Dialog :open="open" @update:open="open = $event">
            <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
                <DialogHeader>
                    <DialogTitle class="text-[16px] font-extrabold tracking-tight">{{ dialogTitle }}</DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="flex flex-col gap-1.5">
                        <label for="name" class="ryh-label">Name</label>
                        <input id="name" v-model="form.name" type="text" class="ryh-input" required placeholder="What's cooking?" />
                        <p v-if="form.errors.name" class="text-[12.5px] text-ryh-coral">{{ form.errors.name }}</p>
                    </div>

                    <RecipeDetailFields
                        v-model:duration-label="form.duration_label"
                        v-model:difficulty="form.difficulty"
                        v-model:tags="form.tags"
                        v-model:ingredients="form.ingredients"
                        v-model:tint="form.tint"
                        v-model:is-favourite="form.is_favourite"
                        :tag-options="tagOptions"
                    />

                    <div class="flex flex-col gap-1.5">
                        <label for="description" class="ryh-label">Notes</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="2"
                            maxlength="2000"
                            class="ryh-input h-auto py-2.5"
                            placeholder="Optional"
                        ></textarea>
                        <p v-if="form.errors.description" class="text-[12.5px] text-ryh-coral">{{ form.errors.description }}</p>
                    </div>

                    <div v-if="editing && editing.ingredients.length > 0" class="flex items-center gap-2 border-t border-ryh-line pt-4">
                        <select v-model="selectedListId" aria-label="Shopping list for ingredients" class="ryh-input">
                            <option :value="null">Add ingredients to…</option>
                            <option v-for="list in shoppingLists" :key="list.id" :value="list.id">{{ list.name }}</option>
                        </select>
                        <button
                            type="button"
                            class="ryh-btn w-auto bg-ryh-soft text-ryh-ink"
                            :disabled="selectedListId === null"
                            @click="submitAddIngredients"
                        >
                            Add to list
                        </button>
                    </div>

                    <div
                        v-if="!editing && form.ingredients.some((i) => i.name.trim() !== '')"
                        class="flex flex-col gap-1.5 border-t border-ryh-line pt-4"
                    >
                        <label for="shopping_list_id" class="ryh-label">Also add ingredients to a shopping list</label>
                        <select id="shopping_list_id" v-model="form.shopping_list_id" class="ryh-input">
                            <option :value="null">Don't add</option>
                            <option v-for="list in shoppingLists" :key="list.id" :value="list.id">{{ list.name }}</option>
                        </select>
                    </div>

                    <p v-if="hasUnhandledErrors" class="text-[12.5px] text-ryh-coral">Couldn't save — check the recipe details above.</p>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="ryh-btn w-auto bg-ryh-coral text-white" :disabled="form.processing">
                            {{ editing ? 'Save changes' : 'Add recipe' }}
                        </button>
                        <button type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" @click="open = false">Cancel</button>
                        <button v-if="editing?.can.delete" type="button" class="ryh-btn ml-auto w-auto bg-ryh-soft text-ryh-coral" @click="destroy">
                            Delete
                        </button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog
            :open="confirmOpen"
            message="Delete this recipe? Any planned meals using it will be removed too."
            @update:open="confirmOpen = $event"
            @confirm="confirmDestroy"
        />
    </RyhLayout>
</template>
