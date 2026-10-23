<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Button from '@/components/ryh/ui/Button.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <AuthLayout title="Reset password" description="Please enter your new password below.">
        <Head title="Reset password" />

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="email" class="ryh-label">Email</label>
                <input id="email" v-model="form.email" type="email" name="email" class="ryh-input" autocomplete="email" readonly />
                <InputError :message="form.errors.email" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password" class="ryh-label">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    name="password"
                    class="ryh-input"
                    required
                    autocomplete="new-password"
                    autofocus
                    placeholder="••••••••"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex flex-col gap-1.5">
                <label for="password_confirmation" class="ryh-label">Confirm password</label>
                <input
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    name="password_confirmation"
                    class="ryh-input"
                    required
                    autocomplete="new-password"
                    placeholder="••••••••"
                />
                <InputError :message="form.errors.password_confirmation" />
            </div>

            <Button class="mt-2" type="submit" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Reset password
            </Button>
        </form>
    </AuthLayout>
</template>
