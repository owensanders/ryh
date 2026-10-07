import { disablePush } from '@/lib/push';
import { router } from '@inertiajs/vue3';

/**
 * Signs out, first turning push off for this browser so it stops getting the
 * account's notifications — it may be a shared or handed-on phone. Use this
 * instead of a plain `<Link :href="route('logout')" method="post">`.
 */
export async function logout(): Promise<void> {
    await disablePush().catch(() => undefined);
    router.post(route('logout'));
}
