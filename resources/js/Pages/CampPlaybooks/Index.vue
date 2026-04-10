<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { DocumentCheckIcon, PencilSquareIcon, PlusIcon } from '@heroicons/vue/24/outline';

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

const showAddForm = ref(false);
const form = useForm({
    camp_year: new Date().getFullYear(),
    title: '',
    content: '',
});

const editForms = ref({});

function toggleAddForm() {
    if (!canCreate.value) return;
    showAddForm.value = !showAddForm.value;
}

function submitAdd() {
    if (!canCreate.value) return;
    form.post(route('camp-playbooks.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.camp_year = new Date().getFullYear();
            showAddForm.value = false;
        },
    });
}

function editState(item) {
    if (!editForms.value[item.id]) {
        editForms.value[item.id] = useForm({
            camp_year: item.camp_year,
            title: item.title,
            content: item.content,
        });
    }
    return editForms.value[item.id];
}

function submitUpdate(item) {
    if (!canUpdate.value) return;
    const state = editState(item);
    state.patch(route('camp-playbooks.update', item.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="`${speltakLabel} - Draaiboek`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Draaiboek</h2>
                <button
                    v-if="canCreate"
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-white shadow-sm transition hover:bg-emerald-800"
                    title="Toevoegen"
                    aria-label="Toevoegen"
                    @click="toggleAddForm"
                >
                    <PlusIcon class="h-5 w-5" />
                </button>
            </div>
        </template>

        <div class="space-y-4">
            <form
                v-if="canCreate && showAddForm"
                class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
                @submit.prevent="submitAdd"
            >
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuw kampdraaiboek</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    <input v-model="form.camp_year" type="number" min="2020" max="2100" class="rounded border border-app-border px-3 py-2" placeholder="Jaar" required />
                    <input v-model="form.title" type="text" class="rounded border border-app-border px-3 py-2" placeholder="Titel (bijv. Pinksterkamp 2026)" required />
                    <textarea v-model="form.content" rows="10" class="rounded border border-app-border px-3 py-2 sm:col-span-2" placeholder="Werk hier het draaiboek uit..." />
                </div>
                <button type="submit" class="btn-action-save" :disabled="form.processing" title="Opslaan" aria-label="Opslaan">
                    <DocumentCheckIcon class="h-5 w-5" />
                </button>
            </form>

            <div class="surface-brand-top space-y-3 rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <div v-if="!props.items.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
                    Nog geen draaiboek toegevoegd.
                </div>
                <div v-for="item in props.items" :key="`playbook-${item.id}`" class="rounded-lg border border-app-border bg-white p-3">
                    <form class="grid gap-3 sm:grid-cols-2" @submit.prevent="submitUpdate(item)">
                        <input v-model="editState(item).camp_year" type="number" min="2020" max="2100" class="rounded border border-app-border px-3 py-2" :disabled="!canUpdate" required />
                        <input v-model="editState(item).title" type="text" class="rounded border border-app-border px-3 py-2" :disabled="!canUpdate" required />
                        <textarea v-model="editState(item).content" rows="10" class="rounded border border-app-border px-3 py-2 sm:col-span-2" :disabled="!canUpdate" />
                        <div v-if="canUpdate" class="sm:col-span-2">
                            <button type="submit" class="btn-action-save" :disabled="editState(item).processing" title="Opslaan" aria-label="Opslaan">
                                <PencilSquareIcon class="h-5 w-5" />
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

