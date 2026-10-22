<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import type { HouseData } from '@/types/generated';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

/** Where the household lives: its days, and so its calendar and meal planner, turn over at that midnight. */
const props = defineProps<{ house: HouseData }>();

const form = useForm({ timezone: props.house.timezone });

// The browser's own list, with the saved one kept in case this browser doesn't know it.
const timezones = computed(() => {
    const known = typeof Intl.supportedValuesOf === 'function' ? Intl.supportedValuesOf('timeZone') : [];

    return known.includes(props.house.timezone) ? known : [props.house.timezone, ...known];
});

function save(): void {
    form.patch(route('house.timezone'), { preserveScroll: true });
}
</script>

<template>
    <section class="rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Time zone</h3>
        <p class="mt-1 text-[12.5px] text-ryh-ink3">Your calendar and meal plan turn over at midnight here.</p>

        <form class="mt-3 flex gap-2" @submit.prevent="save">
            <select v-model="form.timezone" aria-label="Time zone" class="ryh-input min-w-0 flex-1">
                <option v-for="timezone in timezones" :key="timezone" :value="timezone">{{ timezone.replaceAll('_', ' ') }}</option>
            </select>
            <button type="submit" class="ryh-btn w-auto flex-none bg-ryh-soft text-ryh-ink" :disabled="form.processing || !form.isDirty">Save</button>
        </form>
        <InputError :message="form.errors.timezone" />
    </section>
</template>
