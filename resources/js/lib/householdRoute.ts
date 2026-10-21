import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import type { App } from 'vue';
import { route as ziggyRoute, type Config } from 'ziggy-js';

// Written into the page by Blade's @routes.
declare const Ziggy: Config;

const page = usePage<SharedData>();

/**
 * Ziggy's route(), with {household} filled in from the page this tab is
 * showing, read at the moment of the call. So route('chores.index') is this
 * tab's household even when another tab is on a different one, and a page
 * rendering straight after a household switch already links to the new one.
 * (Ziggy's own default comes from the server once per full page load, so it
 * would lag a switch made without one.)
 */
export const route = ((name?: string, params?: unknown, absolute?: boolean) => {
    const slug = page.props.household?.slug;
    const config = slug ? { ...Ziggy, defaults: { ...Ziggy.defaults, household: slug } } : undefined;

    return ziggyRoute(name as never, params as never, absolute, config);
}) as typeof globalThis.route;

/** Puts it everywhere route() is used: templates, and the global the page scripts call. */
export const HouseholdRoutes = {
    install(app: App): void {
        app.config.globalProperties.route = route;
        globalThis.route = route;
    },
};
