<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import RecipeDetailFields from '@/components/ryh/RecipeDetailFields.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useMealEditor } from '@/composables/useMealEditor';
import type { MemberData, ShoppingListData } from '@/types/generated';
import type { TagOption } from '@/types/ryh';

/** Adding or editing one planned meal. */
defineProps<{ members: MemberData[]; shoppingLists: ShoppingListData[]; tagOptions: TagOption[] }>();

const SLOTS = ['breakfast', 'lunch', 'dinner'];

const {
    open,
    editing,
    confirmOpen,
    form,
    recipes,
    loadingRecipes,
    showRecipeDetails,
    hasUnhandledErrors,
    onRecipeSelected,
    onNewRecipeNameInput,
    submit,
    destroy,
    confirmDestroy,
} = useMealEditor();
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
            <DialogHeader>
                <DialogTitle class="text-[16px] font-extrabold tracking-tight">{{ editing ? 'Edit meal' : 'New meal' }}</DialogTitle>
            </DialogHeader>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <div class="flex flex-col gap-1.5">
                    <label for="recipe_id" class="ryh-label">Recipe</label>
                    <select id="recipe_id" v-model="form.recipe_id" class="ryh-input" @change="onRecipeSelected">
                        <option :value="null">{{ loadingRecipes ? 'Loading your recipes…' : '— Choose from the library —' }}</option>
                        <option v-for="recipe in recipes" :key="recipe.id" :value="recipe.id">{{ recipe.name }}</option>
                    </select>
                    <p v-if="form.errors.recipe_id" class="text-[12.5px] text-ryh-coral">{{ form.errors.recipe_id }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="new_recipe_name" class="ryh-label">…or add a new recipe</label>
                    <input
                        id="new_recipe_name"
                        v-model="form.new_recipe_name"
                        type="text"
                        class="ryh-input"
                        placeholder="Recipe name"
                        @input="onNewRecipeNameInput"
                    />
                    <p v-if="form.errors.new_recipe_name" class="text-[12.5px] text-ryh-coral">{{ form.errors.new_recipe_name }}</p>
                </div>

                <div v-if="showRecipeDetails" class="flex flex-col gap-1.5">
                    <label for="new_recipe_description" class="ryh-label">Notes</label>
                    <textarea
                        id="new_recipe_description"
                        v-model="form.new_recipe_description"
                        rows="2"
                        maxlength="2000"
                        class="ryh-input h-auto py-2.5"
                        placeholder="Optional"
                    ></textarea>
                    <p v-if="form.errors.new_recipe_description" class="text-[12.5px] text-ryh-coral">
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
                        <label for="planned_on" class="ryh-label">Day</label>
                        <input id="planned_on" v-model="form.planned_on" type="date" class="ryh-input" required />
                        <p v-if="form.errors.planned_on" class="text-[12.5px] text-ryh-coral">{{ form.errors.planned_on }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="slot" class="ryh-label">Slot</label>
                        <select id="slot" v-model="form.slot" class="ryh-input">
                            <option v-for="slot in SLOTS" :key="slot" :value="slot">{{ slot }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label for="cook_id" class="ryh-label">Cook</label>
                    <select id="cook_id" v-model="form.cook_id" class="ryh-input">
                        <option :value="null">Unassigned</option>
                        <option v-for="member in members" :key="member.id" :value="member.id">{{ member.name }}</option>
                    </select>
                </div>

                <div
                    v-if="showRecipeDetails && form.new_recipe_ingredients.some((i) => i.name.trim() !== '')"
                    class="flex flex-col gap-1.5 border-t border-ryh-line pt-4"
                >
                    <label for="new_recipe_shopping_list_id" class="ryh-label">Also add ingredients to a shopping list</label>
                    <select id="new_recipe_shopping_list_id" v-model="form.new_recipe_shopping_list_id" class="ryh-input">
                        <option :value="null">Don't add</option>
                        <option v-for="list in shoppingLists" :key="list.id" :value="list.id">{{ list.name }}</option>
                    </select>
                </div>

                <p v-if="hasUnhandledErrors" class="text-[12.5px] text-ryh-coral">Couldn't save — check the recipe details above.</p>

                <div class="flex items-center gap-2">
                    <button type="submit" class="ryh-btn w-auto bg-ryh-coral text-white" :disabled="form.processing">
                        {{ editing ? 'Save changes' : 'Add meal' }}
                    </button>
                    <button type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" @click="open = false">Cancel</button>
                    <button v-if="editing?.can.delete" type="button" class="ryh-btn ml-auto w-auto bg-ryh-soft text-ryh-coral" @click="destroy">
                        Remove
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog :open="confirmOpen" message="Remove this meal from the plan?" @update:open="confirmOpen = $event" @confirm="confirmDestroy" />
</template>
