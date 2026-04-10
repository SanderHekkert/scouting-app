<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppShellBackground from '@/Components/AppShellBackground.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Bars3Icon,
    CalendarDaysIcon,
    ClipboardDocumentListIcon,
    FlagIcon,
    BellAlertIcon,
    BanknotesIcon,
    DocumentTextIcon,
    HomeIcon,
    IdentificationIcon,
    InformationCircleIcon,
    ShieldCheckIcon,
    UserGroupIcon,
    UsersIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';

const page = usePage();
const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    loodsen: 'Loodsen',
    wilde_vaart: 'Wilde Vaart',
    bestuur: 'Bestuur',
};
const allSections = ['bevers', 'dolfijnen', 'zeeverkenners', 'wilde_vaart', 'loodsen', 'bestuur'];
const sectionButtonClass = {
    dolfijnen: {
        active: 'bg-emerald-600/20 text-emerald-700',
        inactive: 'bg-emerald-500/10 text-emerald-700 hover:bg-emerald-500/20',
    },
    zeeverkenners: {
        active: 'bg-yellow-400/35 text-yellow-900',
        inactive: 'bg-yellow-300/25 text-yellow-900 hover:bg-yellow-300/40',
    },
    loodsen: {
        active: 'bg-purple-600/25 text-purple-800',
        inactive: 'bg-purple-500/15 text-purple-800 hover:bg-purple-500/25',
    },
    bevers: {
        active: 'bg-red-600/20 text-red-700',
        inactive: 'bg-red-500/10 text-red-700 hover:bg-red-500/20',
    },
    wilde_vaart: {
        active: 'bg-blue-600/20 text-blue-700',
        inactive: 'bg-blue-500/10 text-blue-700 hover:bg-blue-500/20',
    },
    bestuur: {
        active: 'bg-slate-600/20 text-slate-700',
        inactive: 'bg-slate-500/10 text-slate-700 hover:bg-slate-500/20',
    },
};
const sectionNavActiveClass = {
    dolfijnen: 'bg-emerald-600/15 text-emerald-800 ring-1 ring-emerald-600/25',
    zeeverkenners: 'bg-yellow-400/25 text-yellow-900 ring-1 ring-yellow-500/35',
    loodsen: 'bg-purple-600/15 text-purple-800 ring-1 ring-purple-600/25',
    bevers: 'bg-red-600/15 text-red-700 ring-1 ring-red-600/25',
    wilde_vaart: 'bg-blue-600/15 text-blue-700 ring-1 ring-blue-600/25',
    bestuur: 'bg-slate-600/15 text-slate-700 ring-1 ring-slate-600/25',
};

const activeSection = computed(() => page.props.auth?.active_section || 'dolfijnen');
const permissionMap = computed(() => page.props.auth?.permissions || {});
const availableSections = computed(() => {
    if (isAdmin.value || isBoardMember.value) {
        return allSections;
    }
    const roles = page.props.auth?.section_roles || [];
    const allowed = new Set(roles.map((r) => r.section).filter((section) => section !== '*'));
    return allSections.filter((section) => allowed.has(section));
});
const isAdmin = computed(() =>
    (page.props.auth?.section_roles || []).some((r) => r.section === '*' && r.role === 'admin'),
);
const isBoardMember = computed(() =>
    (page.props.auth?.section_roles || []).some((r) => r.section === '*' && ['bestuurslid', 'penningmeester', 'secretaresse', 'voorzitter'].includes(r.role)),
);

function canView(module) {
    if (!module) return true;
    const row = permissionMap.value?.[module];
    if (!row) return false;
    return !!row.view;
}

function switchSection(section) {
    if (!section || section === activeSection.value) return;
    router.post(
        route('active-section.update'),
        { section },
        {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                // Forceer volledige herlaad zodat alle tabbladen/data direct matchen met de gekozen speltak.
                window.location.reload();
            },
        },
    );
}

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

