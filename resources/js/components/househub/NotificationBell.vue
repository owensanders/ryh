<script setup lang="ts">
import { deleteAll, markAllAsRead, notifications, openNotification, subscribeToNotifications } from '@/composables/useNotifications';
import { onClickOutside } from '@vueuse/core';
import { Bell } from 'lucide-vue-next';
import { computed, ref } from 'vue';

subscribeToNotifications();

const open = ref(false);
const root = ref<HTMLElement | null>(null);

onClickOutside(root, () => (open.value = false));

const unreadCount = computed(() => notifications.value.filter((notification) => notification.read_at === null).length);

function toggle(): void {
    open.value = !open.value;
}
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            title="Notifications"
            class="grid h-[38px] w-[38px] place-items-center rounded-xl bg-hh-soft text-sm transition-colors hover:bg-hh-line"
            @click="toggle"
        >
            <Bell class="h-4 w-4" />
            <span
                v-if="unreadCount > 0"
                class="absolute right-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-hh-coral px-1 text-[9px] font-bold text-white"
            >
                {{ unreadCount }}
            </span>
        </button>

        <div
            v-if="open"
            class="fixed inset-x-3 top-[68px] z-50 rounded-2xl border border-hh-line bg-hh-card p-2 shadow-hh sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-80"
        >
            <div class="flex items-center justify-between px-2 py-1.5">
                <span class="text-[13px] font-extrabold">Notifications</span>
                <div class="flex items-center gap-2">
                    <button
                        v-if="unreadCount > 0"
                        type="button"
                        class="text-[11.5px] font-semibold text-hh-ink3 hover:text-hh-ink"
                        @click="markAllAsRead"
                    >
                        Mark all as read
                    </button>
                    <button
                        v-if="notifications.length > 0"
                        type="button"
                        class="text-[11.5px] font-semibold text-hh-ink3 hover:text-hh-ink"
                        @click="deleteAll"
                    >
                        Delete all
                    </button>
                </div>
            </div>

            <div v-if="notifications.length === 0" class="px-2 py-6 text-center text-[12.5px] text-hh-ink3">Nothing yet.</div>

            <button
                v-for="notification in notifications"
                :key="notification.id"
                type="button"
                class="flex w-full items-start gap-2 rounded-xl px-2 py-2 text-left text-[13px] transition-colors hover:bg-hh-soft"
                @click="openNotification(notification)"
            >
                <span class="mt-1.5 h-1.5 w-1.5 flex-none rounded-full" :class="notification.read_at === null ? 'bg-hh-coral' : 'bg-transparent'" />
                <span :class="notification.read_at === null ? 'font-semibold text-hh-ink' : 'text-hh-ink3'">{{ notification.body }}</span>
            </button>
        </div>
    </div>
</template>
