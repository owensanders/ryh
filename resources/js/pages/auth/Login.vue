<script setup lang="ts">
import Button from '@/components/ui/Button.vue';
import Checkbox from '@/components/ui/Checkbox.vue';
import Field from '@/components/ui/Field.vue';
import Input from '@/components/ui/Input.vue';
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

        <div v-if="status" class="mb-4 text-[13px] font-semibold text-ryh-mint">
            {{ status }}
        </div>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <Field label="Email address" for="email" :error="form.errors.email">
                <Input id="email" v-model="form.email" type="email" required autofocus autocomplete="email" placeholder="you@example.com" />
            </Field>

            <Field label="Password" for="password" :error="form.errors.password">
                <template v-if="canResetPassword" #aside>
                    <Link :href="route('password.request')" class="text-[12.5px] font-semibold text-ryh-coral hover:opacity-75"
                        >Forgot password?</Link
                    >
                </template>
                <Input id="password" v-model="form.password" type="password" required autocomplete="current-password" placeholder="••••••••" />
            </Field>

            <label for="remember" class="flex items-center gap-2.5 text-[13px] text-ryh-ink2">
                <Checkbox id="remember" v-model="form.remember" />
                Remember me
            </label>

            <Button class="mt-2" type="submit" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Sign in
            </Button>
        </form>

        <p class="mt-6 text-center text-[13px] text-ryh-ink3">
            Don't have an account?
            <Link :href="route('register')" class="font-semibold text-ryh-coral hover:opacity-75">Sign up</Link>
        </p>
    </AuthBase>
</template>
