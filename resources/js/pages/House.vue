<script setup lang="ts">
import HouseHero from '@/components/house/HouseHero.vue';
import HouseMembers from '@/components/house/HouseMembers.vue';
import HousePlan from '@/components/house/HousePlan.vue';
import HouseRoles from '@/components/house/HouseRoles.vue';
import HouseTimezone from '@/components/house/HouseTimezone.vue';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import type { HouseData } from '@/types/generated';

defineOptions({ layout: RyhShell });

defineProps<{ house: HouseData }>();
</script>

<template>
    <RyhLayout title="House" subtitle="House information">
        <div class="flex animate-ryh-rise flex-col gap-4">
            <HouseHero :house="house" />

            <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-[1.5fr_1fr]">
                <HouseMembers :house="house" />

                <div class="flex flex-col gap-4">
                    <HousePlan :house="house" />
                    <HouseTimezone :house="house" />
                    <HouseRoles :house="house" />
                </div>
            </div>
        </div>
    </RyhLayout>
</template>
