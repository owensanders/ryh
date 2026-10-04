<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import InputError from '@/components/InputError.vue';
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
    <section class="rounded-[22px] border border-hh-line bg-hh-card p-[22px]">
        <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Delete account</h3>
        <p class="mt-1 text-[13px] text-hh-ink3">Delete your account and all of its resources.</p>

        <!-- hh-* tokens are bare `var(...)`, so Tailwind can't apply an opacity modifier to them. -->
        <div class="mt-5 rounded-[13px] bg-[color-mix(in_srgb,var(--hh-coral)_10%,transparent)] p-3.5">
            <p class="text-[13px] font-bold text-hh-coral">Warning</p>
            <p class="mt-0.5 text-[13px] text-hh-ink2">Please proceed with caution, this cannot be undone.</p>

            <Dialog>
                <DialogTrigger class="hh-btn mt-3.5 w-auto bg-hh-coral text-white"> Delete account </DialogTrigger>
                <DialogContent class="rounded-[22px] border-hh-line bg-hh-card text-hh-ink sm:rounded-[22px]">
                    <form class="flex flex-col gap-5" @submit="deleteUser">
                        <DialogHeader class="space-y-2">
                            <DialogTitle class="text-[16px] font-extrabold tracking-tight">Are you sure you want to delete your account?</DialogTitle>
                            <DialogDescription class="text-[13px] text-hh-ink3">
                                Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your
                                password to confirm you would like to permanently delete your account.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="flex flex-col gap-1.5">
                            <label for="password" class="sr-only">Password</label>
                            <input
                                id="password"
                                ref="passwordInput"
                                v-model="form.password"
                                type="password"
                                name="password"
                                class="hh-input"
                                required
                                autocomplete="current-password"
                                placeholder="Password"
                            />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div v-if="householdError" class="rounded-[13px] bg-[color-mix(in_srgb,var(--hh-coral)_10%,transparent)] p-3.5">
                            <p class="text-[13px] text-hh-ink2">{{ householdError }}</p>
                            <a :href="route('house.index')" class="mt-1.5 inline-block text-[13px] font-bold text-hh-coral underline">
                                Go promote another member to Owner instead
                            </a>
                            <label class="mt-3 flex items-start gap-2 text-[13px] text-hh-ink2">
                                <input v-model="form.confirm_household_deletion" type="checkbox" class="mt-0.5" />
                                I understand — delete my household and all its data
                            </label>
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose class="hh-btn w-auto bg-hh-soft text-hh-ink" @click="closeModal"> Cancel </DialogClose>

                            <button
                                type="submit"
                                class="hh-btn w-auto bg-hh-coral text-white"
                                :disabled="form.processing || (!!householdError && !form.confirm_household_deletion)"
                            >
                                Delete account
                            </button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </section>
</template>
