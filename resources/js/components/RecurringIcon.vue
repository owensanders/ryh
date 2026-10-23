<script setup lang="ts">
import IconButton from '@/components/ui/IconButton.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Repeat } from 'lucide-vue-next';
import { ref } from 'vue';

// Hover/focus shows the hint on desktop; radix tooltips ignore touch, so a tap
// opens it too (tapping elsewhere closes it).
const open = ref(false);
</script>

<template>
    <TooltipProvider :delay-duration="150">
        <Tooltip v-model:open="open" disable-closing-trigger>
            <TooltipTrigger as-child>
                <IconButton
                    label="Repeats every month"
                    class="-m-1 grid flex-none place-items-center rounded p-1 text-ryh-ink3 hover:text-ryh-ink"
                    @click="open = true"
                >
                    <Repeat class="h-3 w-3" />
                </IconButton>
            </TooltipTrigger>
            <TooltipContent class="px-2 py-1 text-xs">Repeats every month</TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
