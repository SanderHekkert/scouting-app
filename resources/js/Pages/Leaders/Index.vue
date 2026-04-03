<script setup>
import { computed, onMounted, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    ChevronRightIcon,
    MagnifyingGlassIcon,
    PlusIcon,
    PencilSquareIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    leaders: {
        type: Array,
        default: () => [],
    },
    open_edit_leader_id: {
        type: Number,
        default: null,
    },
});

const showAddForm = ref(false);
const showEditForm = ref(false);
const editingLeaderId = ref(null);

const form = useForm({
    first_name: '',
    last_name: '',
    address: '',
    postal_code: '',
    city: '',
    birthday: '',
    phone_number: '',
    email: '',
    bijzonderheden: '',
});

const editForm = useForm({
    first_name: '',
    last_name: '',
    address: '',
    postal_code: '',
    city: '',
    birthday: '',
    phone_number: '',
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
        l.address,
        l.postal_code,
        l.city,
        l.phone_number,
        l.email,
        l.bijzonderheden,
        bdayRaw,
        bdaySearch,
    ]);
}

const filteredLeaders = computed(() =>
    (props.leaders || []).filter((l) => leaderMatchesSearch(l, leaderSearchQuery.value)),
);

function toggleAddForm() {
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        showEditForm.value = false;
        form.reset();
        form.clearErrors();
    }
}

function openEditForm(leader) {
    if (!leader) return;
    editingLeaderId.value = leader.id;
    editForm.first_name = leader.first_name ?? '';
    editForm.last_name = leader.last_name ?? '';
    editForm.address = leader.address ?? '';
    editForm.postal_code = leader.postal_code ?? '';
    editForm.city = leader.city ?? '';
    editForm.birthday = leader.birthday ? String(leader.birthday).slice(0, 10) : '';
    editForm.phone_number = leader.phone_number ?? '';
    editForm.email = leader.email ?? '';
    editForm.bijzonderheden = leader.bijzonderheden ?? '';
    editForm.clearErrors();
    showEditForm.value = true;
    showAddForm.value = false;
}

function closeEditForm() {
    showEditForm.value = false;
    editingLeaderId.value = null;
    editForm.reset();
}

onMounted(() => {
    const id = props.open_edit_leader_id;
    if (!id) return;
    const l = props.leaders?.find((x) => x.id === id);
    if (l) openEditForm(l);
});

function leaderListName(l) {
    return [l.first_name, l.last_name].filter(Boolean).join(' ').trim() || '–';
}

function normalizeLeaderPayload(data) {
    return {
        ...data,
        birthday: data.birthday || null,
    };
}

function submitAdd() {
    form.transform((d) => normalizeLeaderPayload(d)).post(route('leaders.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            showAddForm.value = false;
        },
    });
}

function submitEdit() {
    if (!editingLeaderId.value) return;
    editForm
        .transform((d) => normalizeLeaderPayload(d))
        .put(route('leaders.update', editingLeaderId.value), {
            preserveScroll: true,
            onSuccess: () => {
                closeEditForm();
            },
        });
}

