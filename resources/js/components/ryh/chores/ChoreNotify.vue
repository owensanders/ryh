<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Bell } from 'lucide-vue-next';
import { computed, onUnmounted, ref } from 'vue';

/** Reminds everyone of their outstanding chores, then waits out a cooldown. */
const props = defineProps<{ availableAt: string | null }>();

const now = ref(new Date());
const nowTimer = setInterval(() => (now.value = new Date()), 30_000);
onUnmounted(() => clearInterval(nowTimer));

const notifyChoresReady = computed(() => props.availableAt === null || new Date(props.availableAt) <= now.value);

const notifyChoresCooldownLabel = computed(() => {
    if (notifyChoresReady.value || props.availableAt === null) {
        return null;
    }

    const minutes = Math.max(1, Math.ceil((new Date(props.availableAt).getTime() - now.value.getTime()) / 60_000));

    return `Available again in ${minutes}m`;
});

function notifyChores(): void {
    if (!notifyChoresReady.value) {
        return;
    }

    router.post(route('chores.notify'), {}, { preserveScroll: true, only: ['notifyChoresAvailableAt'] });
}
</script>

<template>
    <div class="flex items-center justify-end gap-2.5">
        <span v-if="notifyChoresCooldownLabel" class="text-[12px] text-ryh-ink3">{{ notifyChoresCooldownLabel }}</span>
        <button
            type="button"
            :disabled="!notifyChoresReady"
            class="ryh-btn w-auto bg-ryh-soft text-ryh-ink disabled:cursor-not-allowed disabled:opacity-50"
            @click="notifyChores"
        >
            <Bell class="h-4 w-4" :stroke-width="2.5" />
            Notify Chores
        </button>
    </div>
</template>
