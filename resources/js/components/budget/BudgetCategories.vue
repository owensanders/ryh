<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import RecurringIcon from '@/components/RecurringIcon.vue';
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import IconButton from '@/components/ui/IconButton.vue';
import Input from '@/components/ui/Input.vue';
import { useConfirm } from '@/composables/useConfirm';
import type { BudgetCategoryData } from '@/types/generated';
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

/** Where it goes: the month's categories, edited in place, with an add form underneath. */
const props = defineProps<{ month: string; categories: BudgetCategoryData[] }>();

const editingCategoryId = ref<number | null>(null);
const categoryForm = useForm({ label: '', budgeted: '', is_recurring: false as boolean });
const newCategoryForm = useForm({ label: '', budgeted: '', is_recurring: false });
const { open: confirming, message: confirmMessage, ask: confirmFirst, confirm } = useConfirm();

function openEditCategory(category: BudgetCategoryData): void {
    editingCategoryId.value = category.id;
    categoryForm.clearErrors();
    categoryForm.defaults({
        label: category.label,
        budgeted: (category.budgetedPence / 100).toString(),
        is_recurring: category.isRecurring,
    });
    categoryForm.reset();
}

function submitCategory(category: BudgetCategoryData): void {
    categoryForm.patch(route('budget.categories.update', { category: category.id }), {
        preserveScroll: true,
        onSuccess: () => (editingCategoryId.value = null),
    });
}

function destroyCategory(category: BudgetCategoryData): void {
    confirmFirst(`Remove "${category.label}"? Its logged spending will be removed too.`, () => {
        router.delete(route('budget.categories.destroy', { category: category.id }), { preserveScroll: true });
    });
}

function addCategory(): void {
    if (newCategoryForm.label.trim() === '') {
        return;
    }

    newCategoryForm.post(route('budget.categories.store', { month: props.month }), {
        preserveScroll: true,
        onSuccess: () => newCategoryForm.reset(),
    });
}
</script>

<template>
    <Card as="section" class="flex flex-col">
        <div class="mb-2.5 flex items-baseline gap-2.5">
            <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Where it goes</h3>
        </div>

        <div class="flex flex-col gap-1">
            <div
                v-for="category in categories"
                :key="category.id"
                class="flex items-center gap-2.5 rounded-[14px] px-1 py-2.5 transition hover:bg-ryh-soft sm:gap-3.5 sm:px-2"
            >
                <span class="grid h-8 w-8 flex-none place-items-center rounded-[10px] text-sm" :style="{ background: category.colour }">
                    {{ category.icon }}
                </span>

                <div class="min-w-0 flex-1">
                    <template v-if="editingCategoryId === category.id">
                        <div class="flex flex-wrap items-center gap-2">
                            <Input v-model="categoryForm.label" type="text" class="h-8 basis-full text-[13.5px] sm:flex-1 sm:basis-0" />
                            <span class="text-[12.5px] text-ryh-ink3">budget £</span>
                            <Input v-model="categoryForm.budgeted" type="text" class="h-8 w-[80px] text-[12.5px]" />
                        </div>
                        <label class="mt-1.5 flex items-center gap-1.5 text-[12px] text-ryh-ink3">
                            <input v-model="categoryForm.is_recurring" type="checkbox" class="h-3.5 w-3.5" />
                            Repeats every month
                        </label>
                    </template>
                    <template v-else>
                        <div class="flex items-center gap-2">
                            <span class="min-w-0 truncate text-sm font-semibold">{{ category.label }}</span>
                            <RecurringIcon v-if="category.isRecurring" />
                            <span class="ml-auto font-mono text-[12.5px]">{{ category.budgeted }}</span>
                        </div>
                        <div class="mt-1.5 h-[7px] overflow-hidden rounded bg-ryh-sunk">
                            <span
                                class="block h-full rounded transition-[width]"
                                :style="{ width: `${category.shareOfTotal}%`, background: category.colour }"
                            ></span>
                        </div>
                    </template>
                </div>

                <div class="flex flex-none gap-1">
                    <button
                        v-if="editingCategoryId === category.id"
                        type="button"
                        title="Done"
                        class="grid h-8 w-8 place-items-center rounded-[10px] bg-ryh-card text-xs transition hover:bg-ryh-line"
                        @click="submitCategory(category)"
                    >
                        ✓
                    </button>
                    <IconButton
                        label="Edit category"
                        v-else
                        class="grid h-8 w-8 place-items-center rounded-[10px] text-ryh-ink3 transition hover:bg-ryh-line hover:text-ryh-ink"
                        @click="openEditCategory(category)"
                    >
                        <Pencil class="h-3.5 w-3.5" />
                    </IconButton>
                    <IconButton
                        label="Delete category"
                        class="grid h-8 w-8 place-items-center rounded-[10px] text-ryh-ink3 transition hover:bg-ryh-soft hover:text-ryh-coral"
                        @click="destroyCategory(category)"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                    </IconButton>
                </div>
            </div>
        </div>

        <p v-if="categories.length === 0" class="my-2 text-[13px] text-ryh-ink3">
            No categories yet — add your first one below to start budgeting this month.
        </p>

        <div class="mt-2.5 flex flex-wrap gap-2 sm:flex-nowrap">
            <Input
                v-model="newCategoryForm.label"
                type="text"
                class="basis-full sm:flex-1 sm:basis-0"
                placeholder="New category, e.g. Pets"
                aria-label="New category"
                @keydown.enter="addCategory"
            />
            <Input
                v-model="newCategoryForm.budgeted"
                type="text"
                class="flex-1 sm:w-[112px] sm:flex-none"
                placeholder="£ budget"
                aria-label="New category budget"
                @keydown.enter="addCategory"
            />
            <Button class="w-auto flex-none" @click="addCategory">
                <Plus class="h-4 w-4" :stroke-width="2.5" />
                Add
            </Button>
        </div>
        <label class="mt-2 flex items-center gap-1.5 text-[12px] text-ryh-ink3">
            <input v-model="newCategoryForm.is_recurring" type="checkbox" class="h-3.5 w-3.5" />
            Repeat this every month
        </label>
    </Card>

    <ConfirmDialog :open="confirming" :message="confirmMessage" @update:open="confirming = $event" @confirm="confirm" />
</template>