const mainNavItems = computed(() => ([
    { label: 'Dashboard', route: 'dashboard', module: 'dashboard', icon: HomeIcon },
    { label: 'Agenda', route: 'agenda.index', matchRoutes: ['agenda.*'], module: 'events', icon: CalendarDaysIcon },
    { label: 'Opkomsten', route: 'opkomsten.index', matchRoutes: ['opkomsten.*', 'jaar-thema'], module: 'events', icon: FlagIcon, hideForBestuur: true },
    { label: 'Potjes', route: 'finance.pots.index', matchRoutes: ['finance.pots.*'], module: 'financien', icon: BanknotesIcon },
    { label: 'Declaraties', route: 'finance.declarations.index', matchRoutes: ['finance.declarations.*'], module: 'financien', icon: BanknotesIcon },
]).filter((item) => canView(item.module) && !(item.hideForBestuur && activeSection.value === 'bestuur')));

/** Eén sidebar-link; subpagina’s bereik je via SpeltakSubnav op de pagina zelf. */
const dolfijnenNavItem = {
    label: 'Leden',
    route: 'members.index',
    matchRoutes: [
        'members.index',
        'members.show',
        'members.bijzonderheden',
        'tipper-topper-opkomst.*',
        'pods.*',
    ],
    module: 'members',
    icon: UserGroupIcon,
};
const showSpeltakNav = computed(
    () => canView(dolfijnenNavItem.module) && activeSection.value !== 'bestuur',
);

const tailNavItems = computed(() => ([
    { label: 'Leiding', route: 'leaders.index', matchRoutes: ['leaders.*'], module: 'leaders', icon: UsersIcon },
    { label: 'Belangrijke info', route: 'info-notes.index', matchRoutes: ['info-notes.*'], module: 'info_notes', icon: InformationCircleIcon },
    { label: 'Taakverdeling', route: 'task-items.index', matchRoutes: ['task-items.*', 'task-categories.*'], module: 'task_items', icon: ClipboardDocumentListIcon },
    { label: 'Begroting', route: 'camp-budgets.index', matchRoutes: ['camp-budgets.*'], module: 'camp_budgets', icon: BanknotesIcon },
    { label: 'Draaiboek', route: 'camp-playbooks.index', matchRoutes: ['camp-playbooks.*'], module: 'camp_playbooks', icon: DocumentTextIcon },
    ...((isAdmin.value || isBoardMember.value)
        ? [{ label: 'Gebruikers', route: 'admin.users.index', matchRoutes: ['admin.users.*'], icon: IdentificationIcon }]
        : []),
    ...((activeSection.value === 'bestuur' && (isAdmin.value || isBoardMember.value))
        ? [{ label: 'Pushmeldingen', route: 'admin.push-notifications.index', matchRoutes: ['admin.push-notifications.*'], icon: BellAlertIcon }]
        : []),
    ...((canView('members') && (
        isAdmin.value
        || (activeSection.value === 'bestuur' && isBoardMember.value)
        || (page.props.auth?.section_roles || []).some((r) => r.section === activeSection.value && ['teamleider', 'ouder_contact'].includes(r.role))
    ))
        ? [{ label: 'Gezondheidsformulieren', route: 'admin.health-forms.index', matchRoutes: ['admin.health-forms.*'], icon: DocumentTextIcon }]
        : []),
    ...((isAdmin.value || (page.props.auth?.section_roles || []).some((r) => r.section !== '*' && r.role === 'teamleider'))
        ? [{ label: 'Rechtenbeheer', route: 'permissions.index', matchRoutes: ['permissions.*'], icon: ShieldCheckIcon }]
        : []),
]).filter((item) => !item.module || canView(item.module)));

const firstAccessibleRoute = computed(() => {
    const primary = mainNavItems.value[0]?.route;
    if (primary) return primary;
    if (showSpeltakNav.value) return dolfijnenNavItem.route;
    const secondary = tailNavItems.value[0]?.route;
    if (secondary) return secondary;
    return 'profile.edit';
});

const mobileMenuOpen = ref(false);

