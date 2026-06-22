<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, DocumentCheckIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';
import { useSaveRedirect } from '@/utils/saveForm';

const props = defineProps({
    member: { type: Object, required: true },
});
const { applySaveRedirect, saveFormOptions } = useSaveRedirect();

const page = usePage();
const sectionLabelMap = {
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    loodsen: 'Loodsen',
    bevers: 'Bevers',
    wilde_vaart: 'Wilde Vaart',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabelMap[page.props.auth?.active_section] || 'Dolfijnen');
const isBestuurSection = computed(() => (page.props.auth?.active_section ?? '') === 'bestuur');
const isBeversSection = computed(() => (page.props.auth?.active_section ?? '') === 'bevers');
const allSections = ['bevers', 'dolfijnen', 'zeeverkenners', 'wilde_vaart', 'loodsen', 'bestuur'];
const transferOptions = computed(() => {
    return allSections.map((section) => ({
        value: section,
        label: sectionLabelMap[section] || section,
    }));
});

const form = useForm({
    installed: Boolean(props.member.installed),
    gedoopt: Boolean(props.member.gedoopt),
    first_name: props.member.first_name || '',
    last_name: props.member.last_name || '',
    birthday: props.member.birthday ? String(props.member.birthday).slice(0, 10) : '',
    address: props.member.address || '',
    postal_code: props.member.postal_code || '',
    city: props.member.city || '',
    email_parents: props.member.email_parents || '',
    phone_mother: props.member.phone_mother || '',
    phone_father: props.member.phone_father || '',
    bijzonderheden: props.member.bijzonderheden || '',
});
const transferForm = useForm({
    target_section: transferOptions.value[0]?.value || '',
});
const showDeleteModal = ref(false);

function submit() {
    form
        .transform((data) => applySaveRedirect(data))
        .patch(route('members.update', props.member.id), saveFormOptions());
}

function submitTransfer() {
    if (!transferForm.target_section) return;
    if (!confirm(`Weet je zeker dat je dit lid wilt overvliegen naar ${sectionLabelMap[transferForm.target_section] || transferForm.target_section}?`)) return;

    transferForm.patch(route('members.transfer', props.member.id), {
        preserveScroll: true,
    });
}

function deleteMember() {
    showDeleteModal.value = true;
}

function confirmDeleteMember() {
    router.delete(route('members.destroy', props.member.id));
}
</script>

<template>
    <Head :title="`${speltakLabel} - Lid bewerken`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Lid bewerken</h2>
                <Link :href="route('members.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-[11rem_1fr] sm:items-start">
                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Geinstalleerd</label>
                <select v-model="form.installed" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark">
                    <option :value="true">Ja</option>
                    <option :value="false">Nee</option>
                </select>

                <template v-if="!isBestuurSection && !isBeversSection">
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

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">E-mail ouders</label>
                <input v-model="form.email_parents" type="email" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Telefoon moeder</label>
                <input v-model="form.phone_mother" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Telefoon vader</label>
                <input v-model="form.phone_father" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Bijzonderheden</label>
                <textarea v-model="form.bijzonderheden" rows="4" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />

                <template v-if="transferOptions.length > 0">
                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Overvliegen naar</label>
                    <div class="flex flex-wrap items-center gap-2">
                        <select v-model="transferForm.target_section" class="min-w-[14rem] rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark">
                            <option v-for="option in transferOptions" :key="`transfer-${option.value}`" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <button type="button" class="inline-flex items-center gap-2 rounded border border-brand-blue/45 px-3 py-2 text-sm font-medium text-app-ink hover:bg-brand-blue/10 dark:text-app-ink-dark dark:hover:bg-brand-blue/15 disabled:opacity-50" :disabled="transferForm.processing || !transferForm.target_section" @click="submitTransfer">
                            Overvliegen
                        </button>
                    </div>
                </template>

                <span class="hidden sm:block" aria-hidden="true" />
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-action-save" :disabled="form.processing" title="Opslaan" aria-label="Opslaan">
                        <DocumentCheckIcon class="h-5 w-5" />
                    </button>
                    <button type="button" class="btn-action-delete btn-action-delete--lg" title="Verwijderen" @click="deleteMember">
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>

    <AppConfirmModal
        :show="showDeleteModal"
        title="Contact verwijderen?"
        :message="`Weet je zeker dat je ${props.member.first_name || 'dit contact'} wilt verwijderen?`"
        confirm-text="Ja, verwijderen"
        cancel-text="Annuleren"
        @close="showDeleteModal = false"
        @confirm="confirmDeleteMember"
    />
</template>
