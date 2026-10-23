<script setup lang="ts">
import Button from '@/components/ryh/ui/Button.vue';
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

        <div v-if="status === 'verification-link-sent'" class="mb-4 text-[13px] font-semibold text-ryh-mint">
            A new verification link has been sent to the email address you provided during registration.
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <Button variant="secondary" type="submit" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Resend verification email
            </Button>
        </form>

        <p class="mt-6 text-center text-[13px] text-ryh-ink3">
            <button type="button" class="font-semibold text-ryh-coral hover:opacity-75" @click="logout">Log out</button>
        </p>
    </AuthLayout>
</template>
