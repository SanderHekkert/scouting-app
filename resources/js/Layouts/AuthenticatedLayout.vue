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

const mainNavItems = [
    { label: 'Dashboard', route: 'dashboard' },
    { label: 'Agenda', route: 'events.index', matchRoutes: ['events.*', 'jaar-thema'] },
];

/** Eén sidebar-link; subpagina’s bereik je via DolfijnenSubnav op de pagina zelf. */
const dolfijnenNavItem = {
    label: 'Dolfijnen',
    route: 'members.index',
    matchRoutes: [
        'members.index',
        'members.show',
        'members.bijzonderheden',
        'tipper-topper-opkomst.*',
        'pods.*',
    ],
};

const tailNavItems = [
    { label: 'Leiding', route: 'leaders.index', matchRoutes: ['leaders.*'] },
    { label: 'Belangrijke info', route: 'info-notes.index', matchRoutes: ['info-notes.*'] },
    { label: 'Taakverdeling', route: 'task-items.index', matchRoutes: ['task-items.*', 'task-categories.*'] },
];

const mobileMenuOpen = ref(false);

function navItemIsActive(item) {
    if (item.matchRoutes?.length) {
        return item.matchRoutes.some((pattern) => route().current(pattern));
    }
    return route().current(item.route);
}

const activeMobileLabel = computed(() => {
    if (navItemIsActive(dolfijnenNavItem)) {
        return dolfijnenNavItem.label;
    }
    const inMain = mainNavItems.find((item) => navItemIsActive(item));
    if (inMain) {
        return inMain.label;
    }
    const inTail = tailNavItems.find((item) => navItemIsActive(item));
    if (inTail) {
        return inTail.label;
    }
    if (route().current('profile.edit')) {
        return 'Profiel';
    }
    return 'Menu';
});

function closeMobileMenu() {
    mobileMenuOpen.value = false;
}
</script>

<template>
    <AppShellBackground>
        <div class="relative min-h-screen">
            <aside
                class="fixed inset-y-0 start-0 z-30 hidden h-screen w-72 flex-row border-e border-slate-200 bg-white shadow-xl md:flex dark:border-slate-200 dark:bg-white"
            >
                <div class="flex h-full min-h-0 min-w-0 flex-1 flex-col p-5">
                    <div class="shrink-0">
                        <Link
                            :href="route('dashboard')"
                            class="mb-3 block rounded-lg outline-none transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-brand-blue/80"
                        >
                            <ApplicationLogo class="max-h-14 max-w-[13rem]" />
                        </Link>
                        <p class="text-lg font-bold leading-tight text-brand-blue-dark">
                            Fridtjof Nansen Groep 12
                        </p>
                        <p class="mt-1 text-xs text-app-muted">Dolfijnen Applicatie</p>
                    </div>

                    <nav
                        class="mt-6 flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto overscroll-contain [-webkit-overflow-scrolling:touch]"
                        aria-label="Hoofdnavigatie"
                    >
                        <Link
                            v-for="item in mainNavItems"
                            :key="item.route"
                            :href="route(item.route)"
                            class="block shrink-0 rounded-lg px-3 py-2 text-sm font-medium transition"
                            :class="
                                navItemIsActive(item)
                                    ? 'bg-brand-red/10 text-brand-red'
                                    : 'text-slate-800 hover:bg-brand-blue/10'
                            "
                        >
                            {{ item.label }}
                        </Link>

                        <Link
                            :href="route(dolfijnenNavItem.route)"
                            class="block shrink-0 rounded-lg px-3 py-2 text-sm font-medium transition"
                            :class="
                                navItemIsActive(dolfijnenNavItem)
                                    ? 'bg-brand-red/10 text-brand-red'
                                    : 'text-slate-800 hover:bg-brand-blue/10'
                            "
                        >
                            {{ dolfijnenNavItem.label }}
                        </Link>

                        <Link
                            v-for="item in tailNavItems"
                            :key="item.route"
                            :href="route(item.route)"
                            class="block shrink-0 rounded-lg px-3 py-2 text-sm font-medium transition"
                            :class="
                                navItemIsActive(item)
                                    ? 'bg-brand-red/10 text-brand-red'
                                    : 'text-slate-800 hover:bg-brand-blue/10'
                            "
                        >
                            {{ item.label }}
                        </Link>
                    </nav>

                    <div class="mt-auto shrink-0 border-t border-slate-200 pt-4">
                        <Link
                            :href="route('profile.edit')"
                            class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-brand-blue/10"
                        >
                            <span
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-blue text-sm font-semibold text-white"
                                aria-hidden="true"
                            >
                                {{ userInitials }}
                            </span>
                            <span class="min-w-0 flex-1 text-left">
                                <span class="block truncate text-sm font-medium text-slate-900">{{
                                    $page.props.auth.user.name
                                }}</span>
                                <span class="block truncate text-xs text-app-muted">{{
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

            <main class="min-h-screen min-w-0 p-4 md:ms-72 md:p-6">
                <div class="mb-4 md:hidden">
                    <div class="surface-brand-top sticky top-2 z-40 rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-200 dark:bg-white">
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
                                class="flex min-h-[2.75rem] flex-1 items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-800 transition hover:bg-brand-blue/10"
                                :aria-expanded="mobileMenuOpen"
                                @click="mobileMenuOpen = !mobileMenuOpen"
                            >
                                <span class="min-w-0 truncate">{{ activeMobileLabel }}</span>
                                <span class="shrink-0 text-xs text-app-muted">
                                    {{ mobileMenuOpen ? 'Sluiten' : 'Menu' }}
                                </span>
                            </button>
                        </div>
                        <div v-if="mobileMenuOpen" class="pt-2">
                            <nav class="flex max-h-[min(70vh,28rem)] flex-col gap-1 overflow-y-auto">
                                <Link
                                    v-for="item in mainNavItems"
                                    :key="`mobile-${item.route}`"
                                    :href="route(item.route)"
                                    class="rounded-lg px-3 py-2.5 text-xs font-medium transition"
                                    :class="
                                        navItemIsActive(item)
                                            ? 'bg-brand-red/10 text-brand-red'
                                            : 'text-slate-800 hover:bg-brand-blue/10'
                                    "
                                    @click="closeMobileMenu"
                                >
                                    {{ item.label }}
                                </Link>
                                <Link
                                    :href="route(dolfijnenNavItem.route)"
                                    class="rounded-lg px-3 py-2.5 text-xs font-medium transition"
                                    :class="
                                        navItemIsActive(dolfijnenNavItem)
                                            ? 'bg-brand-red/10 text-brand-red'
                                            : 'text-slate-800 hover:bg-brand-blue/10'
                                    "
                                    @click="closeMobileMenu"
                                >
                                    {{ dolfijnenNavItem.label }}
                                </Link>
                                <Link
                                    v-for="item in tailNavItems"
                                    :key="`mobile-${item.route}`"
                                    :href="route(item.route)"
                                    class="rounded-lg px-3 py-2.5 text-xs font-medium transition"
                                    :class="
                                        navItemIsActive(item)
                                            ? 'bg-brand-red/10 text-brand-red'
                                            : 'text-slate-800 hover:bg-brand-blue/10'
                                    "
                                    @click="closeMobileMenu"
                                >
                                    {{ item.label }}
                                </Link>
                                <Link
                                    :href="route('profile.edit')"
                                    class="rounded-lg px-3 py-2.5 text-xs font-medium transition"
                                    :class="route().current('profile.edit') ? 'bg-brand-red/10 text-brand-red' : 'text-slate-800 hover:bg-brand-blue/10'"
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
