<script setup lang="ts">
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useHouseholdRole } from '@/composables/useHouseholdRole';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import { dial, tickStyle } from '@/lib/ryh';
import type { ChoreColumnData, ChoreData, ChoreStatusEnum, MemberData, MemberScoreData } from '@/types/generated';
import { router, useForm } from '@inertiajs/vue3';
import { Bell, Plus, X } from 'lucide-vue-next';
import { computed, onUnmounted, ref } from 'vue';

defineOptions({ layout: RyhShell });

const props = defineProps<{
    columns: ChoreColumnData[];
    scores: MemberScoreData[];
    members: MemberData[];
    notifyChoresAvailableAt: string | null;
}>();

const { canManage, canToggleChore } = useHouseholdRole();

// Notify chores
const now = ref(new Date());
const nowTimer = setInterval(() => (now.value = new Date()), 30_000);
onUnmounted(() => clearInterval(nowTimer));

const notifyChoresReady = computed(() => props.notifyChoresAvailableAt === null || new Date(props.notifyChoresAvailableAt) <= now.value);

const notifyChoresCooldownLabel = computed(() => {
    if (notifyChoresReady.value || props.notifyChoresAvailableAt === null) {
        return null;
    }

    const minutes = Math.max(1, Math.ceil((new Date(props.notifyChoresAvailableAt).getTime() - now.value.getTime()) / 60_000));

    return `Available again in ${minutes}m`;
});

function notifyChores(): void {
    if (!notifyChoresReady.value) {
        return;
    }

    router.post(route('chores.notify'), {}, { preserveScroll: true });
}

// Add chore
const draft = ref('');
const draftAssignedTo = ref('');

function addChore(): void {
    if (!draft.value.trim()) {
        return;
    }

    router.post(
        route('chores.store'),
        {
            name: draft.value.trim(),
            assigned_to: draftAssignedTo.value === '' ? null : Number(draftAssignedTo.value),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                draft.value = '';
            },
        },
    );
}

// Drag and drop between columns
const draggingId = ref<number | null>(null);

function startDrag(event: DragEvent, id: number): void {
    draggingId.value = id;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
    }
}

function drop(status: ChoreStatusEnum): void {
    const chore = draggingId.value;
    draggingId.value = null;

    if (chore === null) {
        return;
    }

    router.patch(route('chores.move', { chore }), { status }, { preserveScroll: true });
}

function toggle(chore: ChoreData): void {
    if (!canToggleChore(chore)) {
        return;
    }

    router.patch(route('chores.toggle', { chore: chore.id }), {}, { preserveScroll: true });
}

// Edit
const open = ref(false);
const editing = ref<ChoreData | null>(null);

const form = useForm({
    name: '',
    assigned_to: '',
});

function openEdit(chore: ChoreData): void {
    editing.value = chore;
    form.clearErrors();
    form.defaults({
        name: chore.name,
        assigned_to: chore.assignee ? String(chore.assignee.id) : '',
    });
    form.reset();
    open.value = true;
}

