<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, ArrowTopRightOnSquareIcon, CalendarDaysIcon, PaperClipIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    item: { type: Object, required: true },
});

const audienceLabel = computed(() => {
    if (props.item.audience_scope === 'all') return 'Iedereen';
    if (props.item.audience_scope === 'selected') return 'Specifieke personen';
    return 'Alleen mezelf';
});
</script>

<template>
    <Head title="Agenda-item details" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Agenda-item details</h2>
                <Link :href="route('agenda.index')" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-app-border text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>
        <div class="surface-brand-top space-y-5 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark">
            <div class="flex flex-wrap gap-2">
                <a
                    v-if="props.item.google_calendar_url"
                    :href="props.item.google_calendar_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-white px-3 py-2 text-sm font-medium text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                >
                    <CalendarDaysIcon class="h-4 w-4" />
                    Google Agenda
                </a>
                <a
                    :href="route('agenda.ics', props.item.id)"
                    class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-white px-3 py-2 text-sm font-medium text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                >
                    <CalendarDaysIcon class="h-4 w-4" />
                    Download .ics
                </a>
                <a
                    v-if="props.item.attachment_name"
                    :href="route('agenda.attachment.download', props.item.id)"
                    class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-white px-3 py-2 text-sm font-medium text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                >
                    <PaperClipIcon class="h-4 w-4" />
                    {{ props.item.attachment_name }}
                </a>
                <a
                    v-if="props.item.link_url"
                    :href="props.item.link_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-white px-3 py-2 text-sm font-medium text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                >
                    <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                    Externe link
                </a>
            </div>

            <div class="grid gap-4 sm:grid-cols-[12rem_1fr] sm:items-start">
                <p class="text-sm font-semibold sm:pt-1">Naam activiteit</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">{{ props.item.theme || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Datum</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">{{ props.item.event_date || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Locatie</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">{{ props.item.location || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Tijdstip</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">{{ props.item.time_slot || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Genodigden</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 whitespace-pre-wrap dark:border-app-border-dark dark:bg-app-canvas-dark">{{ props.item.invitees || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Notities</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 whitespace-pre-wrap dark:border-app-border-dark dark:bg-app-canvas-dark">{{ props.item.notes || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Zichtbaar voor</p>
                <div class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">
                    <p>{{ audienceLabel }}</p>
                    <div v-if="props.item.audience_scope === 'selected'" class="mt-2 flex flex-wrap gap-2">
                        <span
                            v-for="user in props.item.target_users || []"
                            :key="`target-user-${user.id}`"
                            class="inline-flex items-center rounded-full bg-brand-blue/15 px-2.5 py-1 text-xs"
                        >
                            {{ user.name }}
                        </span>
                        <span v-if="!(props.item.target_users || []).length" class="text-sm text-app-muted dark:text-app-muted-dark">Geen specifieke personen gekozen</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
