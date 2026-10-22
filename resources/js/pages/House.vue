<script setup lang="ts">
import HouseHero from '@/components/ryh/house/HouseHero.vue';
import HouseMembers from '@/components/ryh/house/HouseMembers.vue';
import HousePlan from '@/components/ryh/house/HousePlan.vue';
import HouseRoles from '@/components/ryh/house/HouseRoles.vue';
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
                    <HouseRoles :house="house" />
                </div>
            </div>
        </div>
    </RyhLayout>
</template>
