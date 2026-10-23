<script setup lang="ts">
import ShoppingListDialog from '@/components/shopping/ShoppingListDialog.vue';
import ShoppingListPanel from '@/components/shopping/ShoppingListPanel.vue';
import Button from '@/components/ui/Button.vue';
import { useHouseholdAbilities } from '@/composables/useHouseholdAbilities';
import RyhLayout from '@/layouts/RyhLayout.vue';
import RyhShell from '@/layouts/RyhShell.vue';
import type { ShoppingListData } from '@/types/generated';
import { Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';

defineOptions({ layout: RyhShell });

defineProps<{
    lists: ShoppingListData[];
    active: ShoppingListData | null;
}>();

const can = useHouseholdAbilities();

const listDialog = ref<InstanceType<typeof ShoppingListDialog> | null>(null);
</script>

<template>
    <RyhLayout title="Shopping Lists" :subtitle="`${lists.length} lists`">
        <div class="flex animate-ryh-rise flex-col items-start gap-5 lg:flex-row">
            <div class="flex w-full flex-none flex-col gap-1.5 lg:w-[232px]">
                <Button class="mb-1 w-auto self-start" v-if="can?.createShoppingLists" @click="listDialog?.openNew()">
                    <Plus class="h-4 w-4" :stroke-width="2.5" />
                    New list
                </Button>

                <Link
                    v-for="list in lists"
                    :key="list.id"
                    :href="route('shopping.index', { slug: list.slug })"
                    class="flex min-h-[48px] items-center gap-2.5 rounded-[14px] border px-3.5 text-left transition hover:bg-ryh-card"
                    :class="active?.id === list.id ? 'border-ryh-line bg-ryh-card' : 'border-transparent bg-transparent'"
                >
                    <span class="h-2.5 w-2.5 flex-none rounded-[3px]" :style="{ background: list.colour }"></span>
                    <span class="flex-1 text-sm font-semibold">{{ list.name }}</span>
                    <span class="font-mono text-[11.5px] text-ryh-ink3">{{ list.remaining }}/{{ list.total }}</span>
                </Link>
            </div>

            <ShoppingListPanel :active="active" @rename="listDialog?.openEdit($event)" />
        </div>

        <ShoppingListDialog ref="listDialog" />
    </RyhLayout>
</template>
