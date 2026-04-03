<script setup>
import { computed, ref } from 'vue';
import AppShellBackground from '@/Components/AppShellBackground.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
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
    <AppShellBackground>
        <div class="flex min-h-screen">
            <aside
                class="sticky top-0 hidden h-screen w-72 shrink-0 flex-row border-r border-white/25 bg-white/85 shadow-xl backdrop-blur-xl md:flex dark:border-white/10 dark:bg-slate-950/50"
            >
                <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-y-auto p-5">
                    <Link
                        :href="route('dashboard')"
                        class="mb-3 block rounded-lg outline-none transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-brand-blue/80"
                    >
                        <ApplicationLogo class="max-h-14 max-w-[13rem]" />
                    </Link>
                    <p class="text-lg font-bold leading-tight text-brand-blue-dark dark:text-app-ink-dark">
                        Fridtjof Nansen Groep 12
                    </p>
                    <p class="mt-1 text-xs text-app-muted dark:text-slate-400">Dolfijnen Applicatie</p>

                    <nav class="mt-6 space-y-2">
                        <Link
                            v-for="item in links"
                            :key="item.route"
                            :href="route(item.route)"
                            class="block rounded-lg px-3 py-2 text-sm font-medium transition"
                            :class="route().current(item.route) ? 'bg-brand-red/10 text-brand-red dark:bg-brand-blue/30 dark:text-white' : 'text-slate-800 hover:bg-brand-blue/10 dark:text-slate-200 dark:hover:bg-white/10'"
                        >
                            {{ item.label }}
                        </Link>
                    </nav>

                    <div class="mt-10 border-t border-slate-200/80 pt-4 dark:border-white/10">
                        <Link
                            :href="route('profile.edit')"
                            class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-brand-blue/10 dark:hover:bg-brand-blue/15"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-blue text-sm font-semibold text-white"
                                aria-hidden="true"
                            >
                                {{ userInitials }}
                            </span>
                            <span class="min-w-0 flex-1 text-left">
                                <span class="block truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{
                                    $page.props.auth.user.name
                                }}</span>
                                <span class="block truncate text-xs text-app-muted dark:text-slate-400">{{
                                    $page.props.auth.user.email
                                }}</span>
                            </span>
                        </Link>
                    </div>
                </div>
                <div
                    class="w-1.5 shrink-0 self-stretch bg-gradient-to-b from-brand-red via-brand-yellow to-brand-blue"
                    aria-hidden="true"
                />
            </aside>

            <main class="flex-1 p-4 md:p-6">
                <div class="mb-4 md:hidden">
                    <div class="surface-brand-top sticky top-2 z-40 rounded-xl border border-white/30 bg-white/90 p-2 shadow-lg backdrop-blur-md dark:border-white/10 dark:bg-slate-950/55">
                        <div class="mb-2 flex items-center gap-2">
                            <Link
                                :href="route('dashboard')"
                                class="shrink-0 rounded-md outline-none focus-visible:ring-2 focus-visible:ring-brand-blue/80"
                                @click="closeMobileMenu"
                            >
                                <ApplicationLogo class="max-h-10 max-w-[6.5rem]" />
                            </Link>
                            <button
                                type="button"
                                class="flex min-h-[2.75rem] flex-1 items-center justify-between rounded-lg border border-slate-200/80 bg-white/80 px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-brand-blue/10 dark:border-white/10 dark:bg-slate-900/40 dark:text-slate-100 dark:hover:bg-white/10"
                                :aria-expanded="mobileMenuOpen"
                                @click="mobileMenuOpen = !mobileMenuOpen"
                            >
                                <span class="min-w-0 truncate">{{ activeMobileLabel }}</span>
                                <span class="shrink-0 text-xs text-app-muted dark:text-slate-400">
                                    {{ mobileMenuOpen ? 'Sluiten' : 'Menu' }}
                                </span>
                            </button>
                        </div>
                        <div v-if="mobileMenuOpen" class="pt-2">
                            <nav class="grid grid-cols-2 gap-1">
                                <Link
                                    v-for="item in links"
                                    :key="`mobile-${item.route}`"
                                    :href="route(item.route)"
                                    class="rounded-lg px-3 py-2.5 text-xs font-medium transition"
                                    :class="route().current(item.route) ? 'bg-brand-red/10 text-brand-red dark:bg-brand-blue/30 dark:text-white' : 'text-slate-800 hover:bg-brand-blue/10 dark:text-slate-200 dark:hover:bg-white/10'"
                                    @click="closeMobileMenu"
                                >
                                    {{ item.label }}
                                </Link>
                                <Link
                                    :href="route('profile.edit')"
                                    class="rounded-lg px-3 py-2.5 text-xs font-medium transition"
                                    :class="route().current('profile.edit') ? 'bg-brand-red/10 text-brand-red dark:bg-brand-blue/30 dark:text-white' : 'text-slate-800 hover:bg-brand-blue/10 dark:text-slate-200 dark:hover:bg-white/10'"
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
                    class="surface-brand-top mb-6 w-full rounded-xl border border-white/35 bg-white/90 p-5 shadow-lg backdrop-blur-md dark:border-white/10 dark:bg-slate-950/50"
                >
                    <slot name="header" />
                </header>
                <slot />
            </main>
        </div>
    </AppShellBackground>
</template>
