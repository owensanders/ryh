<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/lib/logout';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};
</script>

<template>
    <AuthLayout title="Verify email" description="Click the link we just emailed you to verify your address.">
        <Head title="Email verification" />

        <div v-if="status === 'verification-link-sent'" class="mb-4 text-[13px] font-semibold text-hh-mint">
            A new verification link has been sent to the email address you provided during registration.
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <button type="submit" class="hh-btn bg-hh-soft text-hh-ink" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Resend verification email
            </button>
        </form>

        <p class="mt-6 text-center text-[13px] text-hh-ink3">
            <button type="button" class="font-semibold text-hh-coral hover:opacity-75" @click="logout">Log out</button>
        </p>
    </AuthLayout>
</template>
