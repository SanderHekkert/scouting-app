<script setup>
import { ArrowDownTrayIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    items: { type: Array, default: () => [] },
    emptyMessage: { type: String, default: 'Nog geen agenda-items.' },
});

const emit = defineEmits(['edit', 'delete']);

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
</script>

<template>
    <div v-if="!items?.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
        {{ emptyMessage }}
    </div>
    <div v-else class="-mx-1 overflow-x-auto rounded-lg border border-brand-blue/25 sm:mx-0">
        <table class="w-full min-w-[64rem] border-collapse text-left text-sm text-app-ink dark:text-app-ink-dark">
            <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                    <th class="px-3 py-2.5">Naam activiteit</th>
                    <th class="px-3 py-2.5">Datum</th>
                    <th class="px-3 py-2.5">Locatie</th>
                    <th class="px-3 py-2.5">Tijdstip</th>
                    <th class="px-3 py-2.5">Genodigden</th>
                    <th class="px-3 py-2.5">URL</th>
                    <th class="px-3 py-2.5">Bijlagen</th>
                    <th class="px-3 py-2.5">Export</th>
                    <th class="px-3 py-2.5">Notities</th>
                    <th class="px-3 py-2.5">Acties</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-blue/25">
                <tr v-for="item in items" :key="item.id" class="bg-brand-blue/5 dark:bg-app-panel-dark/50">
                    <td class="px-3 py-2.5 align-top">{{ item.theme || '-' }}</td>
                    <td class="px-3 py-2.5 align-top tabular-nums">{{ item.event_date ? String(item.event_date).slice(0, 10) : '-' }}</td>
                    <td class="px-3 py-2.5 align-top">{{ item.location || '-' }}</td>
                    <td class="px-3 py-2.5 align-top">{{ item.time_slot || '-' }}</td>
                    <td class="max-w-[14rem] whitespace-pre-wrap px-3 py-2.5 align-top">{{ item.invitees || '-' }}</td>
                    <td class="max-w-[14rem] break-all px-3 py-2.5 align-top">
                        <a v-if="safeExternalUrl(item.link_url)" :href="safeExternalUrl(item.link_url)" target="_blank" rel="noopener noreferrer" class="text-brand-blue underline">{{ item.link_url }}</a>
                        <span v-else>-</span>
                    </td>
                    <td class="px-3 py-2.5 align-top">
                        <a v-if="item.has_attachment" :href="route('agenda.attachment.download', item.id)" class="text-brand-blue underline">
                            {{ item.attachment_name || 'Download' }}
                        </a>
                        <span v-else>-</span>
                    </td>
                    <td class="px-3 py-2.5 align-top">
                        <div class="flex items-center gap-2">
                            <a :href="item.google_calendar_url" target="_blank" rel="noopener noreferrer" class="text-brand-blue underline">Google</a>
                            <a :href="route('agenda.ics', item.id)" class="inline-flex items-center gap-1 text-brand-blue underline">
                                <ArrowDownTrayIcon class="h-4 w-4" />
                                .ics
                            </a>
                        </div>
                    </td>
                    <td class="max-w-[14rem] whitespace-pre-wrap px-3 py-2.5 align-top">{{ item.notes || '-' }}</td>
                    <td class="px-3 py-2.5 align-top">
                        <button type="button" class="btn-action-edit" @click="emit('edit', item)">
                            <PencilSquareIcon class="h-4 w-4" />
                        </button>
                        <button type="button" class="btn-action-delete ms-2" @click="emit('delete', item)">
                            <TrashIcon class="h-4 w-4" />
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
