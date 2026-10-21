import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useHouseholdRole() {
    const page = usePage<SharedData>();
    const user = computed(() => page.props.auth?.user ?? null);
    const role = computed(() => page.props.household?.role ?? null);
    const canManage = computed(() => role.value === 'owner' || role.value === 'adult');
    const isOwner = computed(() => role.value === 'owner');
    const isTeen = computed(() => role.value === 'teen');

    // Teen/Child can only tick off chores assigned to them; Owner/Adult can toggle any.
    function canToggleChore(chore: { assignee: { id: number } | null }): boolean {
        return canManage.value || chore.assignee?.id === user.value?.id;
    }

    return { user, role, canManage, isOwner, isTeen, canToggleChore };
}
