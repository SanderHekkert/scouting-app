<script setup>
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    events: {
        type: Array,
        default: () => [],
    },
    emptyMessage: {
        type: String,
        default: 'Geen agenda-items.',
    },
    isFieldSaving: {
        type: Function,
        required: true,
    },
    /** Rij visueel benadrukken (bijv. na link vanaf dashboard ?event=id) */
    highlightEventId: {
        type: Number,
        default: null,
    },
    leaders: {
        type: Array,
        default: () => [],
    },
    taskItems: {
        type: Array,
        default: () => [],
    },
    canEditAgenda: {
        type: Boolean,
        default: true,
    },
    canMarkOwnPresence: {
        type: Boolean,
        default: false,
    },
    currentUserName: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['delete', 'edit', 'set-own-attendance']);

function splitAbsentNames(value) {
    const text = String(value ?? '').trim();
    if (!text) return [];
    const items = text
        .split(',')
        .map((n) => n.trim())
        .filter(Boolean);
    return [...new Set(items)];
}

function firstNameOnly(name) {
    const s = String(name ?? '').trim();
    if (!s) return '';
    return s.split(/\s+/)[0] || s;
}

function isCurrentUserAbsent(event) {
    const self = String(props.currentUserName || '').trim().toLowerCase();
    if (!self) return false;
    return splitAbsentNames(event?.absent).some((name) => String(name).trim().toLowerCase() === self);
}

function taskIdsForEvent(event) {
    return Array.isArray(event?.task_item_ids) ? event.task_item_ids.map((id) => Number(id)).filter((id) => Number.isFinite(id)) : [];
}

function taskLabelById(id) {
    return props.taskItems.find((task) => Number(task.id) === Number(id))?.title || `Taak #${id}`;
}

</script>

