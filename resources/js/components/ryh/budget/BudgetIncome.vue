<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import RecurringIcon from '@/components/ryh/RecurringIcon.vue';
import Button from '@/components/ryh/ui/Button.vue';
import Field from '@/components/ryh/ui/Field.vue';
import Input from '@/components/ryh/ui/Input.vue';
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
                <Field label="Name" for="income-label" :error="incomeForm.errors.label">
                    <Input id="income-label" v-model="incomeForm.label" type="text" required placeholder="Salary" />
                </Field>

                <div class="grid grid-cols-2 gap-3">
                    <Field label="Details" for="income-meta">
                        <Input id="income-meta" v-model="incomeForm.meta" type="text" placeholder="Paid 28th" />
                    </Field>

                    <Field label="Amount" for="income-amount" :error="incomeForm.errors.amount">
                        <Input id="income-amount" v-model="incomeForm.amount" type="text" placeholder="£" />
                    </Field>
                </div>

                <label class="flex items-center gap-1.5 text-[12px] text-ryh-ink3">
                    <input v-model="incomeForm.is_recurring" type="checkbox" class="h-3.5 w-3.5" />
                    Repeat this every month
                </label>

                <div class="flex items-center gap-2">
                    <Button class="w-auto" type="submit" :disabled="incomeForm.processing">
                        {{ editingIncome ? 'Save changes' : 'Add income source' }}
                    </Button>
                    <Button variant="secondary" class="w-auto" @click="incomeOpen = false">Cancel</Button>
                    <Button variant="danger" class="ml-auto w-auto" v-if="editingIncome" @click="destroyIncome"> Delete </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog :open="confirming" :message="confirmMessage" @update:open="confirming = $event" @confirm="confirm" />
</template>
