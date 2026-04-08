<script setup>
import AgendaItemsTable from '@/Components/AgendaItemsTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, DocumentCheckIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    opkomsten: { type: Array, default: () => [] },
});
const page = usePage();
const activeSection = computed(() => page.props.auth?.active_section ?? 'dolfijnen');
const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const weekDays = ['Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo'];
const visibleMonth = ref(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
const monthFormatter = new Intl.DateTimeFormat('nl-NL', { month: 'long', year: 'numeric' });

const showAddForm = ref(false);
const form = useForm({
    theme: '',
    event_date: '',
    location: '',
    time_slot: '',
    invitees: '',
    link_url: '',
    attachment_file: null,
    notes: '',
});

function toggleAddForm() {
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        form.reset();
        form.clearErrors();
    }
}

function submitAdd() {
    form.post(route('agenda.store'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
}

function onAttachmentChange(event) {
    form.attachment_file = event?.target?.files?.[0] || null;
}

function editItem(item) {
    router.get(route('agenda.show', item.id));
}

function deleteItem(item) {
    if (!item?.id) return;
    if (!confirm('Dit agenda-item verwijderen?')) return;
    router.delete(route('agenda.destroy', item.id), { preserveScroll: true });
}

function parseDateOnly(dateString) {
    const [y, m, d] = String(dateString || '').split('-').map((v) => Number(v));
    if (!Number.isFinite(y) || !Number.isFinite(m) || !Number.isFinite(d)) return null;
    return new Date(y, m - 1, d);
}

function toDateKey(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

const calendarEntries = computed(() => {
    const agendaItems = (props.items || []).map((item) => ({
        id: item.id,
        kind: 'agenda',
        title: item.theme || 'Agenda-item',
        date: String(item.event_date || ''),
        href: route('agenda.show', item.id),
        tag: 'Agenda',
    }));

    const opkomstenItems = (props.opkomsten || []).map((ev) => ({
        id: ev.id,
        kind: 'opkomst',
        title: ev.theme || ev.activity || 'Opkomst',
        date: String(ev.event_date || ''),
        href: route('opkomsten.show', ev.id),
        tag: ev.is_shared ? 'Gezamenlijk' : 'Opkomst',
    }));

    return [...agendaItems, ...opkomstenItems];
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

function previousMonth() {
    visibleMonth.value = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth() - 1, 1);
}

function nextMonth() {
    visibleMonth.value = new Date(visibleMonth.value.getFullYear(), visibleMonth.value.getMonth() + 1, 1);
}
</script>

<template>
    <Head title="Agenda" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Agenda</h2>
                <button type="button" class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-app-panel px-3 py-2 text-sm font-medium text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15" @click="toggleAddForm">
                    <PlusIcon class="h-5 w-5" />
                    Nieuwe activiteit toevoegen
                </button>
            </div>
        </template>

        <div class="space-y-4">
            <div class="surface-brand-top rounded-2xl border border-app-border bg-white/95 p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-full border border-app-border bg-app-panel p-2 text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark"
                        @click="previousMonth"
                    >
                        <ChevronLeftIcon class="h-5 w-5" />
                    </button>
                    <h3 class="text-lg font-semibold tracking-tight text-app-ink dark:text-app-ink-dark">{{ monthLabel }}</h3>
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-full border border-app-border bg-app-panel p-2 text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark"
                        @click="nextMonth"
                    >
                        <ChevronRightIcon class="h-5 w-5" />
                    </button>
                </div>

                <div class="grid grid-cols-7 border-b border-app-border pb-2 text-center text-xs font-semibold uppercase tracking-wide text-app-muted dark:border-app-border-dark dark:text-app-muted-dark">
                    <span v-for="day in weekDays" :key="`week-${day}`">{{ day }}</span>
                </div>

                <div class="mt-2 grid grid-cols-7 gap-2">
                    <div
                        v-for="cell in calendarDays"
                        :key="cell.key"
                        class="min-h-[7.25rem] rounded-xl border p-2"
                        :class="[
                            cell.inCurrentMonth
                                ? 'border-app-border bg-app-panel dark:border-app-border-dark dark:bg-app-panel-dark'
                                : 'border-app-border/60 bg-app-canvas/60 dark:border-app-border-dark/50 dark:bg-app-canvas-dark/60',
                            cell.isToday ? 'ring-2 ring-brand-blue/60' : '',
                        ]"
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
                                    : 'bg-brand-blue/15 text-brand-blue-dark hover:bg-brand-blue/25 dark:bg-brand-blue/25 dark:text-app-ink-dark'"
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
            </div>

            <form v-show="showAddForm" class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark" @submit.prevent="submitAdd">
                <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
                    <label class="text-sm font-semibold sm:pt-2.5">Naam activiteit</label>
                    <input v-model="form.theme" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">Datum</label>
                    <input v-model="form.event_date" type="date" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">Locatie</label>
                    <input v-model="form.location" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">Tijdstip</label>
                    <input v-model="form.time_slot" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">Genodigden</label>
                    <textarea v-model="form.invitees" rows="2" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">URL</label>
                    <input v-model="form.link_url" type="url" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <label class="text-sm font-semibold sm:pt-2.5">Bijlage</label>
                    <input type="file" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" @change="onAttachmentChange" />
                    <label class="text-sm font-semibold sm:pt-2.5">Notities</label>
                    <textarea v-model="form.notes" rows="3" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />
                    <span class="hidden sm:block" />
                    <button type="submit" class="inline-flex items-center gap-2 rounded bg-brand-blue px-5 py-2 text-sm font-medium text-white hover:bg-brand-blue-dark disabled:opacity-50" :disabled="form.processing">
                        <DocumentCheckIcon class="h-5 w-5" />
                        Opslaan
                    </button>
                </div>
            </form>

            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <h3 class="mb-3 text-lg font-semibold">Actuele agenda-items</h3>
                <AgendaItemsTable :items="props.items" empty-message="Nog geen actuele agenda-items." @edit="editItem" @delete="deleteItem" />
            </div>

            <div v-if="activeSection !== 'bestuur'" class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <h3 class="mb-3 text-lg font-semibold">Opkomsten in deze agenda</h3>
                <div v-if="!props.opkomsten?.length" class="py-4 text-sm text-app-muted dark:text-app-muted-dark">
                    Geen opkomsten voor deze speltak.
                </div>
                <div v-else class="space-y-2">
                    <div
                        v-for="ev in props.opkomsten"
                        :key="`agenda-opkomst-${ev.id}`"
                        class="rounded-lg border border-brand-blue/25 bg-brand-blue/5 px-3 py-2 dark:bg-app-panel-dark/60"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium">{{ ev.theme || ev.activity || 'Opkomst' }}</p>
                                <p class="text-xs text-app-muted dark:text-app-muted-dark">
                                    {{ String(ev.event_date || '').slice(0, 10) }}
                                    <span v-if="ev.event_type"> · {{ ev.event_type }}</span>
                                    <span v-if="ev.section"> · {{ sectionLabels[ev.section] || ev.section }}</span>
                                </p>
                                <p v-if="ev.is_shared && ev.shared_sections?.length" class="mt-1 text-xs text-brand-blue">
                                    Gezamenlijk met: {{ ev.shared_sections.map((s) => sectionLabels[s] || s).join(', ') }}
                                </p>
                            </div>
                            <Link :href="route('opkomsten.show', ev.id)" class="text-xs font-semibold text-brand-blue underline">
                                Open
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
