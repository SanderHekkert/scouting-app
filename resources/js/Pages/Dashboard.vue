<script setup>
import EditableTextCell from '@/Components/EditableTextCell.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    ArrowTopRightOnSquareIcon,
    CalendarDaysIcon,
    CakeIcon,
    ChartBarIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    todayEvents: { type: Array, default: () => [] },
    upcomingEvents: { type: Array, default: () => [] },
    upcomingBirthdays: { type: Array, default: () => [] },
    yearEventsCount: { type: Number, default: 0 },
    memberCount: { type: Number, default: 0 },
    leaderCount: { type: Number, default: 0 },
    leaderAbsenceChart: { type: Array, default: () => [] },
});
const page = usePage();
const speltakLabel = computed(() =>
    page.props.auth?.active_section === 'zeeverkenners' ? 'Zeeverkenners' : 'Dolfijnen',
);

const maxAbsenceCount = computed(() =>
    Math.max(0, ...(props.leaderAbsenceChart || []).map((r) => Number(r?.absence_count) || 0)),
);

function absenceBarWidth(count) {
    const m = maxAbsenceCount.value;
    const c = Number(count) || 0;
    if (m === 0) {
        return c === 0 ? '0%' : '100%';
    }
    return `${(c / m) * 100}%`;
}

/** Tooltip: burgerlijke naam als de staaf de scoutingnaam toont. */
function absenceChartRowTitle(row) {
    if (row?.leader_name && row?.real_name) {
        return `${row.real_name} — scoutingnaam: ${row.leader_name}`;
    }
    return row?.real_name || row?.name || '';
}

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
        ? 'bg-brand-blue/25 text-brand-blue-dark ring-brand-blue/40 dark:bg-brand-blue/35 dark:text-app-ink-dark dark:ring-brand-blue/50'
        : 'bg-brand-green/20 text-brand-green ring-brand-green/35 dark:bg-brand-green/30 dark:text-app-ink-dark';
}

function formatIsoToNl(iso) {
    if (!iso) return '–';
    const s = String(iso).slice(0, 10);
    const parts = s.split('-');
    if (parts.length !== 3) return s;
    const [y, m, d] = parts;
    return `${d}-${m}-${y}`;
}

const savingEventId = ref(null);

function saveEventTheme(ev, newTheme) {
    savingEventId.value = ev.id;
    router.patch(
        route('events.update-theme', ev.id),
        { theme: newTheme },
        {
            preserveScroll: true,
            onFinish: () => {
                savingEventId.value = null;
            },
        },
    );
}

