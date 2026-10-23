<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Button from '@/components/ryh/ui/Button.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <AuthLayout title="Confirm your password" description="This is a secure area. Please confirm your password before continuing.">
        <Head title="Confirm password" />

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="password" class="ryh-label">Password</label>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="ryh-input"
                    required
                    autocomplete="current-password"
                    autofocus
                    placeholder="••••••••"
                />
                <InputError :message="form.errors.password" />
            </div>

            <Button class="mt-2" type="submit" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Confirm password
            </Button>
        </form>
    </AuthLayout>
</template>
