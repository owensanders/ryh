<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Button from '@/components/ryh/ui/Button.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout title="Forgot password" description="We'll email you a link to set a new one.">
        <Head title="Forgot password" />

        <div v-if="status" class="mb-4 text-[13px] font-semibold text-ryh-mint">
            {{ status }}
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="email" class="ryh-label">Email address</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    name="email"
                    class="ryh-input"
                    required
                    autocomplete="off"
                    autofocus
                    placeholder="you@example.com"
                />
                <InputError :message="form.errors.email" />
            </div>

            <Button class="mt-2" type="submit" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Email password reset link
            </Button>
        </form>

        <p class="mt-6 text-center text-[13px] text-ryh-ink3">
            Or, return to
            <Link :href="route('login')" class="font-semibold text-ryh-coral hover:opacity-75">log in</Link>
        </p>
    </AuthLayout>
</template>
