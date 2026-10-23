<script setup lang="ts">
import { tint } from '@/lib/ryh';
import type { RecipeData } from '@/types/generated';
import { Link } from '@inertiajs/vue3';

/** The favourites from the recipe library, under the week. */
defineProps<{ library: RecipeData[]; recipeCount: number }>();
</script>

<template>
    <div class="rounded-[22px] border border-ryh-line bg-ryh-panel p-4 sm:p-[22px]">
        <div class="mb-3.5 flex items-baseline gap-2.5">
            <h3 class="text-[15px] font-extrabold">Recipe library</h3>
            <span class="text-xs text-ryh-ink3">Favourites · {{ recipeCount }} saved</span>
            <Link :href="route('recipes.index')" class="ml-auto text-[12.5px] font-semibold text-ryh-coral hover:opacity-75"> Manage recipes </Link>
        </div>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
            <div v-for="recipe in library" :key="recipe.id" class="rounded-2xl border border-ryh-line bg-ryh-card p-3.5">
                <div class="mb-2.5 h-[58px] rounded-xl" :style="{ background: tint(recipe.tint) }"></div>
                <div class="text-[13.5px] font-bold leading-tight">{{ recipe.name }}</div>
                <div class="mt-1.5 text-[11.5px] text-ryh-ink3">{{ recipe.meta }}</div>
            </div>
        </div>
    </div>
</template>
