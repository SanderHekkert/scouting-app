<script setup>
import { computed, ref } from 'vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import LeadersCreateForm from '@/Pages/Leaders/Partials/LeadersCreateForm.vue';
import LeadersOverviewBoard from '@/Pages/Leaders/Partials/LeadersOverviewBoard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    leaders: {
        type: Array,
        default: () => [],
    },
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
const leaderPerms = computed(() => page.props.auth?.permissions?.leaders ?? {});
const canCreateLeaders = computed(() => !!leaderPerms.value.create);
const canUpdateLeaders = computed(() => !!leaderPerms.value.update);
const canDeleteLeaders = computed(() => !!leaderPerms.value.delete);

const showAddForm = ref(false);
const deleteModalLeader = ref(null);

const form = useForm({
    installed: false,
    gedoopt: false,
    first_name: '',
    last_name: '',
    address: '',
    postal_code: '',
    city: '',
    birthday: '',
    phone_number: '',
    emergency_contact: '',
    email: '',
    bijzonderheden: '',
});

const leaderSearchQuery = ref('');

function matchesMultiFieldSearch(rawQuery, fieldValues) {
    const q = String(rawQuery ?? '').trim().toLowerCase();
    if (!q) {
        return true;
    }
    const haystack = fieldValues
        .filter((v) => v != null && String(v).trim() !== '')
        .map((v) => String(v).toLowerCase())
        .join(' ');
    const parts = q.split(/\s+/).filter(Boolean);
    return parts.every((part) => haystack.includes(part));
}

function leaderMatchesSearch(l, rawQuery) {
    const bdayRaw =
        l.birthday != null && l.birthday !== '' ? String(l.birthday).slice(0, 10) : '';
    const bdayFmt = bdayRaw ? formatBirthday(l.birthday) : '';
    const bdaySearch = bdayFmt !== '–' ? bdayFmt : '';

    return matchesMultiFieldSearch(rawQuery, [
        l.first_name,
        l.last_name,
        l.section_role_label,
        l.address,
        l.postal_code,
        l.city,
        l.phone_number,
        l.email,
        l.bijzonderheden,
        bdayRaw,
        bdaySearch,
        l.installed ? 'ja geïnstalleerd' : 'nee niet geïnstalleerd',
        l.gedoopt ? 'ja gedoopt' : 'nee niet gedoopt',
    ]);
}

const filteredLeaders = computed(() =>
    (props.leaders || []).filter((l) => leaderMatchesSearch(l, leaderSearchQuery.value)),
);

function toggleAddForm() {
    if (!canCreateLeaders.value) return;
    router.get(route('leaders.create'));
}

function leaderListName(l) {
    return [l.first_name, l.last_name].filter(Boolean).join(' ').trim() || '–';
}

function leaderFullAddress(l) {
    const parts = [l.address, l.postal_code, l.city]
        .map((v) => (v == null ? '' : String(v).trim()))
        .filter(Boolean);

    return parts.join(', ') || '–';
}

function leaderHasBijzonderheden(l) {
    return l?.bijzonderheden != null && String(l.bijzonderheden).trim() !== '';
}

function yesNo(value) {
    return value ? 'Ja' : 'Nee';
}

function normalizeLeaderPayload(data) {
    return {
        ...data,
        birthday: data.birthday || null,
    };
}

function submitAdd() {
    if (!canCreateLeaders.value) return;
    form.transform((d) => normalizeLeaderPayload(d)).post(route('leaders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
}

function deleteLeader(leader) {
    if (!canDeleteLeaders.value) return;
    if (!leader?.id) return;
    deleteModalLeader.value = leader;
}

function closeDeleteModal() {
    deleteModalLeader.value = null;
}

function confirmDeleteLeader() {
    const leader = deleteModalLeader.value;
    if (!leader?.id) return;
    router.delete(route('leaders.destroy', leader.id), {
        preserveScroll: true,
        onFinish: () => {
            closeDeleteModal();
        },
    });
}

function goToLeaderDetail(leader) {
    if (!canUpdateLeaders.value) return;
    if (!leader?.id) return;
    router.get(route('leaders.show', leader.id));
}

function formatBirthday(value) {
    if (value == null || value === '') return '–';
    const s = String(value).slice(0, 10);
    const parts = s.split('-');
    if (parts.length !== 3) return s;
    const [y, m, d] = parts;
    return `${d}-${m}-${y}`;
}

function leaderAge(value) {
    if (value == null || value === '') return '–';
    const bday = new Date(String(value).slice(0, 10));
    if (Number.isNaN(bday.getTime())) return '–';

    const today = new Date();
    let age = today.getFullYear() - bday.getFullYear();
    const monthDiff = today.getMonth() - bday.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < bday.getDate())) {
        age -= 1;
    }

    return age >= 0 ? String(age) : '–';
}

</script>

<template>
    <Head title="Leiding" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Leiding</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        v-if="canCreateLeaders"
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-white shadow-sm transition hover:bg-emerald-800"
                        title="Toevoegen"
                        aria-label="Toevoegen"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-4 text-app-ink dark:text-app-ink-dark">
            <LeadersCreateForm
                :can-create-leaders="canCreateLeaders"
                :show-add-form="showAddForm"
                :form="form"
                @submit="submitAdd"
            />

            <LeadersOverviewBoard
                :leaders="props.leaders || []"
                :filtered-leaders="filteredLeaders"
                :leader-search-query="leaderSearchQuery"
                :is-bestuur-section="isBestuurSection"
                :can-update-leaders="canUpdateLeaders"
                :can-delete-leaders="canDeleteLeaders"
                :leader-list-name="leaderListName"
                :leader-has-bijzonderheden="leaderHasBijzonderheden"
                :leader-full-address="leaderFullAddress"
                :yes-no="yesNo"
                :format-birthday="formatBirthday"
                :leader-age="leaderAge"
                @update:leader-search-query="leaderSearchQuery = $event"
                @edit-leader="goToLeaderDetail"
                @delete-leader="deleteLeader"
            />
        </div>
    </AuthenticatedLayout>

    <AppConfirmModal
        :show="!!deleteModalLeader"
        title="Leiding verwijderen?"
        :message="deleteModalLeader ? `Weet je zeker dat je ${leaderListName(deleteModalLeader)} wilt verwijderen?` : ''"
        confirm-text="Ja, verwijderen"
        cancel-text="Annuleren"
        @close="closeDeleteModal"
        @confirm="confirmDeleteLeader"
    />
</template>
