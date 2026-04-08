<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownTrayIcon, CloudArrowUpIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    forms: { type: Array, default: () => [] },
    can_manage: { type: Boolean, default: false },
    active_section: { type: String, default: '' },
});

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
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-blue px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-blue-dark"
                >
                    <CloudArrowUpIcon class="h-5 w-5" />
                    Nieuw formulier uploaden
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
                        class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-brand-blue/20 bg-brand-blue/5 px-3 py-2"
                    >
                        <div class="min-w-0 cursor-pointer" @click="router.get(route('admin.health-forms.show', row.id))">
                            <p class="truncate text-sm font-medium text-app-ink dark:text-app-ink-dark">{{ row.original_name }}</p>
                            <p class="text-xs text-app-muted dark:text-app-muted-dark">
                                {{ row.member_name || 'Onbekend lid' }} · {{ row.section || '—' }} · {{ formatSize(row.size) }} · {{ row.uploader_name || 'Onbekend' }} · {{ row.created_at || '—' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="btn-action-edit" title="Downloaden" @click="download(row)">
                                <ArrowDownTrayIcon class="h-4 w-4" />
                            </button>
                            <button v-if="props.can_manage" type="button" class="btn-action-delete" title="Verwijderen" @click="remove(row)">
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
