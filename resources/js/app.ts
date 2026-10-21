import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { configureEcho } from '@laravel/echo-vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import OfflineBanner from './components/OfflineBanner.vue';
import { initializeTheme } from './composables/useAppearance';
import { HouseholdRoutes } from './lib/householdRoute';
import { initOfflineDetection } from './lib/offline';
import { initPwa } from './lib/pwa';

configureEcho({
    broadcaster: 'reverb',
});

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        // The offline banner sits beside every page, sign-in screens included.
        createApp({ render: () => [h(App, props), h(OfflineBanner)] })
            .use(plugin)
            .use(HouseholdRoutes)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Install prompt, service worker and refresh-on-resume for the installed app.
initPwa();
initOfflineDetection();
