<script setup lang="ts">
import { useAiMealPlanForm } from '@/composables/useAiMealPlanForm';
import { Check } from 'lucide-vue-next';

const { step, steps, goTo } = useAiMealPlanForm();
</script>

<template>
    <ol class="flex items-start" aria-label="Plan my week steps">
        <li v-for="(item, index) in steps" :key="item.key" class="relative flex flex-1 flex-col items-center gap-1">
            <span
                v-if="index > 0"
                class="absolute right-1/2 top-[13px] h-0.5 w-full transition-colors"
                :class="index <= step ? 'bg-ryh-ink' : 'bg-ryh-line'"
                aria-hidden="true"
            ></span>
            <button
                type="button"
                class="relative z-10 grid h-7 w-7 place-items-center rounded-full text-[12px] font-extrabold transition"
                :class="
                    index < step
                        ? 'bg-ryh-ink text-ryh-shell'
                        : index === step
                          ? 'border-2 border-ryh-coral bg-ryh-card text-ryh-coral'
                          : 'border border-ryh-line bg-ryh-soft text-ryh-ink3'
                "
                :disabled="index >= step"
                :aria-current="index === step ? 'step' : undefined"
                :aria-label="index < step ? `Back to ${item.label}` : item.label"
                @click="goTo(index)"
            >
                <Check v-if="index < step" class="h-3.5 w-3.5" :stroke-width="3" />
                <template v-else>{{ index + 1 }}</template>
            </button>
            <span class="text-center text-[11px] font-semibold" :class="index === step ? 'text-ryh-ink' : 'text-ryh-ink3'">
                {{ item.label }}
            </span>
        </li>
    </ol>

    <p class="sr-only" aria-live="polite">Step {{ step + 1 }} of {{ steps.length }}: {{ steps[step].label }}</p>
</template>
