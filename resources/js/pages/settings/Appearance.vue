<script setup lang="ts">
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Card from '@/components/ui/Card.vue';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

defineOptions({ layout: RyhShell });
</script>

<template>
    <RyhLayout title="Settings" subtitle="Manage your profile and account">
        <SettingsLayout>
            <Card as="section">
                <h3 class="text-[15px] font-extrabold tracking-[-0.01em]">Appearance</h3>
                <p class="mt-1 text-[13px] text-ryh-ink3">Choose how RYH looks on this device.</p>

                <AppearanceTabs class="mt-5" />
            </Card>
        </SettingsLayout>
    </RyhLayout>
</template>
