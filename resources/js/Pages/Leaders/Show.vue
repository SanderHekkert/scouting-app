<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, DocumentCheckIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

const props = defineProps({
    leader: { type: Object, required: true },
});

const page = usePage();
const isBestuurSection = computed(() => (page.props.auth?.active_section ?? '') === 'bestuur');
const sectionLabelMap = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabelMap[page.props.auth?.active_section] || 'Dolfijnen');

const form = useForm({
    installed: Boolean(props.leader.installed),
    gedoopt: Boolean(props.leader.gedoopt),
    first_name: props.leader.first_name || '',
    last_name: props.leader.last_name || '',
    birthday: props.leader.birthday ? String(props.leader.birthday).slice(0, 10) : '',
    bijzonderheden: props.leader.bijzonderheden || '',
    address: props.leader.address || '',
    postal_code: props.leader.postal_code || '',
    city: props.leader.city || '',
    phone_number: props.leader.phone_number || '',
    emergency_contact: props.leader.emergency_contact || '',
    email: props.leader.email || '',
});
const showDeleteModal = ref(false);

function submit() {
    form.patch(route('leaders.update', props.leader.id), {
        preserveScroll: true,
    });
}

function deleteLeader() {
    showDeleteModal.value = true;
}

function confirmDeleteLeader() {
    router.delete(route('leaders.destroy', props.leader.id));
}
</script>

<template>
    <Head :title="`${speltakLabel} - Leiding bewerken`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Leiding bewerken</h2>
                <Link :href="route('leaders.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-[11rem_1fr] sm:items-start">
                <template v-if="!isBestuurSection">
                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Geinstalleerd</label>
                    <select v-model="form.installed" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark">
                        <option :value="true">Ja</option>
                        <option :value="false">Nee</option>
                    </select>

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Gedoopt</label>
                    <select v-model="form.gedoopt" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark">
                        <option :value="true">Ja</option>
                        <option :value="false">Nee</option>
                    </select>
                </template>

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Voornaam</label>
                <input v-model="form.first_name" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Achternaam</label>
                <input v-model="form.last_name" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Geboortedatum</label>
                <input v-model="form.birthday" type="date" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Adres</label>
                <input v-model="form.address" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Postcode</label>
                <input v-model="form.postal_code" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Plaats</label>
                <input v-model="form.city" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Telefoon</label>
                <input v-model="form.phone_number" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Noodcontact</label>
                <input v-model="form.emergency_contact" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">E-mail</label>
                <input v-model="form.email" type="email" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Bijzonderheden</label>
                <textarea v-model="form.bijzonderheden" rows="4" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <span class="hidden sm:block" aria-hidden="true" />
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-action-save" :disabled="form.processing" title="Opslaan" aria-label="Opslaan">
                        <DocumentCheckIcon class="h-5 w-5" />
                    </button>
                    <button type="button" class="btn-action-delete btn-action-delete--lg" title="Verwijderen" @click="deleteLeader">
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>

    <AppConfirmModal
        :show="showDeleteModal"
        title="Leiding verwijderen?"
        :message="`Weet je zeker dat je ${props.leader.first_name || 'deze leiding'} wilt verwijderen?`"
        confirm-text="Ja, verwijderen"
        cancel-text="Annuleren"
        @close="showDeleteModal = false"
        @confirm="confirmDeleteLeader"
    />
</template>
