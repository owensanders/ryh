import { showToast } from '@/composables/useToast';
import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { useEchoNotification } from '@laravel/echo-vue';
import { ref } from 'vue';

export interface AppNotification {
    id: string;
    body: string;
    url: string;
    readAt: string | null;
}

interface NotificationRow {
    id: string;
    data: { body: string; user_id: number; url: string; [key: string]: unknown };
    readAt: string | null;
}

interface LiveNotificationPayload {
    id: string;
    body: string;
    url: string;
}

export const notifications = ref<AppNotification[]>([]);

/** Loads existing notifications and starts listening for live ones. Call once, from the app layout. */
export function subscribeToNotifications(): void {
    const page = usePage<SharedData>();
    const userId = page.props.auth.user?.id;

    if (page.props.household === null || userId === undefined) {
        return;
    }

    // The bell is a nicety: if this fails it stays empty, and live ones still arrive.
    fetch(route('notifications.index'), { headers: { Accept: 'application/json' } })
        .then((response) => (response.ok ? (response.json() as Promise<NotificationRow[]>) : Promise.reject(new Error(`HTTP ${response.status}`))))
        .then((rows) => {
            notifications.value = rows.map((row) => ({ id: row.id, body: row.data.body, url: row.data.url, readAt: row.readAt }));
        })
        .catch(() => (notifications.value = []));

    // The server only sends someone their own notifications, on their own channel.
    useEchoNotification<LiveNotificationPayload>(`App.Models.User.${userId}`, (notification) => {
        notifications.value.unshift({ id: notification.id, body: notification.body, url: notification.url, readAt: null });
        showToast(notification.body);
    });
}

/** Marks the notification read, then goes to its page, even if marking it failed. */
export function openNotification(notification: AppNotification): void {
    notification.readAt = new Date().toISOString();

    router.patch(
        route('notifications.read', { id: notification.id }),
        {},
        { preserveScroll: true, preserveState: true, only: [], onFinish: () => router.visit(notification.url) },
    );
}

export function markAllAsRead(): void {
    notifications.value.forEach((notification) => (notification.readAt = new Date().toISOString()));

    router.post(route('notifications.readAll'), {}, { preserveScroll: true, preserveState: true, only: [] });
}

export function deleteAll(): void {
    notifications.value = [];

    router.delete(route('notifications.destroyAll'), { preserveScroll: true, preserveState: true, only: [] });
}
