<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, PaperAirplaneIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const destinations = [
    { label: 'Dashboard', value: route('dashboard') },
    { label: 'Agenda', value: route('agenda.index') },
    { label: 'Opkomsten', value: route('opkomsten.index') },
    { label: 'Leden', value: route('members.index') },
    { label: 'Leiding', value: route('leaders.index') },
    { label: 'Taakverdeling', value: route('task-items.index') },
    { label: 'Belangrijke info', value: route('info-notes.index') },
];

const form = useForm({
    title: '',
    body: '',
    url: route('dashboard'),
});

function submit() {
    form.post(route('admin.push-notifications.store'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Pushmelding toevoegen" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Pushmelding toevoegen</h2>
                <Link
                    :href="route('admin.push-notifications.index')"
                    class="btn-action-back"
                    title="Terug"
                    aria-label="Terug"
                >
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
            <p class="text-sm text-app-muted dark:text-app-muted-dark">
                Verstuur een pushmelding naar alle apparaten met een actief abonnement.
            </p>

            <form class="mt-4 grid gap-3" @submit.prevent="submit">
                <input
                    v-model="form.title"
                    type="text"
                    class="rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    placeholder="Titel van de melding"
                />
                <input
                    v-model="form.body"
                    type="text"
                    class="rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    placeholder="Bericht"
                />
                <select
                    v-model="form.url"
                    class="rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:[&>option]:bg-app-canvas-dark dark:[&>option]:text-app-ink-dark"
                >
                    <option v-for="destination in destinations" :key="destination.value" :value="destination.value">
                        {{ destination.label }}
                    </option>
                </select>

                <button
                    type="submit"
                    class="inline-flex w-fit items-center gap-2 rounded bg-brand-blue px-4 py-2 text-sm font-semibold text-white hover:bg-brand-blue-dark disabled:opacity-50"
                    :disabled="form.processing"
                >
                    <PaperAirplaneIcon class="h-4 w-4" />
                    Verstuur push
                </button>
            </form>

            <p v-if="page.props.flash?.status" class="mt-3 text-sm text-emerald-700 dark:text-emerald-300">
                {{ page.props.flash.status }}
            </p>
            <p v-if="form.errors.title" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ form.errors.title }}</p>
            <p v-if="form.errors.body" class="mt-1 text-sm text-red-700 dark:text-red-300">{{ form.errors.body }}</p>
            <p v-if="form.errors.url" class="mt-1 text-sm text-red-700 dark:text-red-300">{{ form.errors.url }}</p>
        </div>
    </AuthenticatedLayout>
</template>
