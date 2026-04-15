<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
import { Bars3Icon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    mobileMenuOpen: { type: Boolean, required: true },
    firstAccessibleRoute: { type: String, required: true },
    activeMobileLabel: { type: String, required: true },
    navSections: { type: Array, required: true },
    navItemIsActive: { type: Function, required: true },
    activeNavClass: { type: String, required: true },
    sectionLabels: { type: Object, required: true },
    activeSection: { type: String, required: true },
    availableSections: { type: Array, required: true },
    sectionButtonClass: { type: Object, required: true },
});

const emit = defineEmits(['toggle-menu', 'close-menu', 'switch-section']);
</script>

<template>
    <div>
        <div
            class="xl:hidden fixed inset-x-0 top-0 z-50 border-b border-slate-200/90 bg-white/95 pt-[env(safe-area-inset-top,0px)] shadow-[0_4px_24px_-4px_rgba(0,0,0,0.12)] backdrop-blur-md dark:border-slate-700 dark:bg-slate-950/95 dark:shadow-black/30"
        >
            <div class="flex h-14 min-h-[3.5rem] items-center gap-2 px-3 sm:px-4">
                <Link
                    :href="route(props.firstAccessibleRoute)"
                    class="touch-manipulation shrink-0 rounded-lg p-2 -ms-1 outline-none ring-brand-blue/80 focus-visible:ring-2 active:bg-slate-100 dark:active:bg-slate-800"
                    aria-label="Naar dashboard"
                    @click="emit('close-menu')"
                >
                    <ApplicationLogo class="max-h-9 max-w-[5.75rem]" />
                </Link>
                <button
                    type="button"
                    class="touch-manipulation flex min-h-11 min-w-0 flex-1 items-center justify-between gap-2 rounded-xl border border-slate-200/90 bg-white px-3 py-2.5 text-left text-sm font-semibold text-slate-900 shadow-sm transition active:scale-[0.99] dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                    :aria-expanded="props.mobileMenuOpen"
                    aria-controls="app-mobile-nav"
                    @click="emit('toggle-menu')"
                >
                    <span class="min-w-0 flex-1 truncate">{{ props.activeMobileLabel }}</span>
                    <span class="flex shrink-0 items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-slate-400">
                        <Bars3Icon v-if="!props.mobileMenuOpen" class="h-6 w-6" aria-hidden="true" />
                        <XMarkIcon v-else class="h-6 w-6" aria-hidden="true" />
                        <span class="whitespace-nowrap">{{ props.mobileMenuOpen ? 'Sluiten' : 'Menu' }}</span>
                    </span>
                </button>
            </div>
        </div>

        <div
            v-show="props.mobileMenuOpen"
            class="xl:hidden fixed inset-0 z-[44] bg-slate-950/45 backdrop-blur-[2px] dark:bg-black/55"
            aria-hidden="true"
            @click="emit('close-menu')"
        />

        <nav
            v-show="props.mobileMenuOpen"
            id="app-mobile-nav"
            class="xl:hidden fixed left-0 right-0 z-[45] max-h-[min(78dvh,32rem)] overflow-y-auto overscroll-contain rounded-b-2xl border-b border-slate-200 bg-white shadow-2xl [-webkit-overflow-scrolling:touch] dark:border-slate-700 dark:bg-slate-950"
            :style="{
                top: 'calc(env(safe-area-inset-top, 0px) + 3.5rem)',
            }"
            aria-label="Mobiel menu"
        >
            <div class="space-y-1 px-3 py-3 pb-[max(1rem,env(safe-area-inset-bottom))]">
                <div v-for="section in props.navSections" :key="`mobile-nav-${section.key}`" class="space-y-1">
                    <p class="px-4 pt-2 text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400 dark:text-slate-500">
                        {{ section.label }}
                    </p>
                    <Link
                        v-for="item in section.items"
                        :key="`mobile-item-${section.key}-${item.route}`"
                        :href="route(item.route)"
                        class="flex min-h-12 items-center rounded-xl px-4 text-base font-medium transition touch-manipulation active:scale-[0.99]"
                        :class="
                            props.navItemIsActive(item)
                                ? props.activeNavClass
                                : 'text-slate-800 hover:bg-brand-blue/10 active:bg-brand-blue/15 dark:text-slate-100 dark:hover:bg-brand-blue/15'
                        "
                        @click="emit('close-menu')"
                    >
                        <component :is="item.icon" class="me-2 h-6 w-6 shrink-0 stroke-2" />
                        <span>{{ item.label }}</span>
                    </Link>
                </div>
                <div class="my-2 border-t border-slate-200 dark:border-slate-700" />
                <Link
                    :href="route('profile.edit')"
                    class="flex min-h-12 items-center rounded-xl px-4 text-base font-medium transition touch-manipulation active:scale-[0.99]"
                    :class="route().current('profile.edit') ? props.activeNavClass : 'text-slate-800 hover:bg-brand-blue/10 dark:text-slate-100'"
                    @click="emit('close-menu')"
                >
                    Profiel
                </Link>
                <div class="px-4 pt-2">
                    <p class="text-center text-[11px] text-slate-400 dark:text-slate-500">
                        FN12 · {{ props.sectionLabels[props.activeSection] || props.activeSection }}
                    </p>
                    <div class="mt-2 flex flex-wrap justify-center gap-2">
                        <button
                            v-for="section in props.availableSections"
                            :key="`mobile-section-${section}`"
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
            </div>
        </nav>
    </div>
</template>
