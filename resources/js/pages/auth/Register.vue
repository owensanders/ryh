<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Button from '@/components/ui/Button.vue';
import Field from '@/components/ui/Field.vue';
import Input from '@/components/ui/Input.vue';
import AuthWizardLayout from '@/layouts/auth/AuthWizardLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    email?: string;
    invited?: boolean;
}>();

const firstName = ref('');
const lastName = ref('');
const showPassword = ref(false);
const acceptedTerms = ref(false);

const form = useForm({
    name: '',
    email: props.email ?? '',
    password: '',
    terms: false as boolean,
});

const passwordScore = computed(() => {
    const value = form.password;
    let score = 0;
    if (value.length >= 10) score++;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
    if (/[0-9]/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;
    return score;
});

const passwordNote = computed(
    () => ['Ten characters minimum.', 'Weak — add numbers or symbols.', 'Getting there.', 'Strong.', 'Very strong.'][passwordScore.value],
);

const ready = computed(
    () =>
        firstName.value.trim() !== '' && lastName.value.trim() !== '' && form.email.includes('@') && passwordScore.value >= 3 && acceptedTerms.value,
);

const submit = () => {
    if (!ready.value) return;

    form.name = `${firstName.value.trim()} ${lastName.value.trim()}`.trim();
    form.terms = acceptedTerms.value;

    form.post(route('register'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthWizardLayout :step="1" step-title="Your account">
        <Head title="Register" />

        <div class="max-w-[560px]">
            <h1 class="text-[32px] font-black tracking-[-0.035em] sm:text-[38px]">Create your account</h1>
            <p class="mt-3 text-[15.5px] leading-relaxed text-ryh-ink2">One login per person. You'll connect it to a household on the next step.</p>

            <form class="mt-7 flex flex-col gap-3.5" @submit.prevent="submit">
                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                    <Field label="First name" for="firstName">
                        <Input id="firstName" v-model="firstName" type="text" autofocus autocomplete="given-name" placeholder="John" />
                    </Field>
                    <Field label="Last name" for="lastName">
                        <Input id="lastName" v-model="lastName" type="text" autocomplete="family-name" placeholder="Doe" />
                    </Field>
                </div>

                <Field label="Email address" for="email">
                    <Input id="email" v-model="form.email" type="email" autocomplete="email" placeholder="you@example.com" />
                    <InputError :message="form.errors.email" />
                </Field>

                <div class="flex flex-col gap-1.5">
                    <label for="password" class="ryh-label">Password</label>
                    <div class="flex gap-2">
                        <Input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            class="flex-1"
                            autocomplete="new-password"
                            placeholder="At least 10 characters"
                        />
                        <button
                            type="button"
                            class="h-11 w-24 flex-none rounded-[13px] border border-ryh-line text-[13px] font-semibold text-ryh-ink2 transition-colors hover:bg-ryh-soft"
                            @click="showPassword = !showPassword"
                        >
                            {{ showPassword ? 'Hide' : 'Show' }}
                        </button>
                    </div>
                    <div class="mt-1 flex gap-1.5">
                        <span
                            v-for="bar in 4"
                            :key="bar"
                            class="h-[5px] flex-1 rounded-[3px] transition-colors"
                            :class="bar <= passwordScore ? 'bg-ryh-mint' : 'bg-ryh-line'"
                        ></span>
                    </div>
                    <span class="text-xs text-ryh-ink3">{{ passwordNote }}</span>
                    <InputError :message="form.errors.password" />
                </div>

                <div
                    class="flex items-start gap-3 rounded-[14px] border border-ryh-line p-3.5 transition-colors hover:bg-ryh-soft"
                    :class="acceptedTerms ? 'bg-ryh-card' : 'bg-transparent'"
                >
                    <button
                        type="button"
                        role="checkbox"
                        :aria-checked="acceptedTerms"
                        aria-labelledby="terms-label"
                        class="mt-0.5 grid h-[21px] w-[21px] flex-none place-items-center rounded-[7px] border-[1.5px] text-[11px] font-extrabold text-[#0E1A2B] transition-colors"
                        :class="acceptedTerms ? 'border-ryh-mint bg-ryh-mint' : 'border-ryh-line bg-transparent'"
                        @click="acceptedTerms = !acceptedTerms"
                    >
                        {{ acceptedTerms ? '✓' : '' }}
                    </button>
                    <span
                        id="terms-label"
                        class="flex-1 cursor-pointer text-[13.5px] leading-relaxed text-ryh-ink2"
                        @click="acceptedTerms = !acceptedTerms"
                    >
                        <template v-if="props.invited">I agree to the </template>
                        <template v-else>I'm 18 or over, and I agree to the </template>
                        <a :href="route('terms')" target="_blank" class="font-semibold text-ryh-ink underline underline-offset-2" @click.stop
                            >Terms of Service</a
                        >
                        and
                        <a :href="route('privacy')" target="_blank" class="font-semibold text-ryh-ink underline underline-offset-2" @click.stop
                            >Privacy Policy</a
                        >.
                        <template v-if="props.invited"> If I'm under 18, the parent or guardian who invited me has agreed to them for me.</template>
                    </span>
                </div>
                <InputError :message="form.errors.terms" />

                <div class="mt-2 flex items-center gap-3.5">
                    <Button class="px-6" type="submit" :disabled="!ready || form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Continue
                    </Button>
                    <span v-if="!ready" class="text-[13px] text-ryh-ink3">Name, email, a strong password and the terms box.</span>
                </div>
            </form>
        </div>
    </AuthWizardLayout>
</template>
