<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AuthBase from '@/layouts/AuthLayout.vue';
import { isStandalone } from '@/lib/pwa';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    // The installed app is a personal device's home-screen app, so stay signed in by default.
    remember: isStandalone(),
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <AuthBase title="Welcome back" description="Sign in to your household.">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-[13px] font-semibold text-hh-mint">
            {{ status }}
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div class="flex flex-col gap-1.5">
                <label for="email" class="hh-label">Email address</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="hh-input"
                    required
                    autofocus
                    tabindex="1"
                    autocomplete="email"
                    placeholder="you@example.com"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="flex flex-col gap-1.5">
                <div class="flex items-center justify-between">
                    <label for="password" class="hh-label">Password</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-[12.5px] font-semibold text-hh-coral hover:opacity-75"
                        tabindex="5"
                    >
                        Forgot password?
                    </Link>
                </div>
                <input
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="hh-input"
                    required
                    tabindex="2"
                    autocomplete="current-password"
                    placeholder="••••••••"
                />
                <InputError :message="form.errors.password" />
            </div>

            <label for="remember" class="flex items-center gap-2.5 text-[13px] text-hh-ink2">
                <input
                    id="remember"
                    v-model="form.remember"
                    type="checkbox"
                    tabindex="3"
                    class="h-4 w-4 accent-hh-coral focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-hh-coral"
                />
                Remember me
            </label>

            <button type="submit" class="hh-btn mt-2 bg-hh-coral text-white" tabindex="4" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Sign in
            </button>
        </form>

        <p class="mt-6 text-center text-[13px] text-hh-ink3">
            Don't have an account?
            <Link :href="route('register')" class="font-semibold text-hh-coral hover:opacity-75" :tabindex="6">Sign up</Link>
        </p>
    </AuthBase>
</template>
