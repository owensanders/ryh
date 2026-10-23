<script setup lang="ts">
import Field from '@/components/ryh/ui/Field.vue';
import IconButton from '@/components/ryh/ui/IconButton.vue';
import Input from '@/components/ryh/ui/Input.vue';
import { tint } from '@/lib/ryh';
import type { TagOption } from '@/types/ryh';
import { X } from 'lucide-vue-next';

defineProps<{ tagOptions: TagOption[] }>();

const TINTS = [0, 1, 2, 3, 4];

const durationLabel = defineModel<string>('durationLabel', { required: true });
const difficulty = defineModel<string>('difficulty', { required: true });
const tags = defineModel<string[]>('tags', { required: true });
const ingredients = defineModel<{ name: string; quantity: string }[]>('ingredients', { required: true });
const tintValue = defineModel<number>('tint', { required: true });
const isFavourite = defineModel<boolean>('isFavourite', { required: true });

function addIngredientRow(): void {
    ingredients.value.push({ name: '', quantity: '' });
}

function removeIngredientRow(index: number): void {
    ingredients.value.splice(index, 1);
}
</script>

<template>
    <div class="grid grid-cols-2 gap-3">
        <Field label="Duration" for="duration_label">
            <Input id="duration_label" v-model="durationLabel" type="text" placeholder="30 min" />
        </Field>

        <Field label="Difficulty" for="difficulty">
            <Input id="difficulty" v-model="difficulty" type="text" placeholder="Easy" />
        </Field>
    </div>

    <div class="flex flex-col gap-1.5">
        <span class="ryh-label">Tags</span>
        <div class="flex flex-wrap gap-x-3 gap-y-1.5">
            <label v-for="option in tagOptions" :key="option.value" class="flex items-center gap-1.5 text-[12.5px] text-ryh-ink2">
                <input v-model="tags" type="checkbox" :value="option.value" class="h-3.5 w-3.5 accent-ryh-coral" />
                {{ option.label }}
            </label>
        </div>
    </div>

    <div class="flex flex-col gap-1.5">
        <span class="ryh-label">Ingredients</span>
        <div v-for="(ingredient, index) in ingredients" :key="index" class="flex items-center gap-2">
            <Input v-model="ingredient.name" type="text" placeholder="Ingredient" :aria-label="`Ingredient ${index + 1}`" />
            <Input v-model="ingredient.quantity" type="text" class="w-24" placeholder="Qty" :aria-label="`Ingredient ${index + 1} quantity`" />
            <IconButton
                label="Remove ingredient"
                class="grid h-7 w-7 flex-none place-items-center rounded-md text-ryh-ink3 hover:bg-ryh-soft hover:text-ryh-coral"
                @click="removeIngredientRow(index)"
            >
                <X class="h-3.5 w-3.5" />
            </IconButton>
        </div>
        <button type="button" class="w-auto self-start text-[12.5px] font-semibold text-ryh-coral hover:opacity-75" @click="addIngredientRow">
            + Add ingredient
        </button>
    </div>

    <div class="flex flex-col gap-1.5">
        <span class="ryh-label">Colour</span>
        <div class="flex gap-2">
            <IconButton
                :label="`Colour ${t + 1}`"
                v-for="t in TINTS"
                :key="t"
                :aria-pressed="tintValue === t"
                class="h-7 w-7 rounded-[9px] transition"
                :class="tintValue === t ? 'ring-2 ring-ryh-ink ring-offset-2 ring-offset-ryh-card' : ''"
                :style="{ background: tint(t) }"
                @click="tintValue = t"
            ></IconButton>
        </div>
    </div>

    <label class="flex items-center gap-2.5 text-[13px] text-ryh-ink2">
        <input v-model="isFavourite" type="checkbox" class="h-4 w-4 accent-ryh-coral" />
        Show in the planner's quick-pick favourites
    </label>
</template>
