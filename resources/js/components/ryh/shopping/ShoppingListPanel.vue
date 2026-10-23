<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import ShoppingItemDialog from '@/components/ryh/shopping/ShoppingItemDialog.vue';
import IconButton from '@/components/ryh/ui/IconButton.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import { useOptimistic } from '@/composables/useOptimistic';
import { tickStyle } from '@/lib/ryh';
import type { ShoppingItemData, ShoppingListData } from '@/types/generated';
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

/** The open list: its items (ticked off at once), adding one, and renaming or deleting the list. */
const props = defineProps<{ active: ShoppingListData | null }>();

const emit = defineEmits<{ rename: [list: ShoppingListData] }>();

const can = useHouseholdAbilities();

// Ticks show at once; the server's list replaces this when it answers.
const { state: activeList, apply: updateActiveList } = useOptimistic(() => props.active);

const itemDialog = ref<InstanceType<typeof ShoppingItemDialog> | null>(null);
const { open: confirming, message: confirmMessage, ask: confirmFirst, confirm } = useConfirm();

const draft = useForm({ name: '', quantity: '' });

function addItem(): void {
    if (!props.active || draft.name.trim() === '') {
        return;
    }

    draft.post(route('shopping.items.store', { slug: props.active.slug }), {
        preserveScroll: true,
        onSuccess: () => draft.reset(),
    });
}

function toggleItem(item: ShoppingItemData): void {
    if (!item.can.toggle) {
        return;
    }

    updateActiveList(
        (list) => {
            const shown = list?.items.find((candidate) => candidate.id === item.id);

            if (shown && list) {
                shown.done = !shown.done;
                list.remaining += shown.done ? -1 : 1;
            }
        },
        (callbacks) => router.patch(route('shopping.items.toggle', { item: item.id }), {}, { preserveScroll: true, ...callbacks }),
    );
}

function destroyActiveList(): void {
    if (!props.active) {
        return;
    }

    const list = props.active;
    confirmFirst(`Delete "${list.name}"? All its items will be removed too.`, () => {
        router.delete(route('shopping.lists.destroy', { list: list.id }));
    });
}
</script>

<template>
    <div v-if="activeList" class="w-full min-w-0 flex-1 rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
        <div class="flex items-center gap-3 border-b border-ryh-line pb-4">
            <div class="flex-1">
                <div class="flex items-center gap-1.5">
                    <h3 class="text-lg font-extrabold tracking-[-0.02em]">{{ activeList.name }}</h3>
                    <IconButton
                        label="Rename list"
                        v-if="activeList.can.update"
                        class="rounded-lg p-1 transition hover:bg-ryh-soft"
                        @click="emit('rename', activeList)"
                    >
                        <Pencil class="h-3.5 w-3.5 text-ryh-ink3" />
                    </IconButton>
                </div>
                <div class="mt-1 text-[13px] text-ryh-ink3">{{ activeList.remaining }} items left</div>
            </div>
            <IconButton
                label="Delete list"
                v-if="activeList.can.delete"
                class="rounded-lg p-1.5 transition hover:bg-ryh-soft"
                @click="destroyActiveList"
            >
                <Trash2 class="h-4 w-4 text-ryh-ink3" />
            </IconButton>
        </div>

        <form v-if="can?.createShoppingItems" class="my-4 flex gap-2" @submit.prevent="addItem">
            <input
                v-model="draft.name"
                placeholder="Add to this list…"
                aria-label="Add to this list"
                class="h-12 min-w-0 flex-1 rounded-[14px] border border-ryh-line bg-ryh-sunk px-4 text-[14.5px] text-ryh-ink placeholder:text-ryh-ink3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ryh-coral"
            />
            <input
                v-model="draft.quantity"
                placeholder="Qty"
                aria-label="Quantity"
                class="h-12 w-16 flex-none rounded-[14px] border border-ryh-line bg-ryh-sunk px-3 text-[14.5px] text-ryh-ink placeholder:text-ryh-ink3 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ryh-coral sm:w-20"
            />
            <button
                type="submit"
                :disabled="draft.processing"
                class="h-12 flex-none rounded-[14px] bg-ryh-coral px-4 text-sm font-bold text-white transition hover:-translate-y-0.5 disabled:opacity-50 sm:px-[22px]"
            >
                Add
            </button>
        </form>
        <p v-if="draft.errors.name" class="-mt-2 mb-3 text-[13px] text-ryh-coral">{{ draft.errors.name }}</p>

        <div class="flex flex-col gap-0.5">
            <div v-for="item in activeList.items" :key="item.id" class="group relative">
                <button
                    type="button"
                    :disabled="!item.can.toggle"
                    role="checkbox"
                    :aria-checked="item.done"
                    class="flex min-h-[46px] w-full items-center gap-3 rounded-[13px] px-2.5 pr-10 text-left transition-colors hover:bg-ryh-soft disabled:cursor-not-allowed"
                    @click="toggleItem(item)"
                >
                    <span
                        aria-hidden="true"
                        class="grid h-[22px] w-[22px] flex-none place-items-center rounded-lg border-[1.5px] text-xs text-[#0E1A2B] transition"
                        :style="tickStyle(item.done)"
                    >
                        {{ item.done ? '✓' : '' }}
                    </span>
                    <span class="flex-1 text-[14.5px]" :class="item.done ? 'text-ryh-ink3 line-through' : 'text-ryh-ink'">
                        {{ item.name }}
                    </span>
                    <span class="font-mono text-[11.5px] text-ryh-ink3">{{ item.quantity }}</span>
                </button>
                <IconButton
                    label="Edit item"
                    v-if="item.can.update"
                    class="absolute right-0.5 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-lg transition [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-hover:opacity-100"
                    @click.stop="itemDialog?.openEdit(item)"
                >
                    <Pencil class="h-3.5 w-3.5 text-ryh-ink3" />
                </IconButton>
            </div>
        </div>

        <p v-if="activeList.items.length === 0" class="py-10 text-center text-sm text-ryh-ink3">This list is empty.</p>
    </div>

    <ShoppingItemDialog ref="itemDialog" />
    <ConfirmDialog :open="confirming" :message="confirmMessage" @update:open="confirming = $event" @confirm="confirm" />
</template>
