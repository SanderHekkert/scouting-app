<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    form: { type: Object, required: true },
});

const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
};

function download() {
    window.location.href = route('admin.health-forms.download', props.form.id);
}
</script>

<template>
    <Head title="Gezondheidsformulier" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Gezondheidsformulier detail</h2>
                <Link :href="route('admin.health-forms.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <div class="space-y-4">
            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-brand-blue/25 pb-3">
                    <div>
                        <p class="text-base font-semibold text-app-ink dark:text-app-ink-dark">{{ form.original_name }}</p>
                        <p class="text-xs text-app-muted dark:text-app-muted-dark">
                            {{ sectionLabels[form.section] || form.section }} · {{ form.member?.full_name || 'Onbekend lid' }}
                        </p>
                    </div>
                    <button type="button" class="inline-flex items-center gap-2 rounded bg-brand-blue px-3 py-2 text-sm font-semibold text-white hover:bg-brand-blue-dark" @click="download">
                        <ArrowDownTrayIcon class="h-4 w-4" />
                        Download
                    </button>
                </div>

                <div class="mt-4">
                    <p class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Alle info van dit lid</p>
                    <p class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">Naam: {{ form.member?.full_name || '—' }}</p>
                    <p class="text-sm text-app-ink dark:text-app-ink-dark">Adres: {{ form.member?.address || '—' }}</p>
                    <p class="text-sm text-app-ink dark:text-app-ink-dark">Postcode: {{ form.member?.postal_code || '—' }}</p>
                    <p class="text-sm text-app-ink dark:text-app-ink-dark">Woonplaats: {{ form.member?.city || '—' }}</p>
                    <p class="text-sm text-app-ink dark:text-app-ink-dark">Geboortedatum: {{ form.member?.birthday || '—' }}</p>
                    <p class="text-sm text-app-ink dark:text-app-ink-dark">Telefoon moeder: {{ form.member?.phone_mother || '—' }}</p>
                    <p class="text-sm text-app-ink dark:text-app-ink-dark">Telefoon vader: {{ form.member?.phone_father || '—' }}</p>
                    <p class="text-sm text-app-ink dark:text-app-ink-dark">E-mailadres ouders: {{ form.member?.email_parents || '—' }}</p>
                    <p class="text-sm text-app-ink dark:text-app-ink-dark">Bijzonderheden: {{ form.member?.bijzonderheden || '—' }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
