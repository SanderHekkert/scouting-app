<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    canCreateCrossSection: { type: Boolean, default: false },
    targetSections: { type: Array, default: () => [] },
});

const page = usePage();
const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = sectionLabels[page.props.auth?.active_section] || 'Dolfijnen';

const form = useForm({
    category: '',
    content: '',
    link: '',
    target_section: '',
});

function submit() {
    form.post(route('info-notes.store'));
}
</script>

<template>
    <Head :title="`${speltakLabel} - Belangrijke info toevoegen`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Belangrijke info toevoegen</h2>
                <Link :href="route('info-notes.index')" class="btn-action-back">Terug</Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5" @submit.prevent="submit">
            <div class="grid gap-3">
                <input v-model="form.category" type="text" placeholder="Categorie" class="rounded border border-app-border px-3 py-2" required />
                <textarea v-model="form.content" rows="5" placeholder="Inhoud" class="rounded border border-app-border px-3 py-2" required />
                <input v-model="form.link" type="text" placeholder="Link (optioneel)" class="rounded border border-app-border px-3 py-2" />
                <select v-if="props.canCreateCrossSection" v-model="form.target_section" class="rounded border border-app-border px-3 py-2">
                    <option value="">Kies speltak</option>
                    <option v-for="section in props.targetSections" :key="section" :value="section">{{ sectionLabels[section] || section }}</option>
                </select>
            </div>
            <button type="submit" class="rounded bg-emerald-700 px-4 py-2 text-white">Opslaan</button>
        </form>
    </AuthenticatedLayout>
</template>

