<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    opkomsten: { type: Array, default: () => [] },
    availableUsers: { type: Array, default: () => [] },
});
const page = usePage();
const activeSection = computed(() => String(page.props.activeSection || ''));
const isBestuur = computed(() => activeSection.value === 'bestuur');
const canCreateAgendaItem = computed(() => !!page.props.auth?.permissions?.events?.create);
const weekDays = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];
const visibleMonth = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
const selectedDateKey = ref('');
const agendaSearch = ref('');
const showSearchPanel = ref(false);
const viewMode = ref('month');
const monthFormatter = new Intl.DateTimeFormat('nl-NL', { month: 'long', year: 'numeric' });
const dayFormatter = new Intl.DateTimeFormat('nl-NL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const yearFormatter = new Intl.DateTimeFormat('nl-NL', { year: 'numeric' });

function goToCreateAgendaItem() {
    if (!canCreateAgendaItem.value) return;
    router.get(route('agenda.create'));
}

function toggleSearchPanel() {
    showSearchPanel.value = !showSearchPanel.value;
    if (!showSearchPanel.value) {
        agendaSearch.value = '';
    }
}

function createAgendaItemForDay(dayKey) {
    if (!canCreateAgendaItem.value) return;
    router.get(route('agenda.create'), { date: dayKey });
}

function toDateKey(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function fromDateKey(key) {
    const [y, m, d] = String(key || '').split('-').map((v) => Number(v));
    if (!y || !m || !d) return new Date();
    return new Date(y, m - 1, d);
}

const calendarEntries = computed(() => {
    const agendaItems = (props.items || []).flatMap((item) => {
        const start = String(item.event_date || '');
        const end = String(item.end_date || item.event_date || '');
        if (!start) return [];
        const startDate = fromDateKey(start);
        const endDate = fromDateKey(end);
        const rangeEnd = endDate >= startDate ? endDate : startDate;
        const entries = [];
        const cursor = new Date(startDate);
        while (cursor <= rangeEnd) {
            entries.push({
                id: item.id,
                kind: 'agenda',
                title: item.theme || 'Agenda-item',
                date: toDateKey(cursor),
                href: route('agenda.show', item.id),
                tag: 'Agenda',
            });
            cursor.setDate(cursor.getDate() + 1);
        }
        return entries;
    });

    const opkomstenItems = (props.opkomsten || []).map((ev) => ({
        id: ev.id,
        kind: 'opkomst',
        title: ev.theme || ev.activity || 'Opkomst',
        date: String(ev.event_date || ''),
        href: route('opkomsten.show', ev.id),
        tag: ev.is_shared ? 'Gezamenlijk' : 'Opkomst',
        section: String(ev.section || ''),
    }));

    return [...agendaItems, ...opkomstenItems];
});

const searchResults = computed(() => {
    const q = String(agendaSearch.value || '').trim().toLowerCase();
    if (!q) return [];
    return calendarEntries.value.filter((entry) =>
        `${entry.title} ${entry.tag} ${entry.date || ''}`.toLowerCase().includes(q),
    );
});

const entriesByDate = computed(() => {
    const map = {};
    for (const entry of calendarEntries.value) {
        if (!entry.date) continue;
        if (!map[entry.date]) map[entry.date] = [];
        map[entry.date].push(entry);
    }
    return map;
});

const monthLabel = computed(() => {
    const label = monthFormatter.format(visibleMonth.value);
    return label.charAt(0).toUpperCase() + label.slice(1);
});

const calendarDays = computed(() => {
    const firstDay = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth(), 1);
    const firstWeekdayMondayBased = (firstDay.getDay() + 6) % 7;
    const startDate = new Date(firstDay);
    startDate.setDate(firstDay.getDate() - firstWeekdayMondayBased);

    return Array.from({ length: 42 }).map((_, index) => {
        const date = new Date(startDate);
        date.setDate(startDate.getDate() + index);
        const key = toDateKey(date);
        return {
            key,
            date,
            day: date.getDate(),
            inCurrentMonth: date.getMonth() === visibleMonth.value.getMonth(),
            isToday: key === toDateKey(new Date()),
            entries: entriesByDate.value[key] || [],
        };
    });
});

const selectedDateEntries = computed(() => entriesByDate.value[selectedDateKey.value] || []);

const selectedDate = computed(() => fromDateKey(selectedDateKey.value));
const selectedDateLabel = computed(() => {
    const label = dayFormatter.format(selectedDate.value);
    return label.charAt(0).toUpperCase() + label.slice(1);
});

const weekDaysData = computed(() => {
    const base = selectedDate.value;
    const weekdayMondayBased = (base.getDay() + 6) % 7;
    const monday = new Date(base);
    monday.setDate(base.getDate() - weekdayMondayBased);

    return Array.from({ length: 7 }).map((_, index) => {
        const date = new Date(monday);
        date.setDate(monday.getDate() + index);
        const key = toDateKey(date);
        return {
            key,
            date,
            day: date.getDate(),
            entries: entriesByDate.value[key] || [],
            isToday: key === toDateKey(new Date()),
        };
    });
});

const weekLabel = computed(() => {
    const first = weekDaysData.value[0]?.date || selectedDate.value;
    const last = weekDaysData.value[6]?.date || selectedDate.value;
    const firstLabel = new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short' }).format(first);
    const lastLabel = new Intl.DateTimeFormat('nl-NL', { day: 'numeric', month: 'short', year: 'numeric' }).format(last);
    return `${firstLabel} - ${lastLabel}`;
});

const yearLabel = computed(() => yearFormatter.format(selectedDate.value));

const yearMonths = computed(() => {
    const year = selectedDate.value.getFullYear();
    return Array.from({ length: 12 }).map((_, monthIndex) => {
        const date = new Date(year, monthIndex, 1);
        const month = new Intl.DateTimeFormat('nl-NL', { month: 'long' }).format(date);
        const monthTitle = month.charAt(0).toUpperCase() + month.slice(1);
        const prefix = `${year}-${String(monthIndex + 1).padStart(2, '0')}-`;
        const count = Object.keys(entriesByDate.value).filter((k) => k.startsWith(prefix)).length;
        return {
            monthIndex,
            monthTitle,
            count,
        };
    });
});

if (!selectedDateKey.value) {
    selectedDateKey.value = toDateKey(new Date());
}

function previousMonth() {
    visibleMonth.value = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth() - 1, 1);
}

