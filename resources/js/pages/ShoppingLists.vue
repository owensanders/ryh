<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import { tickStyle } from '@/lib/ryh';
import type { ShoppingItemData, ShoppingListData } from '@/types/generated';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineOptions({ layout: RyhShell });

const props = defineProps<{
    lists: ShoppingListData[];
    active: ShoppingListData | null;
}>();

const COLOURS = ['mint', 'lilac', 'sun', 'sky', 'coral'];

const can = useHouseholdAbilities();

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

    router.patch(route('shopping.items.toggle', { item: item.id }), {}, { preserveScroll: true });
}

// List create / rename / delete
const listDialogOpen = ref(false);
const editingList = ref<ShoppingListData | null>(null);
const listDialogTitle = computed(() => (editingList.value ? 'Rename list' : 'New list'));

const listForm = useForm({ name: '', colour: 'mint' });

function openNewList(): void {
    editingList.value = null;
    listForm.clearErrors();
    listForm.defaults({ name: '', colour: 'mint' });
    listForm.reset();
    listDialogOpen.value = true;
}

function openEditList(list: ShoppingListData): void {
    editingList.value = list;
    listForm.clearErrors();
    listForm.defaults({ name: list.name, colour: list.colourKey });
    listForm.reset();
    listDialogOpen.value = true;
}

function submitList(): void {
    const options = { preserveScroll: true, onSuccess: () => (listDialogOpen.value = false) };

    if (editingList.value) {
        listForm.patch(route('shopping.lists.update', { list: editingList.value.id }), options);
    } else {
        listForm.post(route('shopping.lists.store'), options);
    }
}

function destroyActiveList(): void {
    if (!props.active) {
        return;
    }

    const list = props.active;
    requestConfirm(`Delete "${list.name}"? All its items will be removed too.`, () => {
        router.delete(route('shopping.lists.destroy', { list: list.id }));
    });
}

// Item edit / delete
const itemDialogOpen = ref(false);
const editingItem = ref<ShoppingItemData | null>(null);

const itemForm = useForm({ name: '', quantity: '' });

function openEditItem(item: ShoppingItemData): void {
    editingItem.value = item;
    itemForm.clearErrors();
    itemForm.defaults({ name: item.name, quantity: item.quantity ?? '' });
    itemForm.reset();
    itemDialogOpen.value = true;
}

function submitItem(): void {
    if (editingItem.value === null) {
        return;
    }

    itemForm.patch(route('shopping.items.update', { item: editingItem.value.id }), {
        preserveScroll: true,
        onSuccess: () => (itemDialogOpen.value = false),
    });
}

function destroyItem(): void {
    if (editingItem.value === null) {
        return;
    }

    const item = editingItem.value;
    requestConfirm('Delete this item?', () => {
        router.delete(route('shopping.items.destroy', { item: item.id }), {
            preserveScroll: true,
            onSuccess: () => (itemDialogOpen.value = false),
        });
    });
}

// Shared delete-confirmation dialog
const confirmDialogOpen = ref(false);
const confirmMessage = ref('');
let pendingAction: (() => void) | null = null;

function requestConfirm(message: string, action: () => void): void {
    confirmMessage.value = message;
    pendingAction = action;
    confirmDialogOpen.value = true;
}

function confirmDestroy(): void {
    confirmDialogOpen.value = false;
    pendingAction?.();
    pendingAction = null;
}
</script>

