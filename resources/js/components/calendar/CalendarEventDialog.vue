<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Field from '@/components/ui/Field.vue';
import IconButton from '@/components/ui/IconButton.vue';
import Input from '@/components/ui/Input.vue';
import Textarea from '@/components/ui/Textarea.vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useCalendarEditor } from '@/composables/useCalendarEditor';
import { PALETTE } from '@/lib/ryh';
import type { MemberData } from '@/types/generated';

/** Adding or editing one event. */
defineProps<{ members: MemberData[]; todayDate: string }>();

const { open, editing, form, toggleAttendee, submit, destroy } = useCalendarEditor();
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
            <DialogHeader>
                <DialogTitle class="text-[16px] font-extrabold tracking-tight">{{ editing ? 'Edit event' : 'New event' }}</DialogTitle>
            </DialogHeader>

            <form class="flex flex-col gap-4" @submit.prevent="submit">
                <Field label="Title" for="title" :error="form.errors.title">
                    <Input id="title" v-model="form.title" type="text" required placeholder="What's happening?" />
                </Field>

                <label for="is_all_day" class="flex items-center gap-2.5 text-[13px] text-ryh-ink2">
                    <Checkbox id="is_all_day" v-model="form.is_all_day" />
                    All day
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <Field label="Starts" for="starts_at" :error="form.errors.starts_at">
                        <Input id="starts_at" v-model="form.starts_at" type="datetime-local" :min="`${todayDate}T00:00`" required />
                    </Field>

                    <div v-if="!form.is_all_day" class="flex flex-col gap-1.5">
                        <label for="ends_at" class="ryh-label">Ends</label>
                        <Input id="ends_at" v-model="form.ends_at" type="datetime-local" />
                        <p v-if="form.errors.ends_at" class="text-[12.5px] text-ryh-coral">{{ form.errors.ends_at }}</p>
                    </div>
                </div>

                <Field label="Location" for="location" :error="form.errors.location">
                    <Input id="location" v-model="form.location" type="text" maxlength="120" placeholder="Optional" />
                </Field>

                <div class="flex flex-col gap-1.5">
                    <span class="ryh-label">Who's it for?</span>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="member in members"
                            :key="member.id"
                            type="button"
                            class="flex h-[34px] items-center gap-2 rounded-[11px] py-1 pl-1 pr-3 text-[12.5px] transition-colors"
                            :class="
                                form.attendees.includes(member.id)
                                    ? 'bg-ryh-sunk font-bold ring-2 ring-ryh-coral'
                                    : 'bg-ryh-soft font-medium text-ryh-ink2 hover:bg-ryh-line'
                            "
                            :aria-pressed="form.attendees.includes(member.id)"
                            @click="toggleAttendee(member.id)"
                        >
                            <span
                                class="grid h-[26px] w-[26px] place-items-center rounded-lg text-[9.5px] font-extrabold text-[#0E1A2B]"
                                :style="{ background: member.colour }"
                            >
                                {{ member.initials }}
                            </span>
                            {{ member.name.split(' ')[0] }}
                        </button>
                    </div>
                    <p class="text-[12px] text-ryh-ink3">
                        {{ form.attendees.length === 0 ? 'Nobody picked — this one is for the whole house.' : 'Only the people picked above.' }}
                    </p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <span class="ryh-label">Colour</span>
                    <div class="flex gap-2">
                        <IconButton
                            :label="colour"
                            v-for="colour in PALETTE"
                            :key="colour"
                            :aria-pressed="form.colour === colour"
                            class="h-7 w-7 rounded-[9px] transition"
                            :class="form.colour === colour ? 'ring-2 ring-ryh-ink ring-offset-2 ring-offset-ryh-card' : ''"
                            :style="{ background: `var(--ryh-${colour})` }"
                            @click="form.colour = colour"
                        ></IconButton>
                    </div>
                </div>

                <Field label="Notes" for="notes" :error="form.errors.notes">
                    <Textarea id="notes" v-model="form.notes" rows="2" maxlength="2000" class="h-auto py-2.5" placeholder="Optional" />
                </Field>

                <div class="flex items-center gap-2">
                    <Button class="w-auto" type="submit" :disabled="form.processing">
                        {{ editing ? 'Save changes' : 'Add event' }}
                    </Button>
                    <Button variant="secondary" class="w-auto" @click="open = false">Cancel</Button>
                    <Button variant="danger" class="ml-auto w-auto" v-if="editing?.can.delete" @click="destroy"> Delete </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
