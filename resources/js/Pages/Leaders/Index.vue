<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { ChevronRightIcon, DocumentCheckIcon, MagnifyingGlassIcon, PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    leaders: {
        type: Array,
        default: () => [],
    },
});
const page = usePage();
const isBestuurSection = computed(() => (page.props.auth?.active_section ?? '') === 'bestuur');
const leaderPerms = computed(() => page.props.auth?.permissions?.leaders ?? {});
const canCreateLeaders = computed(() => !!leaderPerms.value.create);
const canUpdateLeaders = computed(() => !!leaderPerms.value.update);
const canDeleteLeaders = computed(() => !!leaderPerms.value.delete);

const showAddForm = ref(false);

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
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        form.reset();
        form.clearErrors();
    }
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
    if (!confirm('Deze leiding verwijderen?')) return;
    router.delete(route('leaders.destroy', leader.id), {
        preserveScroll: true,
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
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Leiding</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        v-if="canCreateLeaders"
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-blue text-white shadow-sm transition hover:bg-brand-blue-dark"
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
            <form
                v-if="canCreateLeaders"
                v-show="showAddForm"
                class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-5"
                @submit.prevent="submitAdd"
            >
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuwe leiding</h3>
                <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
                    <label for="add-leader-first-name" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Voornaam
                    </label>
                    <input
                        id="add-leader-first-name"
                        v-model="form.first_name"
                        type="text"
                        autocomplete="given-name"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-leader-last-name" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Achternaam
                    </label>
                    <input
                        id="add-leader-last-name"
                        v-model="form.last_name"
                        type="text"
                        autocomplete="family-name"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-leader-address" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Adres
                    </label>
                    <input
                        id="add-leader-address"
                        v-model="form.address"
                        type="text"
                        autocomplete="street-address"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-leader-postal" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Postcode
                    </label>
                    <input
                        id="add-leader-postal"
                        v-model="form.postal_code"
                        type="text"
                        autocomplete="postal-code"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-leader-city" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Plaats
                    </label>
                    <input
                        id="add-leader-city"
                        v-model="form.city"
                        type="text"
                        autocomplete="address-level2"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-leader-birthday" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Geboortedatum
                    </label>
                    <input
                        id="add-leader-birthday"
                        v-model="form.birthday"
                        type="date"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    />

                    <label for="add-leader-phone" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Telefoonnummer
                    </label>
                    <input
                        id="add-leader-phone"
                        v-model="form.phone_number"
                        type="text"
                        autocomplete="tel"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-leader-emergency-contact" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Noodcontact
                    </label>
                    <input
                        id="add-leader-emergency-contact"
                        v-model="form.emergency_contact"
                        type="text"
                        autocomplete="tel"
                        placeholder="Bijv. Ouder/verzorger + telefoon"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-leader-email" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        E-mail
                    </label>
                    <input
                        id="add-leader-email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-leader-bijz" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Bijzonderheden
                    </label>
                    <textarea
                        id="add-leader-bijz"
                        v-model="form.bijzonderheden"
                        rows="3"
                        placeholder="Allergiën, medicatie, andere aandachtspunten…"
                        class="min-h-[5rem] min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button
                            type="submit"
                            class="btn-action-save"
                            :disabled="form.processing"
                            title="Opslaan"
                            aria-label="Opslaan"
                        >
                            <DocumentCheckIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
                <p v-for="err in Object.values(form.errors)" :key="String(err)" class="text-sm text-red-400">
                    {{ err }}
                </p>
            </form>

            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4">
                <div
                    class="mb-3 flex w-full flex-col gap-3 border-b border-brand-blue/35 pb-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <h3 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">Overzicht</h3>
                    <div class="flex w-full max-w-sm items-center gap-2 self-end sm:ms-auto">
                        <MagnifyingGlassIcon
                            class="h-5 w-5 shrink-0 text-app-muted dark:text-app-muted-dark"
                            aria-hidden="true"
                        />
                        <label class="sr-only" for="leaders-page-search">Zoeken in alle leidingvelden</label>
                        <input
                            id="leaders-page-search"
                            v-model="leaderSearchQuery"
                            type="search"
                            autocomplete="off"
                            placeholder="Zoek op naam, adres, e-mail, bijzonderheden…"
                            class="min-w-0 flex-1 rounded border border-app-border bg-white px-3 py-2 text-sm text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                        />
                    </div>
                </div>
                <div v-if="!props.leaders?.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
                    Nog geen leiding. Voeg iemand toe met de knop hierboven.
                </div>
                <div v-else-if="!filteredLeaders.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
                    Geen resultaten voor deze zoekopdracht.
                </div>
                <div v-else class="space-y-2 md:space-y-0">
                    <div class="md:hidden space-y-2">
                        <div
                            v-for="leader in filteredLeaders"
                            :key="`l-mob-${leader.id}`"
                            class="surface-brand-top flex flex-col rounded-xl border border-brand-blue/30 bg-app-panel px-4 py-3 text-app-ink shadow-sm dark:bg-app-panel-dark/95 dark:text-app-ink-dark"
                        >
                            <Link
                                :href="route('leaders.show', leader.id)"
                                class="flex items-start justify-between gap-3 rounded-lg active:bg-brand-blue/15"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <span
                                        v-if="leaderHasBijzonderheden(leader)"
                                        class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-brand-red"
                                        title="Heeft bijzonderheden"
                                    />
                                    <span class="min-w-0 font-medium leading-snug">{{ leaderListName(leader) }}</span>
                                </span>
                                <ChevronRightIcon class="mt-0.5 h-5 w-5 shrink-0 text-app-muted dark:text-app-muted-dark" aria-hidden="true" />
                            </Link>
                            <div class="mt-1 text-xs text-app-muted dark:text-app-muted-dark">
                                {{ leaderFullAddress(leader) }}
                            </div>
                            <div class="mt-2 border-t border-brand-blue/25 pt-2 text-sm dark:border-brand-blue/35">
                                <p><span class="font-medium">Geïnstalleerd:</span> {{ yesNo(leader.installed) }}</p>
                                <p v-if="!isBestuurSection"><span class="font-medium">Gedoopt:</span> {{ yesNo(leader.gedoopt) }}</p>
                                <p><span class="font-medium">Bijzonderheden:</span> {{ leader.bijzonderheden || '–' }}</p>
                                <p><span class="font-medium">Geboortedatum:</span> {{ formatBirthday(leader.birthday) }}</p>
                                <p><span class="font-medium">Leeftijd:</span> {{ leaderAge(leader.birthday) }}</p>
                                <p><span class="font-medium">Telefoon:</span> {{ leader.phone_number || '–' }}</p>
                                <p><span class="font-medium">Noodcontact:</span> {{ leader.emergency_contact || '–' }}</p>
                                <p><span class="font-medium">E-mail:</span> {{ leader.email || '–' }}</p>
                            </div>
                            <div class="mt-3 flex items-center gap-2">
                                <button v-if="canUpdateLeaders" type="button" class="btn-action-edit" title="Bewerken" @click.stop="goToLeaderDetail(leader)">
                                    <PencilSquareIcon class="h-4 w-4 shrink-0" />
                                </button>
                                <button v-if="canDeleteLeaders" type="button" class="btn-action-delete" title="Verwijderen" @click.stop="deleteLeader(leader)">
                                    <TrashIcon class="h-4 w-4 shrink-0" />
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="hidden overflow-x-auto rounded-lg border border-brand-blue/25 md:block">
                        <table class="w-full min-w-[56rem] border-collapse text-left text-sm text-app-ink lg:min-w-[68rem] dark:text-app-ink-dark">
                        <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Volledige naam</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Geïnstalleerd</th>
                                <th v-if="!isBestuurSection" scope="col" class="whitespace-nowrap px-3 py-2.5">Gedoopt</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Volledig adres</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Bijzonderheden</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Geboortedatum</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Leeftijd</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Telefoonnummer</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Noodcontact</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">E-mail</th>
                                <th scope="col" class="min-w-[11rem] whitespace-nowrap px-3 py-2.5 text-end sm:text-start">
                                    Acties
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-blue/25">
                            <tr
                                v-for="leader in filteredLeaders"
                                :id="`leader-row-${leader.id}`"
                                :key="leader.id"
                                class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                            >
                                <td class="max-w-[12rem] px-3 py-2.5 align-top">
                                    {{ leaderListName(leader) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top">{{ yesNo(leader.installed) }}</td>
                                <td v-if="!isBestuurSection" class="whitespace-nowrap px-3 py-2.5 align-top">{{ yesNo(leader.gedoopt) }}</td>
                                <td class="max-w-[14rem] px-3 py-2.5 align-top">
                                    {{ leaderFullAddress(leader) }}
                                </td>
                                <td class="max-w-[14rem] px-3 py-2.5 align-top">
                                    <span class="line-clamp-2">{{ leader.bijzonderheden || '–' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums">
                                    {{ formatBirthday(leader.birthday) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums">
                                    {{ leaderAge(leader.birthday) }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">
                                    {{ leader.phone_number || '–' }}
                                </td>
                                <td class="max-w-[16rem] px-3 py-2.5 align-top">
                                    {{ leader.emergency_contact || '–' }}
                                </td>
                                <td class="max-w-[16rem] break-all px-3 py-2.5 align-top">
                                    {{ leader.email || '–' }}
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <button v-if="canUpdateLeaders" type="button" class="btn-action-edit me-2" title="Bewerken" @click="goToLeaderDetail(leader)">
                                        <PencilSquareIcon class="h-4 w-4 shrink-0" />
                                    </button>
                                    <button v-if="canDeleteLeaders" type="button" class="btn-action-delete" title="Verwijderen" @click="deleteLeader(leader)">
                                        <TrashIcon class="h-4 w-4 shrink-0" />
                                    </button>
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
