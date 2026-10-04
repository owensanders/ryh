<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import HouseHubLayout from '@/layouts/HouseHubLayout.vue';
import { donut } from '@/lib/househub';
import type { BudgetCategory, BudgetMonthSummary, IncomeSource } from '@/types/househub';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Repeat, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    month: string;
    monthLabel: string;
    previousMonth: string;
    nextMonth: string;
    daysLeftLabel: string;
    leftToSpend: string;
    budgetedLabel: string;
    perDay: string;
    budgetedPence: number;
    incomeTotalPence: number;
    income: IncomeSource[];
    incomeTotal: string;
    categories: BudgetCategory[];
    history: BudgetMonthSummary[];
}>();

function pct(part: number, whole: number): number {
    return whole > 0 ? Math.min(100, Math.round((part / whole) * 100)) : 0;
}

const assignedPct = computed(() => pct(props.budgetedPence, props.incomeTotalPence));
const isCurrentMonth = computed(() => props.month === new Date().toISOString().slice(0, 7));

// Income vs. expenses donut
const incomePct = computed(() => pct(props.incomeTotalPence, props.incomeTotalPence + props.budgetedPence));
const incomeExpenseDonut = computed(() => donut(incomePct.value, 'var(--hh-mint)', 'var(--hh-coral)'));

// Last 3 months bar chart
const maxHistoryPence = computed(() => Math.max(1, ...props.history.map((entry) => entry.totalPence)));
function historyBarHeight(entry: BudgetMonthSummary): number {
    return Math.round((entry.totalPence / maxHistoryPence.value) * 100);
}

// Income sources — add / edit / delete
const incomeOpen = ref(false);
const editingIncome = ref<IncomeSource | null>(null);
const incomeDialogTitle = computed(() => (editingIncome.value ? 'Edit income source' : 'New income source'));

const incomeForm = useForm({ label: '', meta: '', amount: '', is_recurring: false as boolean });

function openNewIncome(): void {
    editingIncome.value = null;
    incomeForm.clearErrors();
    incomeForm.defaults({ label: '', meta: '', amount: '', is_recurring: false });
    incomeForm.reset();
    incomeOpen.value = true;
}

function openEditIncome(income: IncomeSource): void {
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
    requestConfirm('Remove this income source?', () => {
        router.delete(route('budget.income.destroy', { income: income.id }), {
            preserveScroll: true,
            onSuccess: () => (incomeOpen.value = false),
        });
    });
}

// Categories — inline edit / delete, plus an add form at the bottom of the card
const editingCategoryId = ref<number | null>(null);
const categoryForm = useForm({ label: '', budgeted: '', is_recurring: false as boolean });

function openEditCategory(category: BudgetCategory): void {
    editingCategoryId.value = category.id;
    categoryForm.clearErrors();
    categoryForm.defaults({
        label: category.label,
        budgeted: (category.budgetedPence / 100).toString(),
        is_recurring: category.isRecurring,
    });
    categoryForm.reset();
}

function submitCategory(category: BudgetCategory): void {
    categoryForm.patch(route('budget.categories.update', { category: category.id }), {
        preserveScroll: true,
        onSuccess: () => (editingCategoryId.value = null),
    });
}

