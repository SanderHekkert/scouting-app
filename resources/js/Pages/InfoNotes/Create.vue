<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';
import { useSaveRedirect } from '@/utils/saveForm';

const fieldClass =
    'rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark';

const props = defineProps({
    canCreateCrossSection: { type: Boolean, default: false },
    targetSections: { type: Array, default: () => [] },
});
const { applySaveRedirect, saveFormOptions } = useSaveRedirect();

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
    form
        .transform((data) => applySaveRedirect(data))
        .post(route('info-notes.store'), saveFormOptions());
}
</script>

<template>
    <Head :title="`${speltakLabel} - Belangrijke info toevoegen`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Belangrijke info toevoegen</h2>
                <Link :href="route('info-notes.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form
            class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark"
            @submit.prevent="submit"
        >
            <div class="grid gap-3">
                <input v-model="form.category" type="text" placeholder="Categorie" :class="fieldClass" required />
                <textarea v-model="form.content" rows="5" placeholder="Inhoud" :class="fieldClass" required />
                <input v-model="form.link" type="text" placeholder="Link (optioneel)" :class="fieldClass" />
                <select
                    v-if="props.canCreateCrossSection"
                    v-model="form.target_section"
                    :class="`${fieldClass} dark:[&>option]:bg-app-canvas-dark dark:[&>option]:text-app-ink-dark`"
                >
                    <option value="">Kies speltak</option>
                    <option v-for="section in props.targetSections" :key="section" :value="section">{{ sectionLabels[section] || section }}</option>
                </select>
            </div>
            <button type="submit" class="rounded bg-emerald-700 px-4 py-2 text-white">Opslaan</button>
        </form>
    </AuthenticatedLayout>
</template>

