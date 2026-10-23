<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Field from '@/components/ui/Field.vue';
import Input from '@/components/ui/Input.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
    confirm_household_deletion: false,
});

// The server reports the "you're the sole owner" warning under `household`,
// which isn't a form field, so Inertia's form types don't include it.
const householdError = computed(() => (form.errors as Partial<Record<string, string>>).household);

const deleteUser = (e: Event) => {
    e.preventDefault();

    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => {
            form.reset();
        },
    });
};

const closeModal = () => {
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="rounded-[22px] border border-ryh-line bg-ryh-card p-[22px]">
        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Delete account</h3>
        <p class="mt-1 text-[13px] text-ryh-ink3">Delete your account and all of its resources.</p>

        <!-- ryh-* tokens are bare `var(...)`, so Tailwind can't apply an opacity modifier to them. -->
        <div class="mt-5 rounded-[13px] bg-[color-mix(in_srgb,var(--ryh-coral)_10%,transparent)] p-3.5">
            <p class="text-[13px] font-bold text-ryh-coral">Warning</p>
            <p class="mt-0.5 text-[13px] text-ryh-ink2">Please proceed with caution, this cannot be undone.</p>

            <Dialog>
                <DialogTrigger as-child><Button class="mt-3.5 w-auto">Delete account</Button></DialogTrigger>
                <DialogContent class="rounded-[22px] border-ryh-line bg-ryh-card text-ryh-ink sm:rounded-[22px]">
                    <form class="flex flex-col gap-5" @submit="deleteUser">
                        <DialogHeader class="space-y-2">
                            <DialogTitle class="text-[16px] font-extrabold tracking-tight">Are you sure you want to delete your account?</DialogTitle>
                            <DialogDescription class="text-[13px] text-ryh-ink3">
                                Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your
                                password to confirm you would like to permanently delete your account.
                            </DialogDescription>
                        </DialogHeader>

                        <Field label="Password" for="password" hide-label :error="form.errors.password">
                            <Input
                                id="password"
                                ref="passwordInput"
                                v-model="form.password"
                                type="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Password"
                            />
                        </Field>

                        <div v-if="householdError" class="rounded-[13px] bg-[color-mix(in_srgb,var(--ryh-coral)_10%,transparent)] p-3.5">
                            <p class="text-[13px] text-ryh-ink2">{{ householdError }}</p>
                            <a :href="route('house.index')" class="mt-1.5 inline-block text-[13px] font-bold text-ryh-coral underline">
                                Go promote another member to Owner instead
                            </a>
                            <label class="mt-3 flex items-start gap-2 text-[13px] text-ryh-ink2">
                                <Checkbox v-model="form.confirm_household_deletion" class="mt-0.5" />
                                I understand — delete my household and all its data
                            </label>
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child><Button variant="secondary" class="w-auto" @click="closeModal">Cancel</Button></DialogClose>

                            <Button
                                class="w-auto"
                                type="submit"
                                :disabled="form.processing || (!!householdError && !form.confirm_household_deletion)"
                            >
                                Delete account
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </section>
</template>
