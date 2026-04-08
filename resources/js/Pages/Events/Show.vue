<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    event: { type: Object, required: true },
    leaders: { type: Array, default: () => [] },
    taskItems: { type: Array, default: () => [] },
    allSections: { type: Array, default: () => [] },
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
const shareableSections = computed(() => (props.allSections || []).filter((s) => s !== activeSection.value));

const form = useForm({
    theme: props.event.theme || '',
    event_date: String(props.event.event_date || '').slice(0, 10),
    event_type: props.event.event_type || '',
    activity: props.event.activity || '',
    program_by: props.event.program_by || '',
    absent: props.event.absent || '',
    notes: props.event.notes || '',
    task_item_ids: Array.isArray(props.event.task_item_ids) ? [...props.event.task_item_ids] : [],
    shared_sections: Array.isArray(props.event.shared_sections) ? [...props.event.shared_sections] : [],
});

function submit() {
    form.patch(route('events.update', props.event.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Opkomst bewerken" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Opkomst bewerken</h2>
                <Link :href="route('events.index')" class="rounded border border-app-border px-3 py-1.5 text-sm dark:border-app-border-dark">
                    Terug
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-[9rem_1fr] sm:items-start">
                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Thema</label>
                <input v-model="form.theme" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Datum</label>
                <input v-model="form.event_date" type="date" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Type opkomst</label>
                <input v-model="form.event_type" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Wat ga je doen?</label>
                <input v-model="form.activity" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Programma door</label>
                <select v-model="form.program_by" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark">
                    <option value="">Geen gekozen</option>
                    <option v-for="leader in leaders" :key="`leader-${leader}`" :value="leader">{{ leader }}</option>
                </select>

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Afwezig</label>
                <input v-model="form.absent" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Taken</label>
                <div class="grid gap-2">
                    <label
                        v-for="task in taskItems"
                        :key="`event-task-${task.id}`"
                        class="inline-flex items-center gap-2 rounded border border-app-border bg-white px-2 py-1 text-xs dark:border-app-border-dark dark:bg-app-canvas-dark"
                    >
                        <input v-model="form.task_item_ids" type="checkbox" :value="task.id" class="rounded border-app-border" />
                        {{ task.title }}
                    </label>
                </div>

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Gezamenlijk met</label>
                <div class="flex flex-wrap gap-2">
                    <label
                        v-for="section in shareableSections"
                        :key="`share-${section}`"
                        class="inline-flex items-center gap-2 rounded border border-app-border bg-white px-2 py-1 text-xs dark:border-app-border-dark dark:bg-app-canvas-dark"
                    >
                        <input v-model="form.shared_sections" type="checkbox" :value="section" class="rounded border-app-border" />
                        {{ sectionLabels[section] || section }}
                    </label>
                </div>

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Bijzonderheden</label>
                <textarea v-model="form.notes" rows="4" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 dark:border-app-border-dark dark:bg-app-canvas-dark" />

                <span class="hidden sm:block" aria-hidden="true" />
                <div>
                    <button type="submit" class="rounded bg-brand-blue px-5 py-2 text-sm font-medium text-white hover:bg-brand-blue-dark" :disabled="form.processing">
                        Opslaan
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
