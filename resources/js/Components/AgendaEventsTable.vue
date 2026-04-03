<script setup>
import EditableTextCell from '@/Components/EditableTextCell.vue';
import { TrashIcon } from '@heroicons/vue/24/outline';

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
});

const emit = defineEmits(['patch-field', 'delete']);

function patchField(event, field, raw) {
    emit('patch-field', event, field, raw);
}
</script>

<template>
    <div v-if="!props.events?.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
        {{ emptyMessage }}
    </div>
    <div
        v-else
        class="surface-brand-top-lg -mx-1 overflow-x-auto rounded-lg border border-brand-blue/25 sm:mx-0"
    >
        <table class="w-full min-w-[56rem] border-collapse text-left text-sm text-app-ink sm:min-w-[72rem] dark:text-app-ink-dark">
            <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                    <th scope="col" class="min-w-[7rem] px-3 py-2.5">Thema</th>
                    <th scope="col" class="whitespace-nowrap px-3 py-2.5">Datum</th>
                    <th scope="col" class="min-w-[8rem] px-3 py-2.5">Type opkomst</th>
                    <th scope="col" class="min-w-[10rem] px-3 py-2.5">Wat ga je doen?</th>
                    <th scope="col" class="min-w-[7rem] px-3 py-2.5">Programma door</th>
                    <th scope="col" class="min-w-[12rem] px-3 py-2.5">Afwezig</th>
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
                    <td class="px-3 py-2.5 align-top">
                        <EditableTextCell
                            :text="event.theme || ''"
                            multiline
                            :saving="isFieldSaving(event, 'theme')"
                            @save="(v) => patchField(event, 'theme', v)"
                        />
                    </td>
                    <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums">
                        <EditableTextCell
                            :text="event.event_date ? String(event.event_date).slice(0, 10) : ''"
                            input-kind="date"
                            :multiline="false"
                            :saving="isFieldSaving(event, 'event_date')"
                            @save="(v) => patchField(event, 'event_date', v)"
                        />
                    </td>
                    <td class="px-3 py-2.5 align-top">
                        <EditableTextCell
                            :text="event.event_type || ''"
                            :multiline="false"
                            :saving="isFieldSaving(event, 'event_type')"
                            @save="(v) => patchField(event, 'event_type', v)"
                        />
                    </td>
                    <td class="px-3 py-2.5 align-top">
                        <EditableTextCell
                            :text="event.activity || ''"
                            multiline
                            :saving="isFieldSaving(event, 'activity')"
                            @save="(v) => patchField(event, 'activity', v)"
                        />
                    </td>
                    <td class="px-3 py-2.5 align-top">
                        <EditableTextCell
                            :text="event.program_by || ''"
                            :multiline="false"
                            :saving="isFieldSaving(event, 'program_by')"
                            @save="(v) => patchField(event, 'program_by', v)"
                        />
                    </td>
                    <td class="max-w-[18rem] px-3 py-2.5 align-top">
                        <EditableTextCell
                            :text="event.absent || ''"
                            multiline
                            :saving="isFieldSaving(event, 'absent')"
                            @save="(v) => patchField(event, 'absent', v)"
                        />
                    </td>
                    <td class="max-w-[16rem] px-3 py-2.5 align-top">
                        <EditableTextCell
                            :text="event.notes || ''"
                            multiline
                            :saving="isFieldSaving(event, 'notes')"
                            @save="(v) => patchField(event, 'notes', v)"
                        />
                    </td>
                    <td class="px-3 py-2.5 align-top">
                        <button type="button" class="btn-action-delete" @click="$emit('delete', event)">
                            <TrashIcon class="h-4 w-4 shrink-0" />
                            Verwijderen
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