<template>
    <RyhLayout title="Shopping Lists" :subtitle="`${lists.length} lists`">
        <div class="flex animate-ryh-rise flex-col items-start gap-5 lg:flex-row">
            <div class="flex w-full flex-none flex-col gap-1.5 lg:w-[232px]">
                <button
                    v-if="can?.createShoppingLists"
                    type="button"
                    class="ryh-btn mb-1 w-auto self-start bg-ryh-coral text-white"
                    @click="openNewList"
                >
                    <Plus class="h-4 w-4" :stroke-width="2.5" />
                    New list
                </button>

                <Link
                    v-for="list in lists"
                    :key="list.id"
                    :href="route('shopping.index', { slug: list.slug })"
                    class="flex min-h-[48px] items-center gap-2.5 rounded-[14px] border px-3.5 text-left transition hover:bg-ryh-card"
                    :class="active?.id === list.id ? 'border-ryh-line bg-ryh-card' : 'border-transparent bg-transparent'"
                >
                    <span class="h-2.5 w-2.5 flex-none rounded-[3px]" :style="{ background: list.colour }"></span>
                    <span class="flex-1 text-sm font-semibold">{{ list.name }}</span>
                    <span class="font-mono text-[11.5px] text-ryh-ink3">{{ list.remaining }}/{{ list.total }}</span>
                </Link>
            </div>

            <div v-if="active" class="w-full min-w-0 flex-1 rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
                <div class="flex items-center gap-3 border-b border-ryh-line pb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-1.5">
                            <h3 class="text-lg font-extrabold tracking-[-0.02em]">{{ active.name }}</h3>
                            <button
                                v-if="active.can.update"
                                type="button"
                                aria-label="Rename list"
                                class="rounded-lg p-1 transition hover:bg-ryh-soft"
                                @click="openEditList(active)"
                            >
                                <Pencil class="h-3.5 w-3.5 text-ryh-ink3" />
                            </button>
                        </div>
                        <div class="mt-1 text-[13px] text-ryh-ink3">{{ active.remaining }} items left</div>
                    </div>
                    <button
                        v-if="active.can.delete"
                        type="button"
                        aria-label="Delete list"
                        class="rounded-lg p-1.5 transition hover:bg-ryh-soft"
                        @click="destroyActiveList"
                    >
                        <Trash2 class="h-4 w-4 text-ryh-ink3" />
                    </button>
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
                    <div v-for="item in active.items" :key="item.id" class="group relative">
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
                        <button
                            v-if="item.can.update"
                            type="button"
                            aria-label="Edit item"
                            class="absolute right-0.5 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-lg transition [@media(hover:hover)]:opacity-0 [@media(hover:hover)]:group-hover:opacity-100"
                            @click.stop="openEditItem(item)"
                        >
                            <Pencil class="h-3.5 w-3.5 text-ryh-ink3" />
                        </button>
                    </div>
                </div>

                <p v-if="active.items.length === 0" class="py-10 text-center text-sm text-ryh-ink3">This list is empty.</p>
            </div>
        </div>

        <!-- Create / rename list -->
        <Dialog :open="listDialogOpen" @update:open="listDialogOpen = $event">
            <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
                <DialogHeader>
                    <DialogTitle class="text-[16px] font-extrabold tracking-tight">{{ listDialogTitle }}</DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submitList">
                    <div class="flex flex-col gap-1.5">
                        <label for="list-name" class="ryh-label">Name</label>
                        <input id="list-name" v-model="listForm.name" type="text" class="ryh-input" required placeholder="e.g. Tesco" />
                        <p v-if="listForm.errors.name" class="text-[12.5px] text-ryh-coral">{{ listForm.errors.name }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <span class="ryh-label">Colour</span>
                        <div class="flex gap-2">
                            <button
                                v-for="colour in COLOURS"
                                :key="colour"
                                type="button"
                                :title="colour"
                                :aria-label="colour"
                                :aria-pressed="listForm.colour === colour"
                                class="h-7 w-7 rounded-[9px] transition"
                                :class="listForm.colour === colour ? 'ring-2 ring-ryh-ink ring-offset-2 ring-offset-ryh-card' : ''"
                                :style="{ background: `var(--ryh-${colour})` }"
                                @click="listForm.colour = colour"
                            ></button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="ryh-btn w-auto bg-ryh-coral text-white" :disabled="listForm.processing">
                            {{ editingList ? 'Save changes' : 'Add list' }}
                        </button>
                        <button type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" @click="listDialogOpen = false">Cancel</button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit item -->
        <Dialog :open="itemDialogOpen" @update:open="itemDialogOpen = $event">
            <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
                <DialogHeader>
                    <DialogTitle class="text-[16px] font-extrabold tracking-tight">Edit item</DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submitItem">
                    <div class="flex flex-col gap-1.5">
                        <label for="item-name" class="ryh-label">Name</label>
                        <input id="item-name" v-model="itemForm.name" type="text" class="ryh-input" required />
                        <p v-if="itemForm.errors.name" class="text-[12.5px] text-ryh-coral">{{ itemForm.errors.name }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="item-quantity" class="ryh-label">Quantity</label>
                        <input id="item-quantity" v-model="itemForm.quantity" type="text" class="ryh-input" placeholder="x1" />
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="ryh-btn w-auto bg-ryh-coral text-white" :disabled="itemForm.processing">Save changes</button>
                        <button type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" @click="itemDialogOpen = false">Cancel</button>
                        <button
                            v-if="editingItem?.can.delete"
                            type="button"
                            class="ryh-btn ml-auto w-auto bg-ryh-soft text-ryh-coral"
                            @click="destroyItem"
                        >
                            Delete
                        </button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog :open="confirmDialogOpen" :message="confirmMessage" @update:open="confirmDialogOpen = $event" @confirm="confirmDestroy" />
    </RyhLayout>
</template>
