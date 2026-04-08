<script setup>
import AgendaItemsTable from '@/Components/AgendaItemsTable.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    archivedItems: { type: Array, default: () => [] },
});

function editItem(item) {
    router.get(route('agenda.show', item.id));
}

function deleteItem(item) {
    if (!item?.id) return;
    if (!confirm('Dit agenda-item verwijderen?')) return;
    router.delete(route('agenda.destroy', item.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Gearchiveerde agenda-items" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Agenda</h2>
        </template>
        <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
            <h3 class="mb-3 text-lg font-semibold">Gearchiveerde agenda-items</h3>
            <AgendaItemsTable :items="props.archivedItems" empty-message="Nog geen gearchiveerde agenda-items." @edit="editItem" @delete="deleteItem" />
        </div>
    </AuthenticatedLayout>
</template>
