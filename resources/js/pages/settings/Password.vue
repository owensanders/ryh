<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import HouseHubLayout from '@/layouts/HouseHubLayout.vue';
import HouseHubShell from '@/layouts/HouseHubShell.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { TransitionRoot } from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: HouseHubShell });

const passwordInput = ref<HTMLInputElement>();
const currentPasswordInput = ref<HTMLInputElement>();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
        onError: (errors: Record<string, string>) => {
            if (errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }

            if (errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <HouseHubLayout title="Settings" subtitle="Manage your profile and account">
        <SettingsLayout>
            <section class="rounded-[22px] border border-hh-line bg-hh-card p-4 sm:p-[22px]">
                <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Update password</h3>
                <p class="mt-1 text-[13px] text-hh-ink3">Use a long, random password to keep the account secure.</p>

                <form class="mt-5 flex flex-col gap-4" @submit.prevent="updatePassword">
                    <div class="flex flex-col gap-1.5">
                        <label for="current_password" class="hh-label">Current password</label>
                        <input
                            id="current_password"
                            ref="currentPasswordInput"
                            v-model="form.current_password"
                            type="password"
                            class="hh-input"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError :message="form.errors.current_password" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password" class="hh-label">New password</label>
                        <input
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="hh-input"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="password_confirmation" class="hh-label">Confirm password</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="hh-input"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <button type="submit" class="hh-btn w-auto bg-hh-coral text-white" :disabled="form.processing">Save password</button>

                        <TransitionRoot
                            :show="form.recentlySuccessful"
                            enter="transition ease-in-out"
                            enter-from="opacity-0"
                            leave="transition ease-in-out"
                            leave-to="opacity-0"
                        >
                            <p class="text-[13px] font-semibold text-hh-mint">Saved.</p>
                        </TransitionRoot>
                    </div>
                </form>
            </section>
        </SettingsLayout>
    </HouseHubLayout>
</template>
