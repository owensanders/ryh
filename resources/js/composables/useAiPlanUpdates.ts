import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { useEcho } from '@laravel/echo-vue';
import { onBeforeUnmount, watch, type Ref } from 'vue';

const POLL_MS = 5000;
const SEEN_KEY = 'ryh:ai-plans-seen';

/**
 * Keeps the page's aiPlan prop fresh while a plan is being written by its
 * queued job. The job broadcasts when it finishes; a poll every few seconds
 * covers a broadcast that never arrives.
 */
export function useAiPlanUpdates(waiting: Ref<boolean>): void {
    const page = usePage<SharedData>();
    const refresh = (): void => router.reload({ only: ['aiPlan'] });

    const userId = page.props.auth.user?.id;
    if (userId !== undefined) {
        useEcho(`App.Models.User.${userId}`, '.AiMealPlanFinished', refresh);
    }

    let timer: ReturnType<typeof setInterval> | undefined;

    watch(
        waiting,
        (isWaiting) => {
            clearInterval(timer);

            if (isWaiting) {
                timer = setInterval(refresh, POLL_MS);
            }
        },
        { immediate: true },
    );

    onBeforeUnmount(() => clearInterval(timer));
}

/** Plans whose results this browser has already shown, so a finished plan only opens the dialog on itself once. */
export function hasSeenPlan(id: number): boolean {
    try {
        return (JSON.parse(localStorage.getItem(SEEN_KEY) ?? '[]') as number[]).includes(id);
    } catch {
        return false;
    }
}

export function markPlanSeen(id: number): void {
    try {
        const seen = (JSON.parse(localStorage.getItem(SEEN_KEY) ?? '[]') as number[]).filter((seenId) => seenId !== id);
        localStorage.setItem(SEEN_KEY, JSON.stringify([...seen, id].slice(-20)));
    } catch {
        // No storage (private mode): the dialog just won't reopen on it.
    }
}
