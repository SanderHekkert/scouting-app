<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

const userInitials = computed(() => {
    const name = (page.props.auth?.user?.name || '').trim();
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    if (parts.length === 1 && parts[0].length >= 2) {
        return parts[0].slice(0, 2).toUpperCase();
    }
    if (parts.length === 1) {
        return (parts[0][0] || '?').toUpperCase();
    }
    return '?';
});

const links = [
    { label: 'Dashboard', route: 'dashboard' },
    { label: 'Agenda', route: 'events.index' },
    { label: 'Contacten', route: 'members.index' },
    { label: 'Tipper- & Topper opkomst', route: 'tipper-topper-opkomst.index' },
    { label: 'Vinindeling', route: 'pods.index' },
    { label: 'Belangrijke info', route: 'info-notes.index' },
    { label: 'Taakverdeling', route: 'task-items.index' },
];
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <div class="flex min-h-screen">
            <aside
                class="sticky top-0 flex h-screen w-72 shrink-0 flex-col overflow-y-auto border-r border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <h1 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    Fridtjof Nansen 12
                </h1>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dolfijnen administratie</p>

                <nav class="mt-6 space-y-2">
                    <Link
                        v-for="item in links"
                        :key="item.route"
                        :href="route(item.route)"
                        class="block rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="route().current(item.route) ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700'"
                    >
                        {{ item.label }}
                    </Link>
                </nav>

                <div class="mt-10 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <Link
                        :href="route('profile.edit')"
                        class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-gray-100 dark:hover:bg-gray-700"
                    >
                        <span
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white"
                            aria-hidden="true"
                        >
                            {{ userInitials }}
                        </span>
                        <span class="min-w-0 flex-1 text-left">
                            <span class="block truncate text-sm font-medium text-gray-800 dark:text-gray-200">{{
                                $page.props.auth.user.name
                            }}</span>
                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{
                                $page.props.auth.user.email
                            }}</span>
                        </span>
                    </Link>
                </div>
            </aside>

            <main class="flex-1 p-6">
                <header
                    v-if="$slots.header"
                    class="mb-6 w-full rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800"
                >
                    <slot name="header" />
                </header>
                <slot />
            </main>
        </div>
    </div>
</template>
