<script setup lang="ts">
import Button from '@/components/ryh/ui/Button.vue';
import { TransitionRoot } from '@headlessui/vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type SharedData } from '@/types';
import type { AuthUserData } from '@/types/generated';

defineOptions({ layout: RyhShell });

interface Props {
    status?: string;
}

defineProps<Props>();

const page = usePage<SharedData>();
const user = page.props.auth.user as AuthUserData;

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <RyhLayout title="Settings" subtitle="Manage your profile and account">
        <SettingsLayout>
            <section class="rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
                <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Profile information</h3>
                <p class="mt-1 text-[13px] text-ryh-ink3">Update your name and email address.</p>

                <form class="mt-5 flex flex-col gap-4" @submit.prevent="submit">
                    <div class="flex flex-col gap-1.5">
                        <label for="name" class="ryh-label">Name</label>
                        <input id="name" v-model="form.name" type="text" class="ryh-input" required autocomplete="name" placeholder="Full name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="email" class="ryh-label">Email address</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="ryh-input"
                            required
                            autocomplete="username"
                            placeholder="you@example.com"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div v-if="!user.emailVerifiedAt" class="rounded-[13px] bg-ryh-sunk p-3.5 text-[13px] text-ryh-ink2">
                        Your email address is unverified.
                        <Link :href="route('verification.send')" method="post" as="button" class="font-semibold text-ryh-coral hover:opacity-75">
                            Re-send the verification email.
                        </Link>

                        <p v-if="status === 'verification-link-sent'" class="mt-2 font-semibold text-ryh-mint">
                            A new verification link has been sent to your email address.
                        </p>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button class="w-auto" type="submit" :disabled="form.processing">Save</Button>

                        <TransitionRoot
                            :show="form.recentlySuccessful"
                            enter="transition ease-in-out"
                            enter-from="opacity-0"
                            leave="transition ease-in-out"
                            leave-to="opacity-0"
                        >
                            <p class="text-[13px] font-semibold text-ryh-mint">Saved.</p>
                        </TransitionRoot>
                    </div>
                </form>
            </section>

            <DeleteUser />
        </SettingsLayout>
    </RyhLayout>
</template>