function nextMonth() {
    visibleMonth.value = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth() + 1, 1);
}

function previousPeriod() {
    if (viewMode.value === 'day') {
        const d = new Date(selectedDate.value);
        d.setDate(d.getDate() - 1);
        selectedDateKey.value = toDateKey(d);
        return;
    }
    if (viewMode.value === 'week') {
        const d = new Date(selectedDate.value);
        d.setDate(d.getDate() - 7);
        selectedDateKey.value = toDateKey(d);
        return;
    }
    if (viewMode.value === 'year') {
        const d = new Date(selectedDate.value);
        d.setFullYear(d.getFullYear() - 1);
        selectedDateKey.value = toDateKey(d);
        visibleMonth.value = new Date(d.getFullYear(), d.getMonth(), 1);
        return;
    }
    previousMonth();
}

function nextPeriod() {
    if (viewMode.value === 'day') {
        const d = new Date(selectedDate.value);
        d.setDate(d.getDate() + 1);
        selectedDateKey.value = toDateKey(d);
        return;
    }
    if (viewMode.value === 'week') {
        const d = new Date(selectedDate.value);
        d.setDate(d.getDate() + 7);
        selectedDateKey.value = toDateKey(d);
        return;
    }
    if (viewMode.value === 'year') {
        const d = new Date(selectedDate.value);
        d.setFullYear(d.getFullYear() + 1);
        selectedDateKey.value = toDateKey(d);
        visibleMonth.value = new Date(d.getFullYear(), d.getMonth(), 1);
        return;
    }
    nextMonth();
}

const periodLabel = computed(() => {
    if (viewMode.value === 'day') return selectedDateLabel.value;
    if (viewMode.value === 'week') return weekLabel.value;
    if (viewMode.value === 'year') return yearLabel.value;
    return monthLabel.value;
});

function selectDay(dayKey) {
    selectedDateKey.value = dayKey;
}

function openMonth(monthIndex) {
    const year = selectedDate.value.getFullYear();
    visibleMonth.value = new Date(year, monthIndex, 1);
    selectedDateKey.value = toDateKey(new Date(year, monthIndex, 1));
    viewMode.value = 'month';
}

function opkomstColorClass(section) {
    switch (String(section || '').toLowerCase()) {
        case 'bevers':
            return 'bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-500/30 dark:text-red-100';
        case 'dolfijnen':
            return 'bg-green-100 text-green-800 hover:bg-green-200 dark:bg-green-500/30 dark:text-green-100';
        case 'zeeverkenners':
            return 'bg-yellow-100 text-yellow-900 hover:bg-yellow-200 dark:bg-yellow-500/30 dark:text-yellow-100';
        case 'wilde-vaart':
        case 'wilde vaart':
            return 'bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-500/30 dark:text-blue-100';
        case 'loodsen':
            return 'bg-indigo-100 text-indigo-800 hover:bg-indigo-200 dark:bg-indigo-500/30 dark:text-indigo-100';
        case 'bestuur':
            return 'bg-purple-100 text-purple-800 hover:bg-purple-200 dark:bg-purple-500/30 dark:text-purple-100';
        default:
            return 'bg-brand-blue/15 text-brand-blue-dark hover:bg-brand-blue/25 dark:bg-brand-blue/25 dark:text-app-ink-dark';
    }
}
</script>