function submit(): void {
    if (editing.value === null) {
        return;
    }

    form.transform((data) => ({
        ...data,
        assigned_to: data.assigned_to === '' ? null : Number(data.assigned_to),
    }));

    form.patch(route('chores.update', { chore: editing.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}

// Delete
const confirmOpen = ref(false);
const deleting = ref<ChoreData | null>(null);

function requestDelete(chore: ChoreData): void {
    deleting.value = chore;
    confirmOpen.value = true;
}

function confirmDestroy(): void {
    if (deleting.value === null) {
        return;
    }

    confirmOpen.value = false;

    router.delete(route('chores.destroy', { chore: deleting.value.id }), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
}
</script>

<template>
    <RyhLayout title="Chores" subtitle="This week">
        <div class="flex animate-ryh-rise flex-col gap-[18px]">
            <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="score in scores"
                    :key="score.member.id"
                    class="flex items-center gap-3.5 rounded-[20px] border border-ryh-line bg-ryh-card px-[18px] py-4"
                >
                    <div
                        class="grid h-[60px] w-[60px] flex-none place-items-center rounded-full transition-[background] duration-500"
                        :style="{ background: dial(score.percentage, score.member.colour) }"
                    >
                        <div class="grid h-[46px] w-[46px] place-items-center rounded-full bg-ryh-card text-xs font-extrabold">
                            {{ score.member.initials }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[14.5px] font-extrabold">{{ score.member.name.split(' ')[0] }}</div>
                        <div class="mt-[3px] text-[12.5px] text-ryh-ink3">{{ score.done }} of {{ score.total }} this week</div>
                    </div>
                </div>
            </div>

            <div v-if="canManage" class="flex items-center justify-end gap-2.5">
                <span v-if="notifyChoresCooldownLabel" class="text-[12px] text-ryh-ink3">{{ notifyChoresCooldownLabel }}</span>
                <button
                    type="button"
                    :disabled="!notifyChoresReady"
                    class="ryh-btn w-auto bg-ryh-soft text-ryh-ink disabled:cursor-not-allowed disabled:opacity-50"
                    @click="notifyChores"
                >
                    <Bell class="h-4 w-4" :stroke-width="2.5" />
                    Notify Chores
                </button>
            </div>

            <section v-if="canManage" class="flex flex-wrap items-center gap-2.5 rounded-[20px] border border-ryh-line bg-ryh-panel p-[18px]">
                <input
                    v-model="draft"
                    type="text"
                    placeholder="Add a chore, e.g. Clean the bathroom"
                    aria-label="New chore"
                    class="ryh-input h-12 min-w-[220px] flex-1"
                    @keydown.enter="addChore"
                />
                <select v-model="draftAssignedTo" aria-label="Assign new chore to" class="ryh-input h-12 w-[132px]">
                    <option value="">Unassigned</option>
                    <option v-for="member in members" :key="member.id" :value="String(member.id)">{{ member.name }}</option>
                </select>
                <button type="button" class="ryh-btn w-auto bg-ryh-coral text-white" @click="addChore">
                    <Plus class="h-4 w-4" :stroke-width="2.5" />
                    Add chore
                </button>
            </section>

            <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                <div
                    v-for="column in columns"
                    :key="column.status"
                    class="flex min-h-[420px] flex-col gap-2.5 rounded-[20px] border border-dashed p-3.5 transition"
                    :style="{
                        background: draggingId !== null ? 'var(--ryh-sunk)' : 'var(--ryh-panel)',
                        borderColor: draggingId !== null ? 'var(--ryh-line)' : 'transparent',
                    }"
                    @dragover.prevent
                    @drop.prevent="drop(column.status)"
                >
                    <div class="flex items-center gap-2">
                        <span class="text-[13.5px] font-extrabold">{{ column.title }}</span>
                        <span class="rounded-md bg-ryh-soft px-1.5 py-0.5 font-mono text-[11px] text-ryh-ink2">{{ column.count }}</span>
                    </div>

                    <div
                        v-for="chore in column.items"
                        :key="chore.id"
                        :draggable="canManage"
                        class="rounded-2xl border border-ryh-line bg-ryh-card px-3.5 py-3 transition hover:-translate-y-0.5 hover:shadow-ryh"
                        :class="canManage ? 'cursor-grab' : ''"
                        :style="{ opacity: draggingId === chore.id ? 0.4 : 1 }"
                        @dragstart="startDrag($event, chore.id)"
                        @dragend="draggingId = null"
                        @click="canManage && openEdit(chore)"
                    >
                        <div class="flex items-start gap-2.5">
                            <!-- Ticking lives on the box alone so it can't fire at the end of a drag. -->
                            <button
                                type="button"
                                :disabled="!canToggleChore(chore)"
                                :aria-label="chore.done ? `Mark ${chore.name} as not done` : `Mark ${chore.name} as done`"
                                class="mt-px grid h-[19px] w-[19px] flex-none place-items-center rounded-full border-[1.5px] text-[11px] text-[#0E1A2B] disabled:cursor-not-allowed"
                                :style="tickStyle(chore.done)"
                                @click.stop="toggle(chore)"
                            >
                                {{ chore.done ? '✓' : '' }}
                            </button>
                            <span class="flex-1 text-sm font-medium leading-snug" :class="chore.done ? 'text-ryh-ink3 line-through' : 'text-ryh-ink'">
                                {{ chore.name }}
                            </span>
                            <button
                                v-if="canManage"
                                type="button"
                                title="Delete chore"
                                class="grid h-6 w-6 flex-none place-items-center rounded-lg border-0 bg-transparent text-ryh-ink3 transition hover:bg-ryh-soft hover:text-ryh-coral"
                                @click.stop="requestDelete(chore)"
                            >
                                <X class="h-3 w-3" :stroke-width="2.5" />
                            </button>
                        </div>
                        <div v-if="chore.assignee" class="mt-3 flex items-center gap-1.5">
                            <span
                                class="grid h-6 w-6 place-items-center rounded-lg text-[9.5px] font-extrabold text-[#0E1A2B]"
                                :style="{ background: chore.assignee.colour }"
                            >
                                {{ chore.assignee.initials }}
                            </span>
                        </div>
                    </div>

                    <div v-if="column.items.length === 0" class="py-6 text-center text-[12.5px] text-ryh-ink3">
                        {{ column.status === 'done' ? 'Nothing ticked off yet.' : 'Drag a chore here.' }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit -->
        <Dialog :open="open" @update:open="open = $event">
            <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
                <DialogHeader>
                    <DialogTitle class="text-[16px] font-extrabold tracking-tight">Edit chore</DialogTitle>
                </DialogHeader>

                <form class="flex flex-col gap-4" @submit.prevent="submit">
                    <div class="flex flex-col gap-1.5">
                        <label for="name" class="ryh-label">Name</label>
                        <input id="name" v-model="form.name" type="text" class="ryh-input" required placeholder="What needs doing?" />
                        <p v-if="form.errors.name" class="text-[12.5px] text-ryh-coral">{{ form.errors.name }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="assigned_to" class="ryh-label">Assignee</label>
                        <select id="assigned_to" v-model="form.assigned_to" class="ryh-input">
                            <option value="">Unassigned</option>
                            <option v-for="member in members" :key="member.id" :value="String(member.id)">
                                {{ member.name }}
                            </option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="ryh-btn w-auto bg-ryh-coral text-white" :disabled="form.processing">Save changes</button>
                        <button type="button" class="ryh-btn w-auto bg-ryh-soft text-ryh-ink" @click="open = false">Cancel</button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmDialog :open="confirmOpen" message="Delete this chore?" @update:open="confirmOpen = $event" @confirm="confirmDestroy" />
    </RyhLayout>
</template>
