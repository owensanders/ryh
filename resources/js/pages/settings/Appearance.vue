<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

defineOptions({ layout: RyhShell });
</script>

<template>
    <RyhLayout title="Settings" subtitle="Manage your profile and account">
        <SettingsLayout>
            <section class="rounded-[22px] border border-ryh-line bg-ryh-card p-4 sm:p-[22px]">
                <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Appearance</h3>
                <p class="mt-1 text-[13px] text-ryh-ink3">Choose how RYH looks on this device.</p>

                <AppearanceTabs class="mt-5" />
            </section>
        </SettingsLayout>
    </RyhLayout>
</template>
