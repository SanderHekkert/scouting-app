<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, DocumentCheckIcon, DocumentDuplicateIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    mode: { type: String, default: 'create' },
    item: { type: Object, default: null },
    copyItem: { type: Object, default: null },
});

const page = usePage();
const perms = computed(() => page.props.auth?.permissions?.camp_playbooks ?? {});
const canCreate = computed(() => !!perms.value.create);
const canUpdate = computed(() => !!perms.value.update);
const isEdit = computed(() => props.mode === 'edit' && !!props.item?.id);

const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabels[page.props.auth?.active_section] || 'Dolfijnen');

const source = props.item || props.copyItem || {};
const form = useForm({
    camp_year: source.camp_year || new Date().getFullYear(),
    title: source.title || '',
    content: source.content || '',
});

function submit() {
    if (isEdit.value) {
        if (!canUpdate.value) return;
        form.patch(route('camp-playbooks.update', props.item.id));
        return;
    }
    if (!canCreate.value) return;
    form.post(route('camp-playbooks.store'));
}

function destroyItem() {
    if (!isEdit.value || !canUpdate.value) return;
    if (!confirm(`Draaiboek "${props.item.title}" verwijderen?`)) return;
    router.delete(route('camp-playbooks.destroy', props.item.id));
}

function copyItem() {
    if (!isEdit.value || !canCreate.value) return;
    router.post(route('camp-playbooks.copy', props.item.id));
}
</script>

<template>
    <Head :title="`${speltakLabel} - ${isEdit ? 'Draaiboek bewerken' : 'Draaiboek toevoegen'}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-black">{{ speltakLabel }} - {{ isEdit ? 'Draaiboek bewerken' : 'Draaiboek toevoegen' }}</h2>
                <Link :href="route('camp-playbooks.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark" @submit.prevent="submit">
            <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="form.camp_year" type="number" min="2020" max="2100" class="rounded border border-app-border bg-white px-3 py-2 text-black" placeholder="Jaar" required />
                <input v-model="form.title" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-black" placeholder="Titel (bijv. Pinksterkamp 2026)" required />
                <textarea v-model="form.content" rows="12" class="rounded border border-app-border bg-white px-3 py-2 text-black sm:col-span-2" placeholder="Werk hier het draaiboek uit..." />
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-app-border pt-3">
                <button type="submit" class="btn-action-save" :disabled="form.processing" title="Opslaan" aria-label="Opslaan">
                    <DocumentCheckIcon class="h-5 w-5" />
                </button>
                <button v-if="isEdit && canCreate" type="button" class="btn-action-save" title="Kopie maken" aria-label="Kopie maken" @click="copyItem">
                    <DocumentDuplicateIcon class="h-5 w-5" />
                </button>
                <button v-if="isEdit && canUpdate" type="button" class="btn-action-delete" title="Verwijderen" aria-label="Verwijderen" @click="destroyItem">
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
