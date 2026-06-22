<script setup>
import { Link } from '@inertiajs/vue3';
import {
    ArrowDownTrayIcon,
    ArrowTopRightOnSquareIcon,
    CalendarDaysIcon,
    PencilSquareIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';
import { withReturnUrl } from '@/utils/saveForm';

const props = defineProps({
    entry: { type: Object, required: true },
    agendaItem: { type: Object, default: null },
    opkomst: { type: Object, default: null },
    task: { type: Object, default: null },
    sectionLabels: { type: Object, default: () => ({}) },
    canUpdateAgendaItem: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

const audienceLabel = computed(() => {
    if (!props.agendaItem) return '';
    if (props.agendaItem.audience_scope === 'all') return 'Iedereen';
    if (props.agendaItem.audience_scope === 'selected') return 'Specifieke personen';
    return 'Alleen mezelf';
});

const agendaDateLabel = computed(() => {
    if (!props.agendaItem) return '-';
    const start = String(props.agendaItem.event_date || '').trim();
    const end = String(props.agendaItem.end_date || '').trim() || start;
    if (!start) return '-';
    if (start === end) return start.slice(0, 10);
    return `${start.slice(0, 10)} t/m ${end.slice(0, 10)}`;
});

const agendaTimeLabel = computed(() => {
    if (!props.agendaItem) return '-';
    const from = String(props.agendaItem.start_time || '').trim();
    const to = String(props.agendaItem.end_time || '').trim();
    if (from && to) return `${from} - ${to}`;
    if (from) return from;
    if (to) return `tot ${to}`;
    return String(props.agendaItem.time_slot || '').trim() || '-';
});

const sectionLabel = computed(() => {
    const section = String(props.opkomst?.section || '');
    return props.sectionLabels[section] || section || '-';
});

function splitNames(value) {
    if (Array.isArray(value)) {
        return [...new Set(value.map((v) => String(v || '').trim()).filter(Boolean))];
    }
    const text = String(value || '').trim();
    if (!text) return [];
    return [...new Set(text.split(',').map((v) => v.trim()).filter(Boolean))];
}

function safeExternalUrl(url) {
    const raw = String(url || '').trim();
    if (!raw) return null;
    try {
        const parsed = new URL(raw, window.location.origin);
        if (!['http:', 'https:'].includes(parsed.protocol.toLowerCase())) return null;
        return parsed.href;
    } catch {
        return null;
    }
}

const editHref = computed(() => {
    if (!props.agendaItem?.id) return '#';
    return withReturnUrl(route('agenda.edit', props.agendaItem.id));
});
</script>

<template>
    <div class="surface-brand-top rounded-2xl border border-brand-blue/30 bg-white/95 p-4 shadow-sm dark:border-brand-blue/35 dark:bg-app-panel-dark">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">{{ entry.tag }}</p>
                <h3 class="mt-1 text-lg font-semibold text-app-ink dark:text-app-ink-dark">{{ entry.title }}</h3>
            </div>
            <button
                type="button"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-app-border bg-white text-app-muted transition hover:bg-brand-blue/10 hover:text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-muted-dark dark:hover:text-app-ink-dark"
                title="Sluiten"
                aria-label="Sluiten"
                @click="emit('close')"
            >
                <XMarkIcon class="h-5 w-5" />
            </button>
        </div>

        <template v-if="agendaItem">
            <div class="mt-4 flex flex-wrap gap-2">
                <a
                    v-if="agendaItem.google_calendar_url"
                    :href="agendaItem.google_calendar_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-white px-3 py-2 text-sm font-medium text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                >
                    <CalendarDaysIcon class="h-4 w-4" />
                    Google Agenda
                </a>
                <a
                    :href="route('agenda.ics', agendaItem.id)"
                    class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-white px-3 py-2 text-sm font-medium text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                >
                    <ArrowDownTrayIcon class="h-4 w-4" />
                    Download .ics
                </a>
                <a
                    v-if="agendaItem.has_attachment"
                    :href="route('agenda.attachment.download', agendaItem.id)"
                    class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-white px-3 py-2 text-sm font-medium text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                >
                    {{ agendaItem.attachment_name || 'Bijlage' }}
                </a>
                <a
                    v-if="safeExternalUrl(agendaItem.link_url)"
                    :href="safeExternalUrl(agendaItem.link_url)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-white px-3 py-2 text-sm font-medium text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                >
                    <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                    Externe link
                </a>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Datum</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ agendaDateLabel }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Tijdstip</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ agendaTimeLabel }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Locatie</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ agendaItem.location || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Zichtbaar voor</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ audienceLabel }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Genodigden</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-app-ink dark:text-app-ink-dark">{{ agendaItem.invitees || '-' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Notities</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-app-ink dark:text-app-ink-dark">{{ agendaItem.notes || '-' }}</p>
                </div>
            </div>

            <div v-if="canUpdateAgendaItem" class="mt-4 border-t border-app-border pt-4 dark:border-app-border-dark">
                <Link
                    :href="editHref"
                    class="btn-action-edit inline-flex items-center gap-2 px-3 py-2"
                >
                    <PencilSquareIcon class="h-4 w-4" />
                    Bewerken
                </Link>
            </div>
        </template>

        <template v-else-if="opkomst">
            <div class="mt-4 flex flex-wrap gap-2">
                <a
                    v-if="safeExternalUrl(opkomst.link_url)"
                    :href="safeExternalUrl(opkomst.link_url)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-white px-3 py-2 text-sm font-medium text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                >
                    <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                    Externe link
                </a>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Datum</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ opkomst.event_date ? String(opkomst.event_date).slice(0, 10) : '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Tijdstip</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ opkomst.time_slot || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Type</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ opkomst.event_type || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Speltak</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ sectionLabel }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Activiteit</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ opkomst.activity || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Programma door</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ opkomst.program_by || '-' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Locatie</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ opkomst.location || '-' }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Genodigden</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-app-ink dark:text-app-ink-dark">{{ opkomst.invitees || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Aanwezig</p>
                    <div class="mt-1 flex flex-wrap gap-1.5">
                        <span
                            v-for="name in splitNames(opkomst.present_names)"
                            :key="`present-${name}`"
                            class="inline-flex items-center rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs text-emerald-800 dark:text-emerald-200"
                        >
                            {{ name }}
                        </span>
                        <span v-if="!splitNames(opkomst.present_names).length" class="text-sm text-app-muted dark:text-app-muted-dark">-</span>
                    </div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Afwezig</p>
                    <div class="mt-1 flex flex-wrap gap-1.5">
                        <span
                            v-for="name in splitNames(opkomst.absent)"
                            :key="`absent-${name}`"
                            class="inline-flex items-center rounded-full bg-red-500/15 px-2 py-0.5 text-xs text-red-800 dark:text-red-200"
                        >
                            {{ name }}
                        </span>
                        <span v-if="!splitNames(opkomst.absent).length" class="text-sm text-app-muted dark:text-app-muted-dark">-</span>
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Notities</p>
                    <p class="mt-1 whitespace-pre-wrap text-sm text-app-ink dark:text-app-ink-dark">{{ opkomst.notes || '-' }}</p>
                </div>
            </div>
        </template>

        <template v-else-if="task">
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Taak</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ task.title || '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Deadline</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ entry.date || '-' }}</p>
                </div>
            </div>
            <div class="mt-4 border-t border-app-border pt-4 dark:border-app-border-dark">
                <Link :href="route('task-items.index')" class="text-sm font-medium text-brand-blue underline">
                    Naar taakverdeling
                </Link>
            </div>
        </template>
    </div>
</template>
