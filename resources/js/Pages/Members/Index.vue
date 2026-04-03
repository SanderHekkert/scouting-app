<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    members: Array,
});

const showAddForm = ref(false);
const showEditForm = ref(false);
const editingMemberId = ref(null);

const form = useForm({
    installed: true,
    first_name: '',
    last_name: '',
    birthday: '',
    age: '',
    address: '',
    phone_mother: '',
    phone_father: '',
    bijzonderheden: '',
    active: true,
});

const editForm = useForm({
    installed: true,
    first_name: '',
    last_name: '',
    birthday: '',
    age: '',
    address: '',
    phone_mother: '',
    phone_father: '',
    bijzonderheden: '',
    active: true,
});

const memberSearchQuery = ref('');

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
        m.active === true ? 'actief' : '',
        m.active === false ? 'inactief' : '',
    ]);
}

const filteredMembers = computed(() =>
    (props.members || []).filter((m) => memberMatchesSearch(m, memberSearchQuery.value)),
);

function toggleAddForm() {
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        showEditForm.value = false;
        form.reset();
        form.installed = true;
        form.active = true;
        form.clearErrors();
    }
}

function openEditForm(member) {
    if (!member) return;
    editingMemberId.value = member.id;
    editForm.installed = Boolean(member.installed);
    editForm.first_name = member.first_name ?? '';
    editForm.last_name = member.last_name ?? '';
    editForm.birthday = member.birthday ? String(member.birthday).slice(0, 10) : '';
    editForm.age = member.age != null ? String(member.age) : '';
    editForm.address = member.address ?? '';
    editForm.phone_mother = member.phone_mother ?? '';
    editForm.phone_father = member.phone_father ?? '';
    editForm.bijzonderheden = member.bijzonderheden ?? '';
    editForm.active = Boolean(member.active);
    editForm.clearErrors();
    showEditForm.value = true;
    showAddForm.value = false;
}

function closeEditForm() {
    showEditForm.value = false;
    editingMemberId.value = null;
    editForm.reset();
    editForm.installed = true;
    editForm.active = true;
}

function normalizeMemberFields(data) {
    return {
        ...data,
        birthday: data.birthday || null,
        age: data.age === '' || data.age == null ? null : Number(data.age),
    };
}

function submitAdd() {
    form
        .transform((d) => normalizeMemberFields(d))
        .post(route('members.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                form.installed = true;
                form.active = true;
                showAddForm.value = false;
            },
        });
}

function submitEdit() {
    if (!editingMemberId.value) return;
    editForm
        .transform((d) => normalizeMemberFields(d))
        .put(route('members.update', editingMemberId.value), {
            preserveScroll: true,
            onSuccess: () => {
                closeEditForm();
            },
        });
}

