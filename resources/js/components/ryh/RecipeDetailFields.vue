<script setup lang="ts">
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
        <div class="flex flex-col gap-1.5">
            <label for="duration_label" class="ryh-label">Duration</label>
            <input id="duration_label" v-model="durationLabel" type="text" class="ryh-input" placeholder="30 min" />
        </div>

        <div class="flex flex-col gap-1.5">
            <label for="difficulty" class="ryh-label">Difficulty</label>
            <input id="difficulty" v-model="difficulty" type="text" class="ryh-input" placeholder="Easy" />
        </div>
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
            <input v-model="ingredient.name" type="text" class="ryh-input" placeholder="Ingredient" :aria-label="`Ingredient ${index + 1}`" />
            <input
                v-model="ingredient.quantity"
                type="text"
                class="ryh-input w-24"
                placeholder="Qty"
                :aria-label="`Ingredient ${index + 1} quantity`"
            />
            <button
                type="button"
                class="grid h-7 w-7 flex-none place-items-center rounded-md text-ryh-ink3 hover:bg-ryh-soft hover:text-ryh-coral"
                aria-label="Remove ingredient"
                @click="removeIngredientRow(index)"
            >
                <X class="h-3.5 w-3.5" />
            </button>
        </div>
        <button type="button" class="w-auto self-start text-[12.5px] font-semibold text-ryh-coral hover:opacity-75" @click="addIngredientRow">
            + Add ingredient
        </button>
    </div>

    <div class="flex flex-col gap-1.5">
        <span class="ryh-label">Colour</span>
        <div class="flex gap-2">
            <button
                v-for="t in TINTS"
                :key="t"
                type="button"
                :aria-label="`Colour ${t + 1}`"
                :aria-pressed="tintValue === t"
                class="h-7 w-7 rounded-[9px] transition"
                :class="tintValue === t ? 'ring-2 ring-ryh-ink ring-offset-2 ring-offset-ryh-card' : ''"
                :style="{ background: tint(t) }"
                @click="tintValue = t"
            ></button>
        </div>
    </div>

    <label class="flex items-center gap-2.5 text-[13px] text-ryh-ink2">
        <input v-model="isFavourite" type="checkbox" class="h-4 w-4 accent-ryh-coral" />
        Show in the planner's quick-pick favourites
    </label>
</template>
