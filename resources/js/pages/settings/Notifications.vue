<script setup lang="ts">
import HouseHubLayout from '@/layouts/HouseHubLayout.vue';
import HouseHubShell from '@/layouts/HouseHubShell.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { currentSubscription, disablePush, enablePush, pushSupport, type PushSupport } from '@/lib/push';
import type { PushCategory } from '@/types/househub';
import { router } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

defineOptions({ layout: HouseHubShell });

const props = defineProps<{
    categories: PushCategory[];
    vapidPublicKey: string | null;
}>();

const support = ref<PushSupport | null>(null);
const subscribed = ref(false);
const busy = ref(false);
const error = ref<string | null>(null);

onMounted(async () => {
    support.value = pushSupport();
    subscribed.value = support.value === 'available' && (await currentSubscription()) !== null;
});

async function turnOn(): Promise<void> {
    if (!props.vapidPublicKey) {
        return;
    }

    busy.value = true;
    error.value = null;
    try {
        subscribed.value = await enablePush(props.vapidPublicKey);
        support.value = pushSupport();
    } catch {
        error.value = "Couldn't turn notifications on. Please try again.";
    } finally {
        busy.value = false;
    }
}

async function turnOff(): Promise<void> {
    busy.value = true;
    error.value = null;
    try {
        await disablePush();
        subscribed.value = false;
    } finally {
        busy.value = false;
    }
}

function sendTest(): void {
    router.post(route('notifications.test'), {}, { preserveScroll: true });
}

function toggle(category: PushCategory): void {
    const optOuts = props.categories.filter((c) => (c.value === category.value ? c.enabled : !c.enabled)).map((c) => c.value);

    router.patch(route('notifications.update'), { opt_outs: optOuts }, { preserveScroll: true });
}
</script>

<template>
    <HouseHubLayout title="Settings" subtitle="Manage your profile and account">
        <SettingsLayout>
            <section class="rounded-[22px] border border-hh-line bg-hh-card p-4 sm:p-[22px]" aria-labelledby="device-heading">
                <h3 id="device-heading" class="text-[15px] font-extrabold tracking-[-0.01em]">This device</h3>
                <p class="mt-1 text-[13px] text-hh-ink3">Get a notification on this phone or computer when something in the household needs you.</p>

                <div class="mt-5 rounded-2xl bg-hh-sunk p-3.5 text-[13px]">
                    <p v-if="!vapidPublicKey" class="text-hh-ink2">Push notifications aren't set up on this server yet.</p>
                    <p v-else-if="support === 'needs-install'" class="text-hh-ink2">
                        On iPhone and iPad, notifications only work in the installed app. Add HouseHub to your Home Screen from Safari's
                        <strong class="text-hh-ink">Share</strong> menu, open it from there, then turn notifications on here.
                    </p>
                    <p v-else-if="support === 'unsupported'" class="text-hh-ink2">This browser doesn't support notifications.</p>
                    <p v-else-if="support === 'denied'" class="text-hh-ink2">
                        Notifications are blocked for HouseHub. Allow them in your browser or phone settings, then come back here.
                    </p>
                    <div v-else-if="support === 'available'" class="flex flex-wrap items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="font-semibold">{{ subscribed ? 'Notifications are on' : 'Notifications are off' }}</div>
                            <div class="mt-0.5 text-[11.5px] text-hh-ink3">
                                {{ subscribed ? 'You can turn them off here at any time.' : 'Your browser will ask for permission.' }}
                            </div>
                        </div>
                        <template v-if="subscribed">
                            <button
                                type="button"
                                class="rounded-lg border border-hh-line px-3 py-1.5 text-[12.5px] font-semibold text-hh-ink2 transition-colors hover:bg-hh-soft"
                                @click="sendTest"
                            >
                                Send a test
                            </button>
                            <button
                                type="button"
                                class="rounded-lg border border-hh-line px-3 py-1.5 text-[12.5px] font-semibold text-hh-ink2 transition-colors hover:bg-hh-soft"
                                :disabled="busy"
                                @click="turnOff"
                            >
                                Turn off
                            </button>
                        </template>
                        <button v-else type="button" class="hh-btn bg-hh-coral text-white" :disabled="busy" @click="turnOn">
                            <LoaderCircle v-if="busy" class="h-4 w-4 animate-spin" />
                            Turn on
                        </button>
                    </div>
                    <p v-if="error" class="mt-2 text-[12.5px] font-semibold text-hh-coral" role="alert">{{ error }}</p>
                </div>
            </section>

            <section class="rounded-[22px] border border-hh-line bg-hh-card p-4 sm:p-[22px]" aria-labelledby="categories-heading">
                <h3 id="categories-heading" class="text-[15px] font-extrabold tracking-[-0.01em]">What to send</h3>
                <p class="mt-1 text-[13px] text-hh-ink3">
                    Applies to every device you've turned notifications on for. The bell in HouseHub always shows everything.
                </p>

                <div class="mt-5 flex flex-col gap-2">
                    <div v-for="category in categories" :key="category.value" class="flex items-center gap-3 rounded-2xl bg-hh-sunk p-3.5">
                        <div class="min-w-0 flex-1 pr-3">
                            <div class="text-[13px] font-semibold">{{ category.label }}</div>
                            <div class="mt-0.5 text-[11.5px] text-hh-ink3">{{ category.description }}</div>
                        </div>
                        <button
                            type="button"
                            role="switch"
                            :aria-label="category.label"
                            :aria-checked="category.enabled"
                            class="h-[26px] w-11 flex-none rounded-full p-[3px] transition-colors"
                            :style="{ background: category.enabled ? 'var(--hh-mint)' : 'var(--hh-line)' }"
                            @click="toggle(category)"
                        >
                            <span
                                class="block h-5 w-5 rounded-full bg-white transition-transform"
                                :style="{ transform: category.enabled ? 'translateX(18px)' : 'translateX(0)' }"
                            ></span>
                        </button>
                    </div>
                </div>
            </section>
        </SettingsLayout>
    </HouseHubLayout>
</template>
