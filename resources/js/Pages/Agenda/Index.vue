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
const canCreateAgendaItem = computed(() => !!page.props.auth?.permissions?.events?.create);
const canUpdateAgendaItem = computed(() => !!page.props.auth?.permissions?.events?.update);
const weekDays = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];
const visibleMonth = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
const selectedDateKey = ref('');
const agendaSearch = ref('');
const showSearchPanel = ref(false);
const viewMode = ref('month');
const nowTick = ref(Date.now());
const dayColumnHeight = 24 * 56;
const pixelsPerMinute = dayColumnHeight / (24 * 60);
const dragPayload = ref(null);
const resizeState = ref(null);
let resizeRafId = 0;
const monthFormatter = new Intl.DateTimeFormat('nl-NL', { month: 'long', year: 'numeric' });
const dayFormatter = new Intl.DateTimeFormat('nl-NL', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
const yearFormatter = new Intl.DateTimeFormat('nl-NL', { year: 'numeric' });
const timeOnlyFormatter = new Intl.DateTimeFormat('nl-NL', { hour: '2-digit', minute: '2-digit' });

setInterval(() => {
    nowTick.value = Date.now();
}, 60000);

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

function parseTimeMinutes(time) {
    const raw = String(time || '');
    const [hh, mm] = raw.split(':').map((v) => Number(v));
    if (!Number.isInteger(hh) || !Number.isInteger(mm)) return null;
    if (hh < 0 || hh > 23 || mm < 0 || mm > 59) return null;
    return hh * 60 + mm;
}

function toTimeString(totalMinutes) {
    const minutes = Math.max(0, Math.min(23 * 60 + 59, totalMinutes));
    const hh = String(Math.floor(minutes / 60)).padStart(2, '0');
    const mm = String(minutes % 60).padStart(2, '0');
    return `${hh}:${mm}`;
}

function combineDateAndMinutes(dateKey, minutes) {
    const date = fromDateKey(dateKey);
    date.setHours(Math.floor(minutes / 60), minutes % 60, 0, 0);
    return date;
}

function formatEventTime(entry) {
    if (entry.allDay) return 'Hele dag';
    return `${timeOnlyFormatter.format(entry.startAt)} - ${timeOnlyFormatter.format(entry.endAt)}`;
}

const normalizedEntries = computed(() => {
    const agendaItems = (props.items || []).map((item) => {
        const startDateKey = String(item.event_date || '');
        const endDateKey = String(item.end_date || item.event_date || '');
        if (!startDateKey) return null;
        const startMinutes = parseTimeMinutes(item.start_time);
        const endMinutes = parseTimeMinutes(item.end_time);
        const allDay = startMinutes === null && endMinutes === null;
        const startAt = combineDateAndMinutes(startDateKey, allDay ? 8 * 60 : startMinutes);
        const endAt = combineDateAndMinutes(endDateKey, allDay ? 17 * 60 : (endMinutes ?? ((startMinutes ?? 8 * 60) + 60)));
        if (endAt <= startAt) {
            endAt.setMinutes(startAt.getMinutes() + 60);
        }
        return {
            sourceType: 'agenda',
            sourceId: Number(item.id),
            title: item.theme || 'Agenda-item',
            tag: 'Agenda',
            href: route('agenda.show', item.id),
            startAt,
            endAt,
            allDay,
            date: startDateKey,
            endDate: endDateKey,
            section: '',
            canScheduleUpdate: canUpdateAgendaItem.value,
        };
    }).filter(Boolean);

    const opkomstenItems = (props.opkomsten || []).map((ev) => {
        const dateKey = String(ev.event_date || '');
        if (!dateKey) return null;
        return {
            sourceType: 'opkomst',
            sourceId: Number(ev.id),
            title: ev.theme || ev.activity || 'Opkomst',
            tag: ev.is_shared ? 'Gezamenlijk' : 'Opkomst',
            href: route('opkomsten.show', ev.id),
            startAt: combineDateAndMinutes(dateKey, 9 * 60),
            endAt: combineDateAndMinutes(dateKey, 12 * 60),
            allDay: true,
            date: dateKey,
            endDate: dateKey,
            section: String(ev.section || ''),
            canScheduleUpdate: false,
        };
    }).filter(Boolean);

    return [...agendaItems, ...opkomstenItems];
});

const searchResults = computed(() => {
    const q = String(agendaSearch.value || '').trim().toLowerCase();
    if (!q) return [];
    return normalizedEntries.value.filter((entry) =>
        `${entry.title} ${entry.tag} ${entry.date || ''}`.toLowerCase().includes(q),
    );
});

const entriesByDate = computed(() => {
    const map = {};
    for (const entry of normalizedEntries.value) {
        const cursor = new Date(entry.startAt);
        const end = new Date(entry.endAt);
        cursor.setHours(0, 0, 0, 0);
        end.setHours(0, 0, 0, 0);
        while (cursor <= end) {
            const key = toDateKey(cursor);
            if (!map[key]) map[key] = [];
            map[key].push(entry);
            cursor.setDate(cursor.getDate() + 1);
        }
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

const dayHours = computed(() =>
    Array.from({ length: 24 }).map((_, h) => ({
        hour: h,
        label: `${String(h).padStart(2, '0')}:00`,
    })),
);

function minuteOffset(entry, dayKey) {
    const dayStart = combineDateAndMinutes(dayKey, 0);
    const dayEnd = combineDateAndMinutes(dayKey, 24 * 60 - 1);
    const start = Math.max(dayStart.getTime(), entry.startAt.getTime());
    const end = Math.min(dayEnd.getTime(), entry.endAt.getTime());
    const topMin = Math.max(0, Math.round((start - dayStart.getTime()) / 60000));
    const heightMin = Math.max(30, Math.round((end - start) / 60000));
    return {
        top: topMin * pixelsPerMinute,
        height: heightMin * pixelsPerMinute,
    };
}

const dayTimedEntries = computed(() =>
    selectedDateEntries.value.filter((entry) => !entry.allDay).sort((a, b) => a.startAt - b.startAt),
);

const dayAllDayEntries = computed(() => selectedDateEntries.value.filter((entry) => entry.allDay));

const weekTimedByDay = computed(() =>
    weekDaysData.value.map((d) => ({
        key: d.key,
        entries: (d.entries || []).filter((entry) => !entry.allDay).sort((a, b) => a.startAt - b.startAt),
    })),
);

const weekAllDayByDay = computed(() =>
    weekDaysData.value.map((d) => ({
        key: d.key,
        entries: (d.entries || []).filter((entry) => entry.allDay),
    })),
);

const currentTimeLine = computed(() => {
    const now = new Date(nowTick.value);
    const key = toDateKey(now);
    const mins = now.getHours() * 60 + now.getMinutes();
    return {
        key,
        top: mins * pixelsPerMinute,
    };
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
        const firstDay = new Date(year, monthIndex, 1);
        const firstWeekdayMondayBased = (firstDay.getDay() + 6) % 7;
        const startDate = new Date(firstDay);
        startDate.setDate(firstDay.getDate() - firstWeekdayMondayBased);
        const cells = Array.from({ length: 42 }).map((__, index) => {
            const cellDate = new Date(startDate);
            cellDate.setDate(startDate.getDate() + index);
            const key = toDateKey(cellDate);
            return {
                key,
                inCurrentMonth: cellDate.getMonth() === monthIndex,
                day: cellDate.getDate(),
                count: (entriesByDate.value[key] || []).length,
                isToday: key === toDateKey(new Date()),
            };
        });
        return {
            monthIndex,
            monthTitle,
            cells,
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

function goToToday() {
    const now = new Date();
    selectedDateKey.value = toDateKey(now);
    visibleMonth.value = new Date(now.getFullYear(), now.getMonth(), 1);
}

function openCreateAt(dateKey, hour = 9) {
    if (!canCreateAgendaItem.value) return;
    router.get(route('agenda.create'), {
        date: dateKey,
        start_time: `${String(hour).padStart(2, '0')}:00`,
    });
}

function dragStart(entry, event) {
    if (!entry.canScheduleUpdate) return;
    const startMinutes = entry.startAt.getHours() * 60 + entry.startAt.getMinutes();
    dragPayload.value = {
        sourceId: entry.sourceId,
        startMinutes,
        durationMinutes: Math.max(30, Math.round((entry.endAt - entry.startAt) / 60000)),
    };
    event.dataTransfer?.setData('text/plain', String(entry.sourceId));
    event.dataTransfer.effectAllowed = 'move';
}

function dropOnDay(dayKey) {
    if (!dragPayload.value) return;
    applySchedulePatch(dragPayload.value.sourceId, dayKey, dragPayload.value.startMinutes, dragPayload.value.durationMinutes);
    dragPayload.value = null;
}

function applySchedulePatch(entryId, dateKey, startMinutes, durationMinutes) {
    const endMinutes = Math.min(23 * 60 + 59, startMinutes + durationMinutes);
    router.patch(
        route('agenda.schedule.update', entryId),
        {
            event_date: dateKey,
            end_date: dateKey,
            start_time: toTimeString(startMinutes),
            end_time: toTimeString(endMinutes),
        },
        {
            preserveScroll: true,
        },
    );
}

function dropOnHour(dayKey, hour) {
    if (!dragPayload.value) return;
    applySchedulePatch(dragPayload.value.sourceId, dayKey, hour * 60, dragPayload.value.durationMinutes);
    dragPayload.value = null;
}

function startResize(entry, dayKey, ev) {
    if (!entry.canScheduleUpdate) return;
    ev.preventDefault();
    const startMinutes = entry.startAt.getHours() * 60 + entry.startAt.getMinutes();
    const baseDuration = Math.max(30, Math.round((entry.endAt - entry.startAt) / 60000));
    resizeState.value = {
        sourceId: entry.sourceId,
        dayKey,
        startY: ev.clientY,
        startMinutes,
        baseDuration,
    };
    window.addEventListener('mousemove', onResizeMove);
    window.addEventListener('mouseup', endResize);
}

function onResizeMove(ev) {
    if (!resizeState.value) return;
    if (resizeRafId) cancelAnimationFrame(resizeRafId);
    resizeRafId = requestAnimationFrame(() => {
        const deltaPx = ev.clientY - resizeState.value.startY;
        const deltaMinutes = Math.round((deltaPx / pixelsPerMinute) / 15) * 15;
        resizeState.value.nextDuration = Math.max(30, resizeState.value.baseDuration + deltaMinutes);
    });
}

function endResize() {
    if (!resizeState.value) return;
    const duration = resizeState.value.nextDuration ?? resizeState.value.baseDuration;
    applySchedulePatch(resizeState.value.sourceId, resizeState.value.dayKey, resizeState.value.startMinutes, duration);
    resizeState.value = null;
    window.removeEventListener('mousemove', onResizeMove);
    window.removeEventListener('mouseup', endResize);
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
                    <button type="button" class="rounded-lg border border-app-border bg-app-panel px-2.5 py-1 text-xs font-semibold text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark" @click="goToToday">Vandaag</button>
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
                        @dragover.prevent
                        @drop.prevent="dropOnDay(cell.key)"
                    >
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-semibold" :class="cell.inCurrentMonth ? 'text-app-ink dark:text-app-ink-dark' : 'text-app-muted dark:text-app-muted-dark'">
                                {{ cell.day }}
                            </span>
                        </div>

                        <div class="space-y-1">
                            <Link
                                v-for="entry in cell.entries.slice(0, 3)"
                                :key="`cal-entry-${entry.sourceType}-${entry.sourceId}`"
                                :href="entry.href"
                                class="block rounded-lg px-2 py-1 text-[11px] leading-tight"
                                :class="entry.sourceType === 'agenda'
                                    ? 'bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-700/70 dark:text-slate-100'
                                    : opkomstColorClass(entry.section)"
                                :draggable="entry.canScheduleUpdate"
                                @dragstart="dragStart(entry, $event)"
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
                    <div class="grid grid-cols-[3.5rem_repeat(7,minmax(0,1fr))] border-b border-app-border pb-2 text-center text-xs font-semibold uppercase tracking-wide text-app-muted dark:border-app-border-dark dark:text-app-muted-dark">
                        <span />
                        <span v-for="day in weekDays" :key="`week-view-${day}`">{{ day }}</span>
                    </div>
                    <div class="mt-2 grid grid-cols-[3.5rem_repeat(7,minmax(0,1fr))] gap-2">
                        <div class="relative h-[1344px] pt-[5.5rem]">
                            <div v-for="slot in dayHours" :key="`week-hour-label-${slot.hour}`" class="h-14 pe-1 pt-0.5 text-right text-[10px] text-app-muted dark:text-app-muted-dark">
                                {{ slot.label }}
                            </div>
                        </div>
                        <div
                            v-for="day in weekDaysData"
                            :key="`week-day-${day.key}`"
                            class="rounded-xl border border-app-border bg-app-panel p-2 dark:border-app-border-dark dark:bg-app-panel-dark"
                            :class="day.isToday ? 'ring-2 ring-brand-blue/60' : ''"
                            @click="selectDay(day.key)"
                        >
                            <div class="mb-2 h-20 overflow-y-auto">
                                <p class="mb-1 text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ day.day }}</p>
                                <div class="space-y-1">
                                    <Link
                                        v-for="entry in weekAllDayByDay.find((v) => v.key === day.key)?.entries || []"
                                        :key="`week-allday-${day.key}-${entry.sourceType}-${entry.sourceId}`"
                                        :href="entry.href"
                                        class="block rounded-lg px-2 py-1 text-[11px] leading-tight"
                                        :class="entry.sourceType === 'agenda'
                                            ? 'bg-slate-100 text-slate-800 hover:bg-slate-200 dark:bg-slate-700/70 dark:text-slate-100'
                                            : opkomstColorClass(entry.section)"
                                    >
                                        <span class="font-semibold">{{ entry.tag }}</span>
                                        <span class="ms-1">{{ entry.title }}</span>
                                    </Link>
                                </div>
                            </div>
                            <div class="relative h-[1344px] overflow-hidden rounded-lg border border-app-border/70 bg-white dark:border-app-border-dark/70 dark:bg-app-canvas-dark">
                                <div class="absolute inset-0">
                                    <div
                                        v-for="slot in dayHours"
                                        :key="`week-slot-${day.key}-${slot.hour}`"
                                        class="h-14 border-t border-app-border/60 first:border-t-0 dark:border-app-border-dark/60"
                                        @dragover.prevent
                                        @drop.prevent="dropOnHour(day.key, slot.hour)"
                                        @dblclick="openCreateAt(day.key, slot.hour)"
                                    />
                                </div>
                                <Link
                                    v-for="entry in weekTimedByDay.find((v) => v.key === day.key)?.entries || []"
                                    :key="`week-timed-${day.key}-${entry.sourceType}-${entry.sourceId}`"
                                    :href="entry.href"
                                    class="absolute left-1 right-1 rounded-lg border px-2 py-1 text-[11px] shadow-sm"
                                    :class="entry.sourceType === 'agenda'
                                        ? 'border-slate-200 bg-slate-100 text-slate-800'
                                        : 'border-transparent ' + opkomstColorClass(entry.section)"
                                    :style="{
                                        top: `${minuteOffset(entry, day.key).top}px`,
                                        height: `${minuteOffset(entry, day.key).height}px`,
                                    }"
                                    :draggable="entry.canScheduleUpdate"
                                    @dragstart="dragStart(entry, $event)"
                                >
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="truncate font-semibold">{{ entry.title }}</span>
                                        <span class="shrink-0 text-[10px]">{{ formatEventTime(entry) }}</span>
                                    </div>
                                    <button
                                        v-if="entry.canScheduleUpdate"
                                        type="button"
                                        class="absolute right-1 bottom-1 h-2 w-8 cursor-ns-resize rounded-full bg-black/30"
                                        title="Duur aanpassen"
                                        @mousedown="startResize(entry, day.key, $event)"
                                    />
                                </Link>
                                <div
                                    v-if="currentTimeLine.key === day.key"
                                    class="pointer-events-none absolute left-0 right-0 z-30 border-t border-red-500"
                                    :style="{ top: `${currentTimeLine.top}px` }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="viewMode === 'day'" class="mt-2 rounded-xl border border-app-border bg-app-panel p-3 dark:border-app-border-dark dark:bg-app-panel-dark">
                    <p class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ selectedDateLabel }}</p>
                    <div class="mt-2 space-y-1.5">
                        <Link
                            v-for="entry in dayAllDayEntries"
                            :key="`day-all-day-${entry.sourceType}-${entry.sourceId}`"
                            :href="entry.href"
                            class="block rounded-lg border border-brand-blue/20 bg-white px-3 py-2 text-sm text-app-ink hover:bg-brand-blue/10 dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                            :class="entry.sourceType === 'agenda' ? '' : opkomstColorClass(entry.section)"
                        >
                            <span class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">{{ entry.tag }}</span>
                            <p class="mt-0.5 font-medium">{{ entry.title }}</p>
                        </Link>
                    </div>
                    <div class="mt-3 grid grid-cols-[3.5rem_1fr] gap-2">
                        <div class="relative h-[1344px]">
                            <div v-for="slot in dayHours" :key="`day-hour-label-${slot.hour}`" class="h-14 pe-1 pt-0.5 text-right text-[10px] text-app-muted dark:text-app-muted-dark">
                                {{ slot.label }}
                            </div>
                        </div>
                        <div class="relative h-[1344px] overflow-hidden rounded-lg border border-app-border/70 bg-white dark:border-app-border-dark/70 dark:bg-app-canvas-dark">
                            <div class="absolute inset-0">
                                <div
                                    v-for="slot in dayHours"
                                    :key="`day-slot-${slot.hour}`"
                                    class="h-14 border-t border-app-border/60 first:border-t-0 dark:border-app-border-dark/60"
                                    @dragover.prevent
                                    @drop.prevent="dropOnHour(selectedDateKey, slot.hour)"
                                    @dblclick="openCreateAt(selectedDateKey, slot.hour)"
                                />
                            </div>
                            <Link
                                v-for="entry in dayTimedEntries"
                                :key="`day-timed-${entry.sourceType}-${entry.sourceId}`"
                                :href="entry.href"
                                class="absolute left-2 right-2 rounded-lg border px-2 py-1 text-xs shadow-sm"
                                :class="entry.sourceType === 'agenda'
                                    ? 'border-slate-200 bg-slate-100 text-slate-800'
                                    : 'border-transparent ' + opkomstColorClass(entry.section)"
                                :style="{
                                    top: `${minuteOffset(entry, selectedDateKey).top}px`,
                                    height: `${minuteOffset(entry, selectedDateKey).height}px`,
                                }"
                                :draggable="entry.canScheduleUpdate"
                                @dragstart="dragStart(entry, $event)"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate font-semibold">{{ entry.title }}</span>
                                    <span class="text-[10px]">{{ formatEventTime(entry) }}</span>
                                </div>
                                <button
                                    v-if="entry.canScheduleUpdate"
                                    type="button"
                                    class="absolute right-1 bottom-1 h-2 w-8 cursor-ns-resize rounded-full bg-black/30"
                                    title="Duur aanpassen"
                                    @mousedown="startResize(entry, selectedDateKey, $event)"
                                />
                            </Link>
                            <div
                                v-if="currentTimeLine.key === selectedDateKey"
                                class="pointer-events-none absolute left-0 right-0 z-30 border-t border-red-500"
                                :style="{ top: `${currentTimeLine.top}px` }"
                            />
                        </div>
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
                        <div class="mt-2 grid grid-cols-7 gap-0.5 text-[10px]">
                            <span
                                v-for="cell in month.cells"
                                :key="`year-cell-${month.monthIndex}-${cell.key}`"
                                class="flex h-4 items-center justify-center rounded-sm"
                                :class="[
                                    cell.inCurrentMonth ? 'text-app-ink dark:text-app-ink-dark' : 'text-app-muted/50 dark:text-app-muted-dark/50',
                                    cell.isToday ? 'ring-1 ring-brand-blue/70' : '',
                                    cell.count > 0 ? 'bg-brand-blue/15 dark:bg-brand-blue/25' : '',
                                ]"
                            >
                                {{ cell.day }}
                            </span>
                        </div>
                    </button>
                </div>

            </div>

        </div>
    </AuthenticatedLayout>
</template>