<template>
    <div v-if="!props.events?.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
        {{ emptyMessage }}
    </div>
    <div v-else class="space-y-2 md:space-y-0">
        <div class="md:hidden space-y-2">
            <div
                v-for="event in props.events"
                :key="`mob-${event.id}`"
                :id="`agenda-event-row-${event.id}`"
                class="surface-brand-top rounded-xl border border-brand-blue/30 bg-app-panel px-4 py-3 text-app-ink shadow-sm dark:bg-app-panel-dark/95 dark:text-app-ink-dark"
                :class="{
                    'ring-2 ring-brand-yellow/80 ring-offset-2 ring-offset-app-panel dark:ring-brand-yellow/70 dark:ring-offset-app-panel-dark':
                        props.highlightEventId != null && Number(event.id) === props.highlightEventId,
                }"
            >
                <div class="text-sm font-semibold">{{ event.theme || '-' }}</div>
                <div class="mt-2 grid gap-2 text-sm">
                    <div>
                        <p class="text-xs text-app-muted dark:text-app-muted-dark">Datum</p>
                        <p>{{ event.event_date ? String(event.event_date).slice(0, 10) : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-app-muted dark:text-app-muted-dark">Type opkomst</p>
                        <p>{{ event.event_type || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-app-muted dark:text-app-muted-dark">Wat ga je doen?</p>
                        <p>{{ event.activity || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-app-muted dark:text-app-muted-dark">Programma door</p>
                        <p>{{ event.program_by || '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-app-muted dark:text-app-muted-dark">Afwezig</p>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            <span
                                v-for="name in splitAbsentNames(event.absent)"
                                :key="`mob-absent-chip-${event.id}-${name}`"
                                class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                            >
                                {{ firstNameOnly(name) }}
                            </span>
                        </div>
                        <button
                            v-if="props.canMarkOwnPresence"
                            type="button"
                            class="mt-2 rounded-md border border-brand-blue/40 bg-brand-blue/10 px-2 py-1 text-xs font-medium text-brand-blue-dark hover:bg-brand-blue/20"
                            @click="$emit('set-own-attendance', event, isCurrentUserAbsent(event))"
                        >
                            {{ isCurrentUserAbsent(event) ? 'Meld aanwezig' : 'Meld afwezig' }}
                        </button>
                    </div>
                    <div>
                        <p class="text-xs text-app-muted dark:text-app-muted-dark">Taken</p>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            <span
                                v-for="taskId in taskIdsForEvent(event)"
                                :key="`mob-task-chip-${event.id}-${taskId}`"
                                class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                            >
                                {{ taskLabelById(taskId) }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs text-app-muted dark:text-app-muted-dark">Bijzonderheden</p>
                        <p class="whitespace-pre-wrap">{{ event.notes || '-' }}</p>
                    </div>
                </div>
                <div v-if="props.canEditAgenda" class="mt-3 flex items-center gap-2 border-t border-brand-blue/25 pt-3 dark:border-brand-blue/35">
                    <button type="button" class="btn-action-edit" title="Bewerken" @click="$emit('edit', event)">
                        <PencilSquareIcon class="h-4 w-4 shrink-0" />
                    </button>
                    <button type="button" class="btn-action-delete" title="Verwijderen" @click="$emit('delete', event)">
                        <TrashIcon class="h-4 w-4 shrink-0" />
                    </button>
                </div>
            </div>
        </div>
        <div class="surface-brand-top-lg -mx-1 hidden overflow-x-auto rounded-lg border border-brand-blue/25 sm:mx-0 md:block">
        <table class="w-full min-w-[56rem] border-collapse text-left text-sm text-app-ink sm:min-w-[72rem] dark:text-app-ink-dark">
            <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                    <th scope="col" class="min-w-[7rem] px-3 py-2.5">Thema</th>
                    <th scope="col" class="whitespace-nowrap px-3 py-2.5">Datum</th>
                    <th scope="col" class="min-w-[8rem] px-3 py-2.5">Type opkomst</th>
                    <th scope="col" class="min-w-[10rem] px-3 py-2.5">Wat ga je doen?</th>
                    <th scope="col" class="min-w-[7rem] px-3 py-2.5">Programma door</th>
                    <th scope="col" class="min-w-[12rem] px-3 py-2.5">Afwezig</th>
                    <th scope="col" class="min-w-[11rem] px-3 py-2.5">Taken</th>
                    <th scope="col" class="min-w-[11rem] px-3 py-2.5">Bijzonderheden</th>
                    <th scope="col" class="min-w-[9rem] whitespace-nowrap px-3 py-2.5 text-end sm:text-start">Acties</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-blue/25">
                <tr
                    v-for="event in props.events"
                    :key="event.id"
                    :id="`agenda-event-row-${event.id}`"
                    class="scroll-mt-24 bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                    :class="{
                        'relative z-0 ring-2 ring-brand-yellow/80 ring-offset-2 ring-offset-app-panel dark:ring-brand-yellow/70 dark:ring-offset-app-panel-dark':
                            props.highlightEventId != null && Number(event.id) === props.highlightEventId,
                    }"
                >
                    <td class="px-3 py-2.5 align-top">{{ event.theme || '-' }}</td>
                    <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums">{{ event.event_date ? String(event.event_date).slice(0, 10) : '-' }}</td>
                    <td class="px-3 py-2.5 align-top">{{ event.event_type || '-' }}</td>
                    <td class="px-3 py-2.5 align-top">{{ event.activity || '-' }}</td>
                    <td class="px-3 py-2.5 align-top">{{ event.program_by || '-' }}</td>
                    <td class="max-w-[18rem] px-3 py-2.5 align-top">
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="name in splitAbsentNames(event.absent)"
                                :key="`desk-absent-chip-${event.id}-${name}`"
                                class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                            >
                                {{ firstNameOnly(name) }}
                            </span>
                        </div>
                        <button
                            v-if="props.canMarkOwnPresence"
                            type="button"
                            class="mt-2 rounded-md border border-brand-blue/40 bg-brand-blue/10 px-2 py-1 text-xs font-medium text-brand-blue-dark hover:bg-brand-blue/20"
                            @click="$emit('set-own-attendance', event, isCurrentUserAbsent(event))"
                        >
                            {{ isCurrentUserAbsent(event) ? 'Meld aanwezig' : 'Meld afwezig' }}
                        </button>
                    </td>
                    <td class="max-w-[16rem] px-3 py-2.5 align-top">
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="taskId in taskIdsForEvent(event)"
                                :key="`desk-task-chip-${event.id}-${taskId}`"
                                class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                            >
                                {{ taskLabelById(taskId) }}
                            </span>
                        </div>
                    </td>
                    <td class="max-w-[16rem] px-3 py-2.5 align-top whitespace-pre-wrap">{{ event.notes || '-' }}</td>
                    <td class="px-3 py-2.5 align-top space-x-2">
                        <button v-if="props.canEditAgenda" type="button" class="btn-action-edit" title="Bewerken" @click="$emit('edit', event)">
                            <PencilSquareIcon class="h-4 w-4 shrink-0" />
                        </button>
                        <button v-if="props.canEditAgenda" type="button" class="btn-action-delete" title="Verwijderen" @click="$emit('delete', event)">
                            <TrashIcon class="h-4 w-4 shrink-0" />
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
</template>
