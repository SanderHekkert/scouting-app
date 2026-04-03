<script setup>
import { computed, ref } from 'vue';
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
    { label: 'Dolfijnen', route: 'members.index' },
    { label: 'Leiding', route: 'leaders.index' },
    { label: 'Tipper- & Topper opkomst', route: 'tipper-topper-opkomst.index' },
    { label: 'Vinindeling', route: 'pods.index' },
    { label: 'Belangrijke info', route: 'info-notes.index' },
    { label: 'Taakverdeling', route: 'task-items.index' },
];

const mobileMenuOpen = ref(false);

const activeMobileLabel = computed(() => {
    const active = links.find((item) => route().current(item.route));
    if (active) return active.label;
    if (route().current('profile.edit')) return 'Profiel';
    return 'Menu';
});

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}
</script>

<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <div class="flex min-h-screen">
            <aside
                class="sticky top-0 hidden h-screen w-72 shrink-0 flex-col overflow-y-auto border-r border-gray-200 bg-white p-5 shadow-sm md:flex dark:border-gray-700 dark:bg-gray-800"
            >
                <h1 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                    Fridtjof Nansen 12
                </h1>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Dolfijnen Applicatie</p>

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

            <main class="flex-1 p-4 md:p-6">
                <div class="mb-4 md:hidden">
                    <div class="sticky top-2 z-40 rounded-xl border border-gray-200 bg-white/95 p-2 shadow-sm backdrop-blur dark:border-gray-700 dark:bg-gray-800/95">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-lg border border-gray-300 bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-800 transition hover:bg-gray-100 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 dark:hover:bg-gray-700"
                            :aria-expanded="mobileMenuOpen"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                        >
                            <span class="truncate">{{ activeMobileLabel }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ mobileMenuOpen ? 'Sluiten' : 'Menu' }}</span>
                        </button>
                        <div v-if="mobileMenuOpen" class="pt-2">
                            <nav class="grid grid-cols-2 gap-1">
                                <Link
                                    v-for="item in links"
                                    :key="`mobile-${item.route}`"
                                    :href="route(item.route)"
                                    class="rounded-lg px-3 py-2.5 text-xs font-medium transition"
                                    :class="route().current(item.route) ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700'"
                                    @click="closeMobileMenu"
                                >
                                    {{ item.label }}
                                </Link>
                                <Link
                                    :href="route('profile.edit')"
                                    class="rounded-lg px-3 py-2.5 text-xs font-medium transition"
                                    :class="route().current('profile.edit') ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700'"
                                    @click="closeMobileMenu"
                                >
                                    Profiel
                                </Link>
                            </nav>
                        </div>
                    </div>
                </div>
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
