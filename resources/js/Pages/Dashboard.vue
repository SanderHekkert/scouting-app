<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarDaysIcon,
    CakeIcon,
    ClipboardDocumentListIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';

defineProps({
    todayEvents: { type: Array, default: () => [] },
    upcomingEvents: { type: Array, default: () => [] },
    upcomingBirthdays: { type: Array, default: () => [] },
    taskCount: { type: Number, default: 0 },
    memberCount: { type: Number, default: 0 },
    leaderCount: { type: Number, default: 0 },
});

function fullName(row) {
    const ln = row?.last_name ? String(row.last_name).trim() : '';
    const fn = row?.first_name ? String(row.first_name).trim() : '';
    return `${fn}${ln ? ` ${ln}` : ''}`.trim() || '—';
}

function kindLabel(kind) {
    return kind === 'leader' ? 'Leiding' : 'Lid';
}

function kindBadgeClass(kind) {
    return kind === 'leader'
        ? 'bg-brand-blue/25 text-brand-blue-dark ring-brand-blue/40 dark:bg-brand-blue/35 dark:text-brand-yellow-soft dark:ring-brand-blue/50'
        : 'bg-brand-green/20 text-brand-green ring-brand-green/35 dark:bg-brand-green/30 dark:text-brand-yellow-soft';
}

