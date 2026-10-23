<script setup lang="ts">
import Button from '@/components/ryh/ui/Button.vue';
import IconButton from '@/components/ryh/ui/IconButton.vue';
import Select from '@/components/ryh/ui/Select.vue';
import { useMealPlanSuggestions } from '@/composables/useMealPlanSuggestions';
import { pounds } from '@/lib/ryh';
import { ArrowLeftRight } from 'lucide-vue-next';

const emit = defineEmits<{ close: [] }>();

const {
    source,
    marked,
    targetListId,
    accepting,
    acceptIndex,
    acceptFailures,
    assignment,
    canRegenerate,
    canSwap,
    planHint,
    regenerateLabel,
    estimatedTotal,
    overBudget,
    costSummary,
    acceptFailureMessage,
    shoppingLists,
    toggleMarked,
    regenerate,
    swap,
    accept,
} = useMealPlanSuggestions();
</script>

<template>
    <div class="flex flex-col gap-4">
        <p class="rounded-xl bg-ryh-soft px-3 py-2 text-[12.5px] text-ryh-ink2">{{ planHint }}</p>
        <p v-if="source === 'ai'" role="note" class="rounded-xl border border-ryh-sun px-3 py-2 text-[12.5px] text-ryh-ink2">
            <span class="font-bold text-ryh-ink">Check before you cook.</span> AI suggestions can get things wrong — always check ingredients for
            allergies, intolerances and dietary needs yourself.
        </p>

        <div class="flex flex-col gap-2">
            <div v-for="entry in assignment" :key="entry.day.date" class="rounded-2xl border border-ryh-line bg-ryh-panel p-3">
                <div class="flex items-start gap-1.5">
                    <span class="pt-[3px] text-[12px] font-extrabold">{{ entry.day.dayLabel }}</span>
                    <span class="mr-auto text-[15px] font-extrabold tracking-tight">{{ entry.suggestion.name }}</span>
                    <button
                        v-if="canRegenerate"
                        type="button"
                        class="ml-auto flex-none rounded-md border px-2 py-1 text-[11px] font-semibold transition"
                        :class="
                            marked.includes(entry.position) ? 'border-ryh-coral bg-ryh-coral text-white' : 'border-ryh-line bg-ryh-card text-ryh-ink2'
                        "
                        :aria-pressed="marked.includes(entry.position)"
                        :aria-label="`Replace ${entry.day.dayLabel}'s dinner when regenerating`"
                        :disabled="accepting"
                        @click="toggleMarked(entry.position)"
                    >
                        Replace
                    </button>
                    <IconButton
                        :label="`Swap ${entry.day.dayLabel}'s dinner`"
                        v-if="canSwap"
                        class="grid h-7 w-7 flex-none place-items-center rounded-md text-ryh-ink3 transition hover:bg-ryh-soft hover:text-ryh-ink"
                        :disabled="accepting"
                        @click="swap(entry.position)"
                    >
                        <ArrowLeftRight class="h-3.5 w-3.5" :stroke-width="2.5" />
                    </IconButton>
                </div>
                <p v-if="entry.suggestion.description" class="mt-1 text-[12.5px] text-ryh-ink2">{{ entry.suggestion.description }}</p>
                <div class="mt-1.5 flex flex-wrap gap-1.5">
                    <span v-if="entry.suggestion.durationLabel" class="rounded-md bg-ryh-soft px-1.5 py-[3px] font-mono text-[10.5px] text-ryh-ink2">
                        {{ entry.suggestion.durationLabel }}
                    </span>
                    <span v-if="entry.suggestion.difficulty" class="rounded-md bg-ryh-soft px-1.5 py-[3px] text-[10.5px] font-semibold text-ryh-ink2">
                        {{ entry.suggestion.difficulty }}
                    </span>
                    <span v-if="entry.suggestion.estimatedCost" class="rounded-md bg-ryh-soft px-1.5 py-[3px] font-mono text-[10.5px] text-ryh-ink2">
                        ~{{ pounds(entry.suggestion.estimatedCost) }}
                    </span>
                </div>
            </div>
        </div>

        <p v-if="estimatedTotal > 0" class="text-[12.5px] text-ryh-ink2">
            Estimated
            <span class="font-bold" :class="overBudget ? 'text-ryh-coral' : 'text-ryh-ink'">{{ pounds(estimatedTotal) }}</span>
            {{ costSummary }}
        </p>

        <div v-if="shoppingLists.length > 0" class="flex flex-col gap-1.5 border-t border-ryh-line pt-4">
            <label for="ai_shopping_list" class="ryh-label">Add missing ingredients to</label>
            <Select id="ai_shopping_list" v-model="targetListId">
                <option :value="null">Don't add</option>
                <option v-for="list in shoppingLists" :key="list.id" :value="list.id">{{ list.name }}</option>
            </Select>
        </div>

        <p v-if="acceptFailures.length > 0" class="text-[12.5px] text-ryh-coral">{{ acceptFailureMessage }}</p>

        <div class="flex items-center gap-2">
            <Button class="w-auto" v-if="acceptFailures.length === 0" :disabled="accepting" @click="accept(() => emit('close'))">
                {{ accepting ? `Adding ${acceptIndex + 1} of ${assignment.length}…` : 'Accept this week' }}
            </Button>
            <Button variant="secondary" class="w-auto" v-if="canRegenerate" :disabled="accepting" @click="regenerate">
                {{ regenerateLabel }}
            </Button>
            <Button variant="secondary" class="ml-auto w-auto" :disabled="accepting" @click="emit('close')">Close</Button>
        </div>
    </div>
</template>
