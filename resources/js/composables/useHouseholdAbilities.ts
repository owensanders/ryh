import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/** What the signed-in person may do in the household, as its policies answered it. Null outside a household. */
export function useHouseholdAbilities() {
    const page = usePage<SharedData>();

    return computed(() => page.props.household?.can ?? null);
}
