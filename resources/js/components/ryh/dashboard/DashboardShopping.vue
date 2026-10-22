<script setup lang="ts">
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import { useOptimistic } from '@/composables/useOptimistic';
import { tickStyle } from '@/lib/ryh';
import type { ShoppingItemData, ShoppingListData } from '@/types/generated';
import { Link, router, useForm } from '@inertiajs/vue3';

/** The first shopping list's next few items, ticked off at once, and adding one. */
const props = defineProps<{ list: ShoppingListData | null }>();

const can = useHouseholdAbilities();

// Ticks show at once; the server's list replaces this when it answers.
const { state: shown, apply: updateShown } = useOptimistic(() => props.list);

const draft = useForm({ name: '' });

function addItem(): void {
    const slug = props.list?.slug;

    if (!slug || draft.name.trim() === '') {
        return;
    }

    draft.post(route('shopping.items.store', { slug }), {
        preserveScroll: true,
        onSuccess: () => draft.reset(),
    });
}

function toggleItem(item: ShoppingItemData): void {
    if (!item.can.toggle) {
        return;
    }

    updateShown(
        (list) => {
            const ticked = list?.items.find((candidate) => candidate.id === item.id);

            if (ticked && list) {
                ticked.done = !ticked.done;
                list.remaining += ticked.done ? -1 : 1;
            }
        },
        (callbacks) => router.patch(route('shopping.items.toggle', { item: item.id }), {}, { preserveScroll: true, ...callbacks }),
    );
}
</script>

<template>
    <section
        aria-label="Shopping"
        class="flex flex-col rounded-[22px] border border-ryh-line bg-ryh-card p-4 transition hover:-translate-y-0.5 hover:shadow-ryh sm:p-[22px]"
    >
        <template v-if="shown">
            <div class="mb-2.5 flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">{{ shown.name }} list</h3>
                <span class="text-xs text-ryh-ink3">{{ shown.remaining }} left</span>
                <Link :href="route('shopping.index')" class="ml-auto whitespace-nowrap text-[13px] font-semibold text-ryh-coral hover:opacity-75">
                    All lists →
                </Link>
            </div>

            <div class="flex flex-col gap-px">
                <button
                    v-for="item in shown.items.slice(0, 5)"
                    :key="item.id"
                    type="button"
                    :disabled="!item.can.toggle"
                    class="flex min-h-[44px] w-full items-center gap-3 rounded-[11px] px-2 text-left transition-colors hover:bg-ryh-soft disabled:cursor-not-allowed"
                    @click="toggleItem(item)"
                >
                    <span
                        class="grid h-[21px] w-[21px] flex-none place-items-center rounded-[7px] border-[1.5px] text-[11px] text-[#0E1A2B] transition"
                        :style="tickStyle(item.done)"
                    >
                        {{ item.done ? '✓' : '' }}
                    </span>
                    <span class="flex-1 text-sm" :class="item.done ? 'text-ryh-ink3 line-through' : 'text-ryh-ink'">
                        {{ item.name }}
                    </span>
                    <span class="font-mono text-[11.5px] text-ryh-ink3">{{ item.quantity }}</span>
                </button>
            </div>

            <form v-if="can?.createShoppingItems" class="mt-auto flex gap-2 pt-3" @submit.prevent="addItem">
                <input
                    v-model="draft.name"
                    placeholder="Add an item…"
                    aria-label="Add an item"
                    class="h-11 flex-1 rounded-[13px] border border-ryh-line bg-ryh-sunk px-3.5 text-sm text-ryh-ink placeholder:text-ryh-ink3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ryh-coral"
                />
                <button
                    type="submit"
                    :disabled="draft.processing"
                    class="h-11 w-11 rounded-[13px] bg-ryh-coral text-xl text-white transition hover:scale-105 disabled:opacity-50"
                >
                    +
                </button>
            </form>
        </template>
        <template v-else>
            <div class="mb-2.5 flex flex-wrap items-baseline gap-x-2.5 gap-y-1">
                <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Shopping list</h3>
                <Link :href="route('shopping.index')" class="ml-auto whitespace-nowrap text-[13px] font-semibold text-ryh-coral hover:opacity-75">
                    All lists →
                </Link>
            </div>
            <p class="py-10 text-center text-sm text-ryh-ink3">No shopping list yet.</p>
        </template>
    </section>
</template>
