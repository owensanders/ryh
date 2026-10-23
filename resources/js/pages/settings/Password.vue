<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import Card from '@/components/ui/Card.vue';
import Field from '@/components/ui/Field.vue';
import Input from '@/components/ui/Input.vue';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { TransitionRoot } from '@headlessui/vue';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: RyhShell });

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
    <RyhLayout title="Settings" subtitle="Manage your profile and account">
        <SettingsLayout>
            <Card as="section">
                <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Update password</h3>
                <p class="mt-1 text-[13px] text-ryh-ink3">Use a long, random password to keep the account secure.</p>

                <form class="mt-5 flex flex-col gap-4" @submit.prevent="updatePassword">
                    <Field label="Current password" for="current_password" :error="form.errors.current_password">
                        <Input
                            id="current_password"
                            ref="currentPasswordInput"
                            v-model="form.current_password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                    </Field>

                    <Field label="New password" for="password" :error="form.errors.password">
                        <Input
                            id="password"
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                    </Field>

                    <Field label="Confirm password" for="password_confirmation" :error="form.errors.password_confirmation">
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            placeholder="••••••••"
                        />
                    </Field>

                    <div class="flex items-center gap-4">
                        <Button class="w-auto" type="submit" :disabled="form.processing">Save password</Button>

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
            </Card>
        </SettingsLayout>
    </RyhLayout>
</template>
