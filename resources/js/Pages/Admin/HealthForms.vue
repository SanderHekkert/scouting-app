<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, ChevronDownIcon, ChevronUpIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps({
    forms: { type: Array, default: () => [] },
    can_manage: { type: Boolean, default: false },
    active_section: { type: String, default: '' },
});

const expandedRowId = ref(null);

function toggleRow(formRow) {
    if (!formRow?.id) return;
    expandedRowId.value = expandedRowId.value === formRow.id ? null : formRow.id;
}

function download(formRow) {
    if (!formRow?.id) return;
    window.location.href = route('admin.health-forms.download', formRow.id);
}

function remove(formRow) {
    if (!formRow?.id) return;
    if (!confirm(`Bestand "${formRow.original_name}" verwijderen?`)) return;
    router.delete(route('admin.health-forms.destroy', formRow.id), {
        preserveScroll: true,
    });
}

function formatSize(size) {
    const bytes = Number(size || 0);
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}
</script>

<template>
    <Head title="Gezondheidsformulieren" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Gezondheidsformulieren</h2>
                <Link
                    v-if="props.can_manage"
                    :href="route('admin.health-forms.create')"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-blue text-white shadow-sm transition hover:bg-brand-blue-dark"
                    title="Nieuw formulier uploaden"
                    aria-label="Nieuw formulier uploaden"
                >
                    <PlusIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <div class="space-y-4">
            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <div v-if="!props.forms.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
                    Nog geen gezondheidsformulieren geüpload.
                </div>
                <div v-else class="space-y-2">
                    <div
                        v-for="row in props.forms"
                        :key="row.id"
                        class="rounded-lg border border-brand-blue/20 bg-brand-blue/5 px-3 py-2"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <button
                                type="button"
                                class="min-w-0 flex-1 text-left"
                                @click="toggleRow(row)"
                            >
                                <p class="truncate text-sm font-medium text-app-ink dark:text-app-ink-dark">{{ row.original_name }}</p>
                                <p class="text-xs text-app-muted dark:text-app-muted-dark">
                                    {{ row.member_name || 'Onbekend lid' }} · {{ row.section || '—' }} · {{ formatSize(row.size) }} · {{ row.uploader_name || 'Onbekend' }}
                                </p>
                            </button>
                            <div class="flex items-center gap-2">
                                <button type="button" class="btn-action-edit" title="Downloaden" @click="download(row)">
                                    <ArrowDownTrayIcon class="h-4 w-4" />
                                </button>
                                <button v-if="props.can_manage" type="button" class="btn-action-delete" title="Verwijderen" @click="remove(row)">
                                    <TrashIcon class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-brand-blue/40 text-brand-blue hover:bg-brand-blue/10"
                                    :title="expandedRowId === row.id ? 'Inklappen' : 'Uitklappen'"
                                    @click="toggleRow(row)"
                                >
                                    <ChevronUpIcon v-if="expandedRowId === row.id" class="h-4 w-4" />
                                    <ChevronDownIcon v-else class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div v-if="expandedRowId === row.id" class="mt-3 border-t border-brand-blue/20 pt-3">
                            <div class="grid grid-cols-1 gap-2 text-xs text-app-ink dark:text-app-ink-dark md:grid-cols-2">
                                <p><span class="font-semibold">Bestandsnaam:</span> {{ row.original_name || '—' }}</p>
                                <p><span class="font-semibold">Speltak:</span> {{ row.section || '—' }}</p>
                                <p><span class="font-semibold">Lid:</span> {{ row.member_name || 'Onbekend lid' }}</p>
                                <p><span class="font-semibold">Geüpload door:</span> {{ row.uploader_name || 'Onbekend' }}</p>
                                <p class="md:col-span-2"><span class="font-semibold">Aangemaakt op:</span> {{ row.created_at || 'Onbekend' }}</p>
                                <p><span class="font-semibold">Roepnaam:</span> {{ row.member?.first_name || '—' }}</p>
                                <p><span class="font-semibold">Achternaam:</span> {{ row.member?.last_name || '—' }}</p>
                                <p><span class="font-semibold">Adres:</span> {{ row.member?.address || '—' }}</p>
                                <p><span class="font-semibold">Postcode:</span> {{ row.member?.postal_code || '—' }}</p>
                                <p><span class="font-semibold">Woonplaats:</span> {{ row.member?.city || '—' }}</p>
                                <p><span class="font-semibold">Geboortedatum:</span> {{ row.member?.birthday || '—' }}</p>
                                <p><span class="font-semibold">Mobiel moeder:</span> {{ row.member?.phone_mother || '—' }}</p>
                                <p><span class="font-semibold">Mobiel vader:</span> {{ row.member?.phone_father || '—' }}</p>
                                <p><span class="font-semibold">E-mailadres ouders:</span> {{ row.member?.email_parents || '—' }}</p>
                                <p class="md:col-span-2"><span class="font-semibold">Bijzonderheden:</span> {{ row.member?.bijzonderheden || '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
