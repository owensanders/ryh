import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { useEchoNotification } from '@laravel/echo-vue';
import { ref } from 'vue';

export interface AppNotification {
    id: string;
    body: string;
    url: string;
    read_at: string | null;
}

interface NotificationRow {
    id: string;
    data: { body: string; user_id: number; url: string; [key: string]: unknown };
    read_at: string | null;
}

interface LiveNotificationPayload {
    id: string;
    body: string;
    url: string;
    user_id: number;
}

export const notifications = ref<AppNotification[]>([]);
export const latestToast = ref<string | null>(null);

/** Loads existing notifications and starts listening for live ones. Call once, from the app layout. */
export function subscribeToNotifications(): void {
    const page = usePage<SharedData>();
    const householdId = page.props.household?.id;
    const userId = page.props.auth.user?.id;

    if (householdId === undefined) {
        return;
    }

    fetch(route('notifications.index'), { headers: { Accept: 'application/json' } })
        .then((response) => response.json())
        .then((rows: NotificationRow[]) => {
            notifications.value = rows.map((row) => ({ id: row.id, body: row.data.body, url: row.data.url, read_at: row.read_at }));
        });

    useEchoNotification<LiveNotificationPayload>(`household.${householdId}`, (notification) => {
        if (notification.user_id !== userId) {
            return;
        }

        notifications.value.unshift({ id: notification.id, body: notification.body, url: notification.url, read_at: null });
        latestToast.value = notification.body;
    });
}

/** Marks the notification read and lands on its page — the server redirects there in the same request. */
export function openNotification(notification: AppNotification): void {
    notification.read_at = new Date().toISOString();

    router.patch(route('notifications.read', { id: notification.id }));
}

export function markAllAsRead(): void {
    notifications.value.forEach((notification) => (notification.read_at = new Date().toISOString()));

    router.post(route('notifications.readAll'), {}, { preserveScroll: true, preserveState: true, only: [] });
}

export function deleteAll(): void {
    notifications.value = [];

    router.delete(route('notifications.destroyAll'), { preserveScroll: true, preserveState: true, only: [] });
}
