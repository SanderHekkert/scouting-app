<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import AppShellBackground from '@/Components/AppShellBackground.vue';
import AppMobileNav from '@/Layouts/Partials/AppMobileNav.vue';
import AppSidebarDesktop from '@/Layouts/Partials/AppSidebarDesktop.vue';
import BoatIcon from '@/Components/BoatIcon.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BookOpenIcon,
    CalendarDaysIcon,
    ClipboardDocumentListIcon,
    CircleStackIcon,
    CurrencyEuroIcon,
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
        active: 'bg-emerald-600/20 text-emerald-700 dark:bg-emerald-400/45 dark:text-emerald-50',
        inactive: 'bg-emerald-500/10 text-emerald-700 hover:bg-emerald-500/20 dark:bg-emerald-400/25 dark:text-emerald-100 dark:hover:bg-emerald-400/35',
    },
    zeeverkenners: {
        active: 'bg-yellow-400/35 text-yellow-900 dark:bg-yellow-300/50 dark:text-yellow-950',
        inactive: 'bg-yellow-300/25 text-yellow-900 hover:bg-yellow-300/40 dark:bg-yellow-300/30 dark:text-yellow-100 dark:hover:bg-yellow-300/40',
    },
    loodsen: {
        active: 'bg-purple-600/25 text-purple-800 dark:bg-purple-400/45 dark:text-purple-50',
        inactive: 'bg-purple-500/15 text-purple-800 hover:bg-purple-500/25 dark:bg-purple-400/25 dark:text-purple-100 dark:hover:bg-purple-400/35',
    },
    bevers: {
        active: 'bg-red-600/20 text-red-700 dark:bg-red-400/45 dark:text-red-50',
        inactive: 'bg-red-500/10 text-red-700 hover:bg-red-500/20 dark:bg-red-400/25 dark:text-red-100 dark:hover:bg-red-400/35',
    },
    wilde_vaart: {
        active: 'bg-blue-600/20 text-blue-700 dark:bg-blue-400/45 dark:text-blue-50',
        inactive: 'bg-blue-500/10 text-blue-700 hover:bg-blue-500/20 dark:bg-blue-400/25 dark:text-blue-100 dark:hover:bg-blue-400/35',
    },
    bestuur: {
        active: 'bg-slate-600/20 text-slate-700 dark:bg-slate-300/50 dark:text-slate-950',
        inactive: 'bg-slate-500/10 text-slate-700 hover:bg-slate-500/20 dark:bg-slate-300/30 dark:text-slate-100 dark:hover:bg-slate-300/40',
    },
};
const sectionNavActiveClass = {
    dolfijnen: 'bg-emerald-600/15 text-emerald-800 ring-1 ring-emerald-600/25 dark:bg-emerald-400/35 dark:text-emerald-50 dark:ring-emerald-300/55',
    zeeverkenners: 'bg-yellow-400/25 text-yellow-900 ring-1 ring-yellow-500/35 dark:bg-yellow-300/45 dark:text-yellow-950 dark:ring-yellow-200/60',
    loodsen: 'bg-purple-600/15 text-purple-800 ring-1 ring-purple-600/25 dark:bg-purple-400/35 dark:text-purple-50 dark:ring-purple-300/55',
    bevers: 'bg-red-600/15 text-red-700 ring-1 ring-red-600/25 dark:bg-red-400/35 dark:text-red-50 dark:ring-red-300/55',
    wilde_vaart: 'bg-blue-600/15 text-blue-700 ring-1 ring-blue-600/25 dark:bg-blue-400/35 dark:text-blue-50 dark:ring-blue-300/55',
    bestuur: 'bg-slate-600/15 text-slate-700 ring-1 ring-slate-600/25 dark:bg-slate-300/45 dark:text-slate-950 dark:ring-slate-200/60',
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

/** Eén sidebar-link; subpagina’s bereik je via SpeltakSubnav op de pagina zelf. */
const membersNavItem = {
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
const showMembersNav = computed(
    () => canView(membersNavItem.module) && activeSection.value !== 'bestuur',
);

function allowed(item) {
    if (item.hideForBestuur && activeSection.value === 'bestuur') return false;
    if (item.module && !canView(item.module)) return false;
    return true;
}

const navSections = computed(() => {
    const planning = [
        { label: 'Dashboard', route: 'dashboard', module: 'dashboard', icon: HomeIcon },
        { label: 'Agenda', route: 'agenda.index', matchRoutes: ['agenda.*'], module: 'events', icon: CalendarDaysIcon },
        { label: 'Opkomsten', route: 'opkomsten.index', matchRoutes: ['opkomsten.*', 'jaar-thema'], module: 'events', icon: BoatIcon, hideForBestuur: true },
    ].filter(allowed);

    const team = [
        ...(showMembersNav.value ? [membersNavItem] : []),
        { label: 'Leiding', route: 'leaders.index', matchRoutes: ['leaders.*'], module: 'leaders', icon: UsersIcon },
        { label: 'Taakverdeling', route: 'task-items.index', matchRoutes: ['task-items.*', 'task-categories.*'], module: 'task_items', icon: ClipboardDocumentListIcon },
        { label: 'Belangrijke info', route: 'info-notes.index', matchRoutes: ['info-notes.*'], module: 'info_notes', icon: InformationCircleIcon },
        ...((canView('members') && (
            isAdmin.value
            || (activeSection.value === 'bestuur' && isBoardMember.value)
            || (page.props.auth?.section_roles || []).some((r) => r.section === activeSection.value && ['teamleider', 'ouder_contact'].includes(r.role))
        ))
            ? [{ label: 'Gezondheidsformulieren', route: 'admin.health-forms.index', matchRoutes: ['admin.health-forms.*'], icon: DocumentTextIcon }]
            : []),
    ].filter(allowed);

    const financeCamp = [
        { label: 'Potjes', route: 'finance.pots.index', matchRoutes: ['finance.pots.*'], module: 'financien', icon: CircleStackIcon },
        { label: 'Declaraties', route: 'finance.declarations.index', matchRoutes: ['finance.declarations.*'], module: 'financien', icon: CurrencyEuroIcon },
        { label: 'Begroting', route: 'camp-budgets.index', matchRoutes: ['camp-budgets.*'], module: 'camp_budgets', icon: BanknotesIcon },
        { label: 'Draaiboek', route: 'camp-playbooks.index', matchRoutes: ['camp-playbooks.*'], module: 'camp_playbooks', icon: BookOpenIcon },
    ].filter(allowed);

    const beheer = [
        { label: 'Pushmeldingen', route: 'admin.push-notifications.index', matchRoutes: ['admin.push-notifications.*'], icon: BellAlertIcon },
        ...((isAdmin.value || isBoardMember.value)
            ? [{ label: 'Gebruikers', route: 'admin.users.index', matchRoutes: ['admin.users.*'], icon: IdentificationIcon }]
            : []),
        ...((isAdmin.value || (page.props.auth?.section_roles || []).some((r) => r.section !== '*' && r.role === 'teamleider'))
            ? [{ label: 'Rechtenbeheer', route: 'permissions.index', matchRoutes: ['permissions.*'], icon: ShieldCheckIcon }]
            : []),
    ].filter(allowed);

    return [
        { key: 'planning', label: 'Planning', items: planning },
        { key: 'team', label: 'Team', items: team },
        { key: 'finance-camp', label: 'Financien & Kamp', items: financeCamp },
        { key: 'beheer', label: 'Beheer', items: beheer },
    ].filter((section) => section.items.length > 0);
});

const flatNavItems = computed(() => navSections.value.flatMap((section) => section.items));

const firstAccessibleRoute = computed(() => {
    const primary = flatNavItems.value[0]?.route;
    if (primary) return primary;
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
    const inNav = flatNavItems.value.find((item) => navItemIsActive(item));
    if (inNav) {
        return inNav.label;
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
            <AppSidebarDesktop
                :first-accessible-route="firstAccessibleRoute"
                :available-sections="availableSections"
                :active-section="activeSection"
                :section-button-class="sectionButtonClass"
                :section-labels="sectionLabels"
                :nav-sections="navSections"
                :nav-item-is-active="navItemIsActive"
                :active-nav-class="activeNavClass()"
                :user-initials="userInitials"
                :user="$page.props.auth?.user || {}"
                @switch-section="switchSection"
            />

            <AppMobileNav
                :mobile-menu-open="mobileMenuOpen"
                :first-accessible-route="firstAccessibleRoute"
                :active-mobile-label="activeMobileLabel"
                :nav-sections="navSections"
                :nav-item-is-active="navItemIsActive"
                :active-nav-class="activeNavClass()"
                :section-labels="sectionLabels"
                :active-section="activeSection"
                :available-sections="availableSections"
                :section-button-class="sectionButtonClass"
                @toggle-menu="mobileMenuOpen = !mobileMenuOpen"
                @close-menu="closeMobileMenu"
                @switch-section="switchSection"
            />

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