function formatIsoToNl(iso) {
    if (!iso) return '–';
    const s = String(iso).slice(0, 10);
    const parts = s.split('-');
    if (parts.length !== 3) return s;
    const [y, m, d] = parts;
    return `${d}-${m}-${y}`;
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Dashboard</h2>
                <Link
                    :href="route('members.index')"
                    class="text-sm font-medium text-brand-blue hover:text-brand-blue-dark dark:text-brand-blue-light dark:hover:text-brand-yellow-soft"
                >
                    Dolfijnen →
                </Link>
            </div>
        </template>

        <div class="space-y-5 text-app-ink dark:text-app-ink-dark">
            <section
                v-if="todayEvents?.length"
                class="relative overflow-hidden rounded-2xl border-2 border-brand-yellow/70 bg-gradient-to-br from-amber-950/80 via-brand-red/25 to-brand-blue-dark/90 p-5 shadow-lg shadow-brand-red/20 ring-1 ring-brand-yellow/50"
                aria-live="polite"
            >
                <div class="pointer-events-none absolute -end-16 -top-16 size-40 rounded-full bg-amber-400/10 blur-2xl" />
                <div class="flex flex-wrap items-start gap-3">
                    <span
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/20 text-amber-200 ring-1 ring-amber-400/50"
                        aria-hidden="true"
                    >
                        <CalendarDaysIcon class="h-6 w-6" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-200/90">Vandaag</p>
                        <h3 class="mt-1 text-lg font-bold text-amber-50">
                            {{
                                todayEvents.length === 1
                                    ? 'Vandaag is er opkomst'
                                    : `Vandaag zijn er ${todayEvents.length} opkomsten`
                            }}
                        </h3>
                        <ul class="mt-3 space-y-2">
                            <li
                                v-for="ev in todayEvents"
                                :key="ev.id"
                                class="rounded-lg border border-amber-700/40 bg-black/20 px-3 py-2 text-sm"
                            >
                                <p class="font-semibold text-amber-50">{{ ev.theme }}</p>
                                <p class="mt-0.5 text-xs text-amber-100/85">
                                    <span v-if="ev.event_type">{{ ev.event_type }}</span>
                                    <span v-if="ev.event_type && ev.program_by"> · </span>
                                    <span v-if="ev.program_by">Programma: {{ ev.program_by }}</span>
                                </p>
                            </li>
                        </ul>
                        <Link
                            :href="route('events.index')"
                            class="mt-3 inline-flex text-xs font-semibold text-amber-200 underline decoration-amber-300/70 underline-offset-4 hover:text-brand-yellow-soft"
                        >
                            Naar agenda
                        </Link>
                    </div>
                </div>
            </section>

            <section v-else class="rounded-xl border border-app-border bg-app-panel/90 px-4 py-3 text-sm text-app-muted dark:border-brand-blue/30 dark:bg-app-panel-dark/90 dark:text-app-muted-dark">
                <span class="font-medium text-app-ink dark:text-app-ink-dark">Geen opkomst vandaag</span>
                <span class="mx-2 text-app-muted dark:text-app-muted-dark">·</span>
                <Link :href="route('events.index')" class="font-medium text-brand-blue hover:text-brand-blue-dark dark:text-brand-blue-light dark:hover:text-brand-yellow-soft">Bekijk agenda</Link>
            </section>

            <section class="grid gap-3 sm:grid-cols-3">
                <div
                    class="flex gap-3 rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
                >
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-blue/15 text-brand-blue ring-1 ring-brand-blue/30 dark:bg-brand-blue/25 dark:text-brand-yellow-soft dark:ring-brand-blue/45">
                        <UsersIcon class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Dolfijnen</p>
                        <p class="mt-1 tabular-nums text-2xl font-bold text-app-ink dark:text-app-ink-dark">{{ memberCount }}</p>
                    </div>
                </div>
                <div class="flex gap-3 rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-red/15 text-brand-red ring-1 ring-brand-red/35 dark:bg-brand-red/20 dark:text-brand-yellow-soft dark:ring-brand-yellow/40">
                        <UserGroupIcon class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Leiding</p>
                        <p class="mt-1 tabular-nums text-2xl font-bold text-app-ink dark:text-app-ink-dark">{{ leaderCount }}</p>
                    </div>
                </div>
                <div class="flex gap-3 rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-green/15 text-brand-green ring-1 ring-brand-green/35 dark:bg-brand-green/25 dark:text-brand-yellow-soft dark:ring-brand-yellow/40">
                        <ClipboardDocumentListIcon class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Taken</p>
                        <p class="mt-1 tabular-nums text-2xl font-bold text-app-ink dark:text-app-ink-dark">{{ taskCount }}</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                    <div class="flex items-center justify-between gap-3 border-b border-brand-blue/35 pb-3">
                        <div class="flex items-center gap-2">
                            <CalendarDaysIcon class="h-5 w-5 text-brand-blue dark:text-brand-blue-light" />
                            <h3 class="text-base font-semibold text-brand-blue-dark dark:text-brand-yellow-soft">Komende opkomsten</h3>
                        </div>
                    </div>
                    <ul
                        v-if="upcomingEvents?.length"
                        class="mt-4 divide-y divide-app-border dark:divide-brand-blue/25"
                    >
                        <li
                            v-for="ev in upcomingEvents"
                            :key="ev.id"
                            class="flex gap-3 py-3 first:pt-0"
                            :class="{ 'rounded-lg bg-brand-blue/30 px-2 -mx-2 ring-1 ring-brand-blue/40': ev.is_today }"
                        >
                            <div
                                class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-lg border text-center"
                                :class="ev.is_today
                                    ? 'border-amber-500/60 bg-amber-950/40 text-amber-100'
                                    : 'border-app-border bg-app-sidebar text-app-ink dark:border-brand-blue/35 dark:bg-app-canvas-dark dark:text-app-ink-dark'"
                            >
                                <span class="text-[10px] font-bold uppercase leading-none text-app-muted dark:text-app-muted-dark">{{ ev.weekday }}</span>
                                <span class="text-sm font-bold leading-tight">{{ ev.day_month }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-app-ink dark:text-app-ink-dark">{{ ev.theme }}</p>
                                <p class="mt-0.5 text-xs text-app-muted dark:text-app-muted-dark">
                                    <template v-if="ev.is_today">
                                        <span class="font-semibold text-amber-300">Vandaag</span>
                                        <span v-if="ev.event_type || ev.program_by"> · </span>
                                    </template>
                                    <span v-if="ev.event_type">{{ ev.event_type }}</span>
                                    <span v-if="ev.event_type && ev.program_by"> · </span>
                                    <span v-if="ev.program_by">{{ ev.program_by }}</span>
                                </p>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="mt-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
                        Geen geplande opkomsten.
                        <Link :href="route('events.index')" class="font-medium text-brand-blue hover:text-brand-blue-dark dark:text-brand-blue-light dark:hover:text-brand-yellow-soft"> Agenda </Link>
                    </p>
                </section>

                <section class="rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                    <div class="flex items-center justify-between gap-3 border-b border-brand-blue/35 pb-3">
                        <div class="flex items-center gap-2">
                            <CakeIcon class="h-5 w-5 text-brand-red dark:text-brand-yellow-soft" />
                            <h3 class="text-base font-semibold text-brand-red-dark dark:text-brand-yellow-soft">Komende verjaardagen</h3>
                        </div>
                    </div>
                    <ul
                        v-if="upcomingBirthdays?.length"
                        class="mt-4 divide-y divide-app-border dark:divide-brand-blue/25"
                    >
                        <li v-for="row in upcomingBirthdays" :key="`${row.kind}-${row.id}`" class="flex flex-wrap items-center gap-3 py-3 first:pt-0">
                            <div
                                class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-lg border border-app-border bg-app-sidebar text-center text-app-ink dark:border-brand-blue/35 dark:bg-app-canvas-dark dark:text-app-ink-dark"
                            >
                                <span class="text-[10px] font-bold uppercase leading-none text-app-muted dark:text-app-muted-dark">{{ row.weekday }}</span>
                                <span class="text-sm font-bold leading-tight">{{ row.day_month }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium text-app-ink dark:text-app-ink-dark">{{ fullName(row) }}</p>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1"
                                        :class="kindBadgeClass(row.kind)"
                                    >
                                        {{ kindLabel(row.kind) }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-app-muted dark:text-app-muted-dark">
                                    <span
                                        :class="row.days_until === 0 ? 'font-semibold text-brand-red dark:text-brand-yellow-soft' : ''"
                                    >
                                        {{ row.when_label }}
                                    </span>
                                    <span class="text-app-muted dark:text-app-muted-dark"> · </span>
                                    <span>geb. {{ formatIsoToNl(row.birthday) }}</span>
                                </p>
                            </div>
                        </li>
                    </ul>
                    <p v-else class="mt-6 text-center text-sm text-app-muted dark:text-app-muted-dark">Nog geen verjaardagen met datum in het systeem.</p>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
