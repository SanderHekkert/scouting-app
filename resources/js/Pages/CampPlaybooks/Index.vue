<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { DocumentDuplicateIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    items: { type: Array, default: () => [] },
});

const page = usePage();
const perms = computed(() => page.props.auth?.permissions?.camp_playbooks ?? {});
const canCreate = computed(() => !!perms.value.create);
const canUpdate = computed(() => !!perms.value.update);

const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabels[page.props.auth?.active_section] || 'Dolfijnen');

function copyItem(item) {
    if (!canCreate.value) return;
    router.post(route('camp-playbooks.copy', item.id), {}, { preserveScroll: true });
}

function deleteItem(item) {
    if (!canUpdate.value) return;
    if (!confirm(`Draaiboek "${item.title}" verwijderen?`)) return;
    router.delete(route('camp-playbooks.destroy', item.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`${speltakLabel} - Draaiboek`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-black">{{ speltakLabel }} - Draaiboek</h2>
                <Link
                    v-if="canCreate"
                    :href="route('camp-playbooks.create')"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-white shadow-sm transition hover:bg-emerald-800"
                    title="Toevoegen"
                    aria-label="Toevoegen"
                >
                    <PlusIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <div class="space-y-4">
            <div class="surface-brand-top space-y-3 rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <div v-if="!props.items.length" class="py-6 text-center text-sm text-black">
                    Nog geen draaiboek toegevoegd.
                </div>
                <div v-for="item in props.items" :key="`playbook-${item.id}`" class="rounded-lg border border-app-border bg-white p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-black">{{ item.camp_year }} - {{ item.title }}</p>
                            <p class="mt-1 line-clamp-4 whitespace-pre-wrap text-sm text-black">{{ item.content }}</p>
                        </div>
                        <div class="flex items-center gap-1">
                            <Link v-if="canUpdate" :href="route('camp-playbooks.show', item.id)" class="btn-action-save" title="Bewerken" aria-label="Bewerken">
                                <PencilSquareIcon class="h-5 w-5" />
                            </Link>
                            <button v-if="canCreate" type="button" class="btn-action-save" title="Kopie maken" aria-label="Kopie maken" @click="copyItem(item)">
                                <DocumentDuplicateIcon class="h-5 w-5" />
                            </button>
                            <button v-if="canUpdate" type="button" class="btn-action-delete" title="Verwijderen" aria-label="Verwijderen" @click="deleteItem(item)">
                                <TrashIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