function navItemIsActive(item) {
    if (item.matchRoutes?.length) {
        return item.matchRoutes.some((pattern) => route().current(pattern));
    }
    return route().current(item.route);
}

function activeNavClass() {
    return sectionNavActiveClass[activeSection.value] || 'bg-brand-red/10 text-brand-red ring-1 ring-brand-red/25';
}

const activeMobileLabel = computed(() => {
    if (showSpeltakNav.value && navItemIsActive(dolfijnenNavItem)) {
        return dolfijnenNavItem.label;
    }
    const inMain = mainNavItems.value.find((item) => navItemIsActive(item));
    if (inMain) {
        return inMain.label;
    }
    const inTail = tailNavItems.value.find((item) => navItemIsActive(item));
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

/** Voorkom achtergrond-scroll als het mobiele menu open is. */
watch(mobileMenuOpen, (open) => {
    if (typeof document === 'undefined') {
        return;
    }
    document.body.style.overflow = open ? 'hidden' : '';
});

function onDocumentEscape(e) {
    if (e.key === 'Escape') {
        closeMobileMenu();
    }
}

onMounted(() => {
    document.addEventListener('keydown', onDocumentEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', onDocumentEscape);
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <AppShellBackground>
        <div class="relative min-h-screen">
            <aside
                class="fixed inset-y-0 start-0 z-30 hidden h-screen flex-row border-e border-slate-200 bg-white shadow-xl xl:flex xl:w-72 dark:border-slate-200 dark:bg-white"
            >
                <div class="flex h-full min-h-0 min-w-0 flex-1 flex-col p-5">
                    <div class="shrink-0">
                        <Link
                            :href="route(firstAccessibleRoute)"
                            class="mb-3 block rounded-lg outline-none transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-brand-blue/80"
                        >
                            <ApplicationLogo class="max-h-14 max-w-[13rem]" />
                        </Link>
                        <p class="text-lg font-bold leading-tight text-brand-blue-dark">
                            Fridtjof Nansen Groep 12
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                v-for="section in availableSections"
                                :key="`desktop-section-${section}`"
                                type="button"
                                class="rounded-md px-2.5 py-1 text-xs font-semibold transition"
                                :class="section === activeSection
                                    ? (sectionButtonClass[section]?.active || 'bg-brand-red/15 text-brand-red')
                                    : (sectionButtonClass[section]?.inactive || 'bg-brand-blue/10 text-brand-blue-dark hover:bg-brand-blue/20')"
                                @click="switchSection(section)"
                            >
                                {{ sectionLabels[section] || section }}
                            </button>
                        </div>
                    </div>

                    <nav
                        class="mt-6 flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto overscroll-contain [-webkit-overflow-scrolling:touch]"
                        aria-label="Hoofdnavigatie"
                    >
                        <Link
                            v-for="item in mainNavItems"
                            :key="`main-${item.label}-${item.href || item.route}`"
                            :href="route(item.route)"
                            class="block shrink-0 rounded-lg px-3 py-2 text-sm font-medium transition"
                            :class="
                                navItemIsActive(item)
                                    ? activeNavClass()
                                    : 'text-slate-800 hover:bg-brand-blue/10'
                            "
                        >
                            <span class="inline-flex items-center gap-2">
                                <component :is="item.icon" class="h-5 w-5 shrink-0 stroke-2" />
                                <span>{{ item.label }}</span>
                            </span>
                        </Link>

                        <Link
                            v-if="showSpeltakNav"
                            :href="route(dolfijnenNavItem.route)"
                            class="block shrink-0 rounded-lg px-3 py-2 text-sm font-medium transition"
                            :class="
                                navItemIsActive(dolfijnenNavItem)
                                    ? activeNavClass()
                                    : 'text-slate-800 hover:bg-brand-blue/10'
                            "
                        >
                            <span class="inline-flex items-center gap-2">
                                <component :is="dolfijnenNavItem.icon" class="h-5 w-5 shrink-0 stroke-2" />
                                <span>{{ dolfijnenNavItem.label }}</span>
                            </span>
                        </Link>

                        <Link
                            v-for="item in tailNavItems"
                            :key="item.route"
                            :href="route(item.route)"
                            class="block shrink-0 rounded-lg px-3 py-2 text-sm font-medium transition"
                            :class="
                                navItemIsActive(item)
                                    ? activeNavClass()
                                    : 'text-slate-800 hover:bg-brand-blue/10'
                            "
                        >
                            <span class="inline-flex items-center gap-2">
                                <component :is="item.icon" class="h-5 w-5 shrink-0 stroke-2" />
                                <span>{{ item.label }}</span>
                            </span>
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

            <!-- Mobiel: vaste topbalk + overlay-menu (safe areas, grote tikdoelen) -->
            <div
                class="xl:hidden fixed inset-x-0 top-0 z-50 border-b border-slate-200/90 bg-white/95 pt-[env(safe-area-inset-top,0px)] shadow-[0_4px_24px_-4px_rgba(0,0,0,0.12)] backdrop-blur-md dark:border-slate-700 dark:bg-slate-950/95 dark:shadow-black/30"
            >
                <div class="flex h-14 min-h-[3.5rem] items-center gap-2 px-3 sm:px-4">
                    <Link
                        :href="route(firstAccessibleRoute)"
                        class="touch-manipulation shrink-0 rounded-lg p-2 -ms-1 outline-none ring-brand-blue/80 focus-visible:ring-2 active:bg-slate-100 dark:active:bg-slate-800"
                        aria-label="Naar dashboard"
                        @click="closeMobileMenu"
                    >
                        <ApplicationLogo class="max-h-9 max-w-[5.75rem]" />
                    </Link>
                    <button
                        type="button"
                        class="touch-manipulation flex min-h-11 min-w-0 flex-1 items-center justify-between gap-2 rounded-xl border border-slate-200/90 bg-white px-3 py-2.5 text-left text-sm font-semibold text-slate-900 shadow-sm transition active:scale-[0.99] dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                        :aria-expanded="mobileMenuOpen"
                        aria-controls="app-mobile-nav"
                        @click="mobileMenuOpen = !mobileMenuOpen"
                    >
                        <span class="min-w-0 flex-1 truncate">{{ activeMobileLabel }}</span>
                        <span class="flex shrink-0 items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                            <Bars3Icon v-if="!mobileMenuOpen" class="h-6 w-6" aria-hidden="true" />
                            <XMarkIcon v-else class="h-6 w-6" aria-hidden="true" />
                            <span class="whitespace-nowrap">{{ mobileMenuOpen ? 'Sluiten' : 'Menu' }}</span>
                        </span>
                    </button>
                </div>
            </div>

            <div
                v-show="mobileMenuOpen"
                class="xl:hidden fixed inset-0 z-[44] bg-slate-950/45 backdrop-blur-[2px] dark:bg-black/55"
                aria-hidden="true"
                @click="closeMobileMenu"
            />

            <nav
                v-show="mobileMenuOpen"
                id="app-mobile-nav"
                class="xl:hidden fixed left-0 right-0 z-[45] max-h-[min(78dvh,32rem)] overflow-y-auto overscroll-contain rounded-b-2xl border-b border-slate-200 bg-white shadow-2xl [-webkit-overflow-scrolling:touch] dark:border-slate-700 dark:bg-slate-950"
                :style="{
                    top: 'calc(env(safe-area-inset-top, 0px) + 3.5rem)',
                }"
                aria-label="Mobiel menu"
            >
                <div class="space-y-1 px-3 py-3 pb-[max(1rem,env(safe-area-inset-bottom))]">
                    <Link
                        v-for="item in mainNavItems"
                        :key="`mobile-${item.label}-${item.href || item.route}`"
                        :href="route(item.route)"
                        class="flex min-h-12 items-center rounded-xl px-4 text-base font-medium transition touch-manipulation active:scale-[0.99]"
                        :class="
                            navItemIsActive(item)
                                ? activeNavClass()
                                : 'text-slate-800 hover:bg-brand-blue/10 active:bg-brand-blue/15 dark:text-slate-100 dark:hover:bg-brand-blue/15'
                        "
                        @click="closeMobileMenu"
                    >
                        <component :is="item.icon" class="me-2 h-6 w-6 shrink-0 stroke-2" />
                        <span>{{ item.label }}</span>
                    </Link>
                    <Link
                        v-if="showSpeltakNav"
                        :href="route(dolfijnenNavItem.route)"
                        class="flex min-h-12 items-center rounded-xl px-4 text-base font-medium transition touch-manipulation active:scale-[0.99]"
                        :class="
                            navItemIsActive(dolfijnenNavItem)
                                ? activeNavClass()
                                : 'text-slate-800 hover:bg-brand-blue/10 active:bg-brand-blue/15 dark:text-slate-100 dark:hover:bg-brand-blue/15'
                        "
                        @click="closeMobileMenu"
                    >
                        <component :is="dolfijnenNavItem.icon" class="me-2 h-6 w-6 shrink-0 stroke-2" />
                        <span>{{ dolfijnenNavItem.label }}</span>
                    </Link>
                    <Link
                        v-for="item in tailNavItems"
                        :key="`mobile-${item.route}`"
                        :href="route(item.route)"
                        class="flex min-h-12 items-center rounded-xl px-4 text-base font-medium transition touch-manipulation active:scale-[0.99]"
                        :class="
                            navItemIsActive(item)
                                ? activeNavClass()
                                : 'text-slate-800 hover:bg-brand-blue/10 active:bg-brand-blue/15 dark:text-slate-100 dark:hover:bg-brand-blue/15'
                        "
                        @click="closeMobileMenu"
                    >
                        <component :is="item.icon" class="me-2 h-6 w-6 shrink-0 stroke-2" />
                        <span>{{ item.label }}</span>
                    </Link>
                    <div class="my-2 border-t border-slate-200 dark:border-slate-700" />
                    <Link
                        :href="route('profile.edit')"
                        class="flex min-h-12 items-center rounded-xl px-4 text-base font-medium transition touch-manipulation active:scale-[0.99]"
                        :class="route().current('profile.edit') ? activeNavClass() : 'text-slate-800 hover:bg-brand-blue/10 dark:text-slate-100'"
                        @click="closeMobileMenu"
                    >
                        Profiel
                    </Link>
                    <div class="px-4 pt-2">
                        <p class="text-center text-[11px] text-slate-400 dark:text-slate-500">
                            FN12 · {{ sectionLabels[activeSection] || activeSection }}
                        </p>
                        <div class="mt-2 flex flex-wrap justify-center gap-2">
                            <button
                                v-for="section in availableSections"
                                :key="`mobile-section-${section}`"
                                type="button"
                                class="rounded-md px-2.5 py-1 text-xs font-semibold transition"
                                :class="section === activeSection
                                    ? (sectionButtonClass[section]?.active || 'bg-brand-red/15 text-brand-red')
                                    : (sectionButtonClass[section]?.inactive || 'bg-brand-blue/10 text-brand-blue-dark hover:bg-brand-blue/20')"
                                @click="switchSection(section)"
                            >
                                {{ sectionLabels[section] || section }}
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            <main
                class="min-h-screen min-w-0 px-3 pb-[max(1rem,env(safe-area-inset-bottom,0px))] pt-[calc(env(safe-area-inset-top,0px)+3.5rem+0.75rem)] xl:ms-72 xl:px-6 xl:pb-6 xl:pt-6"
            >
                <header
                    v-if="$slots.header"
                    class="surface-brand-top mb-4 w-full rounded-xl border border-white/35 bg-white/90 p-4 shadow-lg backdrop-blur-md sm:mb-6 sm:p-5 dark:border-white/10 dark:bg-slate-950/50"
                >
                    <slot name="header" />
                </header>
                <div class="min-w-0">
                    <slot />
                </div>
            </main>
        </div>
    </AppShellBackground>
</template>
