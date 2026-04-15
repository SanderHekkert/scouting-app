<script setup>
import { computed, ref } from 'vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import MembersCreateForm from '@/Pages/Members/Partials/MembersCreateForm.vue';
import MembersOverviewBoard from '@/Pages/Members/Partials/MembersOverviewBoard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    members: Array,
    open_edit_member_id: {
        type: Number,
        default: null,
    },
});
const page = usePage();
const sectionLabelMap = {
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    loodsen: 'Loodsen',
    bevers: 'Bevers',
    wilde_vaart: 'Wilde Vaart',
    bestuur: 'Bestuur',
};
const sectionSingularMap = {
    dolfijnen: 'Dolfijn',
    zeeverkenners: 'Zeeverkenner',
    loodsen: 'Loods',
    bevers: 'Bever',
    wilde_vaart: 'Wilde Vaart-lid',
    bestuur: 'Bestuurslid',
};
const speltakLabel = computed(() => sectionLabelMap[page.props.auth?.active_section] || 'Dolfijnen');
const speltakSingular = computed(() => sectionSingularMap[page.props.auth?.active_section] || 'Dolfijn');
const isBestuurSection = computed(() => (page.props.auth?.active_section ?? '') === 'bestuur');
const isBeversSection = computed(() => (page.props.auth?.active_section ?? '') === 'bevers');
const memberPerms = computed(() => page.props.auth?.permissions?.members ?? {});
const canCreateMembers = computed(() => !!memberPerms.value.create);
const canUpdateMembers = computed(() => !!memberPerms.value.update);
const canDeleteMembers = computed(() => !!memberPerms.value.delete);

const showAddForm = ref(false);
const rowHighlightMemberId = ref(null);
const deleteModalMember = ref(null);

const form = useForm({
    installed: false,
    gedoopt: false,
    first_name: '',
    last_name: '',
    birthday: '',
    address: '',
    postal_code: '',
    city: '',
    email_parents: '',
    phone_mother: '',
    phone_father: '',
    bijzonderheden: '',
});

const memberSearchQuery = ref('');

const membersTab = computed(() => (route().current('members.bijzonderheden') ? 'bijzonderheden' : 'dolfijnen'));

function memberHasBijzonderheden(m) {
    return m?.bijzonderheden != null && String(m.bijzonderheden).trim() !== '';
}

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

function memberMatchesSearch(m, rawQuery) {
    const bdayRaw =
        m.birthday != null && m.birthday !== '' ? String(m.birthday).slice(0, 10) : '';
    const bdayFmt = bdayRaw ? formatBirthday(m.birthday) : '';
    const bdaySearch = bdayFmt !== '–' ? bdayFmt : '';

    return matchesMultiFieldSearch(rawQuery, [
        m.first_name,
        m.last_name,
        m.address,
        m.phone_mother,
        m.phone_father,
        m.bijzonderheden,
        m.age,
        bdayRaw,
        bdaySearch,
        m.installed ? 'ja geïnstalleerd' : 'nee niet geïnstalleerd',
    ]);
}

const filteredMembers = computed(() =>
    (props.members || []).filter((m) => memberMatchesSearch(m, memberSearchQuery.value)),
);

/** Dolfijnen-tab: oud → jong op leeftijd (hoog naar laag), daarna geboortedatum, dan naam. */
const sortedDolfijnenMembers = computed(() => {
    const list = [...filteredMembers.value];
    const ageNum = (m) => {
        if (m?.age == null || m.age === '') return null;
        const n = Number(m.age);
        return Number.isNaN(n) ? null : n;
    };
    const bdayIso = (m) => (m?.birthday ? String(m.birthday).slice(0, 10) : '');
    const nameKey = (m) =>
        `${m.last_name ?? ''} ${m.first_name ?? ''}`.trim().toLowerCase() ||
        `${m.first_name ?? ''}`.toLowerCase();

    list.sort((a, b) => {
        const ageA = ageNum(a);
        const ageB = ageNum(b);
        if (ageA != null && ageB != null && ageA !== ageB) {
            return ageB - ageA;
        }
        if (ageA != null && ageB == null) return -1;
        if (ageA == null && ageB != null) return 1;

        const dA = bdayIso(a);
        const dB = bdayIso(b);
        if (dA && dB && dA !== dB) {
            return dA.localeCompare(dB);
        }
        if (dA && !dB) return -1;
        if (!dA && dB) return 1;

        return nameKey(a).localeCompare(nameKey(b), 'nl', { sensitivity: 'base' });
    });
    return list;
});

