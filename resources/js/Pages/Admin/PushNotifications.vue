<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { BellAlertIcon, PlusIcon } from '@heroicons/vue/24/outline';

const page = usePage();
const props = defineProps({
    canCreate: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Head title="Pushmeldingen" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Pushmeldingen</h2>
                <Link
                    v-if="props.canCreate"
                    :href="route('admin.push-notifications.create')"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-white shadow-sm transition hover:bg-emerald-800"
                    title="Nieuwe melding"
                    aria-label="Nieuwe melding"
                >
                    <PlusIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
            <div class="flex items-start gap-3">
                <BellAlertIcon class="mt-0.5 h-5 w-5 shrink-0 text-brand-blue" />
                <div>
                    <h3 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">Overzicht</h3>
                    <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                        Hier zie je de pushmeldingen-module. Pushmeldingen worden verstuurd naar apparaten met een actief push-abonnement.
                    </p>
                </div>
            </div>

            <p v-if="props.canCreate" class="mt-4 text-sm text-app-muted dark:text-app-muted-dark">
                Nieuwe pushmelding versturen? Gebruik de plusknop rechtsboven.
            </p>
            <p v-else class="mt-4 text-sm text-app-muted dark:text-app-muted-dark">
                Alleen bestuur en admin kunnen nieuwe pushmeldingen versturen.
            </p>

            <p v-if="page.props.flash?.status" class="mt-3 text-sm text-emerald-700 dark:text-emerald-300">
                {{ page.props.flash.status }}
            </p>

            <div class="mt-5 rounded-lg border border-app-border bg-white/70 p-4 text-sm text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark/70 dark:text-app-ink-dark">
                <p>Tip: gebruikers beheren hun eigen abonnement op de profielpagina bij <span class="font-semibold">Pushmeldingen</span>.</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
