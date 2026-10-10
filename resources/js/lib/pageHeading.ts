import { reactive } from 'vue';

/**
 * What the persistent app shell (layouts/RyhShell.vue) shows in its
 * header. Each screen sets it through `<RyhLayout title subtitle>`, so the
 * shell can stay mounted while only the page content changes.
 */
export const pageHeading = reactive<{ title: string; subtitle?: string }>({ title: '' });