function deleteMember(member) {
    if (!member?.id) return;
    if (!confirm('Dit contact verwijderen?')) return;
    if (editingMemberId.value === member.id) {
        closeEditForm();
    }
    router.delete(route('members.destroy', member.id), {
        preserveScroll: true,
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

function dashIfEmpty(value) {
    if (value == null || String(value).trim() === '') return '–';
    return value;
}

function memberDisplayName(m) {
    const fn = m?.first_name ?? '';
    const ln = m?.last_name ?? '';
    return `${fn}${ln ? ` ${ln}` : ''}`.trim() || '–';
}

function yesNoInstalled(value) {
    return value ? 'Ja' : 'Nee';
}
</script>

<template>
    <Head title="Dolfijnen" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Dolfijnen</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-700"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Contact toevoegen
                    </button>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-white">
            <form
                v-show="showAddForm"
                class="space-y-4 rounded-xl bg-gray-800 p-5 shadow-sm"
                @submit.prevent="submitAdd"
            >
                <h3 class="text-base font-semibold text-white">Nieuw contact</h3>
                <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
                    <label for="add-member-first-name" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Voornaam
                    </label>
                    <input
                        id="add-member-first-name"
                        v-model="form.first_name"
                        type="text"
                        autocomplete="given-name"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-member-last-name" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Achternaam
                    </label>
                    <input
                        id="add-member-last-name"
                        v-model="form.last_name"
                        type="text"
                        autocomplete="family-name"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-member-birthday" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Geboortedatum
                    </label>
                    <input
                        id="add-member-birthday"
                        v-model="form.birthday"
                        type="date"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="add-member-age" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Leeftijd
                    </label>
                    <input
                        id="add-member-age"
                        v-model="form.age"
                        type="number"
                        min="0"
                        max="99"
                        placeholder="Optioneel"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-member-phone-mother" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Telefoon moeder
                    </label>
                    <input
                        id="add-member-phone-mother"
                        v-model="form.phone_mother"
                        type="text"
                        autocomplete="tel"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-member-phone-father" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Telefoon vader
                    </label>
                    <input
                        id="add-member-phone-father"
                        v-model="form.phone_father"
                        type="text"
                        autocomplete="tel"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-member-address" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Adres
                    </label>
                    <input
                        id="add-member-address"
                        v-model="form.address"
                        type="text"
                        autocomplete="street-address"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-member-bijzonderheden" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Bijzonderheden
                    </label>
                    <textarea
                        id="add-member-bijzonderheden"
                        v-model="form.bijzonderheden"
                        rows="3"
                        placeholder="Allergiën, medicatie, dieet, andere aandachtspunten…"
                        class="min-h-[5rem] min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button
                            type="submit"
                            class="rounded bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            Opslaan
                        </button>
                    </div>
                </div>
                <p v-for="err in Object.values(form.errors)" :key="String(err)" class="text-sm text-red-400">
                    {{ err }}
                </p>
            </form>

            <form
                v-show="showEditForm"
                class="space-y-4 rounded-xl border border-amber-900/40 bg-gray-800/90 p-5 shadow-sm"
                @submit.prevent="submitEdit"
            >
                <h3 class="text-base font-semibold text-amber-100">Contact bewerken</h3>
                <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
                    <label for="edit-member-first-name" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Voornaam
                    </label>
                    <input
                        id="edit-member-first-name"
                        v-model="editForm.first_name"
                        type="text"
                        autocomplete="given-name"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-member-last-name" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Achternaam
                    </label>
                    <input
                        id="edit-member-last-name"
                        v-model="editForm.last_name"
                        type="text"
                        autocomplete="family-name"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-member-birthday" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Geboortedatum
                    </label>
                    <input
                        id="edit-member-birthday"
                        v-model="editForm.birthday"
                        type="date"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-member-age" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Leeftijd
                    </label>
                    <input
                        id="edit-member-age"
                        v-model="editForm.age"
                        type="number"
                        min="0"
                        max="99"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-member-phone-mother" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Telefoon moeder
                    </label>
                    <input
                        id="edit-member-phone-mother"
                        v-model="editForm.phone_mother"
                        type="text"
                        autocomplete="tel"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-member-phone-father" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Telefoon vader
                    </label>
                    <input
                        id="edit-member-phone-father"
                        v-model="editForm.phone_father"
                        type="text"
                        autocomplete="tel"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-member-address" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Adres
                    </label>
                    <input
                        id="edit-member-address"
                        v-model="editForm.address"
                        type="text"
                        autocomplete="street-address"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-member-bijzonderheden" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Bijzonderheden
                    </label>
                    <textarea
                        id="edit-member-bijzonderheden"
                        v-model="editForm.bijzonderheden"
                        rows="3"
                        placeholder="Allergiën, medicatie, dieet, andere aandachtspunten…"
                        class="min-h-[5rem] min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="submit"
                            class="rounded bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                            :disabled="editForm.processing"
                        >
                            Bijwerken
                        </button>
                        <button
                            type="button"
                            class="rounded border border-gray-500 px-5 py-2 text-sm font-medium text-white hover:bg-gray-700"
                            @click="closeEditForm"
                        >
                            Annuleren
                        </button>
                    </div>
                </div>
                <p v-for="err in Object.values(editForm.errors)" :key="`e-${String(err)}`" class="text-sm text-red-400">
                    {{ err }}
                </p>
            </form>

            <div class="rounded-xl bg-gray-800 p-4 shadow-sm">
                <div
                    class="mb-3 flex w-full flex-col gap-3 border-b border-gray-600 pb-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <h3 class="text-lg font-semibold text-indigo-200">Dolfijnen</h3>
                    <label class="sr-only" for="members-search">Zoeken in alle contactvelden</label>
                    <input
                        id="members-search"
                        v-model="memberSearchQuery"
                        type="search"
                        autocomplete="off"
                        placeholder="Zoek op naam, adres, telefoon, bijzonderheden…"
                        class="w-full max-w-xs self-end rounded border border-gray-600 bg-gray-900 px-3 py-1.5 text-sm text-white placeholder:text-gray-500 sm:ms-auto"
                    />
                </div>
                <div v-if="!props.members?.length" class="py-6 text-center text-sm text-gray-500">
                    Nog geen Dolfijnen.
                </div>
                <div v-else-if="!filteredMembers.length" class="py-6 text-center text-sm text-gray-500">
                    Geen resultaten voor deze zoekopdracht.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full min-w-[72rem] table-fixed text-sm text-white">
                        <colgroup>
                            <col class="w-[8%]" />
                            <col class="w-[15%]" />
                            <col class="w-[9%]" />
                            <col class="w-[10%]" />
                            <col class="w-[9%]" />
                            <col class="w-[5%]" />
                            <col class="w-[18%]" />
                            <col class="w-[10%]" />
                            <col class="w-[10%]" />
                            <col class="w-[6%]" />
                        </colgroup>
                        <thead>
                            <tr class="text-left text-gray-300">
                                <th class="pb-2">Geïnstalleerd</th>
                                <th class="pb-2">Bijzonderheden</th>
                                <th class="pb-2">Voornaam</th>
                                <th class="pb-2">Achternaam</th>
                                <th class="pb-2">Verjaardag</th>
                                <th class="pb-2">Leeftijd</th>
                                <th class="pb-2">Adres</th>
                                <th class="pb-2">Telefoon moeder</th>
                                <th class="pb-2">Telefoon vader</th>
                                <th class="pb-2 text-right sm:text-left">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="member in filteredMembers"
                                :key="member.id"
                                class="border-t border-gray-600"
                                :class="{ 'bg-gray-900/50': editingMemberId === member.id }"
                            >
                                <td class="py-2 pr-2 align-top">{{ yesNoInstalled(member.installed) }}</td>
                                <td class="pr-2 align-top break-words">
                                    <span v-if="member.bijzonderheden" class="line-clamp-2">{{ member.bijzonderheden }}</span>
                                    <span v-else>–</span>
                                </td>
                                <td class="pr-2 align-top">{{ dashIfEmpty(member.first_name) }}</td>
                                <td class="pr-2 align-top">{{ dashIfEmpty(member.last_name) }}</td>
                                <td class="pr-2 align-top whitespace-nowrap">{{ formatBirthday(member.birthday) }}</td>
                                <td class="pr-2 align-top">{{ member.age ?? '–' }}</td>
                                <td class="pr-2 align-top break-words">{{ dashIfEmpty(member.address) }}</td>
                                <td class="pr-2 align-top break-words">{{ dashIfEmpty(member.phone_mother) }}</td>
                                <td class="align-top break-words">{{ dashIfEmpty(member.phone_father) }}</td>
                                <td class="py-2 align-top">
                                    <div class="flex flex-wrap items-center justify-end gap-2 sm:justify-start">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded border border-gray-500 bg-gray-900 px-2 py-1 text-xs font-medium text-white hover:bg-gray-700"
                                            @click="openEditForm(member)"
                                        >
                                            <PencilSquareIcon class="h-4 w-4" />
                                            Bewerken
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded border border-red-800/60 bg-red-950/35 px-2 py-1 text-xs font-medium text-red-300 hover:bg-red-950/55"
                                            @click="deleteMember(member)"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                            Verwijderen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-xl bg-gray-800 p-5 shadow-sm">
                <h3 class="border-b border-gray-600 pb-2 text-lg font-semibold text-indigo-200">Bijzonderheden</h3>
                <p class="mt-2 text-xs text-gray-400">
                    Allergiën, medicatie, dieet en andere aandachtspunten. Bewerken via het contactformulier hierboven.
                    Voor leiding: menu Leiding.
                </p>

                <h4 class="mt-5 text-sm font-semibold uppercase tracking-wide text-gray-300">Dolfijnen</h4>
                <div v-if="!props.members?.length" class="mt-2 py-4 text-center text-sm text-gray-500">
                    Nog geen contacten.
                </div>
                <div v-else-if="!filteredMembers.length" class="mt-2 py-4 text-center text-sm text-gray-500">
                    Geen contacten die aan deze zoekopdracht voldoen.
                </div>
                <div v-else class="mt-2 overflow-x-auto">
                    <table class="w-full table-fixed text-sm text-white">
                        <colgroup>
                            <col class="w-[22%]" />
                            <col class="w-[58%]" />
                            <col class="w-[20%]" />
                        </colgroup>
                        <thead>
                            <tr class="text-left text-gray-300">
                                <th class="pb-2">Naam</th>
                                <th class="pb-2">Bijzonderheden</th>
                                <th class="pb-2 text-right sm:text-left">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="member in filteredMembers"
                                :key="`bijz-${member.id}`"
                                class="border-t border-gray-600"
                            >
                                <td class="py-2 pr-2 align-top">{{ memberDisplayName(member) }}</td>
                                <td class="pr-2 align-top break-words leading-snug text-gray-200">
                                    <span v-if="member.bijzonderheden" class="whitespace-pre-wrap">{{
                                        member.bijzonderheden
                                    }}</span>
                                    <span v-else class="text-gray-500">–</span>
                                </td>
                                <td class="align-top">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded border border-gray-500 bg-gray-900 px-2 py-1 text-xs font-medium text-white hover:bg-gray-700"
                                        @click="openEditForm(member)"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                        Bewerken
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
