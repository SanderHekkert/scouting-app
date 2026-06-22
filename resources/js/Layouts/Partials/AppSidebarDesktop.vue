<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { CheckBadgeIcon } from '@heroicons/vue/24/outline';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const SIDEBAR_NAV_SCROLL_KEY = 'app-sidebar-nav-scroll';

const navRef = ref(null);

function saveNavScroll() {
    if (navRef.value) {
        sessionStorage.setItem(SIDEBAR_NAV_SCROLL_KEY, String(navRef.value.scrollTop));
    }
}

function restoreNavScroll() {
    const saved = sessionStorage.getItem(SIDEBAR_NAV_SCROLL_KEY);
    if (saved !== null && navRef.value) {
        navRef.value.scrollTop = Number(saved);
    }
}

onMounted(() => {
    nextTick(restoreNavScroll);
    navRef.value?.addEventListener('scroll', saveNavScroll, { passive: true });
});

onBeforeUnmount(() => {
    saveNavScroll();
    navRef.value?.removeEventListener('scroll', saveNavScroll);
});

const props = defineProps({
    firstAccessibleRoute: { type: String, required: true },
    availableSections: { type: Array, required: true },
    activeSection: { type: String, required: true },
    sectionButtonClass: { type: Object, required: true },
    sectionLabels: { type: Object, required: true },
    navSections: { type: Array, required: true },
    navItemIsActive: { type: Function, required: true },
    activeNavClass: { type: String, required: true },
    userInitials: { type: String, required: true },
    user: { type: Object, required: true },
});

const emit = defineEmits(['switch-section']);
</script>

<template>
    <aside
        class="fixed inset-y-0 start-0 z-30 hidden h-screen flex-row border-e border-slate-200 bg-white shadow-xl xl:flex xl:w-72 dark:border-slate-700 dark:bg-slate-950"
    >
        <div class="flex h-full min-h-0 min-w-0 flex-1 flex-col p-5">
            <div class="shrink-0">
                <Link
                    :href="route(props.firstAccessibleRoute)"
                    class="mb-3 block rounded-lg outline-none transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-brand-blue/80"
                >
                    <ApplicationLogo class="max-h-14 max-w-[13rem]" />
                </Link>
                <p class="text-lg font-bold leading-tight text-brand-blue-dark dark:text-slate-100">
                    Fridtjof Nansen Groep 12
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <button
                        v-for="section in props.availableSections"
                        :key="`desktop-section-${section}`"
                        type="button"
                        class="rounded-md px-2.5 py-1 text-xs font-semibold transition"
                        :class="section === props.activeSection
                            ? (props.sectionButtonClass[section]?.active || 'bg-brand-red/15 text-brand-red')
                            : (props.sectionButtonClass[section]?.inactive || 'bg-brand-blue/10 text-brand-blue-dark hover:bg-brand-blue/20')"
                        @click="emit('switch-section', section)"
                    >
                        {{ props.sectionLabels[section] || section }}
                    </button>
                </div>
            </div>

            <nav
                ref="navRef"
                class="mt-6 flex min-h-0 flex-1 flex-col gap-2 overflow-y-auto overscroll-contain [-webkit-overflow-scrolling:touch]"
                aria-label="Hoofdnavigatie"
            >
                <div v-for="section in props.navSections" :key="`desktop-nav-${section.key}`" class="space-y-1">
                    <p class="px-3 pt-2 text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400 dark:text-slate-500">
                        {{ section.label }}
                    </p>
                    <Link
                        v-for="item in section.items"
                        :key="`desktop-item-${section.key}-${item.route}`"
                        :href="route(item.route)"
                        class="block shrink-0 rounded-lg px-3 py-2 text-sm font-medium transition"
                        :class="
                            props.navItemIsActive(item)
                                ? props.activeNavClass
                                : 'text-slate-800 hover:bg-brand-blue/10 dark:text-slate-100 dark:hover:bg-brand-blue/15'
                        "
                    >
                        <span class="inline-flex items-center gap-2">
                            <component :is="item.icon" class="h-5 w-5 shrink-0 stroke-2" />
                            <span>{{ item.label }}</span>
                        </span>
                    </Link>
                </div>
            </nav>

            <div class="mt-auto shrink-0 border-t border-slate-200 pt-4">
                <Link
                    :href="route('profile.edit')"
                    class="flex items-center gap-3 rounded-lg p-2 transition hover:bg-brand-blue/10 dark:border-slate-700 dark:hover:bg-brand-blue/15"
                >
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-blue text-sm font-semibold text-white"
                        aria-hidden="true"
                    >
                        {{ props.userInitials }}
                    </span>
                    <span class="min-w-0 flex-1 text-left">
                        <span class="flex items-center gap-1">
                            <CheckBadgeIcon
                                v-if="props.user?.email_verified_at"
                                class="h-4 w-4 shrink-0 text-brand-blue"
                                title="E-mail geverifieerd"
                            />
                            <span class="block truncate text-sm font-medium text-slate-900 dark:text-slate-100">{{
                                props.user?.name
                            }}</span>
                        </span>
                        <span class="block truncate text-xs text-app-muted dark:text-slate-400">{{
                            props.user?.email
                        }}</span>
                    </span>
                </Link>
            </div>
        </div>
        <div
            class="rainbow-animate-vertical w-1.5 shrink-0 self-stretch bg-gradient-to-b from-brand-red via-brand-yellow to-brand-blue"
            aria-hidden="true"
        />
    </aside>
</template>
