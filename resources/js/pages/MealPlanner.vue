<script setup lang="ts">
import AiMealPlanDialog from '@/components/ai-meal-plan/AiMealPlanDialog.vue';
import MealDialog from '@/components/meals/MealDialog.vue';
import MealPlannerWeek from '@/components/meals/MealPlannerWeek.vue';
import RecipeLibraryStrip from '@/components/meals/RecipeLibraryStrip.vue';
import Button from '@/components/ui/Button.vue';
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import { provideMealEditor } from '@/composables/useMealEditor';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import type { AiMealPlanData, MemberData, PlannerDayData, RecipeData, ShoppingListData } from '@/types/generated';
import type { AiPlannerOptions, TagOption } from '@/types/ryh';
import { Sparkles } from 'lucide-vue-next';
import { ref } from 'vue';

defineOptions({ layout: RyhShell });

const props = defineProps<{
    weekOf: string;
    todayDate: string;
    days: PlannerDayData[];
    library: RecipeData[];
    recipeCount: number;
    recipes?: RecipeData[];
    members: MemberData[];
    shoppingLists: ShoppingListData[];
    tagOptions: TagOption[];
    aiConfigured: boolean;
    aiPaused: boolean;
    aiAvailableFrom: string | null;
    aiSuggestedBudget: number | null;
    aiPlan: AiMealPlanData | null;
    aiOptions: AiPlannerOptions;
}>();

const can = useHouseholdAbilities();

// The meal being added or edited, shared with the week and the dialog.
provideMealEditor(props);

const aiOpen = ref(false);
</script>

<template>
    <RyhLayout title="Meal Planner" :subtitle="`Week of ${weekOf}`">
        <div class="flex animate-ryh-rise flex-col gap-4">
            <div v-if="can?.createPlannedMeals" class="flex flex-wrap items-center gap-3">
                <div class="hidden min-w-0 flex-1 text-[13px] text-ryh-ink3 [@media(hover:hover)]:block">
                    Drag any meal card onto another day to reschedule, or click one to edit it.
                </div>
                <div class="min-w-0 flex-1 text-[13px] text-ryh-ink3 [@media(hover:hover)]:hidden">
                    Tap a meal to edit it or move it to another day.
                </div>
                <Button variant="dark" class="ml-auto w-auto flex-none whitespace-nowrap" @click="aiOpen = true">
                    <Sparkles class="mr-1.5 inline h-3.5 w-3.5" :stroke-width="2.5" />
                    Plan my week
                </Button>
            </div>

            <MealPlannerWeek :days="days" />
            <RecipeLibraryStrip :library="library" :recipe-count="recipeCount" />
        </div>

        <MealDialog :members="members" :shopping-lists="shoppingLists" :tag-options="tagOptions" />

        <AiMealPlanDialog
            v-model:open="aiOpen"
            :today-date="todayDate"
            :ai-configured="aiConfigured"
            :ai-paused="aiPaused"
            :ai-available-from="aiAvailableFrom"
            :ai-suggested-budget="aiSuggestedBudget"
            :ai-plan="aiPlan"
            :ai-options="aiOptions"
            :days="days"
            :shopping-lists="shoppingLists"
        />
    </RyhLayout>
</template>
