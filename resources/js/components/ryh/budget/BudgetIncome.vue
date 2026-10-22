<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import RecurringIcon from '@/components/ryh/RecurringIcon.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useConfirm } from '@/composables/useConfirm';
import type { IncomeSourceData } from '@/types/generated';
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

/** Money in: the month's income sources, and adding, editing or removing one. */
const props = defineProps<{ month: string; income: IncomeSourceData[]; incomeTotal: string }>();

const incomeOpen = ref(false);
const editingIncome = ref<IncomeSourceData | null>(null);
const incomeDialogTitle = computed(() => (editingIncome.value ? 'Edit income source' : 'New income source'));

const incomeForm = useForm({ label: '', meta: '', amount: '', is_recurring: false as boolean });
const { open: confirming, message: confirmMessage, ask: confirmFirst, confirm } = useConfirm();

function openNewIncome(): void {
    editingIncome.value = null;
    incomeForm.clearErrors();
    incomeForm.defaults({ label: '', meta: '', amount: '', is_recurring: false });
    incomeForm.reset();
    incomeOpen.value = true;
}

function openEditIncome(income: IncomeSourceData): void {
    editingIncome.value = income;
    incomeForm.clearErrors();
    incomeForm.defaults({
        label: income.label,
        meta: income.meta ?? '',
        amount: (income.amountPence / 100).toString(),
        is_recurring: income.isRecurring,
    });
    incomeForm.reset();
    incomeOpen.value = true;
}

function submitIncome(): void {
    const options = { preserveScroll: true, onSuccess: () => (incomeOpen.value = false) };

    if (editingIncome.value) {
        incomeForm.patch(route('budget.income.update', { income: editingIncome.value.id }), options);
    } else {
        incomeForm.post(route('budget.income.store', { month: props.month }), options);
    }
}

function destroyIncome(): void {
    if (editingIncome.value === null) {
        return;
    }

    const income = editingIncome.value;
    confirmFirst('Remove this income source?', () => {
        router.delete(route('budget.income.destroy', { income: income.id }), {
            preserveScroll: true,
            onSuccess: () => (incomeOpen.value = false),
        });
    });
}
</script>

<template>
    <section class="flex flex-col rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
        <div class="mb-3.5 flex items-baseline gap-2.5">
            <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Money in</h3>
            <span class="ml-auto text-[13px] font-bold">{{ incomeTotal }}</span>
        </div>

        <div class="flex flex-col gap-2">
            <button
                v-for="source in income"
                :key="source.id"
                type="button"
                class="flex items-center gap-3 rounded-[14px] bg-ryh-sunk p-3 text-left transition hover:-translate-y-px"
                @click="openEditIncome(source)"
            >
                <span
                    class="grid h-[30px] w-[30px] flex-none place-items-center rounded-[10px] text-[10.5px] font-extrabold text-[#0E1A2B]"
                    :style="{ background: source.colour }"
                >
                    {{ source.initials }}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[13.5px] font-semibold">{{ source.label }}</span>
                        <RecurringIcon v-if="source.isRecurring" />
                    </div>
                    <div v-if="source.meta" class="mt-0.5 text-[11.5px] text-ryh-ink3">{{ source.meta }}</div>
                </div>
                <span class="font-mono text-[13px]">{{ source.amount }}</span>
            </button>
        </div>

        <button
            type="button"
            class="mt-2.5 min-h-11 rounded-[14px] border border-dashed border-ryh-line text-[13px] text-ryh-ink3 transition hover:bg-ryh-soft hover:text-ryh-ink"
            @click="openNewIncome"
        >
            + Add income source
        </button>
    </section>

    <Dialog :open="incomeOpen" @update:open="incomeOpen = $event">
        <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
            <DialogHeader>
                <DialogTitle class="text-[16px] font-extrabold tracking-tight">{{ incomeDialogTitle }}</DialogTitle>
            </DialogHeader>

            <form class="flex flex-col gap-4" @submit.prevent="submitIncome">
                <div class="flex flex-col gap-1.5">
                    <label for="income-label" class="ryh-label">Name</label>
                    <input id="income-label" v-model="incomeForm.label" type="text" class="ryh-input" required placeholder="Salary" />
                    <p v-if="incomeForm.errors.label" class="text-[12.5px] text-ryh-coral">{{ incomeForm.errors.label }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-1.5">
                        <label for="income-meta" class="ryh-label">Details</label>
                        <input id="income-meta" v-model="incomeForm.meta" type="text" class="ryh-input" placeholder="Paid 28th" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="income-amount" class="ryh-label">Amount</label>
                        <input id="income-amount" v-model="incomeForm.amount" type="text" class="ryh-input" placeholder="£" />
                        <p v-if="incomeForm.errors.amount" class="text-[12.5px] text-ryh-coral">{{ incomeForm.errors.amount }}</p>
                    </div>
                </div>

                <label class="flex items-center gap-1.5 text-[12px] text-ryh-ink3">
                    <input v-model="incomeForm.is_recurring" type="checkbox" class="h-3.5 w-3.5" />
                    Repeat this every month
                </label>

                <div class="flex items-center gap-2">
                    <button type="submit" class="ryh-btn w-auto bg-ryh-coral text-white" :disabled="incomeForm.processing">
                        {{ editingIncome ? 'Save changes' : 'Add income source' }}
                    </button>
                    <button type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" @click="incomeOpen = false">Cancel</button>
                    <button v-if="editingIncome" type="button" class="ryh-btn ml-auto w-auto bg-ryh-soft text-ryh-coral" @click="destroyIncome">
                        Delete
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog :open="confirming" :message="confirmMessage" @update:open="confirming = $event" @confirm="confirm" />
</template>
