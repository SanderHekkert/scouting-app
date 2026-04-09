<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowTopRightOnSquareIcon, ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    item: { type: Object, required: true },
});

const sectionLabel = computed(() =>
    String(props.item.section || '')
        .split('-')
        .map((part) => {
            const clean = String(part || '').trim();
            if (!clean) return '';
            return clean.charAt(0).toUpperCase() + clean.slice(1);
        })
        .filter(Boolean)
        .join(' ') || '-',
);
</script>

<template>
    <Head title="Opkomst details" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Opkomst details</h2>
                <Link :href="route('agenda.index')" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-app-border text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <div class="surface-brand-top space-y-5 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark">
            <div class="flex flex-wrap gap-2">
                <a
                    v-if="item.link_url"
                    :href="item.link_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-white px-3 py-2 text-sm font-medium text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15"
                >
                    <ArrowTopRightOnSquareIcon class="h-4 w-4" />
                    Externe link
                </a>
            </div>

            <div class="grid gap-4 sm:grid-cols-[12rem_1fr] sm:items-start">
                <p class="text-sm font-semibold sm:pt-1">Naam opkomst</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">{{ item.theme || item.activity || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Datum</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">{{ item.event_date || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Tijdstip</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">{{ item.time_slot || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Locatie</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">{{ item.location || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Type</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">{{ item.event_type || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Programma door</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">{{ item.program_by || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Speltak</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">{{ sectionLabel }}</p>

                <p class="text-sm font-semibold sm:pt-1">Genodigden</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 whitespace-pre-wrap dark:border-app-border-dark dark:bg-app-canvas-dark">{{ item.invitees || '-' }}</p>

                <p class="text-sm font-semibold sm:pt-1">Notities</p>
                <p class="rounded border border-app-border bg-white px-3 py-2 whitespace-pre-wrap dark:border-app-border-dark dark:bg-app-canvas-dark">{{ item.notes || '-' }}</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