function agendaUrlForEvent(ev) {
    return route('events.index', { event: ev.id });
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Dashboard</h2>
            </div>
        </template>

        <div class="space-y-5 text-app-ink dark:text-app-ink-dark">
            <section
                v-if="todayEvents?.length"
                class="surface-brand-top-2xl relative overflow-hidden rounded-2xl border-2 border-brand-yellow/60 bg-gradient-to-br from-brand-yellow-soft/90 via-white to-app-sidebar p-5 shadow-lg shadow-brand-blue/10 ring-1 ring-brand-yellow/40 dark:border-brand-yellow/50 dark:from-app-panel-dark dark:via-app-canvas-dark dark:to-app-panel-dark"
                aria-live="polite"
            >
                <div class="pointer-events-none absolute -end-16 -top-16 size-40 rounded-full bg-brand-blue/10 blur-2xl" />
                <div class="flex flex-wrap items-start gap-3">
                    <span
                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-blue/15 text-brand-blue-dark ring-1 ring-brand-blue/35 dark:bg-brand-blue/25 dark:text-app-ink-dark dark:ring-brand-blue/45"
                        aria-hidden="true"
                    >
                        <CalendarDaysIcon class="h-6 w-6" />
                    </span>
                    <div class="min-w-0 flex-1 text-app-ink dark:text-app-ink-dark">
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-app-muted dark:text-app-muted-dark">Vandaag</p>
                        <h3 class="mt-1 text-lg font-bold text-app-ink dark:text-app-ink-dark">
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
                                class="flex gap-2 rounded-lg border border-app-border bg-white/90 px-3 py-2 text-sm shadow-sm dark:border-brand-blue/35 dark:bg-app-canvas-dark/80"
                            >
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-app-ink dark:text-app-ink-dark">
                                        <EditableTextCell
                                            :text="ev.theme || ''"
                                            :saving="savingEventId === ev.id"
                                            :multiline="false"
                                            @save="(v) => saveEventTheme(ev, v)"
                                        />
                                    </div>
                                    <p class="mt-0.5 text-xs text-app-muted dark:text-app-muted-dark">
                                        <span v-if="ev.event_type">{{ ev.event_type }}</span>
                                        <span v-if="ev.event_type && ev.program_by"> · </span>
                                        <span v-if="ev.program_by">Programma: {{ ev.program_by }}</span>
                                    </p>
                                </div>
                                <Link
                                    :href="agendaUrlForEvent(ev)"
                                    class="mt-0.5 inline-flex shrink-0 touch-manipulation self-start rounded-md p-1 text-brand-blue hover:bg-brand-blue/10 hover:text-brand-blue-dark dark:text-brand-blue-light dark:hover:bg-brand-blue/15 dark:hover:text-app-ink-dark"
                                    title="Deze opkomst in de agenda tonen"
                                    aria-label="Deze opkomst in de agenda tonen"
                                >
                                    <ArrowTopRightOnSquareIcon class="h-5 w-5" />
                                </Link>
                            </li>
                        </ul>
                        <Link
                            :href="route('events.index')"
                            class="mt-3 inline-flex text-xs font-semibold text-brand-blue underline decoration-brand-blue/50 underline-offset-4 hover:text-brand-blue-dark dark:text-brand-blue-light dark:hover:text-app-ink-dark"
                        >
                            Naar agenda
                        </Link>
                    </div>
                </div>
            </section>

            <section v-else class="surface-brand-top rounded-xl border border-app-border bg-app-panel/90 px-4 py-3 text-sm text-app-muted dark:border-brand-blue/30 dark:bg-app-panel-dark/90 dark:text-app-muted-dark">
                <span class="font-medium text-app-ink dark:text-app-ink-dark">Geen opkomst vandaag</span>
                <span class="mx-2 text-app-muted dark:text-app-muted-dark">·</span>
                <Link :href="route('events.index')" class="font-medium text-brand-blue hover:text-brand-blue-dark dark:text-brand-blue-light dark:hover:text-app-ink-dark">Bekijk agenda</Link>
            </section>

            <section class="grid gap-3 sm:grid-cols-3">
                <div
                    class="surface-brand-top flex gap-3 rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
                >
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-blue/15 text-brand-blue ring-1 ring-brand-blue/30 dark:bg-brand-blue/25 dark:text-app-ink-dark dark:ring-brand-blue/45">
                        <UsersIcon class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">{{ speltakLabel }}</p>
                        <p class="mt-1 tabular-nums text-2xl font-bold text-app-ink dark:text-app-ink-dark">{{ memberCount }}</p>
                    </div>
                </div>
                <div class="surface-brand-top flex gap-3 rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-red/15 text-brand-red ring-1 ring-brand-red/35 dark:bg-brand-red/20 dark:text-app-ink-dark dark:ring-brand-yellow/40">
                        <UserGroupIcon class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Leiding</p>
                        <p class="mt-1 tabular-nums text-2xl font-bold text-app-ink dark:text-app-ink-dark">{{ leaderCount }}</p>
                    </div>
                </div>
                <div class="surface-brand-top flex gap-3 rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-green/15 text-brand-green ring-1 ring-brand-green/35 dark:bg-brand-green/25 dark:text-app-ink-dark dark:ring-brand-yellow/40">
                        <CalendarDaysIcon class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Opkomsten dit jaar</p>
                        <p class="mt-1 tabular-nums text-2xl font-bold text-app-ink dark:text-app-ink-dark">{{ yearEventsCount }}</p>
                    </div>
                </div>
            </section>

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                    <div class="flex items-center justify-between gap-3 border-b border-brand-blue/35 pb-3">
                        <div class="flex items-center gap-2">
                            <CalendarDaysIcon class="h-5 w-5 text-brand-blue dark:text-brand-blue-light" />
                            <h3 class="text-base font-semibold text-brand-blue-dark dark:text-app-ink-dark">Komende opkomsten</h3>
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
                            <Link
                                :href="agendaUrlForEvent(ev)"
                                class="flex h-14 w-14 shrink-0 touch-manipulation flex-col items-center justify-center rounded-lg border text-center transition hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-blue focus-visible:ring-offset-2 focus-visible:ring-offset-app-panel dark:focus-visible:ring-offset-app-panel-dark"
                                :class="ev.is_today
                                    ? 'border-brand-yellow/60 bg-brand-yellow-soft/55 text-app-ink dark:border-brand-yellow/50 dark:bg-brand-yellow/20 dark:text-app-ink-dark'
                                    : 'border-app-border bg-app-sidebar text-app-ink dark:border-brand-blue/35 dark:bg-app-canvas-dark dark:text-app-ink-dark'"
                                title="Deze opkomst in de agenda tonen"
                            >
                                <span class="text-[10px] font-bold uppercase leading-none text-app-muted dark:text-app-muted-dark">{{ ev.weekday }}</span>
                                <span class="text-sm font-bold leading-tight">{{ ev.day_month }}</span>
                            </Link>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-app-ink dark:text-app-ink-dark">
                                    <EditableTextCell
                                        :text="ev.theme || ''"
                                        :saving="savingEventId === ev.id"
                                        :multiline="false"
                                        @save="(v) => saveEventTheme(ev, v)"
                                    />
                                </div>
                                <p class="mt-0.5 text-xs text-app-muted dark:text-app-muted-dark">
                                    <template v-if="ev.is_today">
                                        <span class="font-semibold text-app-ink dark:text-app-ink-dark">Vandaag</span>
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
                        <Link :href="route('events.index')" class="font-medium text-brand-blue hover:text-brand-blue-dark dark:text-brand-blue-light dark:hover:text-app-ink-dark"> Agenda </Link>
                    </p>
                </section>

                <section class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                    <div class="flex items-center justify-between gap-3 border-b border-brand-blue/35 pb-3">
                        <div class="flex items-center gap-2">
                            <CakeIcon class="h-5 w-5 text-brand-red dark:text-app-ink-dark" />
                            <h3 class="text-base font-semibold text-brand-red-dark dark:text-app-ink-dark">Komende verjaardagen</h3>
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
                                        :class="row.days_until === 0 ? 'font-semibold text-brand-red dark:text-app-ink-dark' : ''"
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

            <section class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-blue/35 pb-3">
                    <div class="flex items-center gap-2">
                        <ChartBarIcon class="h-5 w-5 text-brand-blue dark:text-brand-blue-light" />
                        <h3 class="text-base font-semibold text-brand-blue-dark dark:text-app-ink-dark">Afwezigheid leiding</h3>
                    </div>
                    <Link
                        :href="route('events.index')"
                        class="text-xs font-semibold text-brand-blue hover:text-brand-blue-dark dark:text-brand-blue-light dark:hover:text-app-ink-dark"
                    >
                        Agenda
                    </Link>
                </div>
                <div v-if="!props.leaderAbsenceChart?.length" class="mt-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
                    Nog geen leiding in het systeem.
                </div>
                <div
                    v-else
                    class="mt-4 space-y-2.5"
                    role="list"
                    aria-label="Aantal keer afwezig genoemd in de agenda per leidinglid"
                >
                    <div
                        v-for="row in props.leaderAbsenceChart"
                        :key="row.id"
                        role="listitem"
                        class="flex items-center gap-2 sm:gap-3"
                    >
                        <span
                            class="w-[6.5rem] shrink-0 truncate text-xs font-medium text-app-ink sm:w-44 sm:text-sm dark:text-app-ink-dark"
                            :title="absenceChartRowTitle(row)"
                        >{{ row.name }}</span>
                        <div
                            class="h-6 min-w-0 flex-1 overflow-hidden rounded-md bg-slate-200/90 dark:bg-brand-blue/20"
                            :aria-hidden="true"
                        >
                            <div
                                class="h-full min-w-px rounded-md bg-gradient-to-r from-brand-blue to-brand-blue-light/90 dark:from-brand-blue-light dark:to-brand-blue-light/70"
                                :style="{ width: absenceBarWidth(row.absence_count) }"
                            />
                        </div>
                        <span class="w-7 shrink-0 tabular-nums text-end text-xs text-app-muted sm:w-8 sm:text-sm dark:text-app-muted-dark">{{
                            row.absence_count
                        }}</span>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
