<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import RecipeDetailFields from '@/components/ryh/RecipeDetailFields.vue';
import Button from '@/components/ryh/ui/Button.vue';
import Field from '@/components/ryh/ui/Field.vue';
import Input from '@/components/ryh/ui/Input.vue';
import Select from '@/components/ryh/ui/Select.vue';
import Textarea from '@/components/ryh/ui/Textarea.vue';
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
                <Field label="Recipe" for="recipe_id" :error="form.errors.recipe_id">
                    <Select id="recipe_id" v-model="form.recipe_id" @change="onRecipeSelected">
                        <option :value="null">{{ loadingRecipes ? 'Loading your recipes…' : '— Choose from the library —' }}</option>
                        <option v-for="recipe in recipes" :key="recipe.id" :value="recipe.id">{{ recipe.name }}</option>
                    </Select>
                </Field>

                <Field label="…or add a new recipe" for="new_recipe_name" :error="form.errors.new_recipe_name">
                    <Input id="new_recipe_name" v-model="form.new_recipe_name" type="text" placeholder="Recipe name" @input="onNewRecipeNameInput" />
                </Field>

                <div v-if="showRecipeDetails" class="flex flex-col gap-1.5">
                    <label for="new_recipe_description" class="ryh-label">Notes</label>
                    <Textarea
                        id="new_recipe_description"
                        v-model="form.new_recipe_description"
                        rows="2"
                        maxlength="2000"
                        class="h-auto py-2.5"
                        placeholder="Optional"
                    />
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
                    <Field label="Day" for="planned_on" :error="form.errors.planned_on">
                        <Input id="planned_on" v-model="form.planned_on" type="date" required />
                    </Field>

                    <Field label="Slot" for="slot">
                        <Select id="slot" v-model="form.slot">
                            <option v-for="slot in SLOTS" :key="slot" :value="slot">{{ slot }}</option>
                        </Select>
                    </Field>
                </div>

                <Field label="Cook" for="cook_id">
                    <Select id="cook_id" v-model="form.cook_id">
                        <option :value="null">Unassigned</option>
                        <option v-for="member in members" :key="member.id" :value="member.id">{{ member.name }}</option>
                    </Select>
                </Field>

                <div
                    v-if="showRecipeDetails && form.new_recipe_ingredients.some((i) => i.name.trim() !== '')"
                    class="flex flex-col gap-1.5 border-t border-ryh-line pt-4"
                >
                    <label for="new_recipe_shopping_list_id" class="ryh-label">Also add ingredients to a shopping list</label>
                    <Select id="new_recipe_shopping_list_id" v-model="form.new_recipe_shopping_list_id">
                        <option :value="null">Don't add</option>
                        <option v-for="list in shoppingLists" :key="list.id" :value="list.id">{{ list.name }}</option>
                    </Select>
                </div>

                <p v-if="hasUnhandledErrors" class="text-[12.5px] text-ryh-coral">Couldn't save — check the recipe details above.</p>

                <div class="flex items-center gap-2">
                    <Button class="w-auto" type="submit" :disabled="form.processing">
                        {{ editing ? 'Save changes' : 'Add meal' }}
                    </Button>
                    <Button variant="secondary" class="w-auto" @click="open = false">Cancel</Button>
                    <Button variant="danger" class="ml-auto w-auto" v-if="editing?.can.delete" @click="destroy"> Remove </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog :open="confirmOpen" message="Remove this meal from the plan?" @update:open="confirmOpen = $event" @confirm="confirmDestroy" />
</template>