function destroyCategory(category: BudgetCategory): void {
    requestConfirm(`Remove "${category.label}"? Its logged spending will be removed too.`, () => {
        router.delete(route('budget.categories.destroy', { category: category.id }), { preserveScroll: true });
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

const newCategoryForm = useForm({ label: '', budgeted: '', is_recurring: false });

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
    <HouseHubLayout title="Budget" :subtitle="monthLabel">
        <div class="flex animate-hh-rise flex-col gap-4">
            <!-- Hero -->
            <section
                class="relative flex flex-col items-stretch gap-5 overflow-hidden rounded-[22px] p-5 text-hh-onhero sm:p-6 lg:flex-row"
                style="background: linear-gradient(118deg, var(--hh-hero1) 0%, var(--hh-hero2) 100%)"
            >
                <div class="absolute -top-24 right-52 h-72 w-72 rounded-full bg-hh-coral opacity-20 blur-3xl"></div>

                <div class="relative min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2.5">
                        <Link
                            :href="route('budget.index', { month: previousMonth })"
                            class="grid h-8 w-8 place-items-center rounded-[10px] border border-hh-heroline bg-hh-herofilm text-[13px]"
                        >
                            ‹
                        </Link>
                        <span class="text-[13px] font-bold uppercase tracking-[0.08em] text-hh-onhero2">{{ monthLabel }}</span>
                        <Link
                            :href="route('budget.index', { month: nextMonth })"
                            class="grid h-8 w-8 place-items-center rounded-[10px] border border-hh-heroline bg-hh-herofilm text-[13px]"
                        >
                            ›
                        </Link>
                        <Link v-if="!isCurrentMonth" :href="route('budget.index')" class="ml-1.5 text-[12.5px] text-hh-onhero2"> This month </Link>
                        <span class="ml-1.5 text-xs text-hh-onhero2">{{ daysLeftLabel }}</span>
                    </div>

                    <div class="mt-3.5 text-[15px] text-hh-onhero2">Left to spend this month</div>
                    <div class="mt-1 text-[44px] font-black leading-none tracking-[-0.04em] sm:text-[52px]">{{ leftToSpend }}</div>

                    <div class="mt-3.5 flex h-3 max-w-[560px] overflow-hidden rounded-lg bg-hh-herofilm">
                        <span class="bg-hh-coral" :style="{ width: `${assignedPct}%` }"></span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-x-5 gap-y-1 text-[13px] text-hh-onhero2">
                        <span
                            ><b class="text-hh-onhero">{{ budgetedLabel }}</b> assigned</span
                        >
                        <span
                            ><b class="text-hh-onhero">{{ perDay }}</b> a day to stay on track</span
                        >
                    </div>
                </div>

                <div class="relative grid w-full flex-none grid-cols-2 gap-2.5 lg:flex lg:w-[230px] lg:flex-col">
                    <div class="flex-1 rounded-2xl border border-hh-heroline bg-hh-herofilm p-4">
                        <div class="text-[11px] font-bold uppercase tracking-[0.09em] text-hh-onhero2">Money in</div>
                        <div class="mt-1.5 text-[22px] font-extrabold tracking-[-0.03em]">{{ incomeTotal }}</div>
                    </div>
                    <div class="flex-1 rounded-2xl border border-hh-heroline bg-hh-herofilm p-4">
                        <div class="text-[11px] font-bold uppercase tracking-[0.09em] text-hh-onhero2">Money out</div>
                        <div class="mt-1.5 text-[22px] font-extrabold tracking-[-0.03em]">{{ budgetedLabel }}</div>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Income vs. expenses -->
                <section class="flex flex-col rounded-[22px] border border-hh-line bg-hh-card p-4 sm:p-[22px]">
                    <h3 class="mb-3.5 text-[15px] font-extrabold tracking-[-0.01em]">Income vs. expenses</h3>

                    <div class="flex flex-col gap-5 min-[420px]:flex-row min-[420px]:items-center">
                        <div class="grid h-[120px] w-[120px] flex-none place-items-center rounded-full" :style="{ background: incomeExpenseDonut }">
                            <div class="flex h-[92px] w-[92px] flex-col items-center justify-center rounded-full bg-hh-card text-center">
                                <span class="text-[11px] text-hh-ink3">Left over</span>
                                <span class="text-[17px] font-extrabold tracking-[-0.02em]">{{ leftToSpend }}</span>
                            </div>
                        </div>

                        <div class="flex w-full flex-1 flex-col gap-2.5">
                            <div class="flex items-center gap-2.5">
                                <span class="h-2.5 w-2.5 flex-none rounded-[3px]" style="background: var(--hh-mint)"></span>
                                <span class="flex-1 text-[13px] font-medium">Income</span>
                                <span class="font-mono text-[13px]">{{ incomeTotal }}</span>
                            </div>
                            <div class="flex items-center gap-2.5">
                                <span class="h-2.5 w-2.5 flex-none rounded-[3px]" style="background: var(--hh-coral)"></span>
                                <span class="flex-1 text-[13px] font-medium">Assigned</span>
                                <span class="font-mono text-[13px]">{{ budgetedLabel }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Last 3 months -->
                <section class="flex flex-col rounded-[22px] border border-hh-line bg-hh-card p-4 sm:p-[22px]">
                    <h3 class="mb-3.5 text-[15px] font-extrabold tracking-[-0.01em]">Last 3 months</h3>

                    <div class="flex h-[120px] flex-1 items-end justify-around gap-4">
                        <div v-for="entry in history" :key="entry.month" class="flex h-full flex-1 flex-col items-center justify-end gap-1.5">
                            <span class="font-mono text-[12px] text-hh-ink3">{{ entry.total }}</span>
                            <div
                                class="w-full max-w-10 rounded-t-md transition-[height]"
                                :class="entry.month === month ? 'bg-hh-coral' : 'bg-hh-sunk'"
                                :style="{ height: `${historyBarHeight(entry)}%` }"
                            ></div>
                            <span class="text-[12px] font-semibold text-hh-ink3">{{ entry.monthLabel }}</span>
                        </div>
                    </div>
                </section>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(360px,1fr)_minmax(460px,1.4fr)]">
                <!-- Money in -->
                <section class="flex flex-col rounded-[22px] border border-hh-line bg-hh-card p-4 sm:p-[22px]">
                    <div class="mb-3.5 flex items-baseline gap-2.5">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Money in</h3>
                        <span class="ml-auto text-[13px] font-bold">{{ incomeTotal }}</span>
                    </div>

                    <div class="flex flex-col gap-2">
                        <button
                            v-for="source in income"
                            :key="source.id"
                            type="button"
                            class="flex items-center gap-3 rounded-[14px] bg-hh-sunk p-3 text-left transition hover:-translate-y-px"
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
                                    <Repeat v-if="source.isRecurring" title="Repeats every month" class="h-3 w-3 flex-none text-hh-ink3" />
                                </div>
                                <div v-if="source.meta" class="mt-0.5 text-[11.5px] text-hh-ink3">{{ source.meta }}</div>
                            </div>
                            <span class="font-mono text-[13px]">{{ source.amount }}</span>
                        </button>
                    </div>

                    <button
                        type="button"
                        class="mt-2.5 min-h-11 rounded-[14px] border border-dashed border-hh-line text-[13px] text-hh-ink3 transition hover:bg-hh-soft hover:text-hh-ink"
                        @click="openNewIncome"
                    >
                        + Add income source
                    </button>
                </section>

                <!-- Where it goes -->
                <section class="flex flex-col rounded-[22px] border border-hh-line bg-hh-card p-4 sm:p-[22px]">
                    <div class="mb-2.5 flex items-baseline gap-2.5">
                        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Where it goes</h3>
                    </div>

                    <div class="flex flex-col gap-1">
                        <div
                            v-for="category in categories"
                            :key="category.id"
                            class="flex items-center gap-2.5 rounded-[14px] px-1 py-2.5 transition hover:bg-hh-soft sm:gap-3.5 sm:px-2"
                        >
                            <span class="grid h-8 w-8 flex-none place-items-center rounded-[10px] text-sm" :style="{ background: category.colour }">
                                {{ category.icon }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <template v-if="editingCategoryId === category.id">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <input
                                            v-model="categoryForm.label"
                                            type="text"
                                            class="hh-input h-8 basis-full text-[13.5px] sm:flex-1 sm:basis-0"
                                        />
                                        <span class="text-[12.5px] text-hh-ink3">budget £</span>
                                        <input v-model="categoryForm.budgeted" type="text" class="hh-input h-8 w-[80px] text-[12.5px]" />
                                    </div>
                                    <label class="mt-1.5 flex items-center gap-1.5 text-[12px] text-hh-ink3">
                                        <input v-model="categoryForm.is_recurring" type="checkbox" class="h-3.5 w-3.5" />
                                        Repeats every month
                                    </label>
                                </template>
                                <template v-else>
                                    <div class="flex items-center gap-2">
                                        <span class="min-w-0 truncate text-sm font-semibold">{{ category.label }}</span>
                                        <Repeat v-if="category.isRecurring" title="Repeats every month" class="h-3 w-3 flex-none text-hh-ink3" />
                                        <span class="ml-auto font-mono text-[12.5px]">{{ category.budgeted }}</span>
                                    </div>
                                    <div class="mt-1.5 h-[7px] overflow-hidden rounded bg-hh-sunk">
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
                                    class="grid h-8 w-8 place-items-center rounded-[10px] bg-hh-card text-xs transition hover:bg-hh-line"
                                    @click="submitCategory(category)"
                                >
                                    ✓
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    title="Edit category"
                                    class="grid h-8 w-8 place-items-center rounded-[10px] text-hh-ink3 transition hover:bg-hh-line hover:text-hh-ink"
                                    @click="openEditCategory(category)"
                                >
                                    <Pencil class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    type="button"
                                    title="Delete category"
                                    class="grid h-8 w-8 place-items-center rounded-[10px] text-hh-ink3 transition hover:bg-hh-soft hover:text-hh-coral"
                                    @click="destroyCategory(category)"
                                >
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <p v-if="categories.length === 0" class="my-2 text-[13px] text-hh-ink3">
                        No categories yet — add your first one below to start budgeting this month.
                    </p>

                    <div class="mt-2.5 flex flex-wrap gap-2 sm:flex-nowrap">
                        <input
                            v-model="newCategoryForm.label"
                            type="text"
                            class="hh-input basis-full sm:flex-1 sm:basis-0"
                            placeholder="New category, e.g. Pets"
                            @keydown.enter="addCategory"
                        />
                        <input
                            v-model="newCategoryForm.budgeted"
                            type="text"
                            class="hh-input flex-1 sm:w-[112px] sm:flex-none"
                            placeholder="£ budget"
                            @keydown.enter="addCategory"
                        />
                        <button type="button" class="hh-btn w-auto flex-none bg-hh-coral text-white" @click="addCategory">
                            <Plus class="h-4 w-4" :stroke-width="2.5" />
                            Add
                        </button>
                    </div>
                    <label class="mt-2 flex items-center gap-1.5 text-[12px] text-hh-ink3">
                        <input v-model="newCategoryForm.is_recurring" type="checkbox" class="h-3.5 w-3.5" />
                        Repeat this every month
                    </label>
                </section>
            </div>
        </div>

        <!-- Add / edit income source -->
        <Dialog :open="incomeOpen" @update:open="incomeOpen = $event">
            <DialogContent class="rounded-[22px] border-hh-line bg-hh-card text-hh-ink sm:rounded-[22px]">
                <DialogHeader>
                    <DialogTitle class="text-[16px] font-extrabold tracking-tight">{{ incomeDialogTitle }}</DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submitIncome">
                    <div class="flex flex-col gap-1.5">
                        <label for="income-label" class="hh-label">Name</label>
                        <input id="income-label" v-model="incomeForm.label" type="text" class="hh-input" required placeholder="Salary" />
                        <p v-if="incomeForm.errors.label" class="text-[12.5px] text-hh-coral">{{ incomeForm.errors.label }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex flex-col gap-1.5">
                            <label for="income-meta" class="hh-label">Details</label>
                            <input id="income-meta" v-model="incomeForm.meta" type="text" class="hh-input" placeholder="Paid 28th" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label for="income-amount" class="hh-label">Amount</label>
                            <input id="income-amount" v-model="incomeForm.amount" type="text" class="hh-input" placeholder="£" />
                            <p v-if="incomeForm.errors.amount" class="text-[12.5px] text-hh-coral">{{ incomeForm.errors.amount }}</p>
                        </div>
                    </div>

                    <label class="flex items-center gap-1.5 text-[12px] text-hh-ink3">
                        <input v-model="incomeForm.is_recurring" type="checkbox" class="h-3.5 w-3.5" />
                        Repeat this every month
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="hh-btn w-auto bg-hh-coral text-white" :disabled="incomeForm.processing">
                            {{ editingIncome ? 'Save changes' : 'Add income source' }}
                        </button>
                        <button type="button" class="hh-btn w-auto bg-hh-soft text-hh-ink" @click="incomeOpen = false">Cancel</button>
                        <button v-if="editingIncome" type="button" class="hh-btn ml-auto w-auto bg-hh-soft text-hh-coral" @click="destroyIncome">
                            Delete
                        </button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog :open="confirmDialogOpen" :message="confirmMessage" @update:open="confirmDialogOpen = $event" @confirm="confirmDestroy" />
    </HouseHubLayout>
</template>
