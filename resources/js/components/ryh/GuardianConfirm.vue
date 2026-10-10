<script setup lang="ts">
// The confirmation the Terms of Service require before anyone under 18 joins a
// household: whoever adds a Teen or Child vouches for being their parent or
// guardian (or having that person's permission). The server records who and when.
defineProps<{ id: string; who?: string }>();

const confirmed = defineModel<boolean>({ required: true });
</script>

<template>
    <div
        class="flex items-start gap-3 rounded-[14px] border p-3.5 transition-colors"
        :class="confirmed ? 'border-ryh-mint bg-ryh-card' : 'border-ryh-sun bg-transparent'"
    >
        <button
            type="button"
            role="checkbox"
            :aria-checked="confirmed"
            :aria-labelledby="`${id}-label`"
            class="mt-0.5 grid h-[21px] w-[21px] flex-none place-items-center rounded-[7px] border-[1.5px] text-[11px] font-extrabold text-[#0E1A2B] transition-colors"
            :class="confirmed ? 'border-ryh-mint bg-ryh-mint' : 'border-ryh-line bg-transparent'"
            @click="confirmed = !confirmed"
        >
            {{ confirmed ? '✓' : '' }}
        </button>
        <span :id="`${id}-label`" class="flex-1 cursor-pointer text-[13px] leading-relaxed text-ryh-ink2" @click="confirmed = !confirmed">
            I'm the parent or guardian of {{ who ?? 'the under-18s I am adding' }}, or I have their parent or guardian's permission. I'm responsible
            for their use of RYH and agree to the
            <a :href="route('terms')" target="_blank" class="font-semibold text-ryh-ink underline underline-offset-2" @click.stop>Terms of Service</a>
            on their behalf.
        </span>
    </div>
</template>