function deleteLeader(leader) {
    if (!leader?.id) return;
    if (!confirm('Deze leiding verwijderen?')) return;
    if (editingLeaderId.value === leader.id) {
        closeEditForm();
    }
    router.delete(route('leaders.destroy', leader.id), {
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
</script>

<template>
    <Head title="Leiding" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Leiding</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-700"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Leiding toevoegen
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
                <h3 class="text-base font-semibold text-white">Nieuwe leiding</h3>
                <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
                    <label for="add-leader-first-name" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Voornaam
                    </label>
                    <input
                        id="add-leader-first-name"
                        v-model="form.first_name"
                        type="text"
                        autocomplete="given-name"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-leader-last-name" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Achternaam
                    </label>
                    <input
                        id="add-leader-last-name"
                        v-model="form.last_name"
                        type="text"
                        autocomplete="family-name"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-leader-address" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Adres
                    </label>
                    <input
                        id="add-leader-address"
                        v-model="form.address"
                        type="text"
                        autocomplete="street-address"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-leader-postal" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Postcode
                    </label>
                    <input
                        id="add-leader-postal"
                        v-model="form.postal_code"
                        type="text"
                        autocomplete="postal-code"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-leader-city" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Plaats
                    </label>
                    <input
                        id="add-leader-city"
                        v-model="form.city"
                        type="text"
                        autocomplete="address-level2"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-leader-birthday" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Geboortedatum
                    </label>
                    <input
                        id="add-leader-birthday"
                        v-model="form.birthday"
                        type="date"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="add-leader-phone" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Telefoonnummer
                    </label>
                    <input
                        id="add-leader-phone"
                        v-model="form.phone_number"
                        type="text"
                        autocomplete="tel"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-leader-email" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        E-mail
                    </label>
                    <input
                        id="add-leader-email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-leader-bijz" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Bijzonderheden
                    </label>
                    <textarea
                        id="add-leader-bijz"
                        v-model="form.bijzonderheden"
                        rows="3"
                        placeholder="Allergiën, medicatie, andere aandachtspunten…"
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
                <h3 class="text-base font-semibold text-amber-100">Leiding bewerken</h3>
                <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
                    <label for="edit-leader-first-name" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Voornaam
                    </label>
                    <input
                        id="edit-leader-first-name"
                        v-model="editForm.first_name"
                        type="text"
                        autocomplete="given-name"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-leader-last-name" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Achternaam
                    </label>
                    <input
                        id="edit-leader-last-name"
                        v-model="editForm.last_name"
                        type="text"
                        autocomplete="family-name"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-leader-address" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Adres
                    </label>
                    <input
                        id="edit-leader-address"
                        v-model="editForm.address"
                        type="text"
                        autocomplete="street-address"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-leader-postal" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Postcode
                    </label>
                    <input
                        id="edit-leader-postal"
                        v-model="editForm.postal_code"
                        type="text"
                        autocomplete="postal-code"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-leader-city" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Plaats
                    </label>
                    <input
                        id="edit-leader-city"
                        v-model="editForm.city"
                        type="text"
                        autocomplete="address-level2"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-leader-birthday" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Geboortedatum
                    </label>
                    <input
                        id="edit-leader-birthday"
                        v-model="editForm.birthday"
                        type="date"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-leader-phone" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Telefoonnummer
                    </label>
                    <input
                        id="edit-leader-phone"
                        v-model="editForm.phone_number"
                        type="text"
                        autocomplete="tel"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-leader-email" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        E-mail
                    </label>
                    <input
                        id="edit-leader-email"
                        v-model="editForm.email"
                        type="email"
                        autocomplete="email"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    />

                    <label for="edit-leader-bijz" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Bijzonderheden
                    </label>
                    <textarea
                        id="edit-leader-bijz"
                        v-model="editForm.bijzonderheden"
                        rows="3"
                        placeholder="Allergiën, medicatie, andere aandachtspunten…"
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
                    <h3 class="text-lg font-semibold text-indigo-200">Overzicht</h3>
                    <div class="flex w-full max-w-sm items-center gap-2 self-end sm:ms-auto">
                        <MagnifyingGlassIcon
                            class="h-5 w-5 shrink-0 text-gray-500"
                            aria-hidden="true"
                        />
                        <label class="sr-only" for="leaders-page-search">Zoeken in alle leidingvelden</label>
                        <input
                            id="leaders-page-search"
                            v-model="leaderSearchQuery"
                            type="search"
                            autocomplete="off"
                            placeholder="Zoek op naam, adres, e-mail, bijzonderheden…"
                            class="min-w-0 flex-1 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-sm text-white placeholder:text-gray-500"
                        />
                    </div>
                </div>
                <div v-if="!props.leaders?.length" class="py-6 text-center text-sm text-gray-500">
                    Nog geen leiding. Voeg iemand toe met de knop hierboven.
                </div>
                <div v-else-if="!filteredLeaders.length" class="py-6 text-center text-sm text-gray-500">
                    Geen resultaten voor deze zoekopdracht.
                </div>
                <div v-else class="space-y-2 md:space-y-0">
                    <div class="md:hidden space-y-2">
                        <Link
                            v-for="leader in filteredLeaders"
                            :key="`l-mob-${leader.id}`"
                            :href="route('leaders.show', leader.id)"
                            class="flex items-center justify-between gap-3 rounded-xl border border-gray-600 bg-gray-800/90 px-4 py-3 text-white active:bg-gray-700"
                        >
                            <span class="min-w-0 truncate font-medium">{{ leaderListName(leader) }}</span>
                            <ChevronRightIcon class="h-5 w-5 shrink-0 text-gray-500" aria-hidden="true" />
                        </Link>
                    </div>
                    <div class="hidden overflow-x-auto rounded-lg border border-gray-700/80 md:block">
                        <table class="w-full min-w-[64rem] border-collapse text-left text-sm text-white">
                        <thead class="border-b border-gray-600 bg-gray-900/60">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Voornaam</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Achternaam</th>
                                <th scope="col" class="min-w-[12rem] px-3 py-2.5">Bijzonderheden</th>
                                <th scope="col" class="min-w-[10rem] px-3 py-2.5">Adres</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Postcode</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Plaats</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Geboortedatum</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Telefoon</th>
                                <th scope="col" class="min-w-[11rem] px-3 py-2.5">E-mail</th>
                                <th scope="col" class="min-w-[11rem] whitespace-nowrap px-3 py-2.5 text-end sm:text-start">
                                    Acties
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-600">
                            <tr
                                v-for="leader in filteredLeaders"
                                :key="leader.id"
                                class="bg-gray-800/40 transition-colors hover:bg-gray-800/70"
                                :class="{ '!bg-gray-900/55': editingLeaderId === leader.id }"
                            >
                                <td class="max-w-[10rem] px-3 py-2.5 align-top">
                                    <span class="line-clamp-2 break-words">{{ dashIfEmpty(leader.first_name) }}</span>
                                </td>
                                <td class="max-w-[10rem] px-3 py-2.5 align-top">
                                    <span class="line-clamp-2 break-words">{{ dashIfEmpty(leader.last_name) }}</span>
                                </td>
                                <td class="max-w-[16rem] px-3 py-2.5 align-top break-words">
                                    <span v-if="leader.bijzonderheden" class="line-clamp-2 text-gray-200">{{
                                        leader.bijzonderheden
                                    }}</span>
                                    <span v-else class="text-gray-500">–</span>
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <span class="line-clamp-3 break-words text-gray-100">{{
                                        dashIfEmpty(leader.address)
                                    }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top text-gray-200">
                                    {{ dashIfEmpty(leader.postal_code) }}
                                </td>
                                <td class="max-w-[9rem] px-3 py-2.5 align-top">
                                    <span class="break-words text-gray-200">{{ dashIfEmpty(leader.city) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums text-gray-200">
                                    {{ formatBirthday(leader.birthday) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums text-gray-200">
                                    {{ dashIfEmpty(leader.phone_number) }}
                                </td>
                                <td class="max-w-[14rem] px-3 py-2.5 align-top break-all">
                                    <a
                                        v-if="leader.email"
                                        :href="`mailto:${leader.email}`"
                                        class="text-indigo-300 underline decoration-indigo-400/80 underline-offset-2 hover:text-indigo-200"
                                    >
                                        {{ leader.email }}
                                    </a>
                                    <span v-else class="text-gray-500">–</span>
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end lg:justify-start">
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center gap-1 rounded border border-gray-500 bg-gray-900 px-2.5 py-1.5 text-xs font-medium text-white hover:bg-gray-700"
                                            @click="openEditForm(leader)"
                                        >
                                            <PencilSquareIcon class="h-4 w-4 shrink-0" />
                                            Bewerken
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center gap-1 rounded border border-red-800/60 bg-red-950/35 px-2.5 py-1.5 text-xs font-medium text-red-300 hover:bg-red-950/55"
                                            @click="deleteLeader(leader)"
                                        >
                                            <TrashIcon class="h-4 w-4 shrink-0" />
                                            Verwijderen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
