<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HouseHubLayout from '@/layouts/HouseHubLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
</script>

<template>
    <HouseHubLayout title="Settings" subtitle="Manage your profile and account">
        <SettingsLayout>
            <section class="rounded-[22px] border border-hh-line bg-hh-card p-4 sm:p-[22px]">
                <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Appearance</h3>
                <p class="mt-1 text-[13px] text-hh-ink3">Choose how HouseHub looks on this device.</p>

                <AppearanceTabs class="mt-5" />
            </section>
        </SettingsLayout>
    </HouseHubLayout>
</template>
