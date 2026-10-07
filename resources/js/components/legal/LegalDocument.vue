<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps<{
    title: string;
    updated: string;
    version: string;
    sections: { id: string; title: string }[];
}>();
</script>

<template>
    <Head :title="title" />

    <PublicLayout>
        <div class="mx-auto max-w-[760px] px-5 pb-20 pt-12 sm:px-8 lg:pt-16">
            <h1 class="text-[34px] font-black leading-[1.05] tracking-[-0.035em] sm:text-[44px]">{{ title }}</h1>
            <p class="mt-3 text-sm text-hh-ink3">Last updated {{ updated }} · version {{ version }}</p>

            <div class="legal-intro mt-6 text-pretty text-[15.5px] leading-relaxed text-hh-ink2">
                <slot name="intro" />
            </div>

            <nav aria-label="Contents" class="mt-8 rounded-[18px] border border-hh-line bg-hh-panel p-5 sm:p-6">
                <div class="text-xs font-bold uppercase tracking-[0.08em] text-hh-ink3">Contents</div>
                <ol class="mt-3 grid list-decimal gap-1.5 pl-5 text-[14.5px] sm:grid-cols-2 sm:gap-x-8">
                    <li v-for="section in sections" :key="section.id">
                        <a :href="`#${section.id}`" class="font-semibold text-hh-ink hover:text-hh-coral">{{ section.title }}</a>
                    </li>
                </ol>
            </nav>

            <article class="legal-prose mt-4">
                <slot />
            </article>
        </div>
    </PublicLayout>
</template>

<style scoped>
.legal-prose {
    color: var(--hh-ink2);
    font-size: 15.5px;
    line-height: 1.7;
    counter-reset: legal-section;
}
.legal-prose :deep(h2) {
    counter-increment: legal-section;
    margin-top: 2.75rem;
    scroll-margin-top: 96px;
    color: var(--hh-ink);
    font-size: 22px;
    font-weight: 800;
    letter-spacing: -0.02em;
    line-height: 1.25;
}
.legal-prose :deep(h2)::before {
    content: counter(legal-section) '. ';
}
.legal-prose :deep(h3) {
    margin-top: 1.5rem;
    color: var(--hh-ink);
    font-size: 16.5px;
    font-weight: 700;
}
.legal-prose :deep(p),
.legal-prose :deep(ul),
.legal-prose :deep(ol),
.legal-prose :deep(table) {
    margin-top: 0.85rem;
}
.legal-prose :deep(ul) {
    list-style: disc;
    padding-left: 1.4rem;
}
.legal-prose :deep(ol) {
    list-style: lower-alpha;
    padding-left: 1.4rem;
}
.legal-prose :deep(li) {
    margin-top: 0.35rem;
}
.legal-prose :deep(strong) {
    color: var(--hh-ink);
}
.legal-intro :deep(a),
.legal-prose :deep(a) {
    color: var(--hh-ink);
    font-weight: 600;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.legal-prose :deep(.legal-table) {
    display: block;
    overflow-x: auto;
}
.legal-prose :deep(table) {
    width: 100%;
    border-collapse: collapse;
    font-size: 14px;
}
.legal-prose :deep(th),
.legal-prose :deep(td) {
    border-bottom: 1px solid var(--hh-line);
    padding: 0.6rem 0.75rem 0.6rem 0;
    text-align: left;
    vertical-align: top;
}
.legal-prose :deep(th) {
    color: var(--hh-ink);
    font-weight: 700;
}
.legal-prose :deep(.legal-callout) {
    margin-top: 1rem;
    border-radius: 14px;
    background: var(--hh-soft);
    padding: 0.9rem 1.1rem;
}
</style>