<template>
    <Head title="Agenda" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Agenda</h2>
                <div class="flex flex-1 flex-wrap items-center justify-end gap-2">
                    <div class="inline-flex rounded-lg border border-app-border bg-app-panel p-1 dark:border-app-border-dark dark:bg-app-panel-dark">
                        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-medium" :class="viewMode === 'day' ? 'bg-brand-blue text-white' : 'text-app-ink hover:bg-brand-blue/10 dark:text-app-ink-dark dark:hover:bg-brand-blue/20'" @click="viewMode = 'day'">Dag</button>
                        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-medium" :class="viewMode === 'week' ? 'bg-brand-blue text-white' : 'text-app-ink hover:bg-brand-blue/10 dark:text-app-ink-dark dark:hover:bg-brand-blue/20'" @click="viewMode = 'week'">Week</button>
                        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-medium" :class="viewMode === 'month' ? 'bg-brand-blue text-white' : 'text-app-ink hover:bg-brand-blue/10 dark:text-app-ink-dark dark:hover:bg-brand-blue/20'" @click="viewMode = 'month'">Maand</button>
                        <button type="button" class="rounded-md px-3 py-1.5 text-sm font-medium" :class="viewMode === 'year' ? 'bg-brand-blue text-white' : 'text-app-ink hover:bg-brand-blue/10 dark:text-app-ink-dark dark:hover:bg-brand-blue/20'" @click="viewMode = 'year'">Jaar</button>
                    </div>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-app-border bg-app-panel text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15"
                        :title="showSearchPanel ? 'Zoeken sluiten' : 'Zoeken'"
                        :aria-label="showSearchPanel ? 'Zoeken sluiten' : 'Zoeken'"
                        @click="toggleSearchPanel"
                    >
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </button>
                    <button v-if="canCreateAgendaItem" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-app-border bg-app-panel text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15" title="Toevoegen" aria-label="Toevoegen" @click="goToCreateAgendaItem">
                        <PlusIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            <div class="surface-brand-top rounded-2xl border border-app-border bg-white/95 p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-full border border-app-border bg-app-panel p-2 text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark"
                        @click="previousPeriod"
                    >
                        <ChevronLeftIcon class="h-5 w-5" />
                    </button>
                    <h3 class="text-lg font-semibold tracking-tight text-app-ink dark:text-app-ink-dark">{{ periodLabel }}</h3>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-full border border-app-border bg-app-panel p-2 text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark"
                        @click="nextPeriod"
                    >
                        <ChevronRightIcon class="h-5 w-5" />
                    </button>
                </div>

                <div v-if="showSearchPanel" class="mb-3 rounded-xl border border-app-border bg-app-panel p-3 dark:border-app-border-dark dark:bg-app-canvas-dark/70">
                    <input
                        v-model="agendaSearch"
                        type="search"
                        placeholder="Zoek in agenda en opkomsten..."
                        class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark"
                    />
                    <div class="mt-3">
                        <table class="w-full text-sm text-app-ink dark:text-app-ink-dark">
                            <tbody class="divide-y divide-brand-blue/20">
                                <tr v-for="entry in searchResults" :key="`search-${entry.kind}-${entry.id}`">
                                    <td class="py-2 pe-2 whitespace-nowrap text-xs text-app-muted dark:text-app-muted-dark">{{ entry.date || '-' }}</td>
                                    <td class="py-2">
                                        <Link :href="entry.href" class="hover:underline">
                                            <span class="font-semibold">{{ entry.tag }}</span>
                                            <span class="ms-1">{{ entry.title }}</span>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p v-if="agendaSearch.trim() !== '' && !searchResults.length" class="py-2 text-sm text-app-muted dark:text-app-muted-dark">
                            Geen resultaten gevonden.
                        </p>
                    </div>
                </div>

                <div v-if="viewMode === 'month'" class="grid grid-cols-7 border-b border-app-border pb-2 text-center text-xs font-semibold uppercase tracking-wide text-app-muted dark:border-app-border-dark dark:text-app-muted-dark">
                    <span v-for="day in weekDays" :key="`week-${day}`">{{ day }}</span>
                </div>

                <div v-if="viewMode === 'month'" class="mt-2 grid grid-cols-7 gap-2">
                    <div
                        v-for="cell in calendarDays"
                        :key="cell.key"
                        class="min-h-[7.25rem] cursor-pointer rounded-xl border p-2"
                        :class="[
                            cell.inCurrentMonth
                                ? 'border-app-border bg-app-panel dark:border-app-border-dark dark:bg-app-panel-dark'
                                : 'border-app-border/60 bg-app-canvas/60 dark:border-app-border-dark/50 dark:bg-app-canvas-dark/60',
                            cell.isToday ? 'ring-2 ring-brand-blue/60' : '',
                            selectedDateKey === cell.key ? 'ring-2 ring-brand-yellow/70' : '',
                        ]"
                        @click="selectDay(cell.key)"
                        @dblclick="createAgendaItemForDay(cell.key)"
                    >
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-semibold" :class="cell.inCurrentMonth ? 'text-app-ink dark:text-app-ink-dark' : 'text-app-muted dark:text-app-muted-dark'">
                                {{ cell.day }}
                            </span>
                        </div>

                        <div class="space-y-1">
                            <Link
                                v-for="entry in cell.entries.slice(0, 3)"
                                :key="`cal-entry-${entry.kind}-${entry.id}`"
                                :href="entry.href"
                                class="block rounded-lg px-2 py-1 text-[11px] leading-tight"
                                :class="entry.kind === 'agenda'
                                    ? 'bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-700/70 dark:text-slate-100'
                                    : opkomstColorClass(entry.section)"
                            >
                                <span class="font-semibold">{{ entry.tag }}</span>
                                <span class="ms-1">{{ entry.title }}</span>
                            </Link>
                            <p v-if="cell.entries.length > 3" class="px-1 text-[11px] text-app-muted dark:text-app-muted-dark">
                                +{{ cell.entries.length - 3 }} meer
                            </p>
                        </div>
                    </div>
                </div>

                <div v-if="viewMode === 'week'" class="mt-2">
                    <div class="grid grid-cols-7 border-b border-app-border pb-2 text-center text-xs font-semibold uppercase tracking-wide text-app-muted dark:border-app-border-dark dark:text-app-muted-dark">
                        <span v-for="day in weekDays" :key="`week-view-${day}`">{{ day }}</span>
                    </div>
                    <div class="mt-2 grid grid-cols-7 gap-2">
                        <div
                            v-for="day in weekDaysData"
                            :key="`week-day-${day.key}`"
                            class="min-h-[8rem] rounded-xl border border-app-border bg-app-panel p-2 dark:border-app-border-dark dark:bg-app-panel-dark"
                            :class="day.isToday ? 'ring-2 ring-brand-blue/60' : ''"
                            @click="selectDay(day.key)"
                        >
                            <p class="mb-1 text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ day.day }}</p>
                            <div class="space-y-1">
                                <Link
                                    v-for="entry in day.entries.slice(0, 4)"
                                    :key="`week-entry-${day.key}-${entry.kind}-${entry.id}`"
                                    :href="entry.href"
                                    class="block rounded-lg px-2 py-1 text-[11px] leading-tight"
                                    :class="entry.kind === 'agenda'
                                        ? 'bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-700/70 dark:text-slate-100'
                                        : opkomstColorClass(entry.section)"
                                >
                                    {{ entry.title }}
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="viewMode === 'day'" class="mt-2 rounded-xl border border-app-border bg-app-panel p-3 dark:border-app-border-dark dark:bg-app-panel-dark">
                    <p class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ selectedDateLabel }}</p>
                    <div v-if="!selectedDateEntries.length" class="mt-2 text-sm text-app-muted dark:text-app-muted-dark">
                        Geen activiteiten op deze dag.
                    </div>
                    <div v-else class="mt-2 space-y-1.5">
                        <Link
                            v-for="entry in selectedDateEntries"
                            :key="`day-entry-${entry.kind}-${entry.id}`"
                            :href="entry.href"
                            class="block rounded-lg border border-brand-blue/20 bg-white px-3 py-2 text-sm text-app-ink hover:bg-brand-blue/10 dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                            :class="entry.kind === 'agenda' ? '' : opkomstColorClass(entry.section)"
                        >
                            <span class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">{{ entry.tag }}</span>
                            <p class="mt-0.5 font-medium">{{ entry.title }}</p>
                        </Link>
                    </div>
                </div>

                <div v-if="viewMode === 'year'" class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <button
                        v-for="month in yearMonths"
                        :key="`year-month-${month.monthIndex}`"
                        type="button"
                        class="rounded-xl border border-app-border bg-app-panel p-3 text-left transition hover:border-brand-blue/50 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:hover:border-brand-blue/40 dark:hover:bg-brand-blue/15"
                        @click="openMonth(month.monthIndex)"
                    >
                        <p class="font-semibold text-app-ink dark:text-app-ink-dark">{{ month.monthTitle }}</p>
                        <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">{{ month.count }} dagen met activiteiten</p>
                    </button>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