/** Tab Bijzonderheden: kinderen met ingevulde bijzonderheden bovenaan; daarna op naam. */
const sortedFilteredMembers = computed(() => {
    const list = [...filteredMembers.value];
    list.sort((a, b) => {
        const ha = memberHasBijzonderheden(a);
        const hb = memberHasBijzonderheden(b);
        if (ha !== hb) {
            return ha ? -1 : 1;
        }
        const sortKey = (m) =>
            `${m.last_name ?? ''} ${m.first_name ?? ''}`.trim().toLowerCase() ||
            `${m.first_name ?? ''}`.toLowerCase();
        return sortKey(a).localeCompare(sortKey(b), 'nl', { sensitivity: 'base' });
    });
    return list;
});

function toggleAddForm() {
    if (!canCreateMembers.value) return;
    router.get(route('members.create'));
}

function normalizeMemberFields(data) {
    return {
        ...data,
        birthday: data.birthday || null,
    };
}

function submitAdd() {
    if (!canCreateMembers.value) return;
    form
        .transform((d) => normalizeMemberFields(d))
        .post(route('members.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                showAddForm.value = false;
            },
        });
}

function deleteMember(member) {
    if (!canDeleteMembers.value) return;
    if (!member?.id) return;
    deleteModalMember.value = member;
}

function closeDeleteModal() {
    deleteModalMember.value = null;
}

function confirmDeleteMember() {
    const member = deleteModalMember.value;
    if (!member?.id) return;
    if (rowHighlightMemberId.value === member.id) {
        rowHighlightMemberId.value = null;
    }
    router.delete(route('members.destroy', member.id), {
        preserveScroll: true,
        onFinish: () => {
            closeDeleteModal();
        },
    });
}

function formatBirthday(value) {
    if (value == null || value === '') return '–';
    const s = String(value).slice(0, 10);
    const parts = s.split('-');
    if (parts.length !== 3) return s;
    const [y, m, d] = parts;
    return `${d}-${m}-${y}`;
}

function memberDisplayName(m) {
    const fn = m?.first_name ?? '';
    const ln = m?.last_name ?? '';
    return `${fn}${ln ? ` ${ln}` : ''}`.trim() || '–';
}

function yesNo(value) {
    return value ? 'Ja' : 'Nee';
}

function editMember(member) {
    if (!canUpdateMembers.value) return;
    if (!member?.id) return;
    router.get(route('members.show', member.id));
}
</script>

<template>
    <Head :title="`${speltakLabel} - Leden`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Leden</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        v-if="canCreateMembers"
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
            <MembersCreateForm
                :can-create-members="canCreateMembers"
                :show-add-form="showAddForm"
                :form="form"
                :is-bestuur-section="isBestuurSection"
                :is-bevers-section="isBeversSection"
                @submit="submitAdd"
            />

            <MembersOverviewBoard
                :members-tab="membersTab"
                :member-search-query="memberSearchQuery"
                :members="props.members || []"
                :filtered-members="filteredMembers"
                :sorted-dolfijnen-members="sortedDolfijnenMembers"
                :sorted-filtered-members="sortedFilteredMembers"
                :speltak-label="speltakLabel"
                :is-bestuur-section="isBestuurSection"
                :is-bevers-section="isBeversSection"
                :can-update-members="canUpdateMembers"
                :can-delete-members="canDeleteMembers"
                :row-highlight-member-id="rowHighlightMemberId"
                :member-display-name="memberDisplayName"
                :yes-no="yesNo"
                :format-birthday="formatBirthday"
                @update:member-search-query="memberSearchQuery = $event"
                @edit-member="editMember"
                @delete-member="deleteMember"
            />
        </div>
    </AuthenticatedLayout>

    <AppConfirmModal
        :show="!!deleteModalMember"
        title="Contact verwijderen?"
        :message="deleteModalMember ? `Weet je zeker dat je ${memberDisplayName(deleteModalMember)} wilt verwijderen?` : ''"
        confirm-text="Ja, verwijderen"
        cancel-text="Annuleren"
        @close="closeDeleteModal"
        @confirm="confirmDeleteMember"
    />
</template>
