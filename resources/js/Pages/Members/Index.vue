<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import SpeltakSubnav from '@/Components/SpeltakSubnav.vue';
import EditableTextCell from '@/Components/EditableTextCell.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ChevronRightIcon, MagnifyingGlassIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

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
    bevers: 'Bevers',
    wilde_vaart: 'Wilde Vaart',
};
const sectionSingularMap = {
    dolfijnen: 'Dolfijn',
    zeeverkenners: 'Zeeverkenner',
    bevers: 'Bever',
    wilde_vaart: 'Wilde Vaart-lid',
};
const speltakLabel = computed(() => sectionLabelMap[page.props.auth?.active_section] || 'Dolfijnen');
const speltakSingular = computed(() => sectionSingularMap[page.props.auth?.active_section] || 'Dolfijn');

const showAddForm = ref(false);
const rowHighlightMemberId = ref(null);
const memberInlineOpen = ref({ key: '', nonce: 0 });

const form = useForm({
    installed: false,
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
        m.active === true ? 'actief' : '',
        m.active === false ? 'inactief' : '',
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
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        rowHighlightMemberId.value = null;
        form.reset();
        form.clearErrors();
    }
}

/** Opent direct de juiste tabelcel (dubbelklik-gedrag via EditableTextCell). */
function requestMemberInlineEdit(member, field = 'first_name') {
    if (!member) return;
    showAddForm.value = false;
    rowHighlightMemberId.value = member.id;
    memberInlineOpen.value = {
        key: `${member.id}:${field}`,
        nonce: memberInlineOpen.value.nonce + 1,
    };
    nextTick(() => {
        document.getElementById(`member-row-${member.id}`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    });
}

onMounted(() => {
    const id = props.open_edit_member_id;
    if (!id) return;
    const m = props.members?.find((x) => x.id === id);
    if (m) requestMemberInlineEdit(m, 'first_name');
});

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
                showAddForm.value = false;
            },
        });
}

function deleteMember(member) {
    if (!member?.id) return;
    if (!confirm('Dit contact verwijderen?')) return;
    if (rowHighlightMemberId.value === member.id) {
        rowHighlightMemberId.value = null;
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

function memberDisplayName(m) {
    const fn = m?.first_name ?? '';
    const ln = m?.last_name ?? '';
    return `${fn}${ln ? ` ${ln}` : ''}`.trim() || '–';
}

const installedSavingId = ref(null);

function setMemberInstalled(member, value) {
    if (!member?.id || Boolean(member.installed) === value) {
        return;
    }
    installedSavingId.value = member.id;
    router.patch(
        route('members.update-installed', member.id),
        { installed: value },
        {
            preserveScroll: true,
            onFinish: () => {
                installedSavingId.value = null;
            },
        },
    );
}

function installedToggleClass(member, isJa) {
    const on = isJa ? Boolean(member.installed) : !member.installed;
    if (on) {
        return isJa
            ? 'bg-emerald-700 text-white ring-2 ring-emerald-400/80'
            : 'bg-rose-900/80 text-rose-100 ring-2 ring-rose-500/70';
    }
    return 'border border-brand-blue/40 bg-app-panel text-app-ink hover:bg-brand-blue/10 dark:border-brand-blue/45 dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/20';
}

const memberFieldSaving = ref(null);

function normalizeMemberQuickPayload(field, raw) {
    if (field === 'age') {
        const s = String(raw ?? '').trim();
        if (s === '') {
            return { age: null };
        }
        const n = Number.parseInt(s, 10);
        return { age: Number.isNaN(n) ? null : n };
    }
    if (field === 'birthday') {
        const s = String(raw ?? '').trim();
        return { birthday: s === '' ? null : s };
    }
    return { [field]: raw ?? '' };
}

function patchMemberField(member, field, raw) {
    const payload = normalizeMemberQuickPayload(field, raw);
    const k = Object.keys(payload)[0];
    memberFieldSaving.value = `${member.id}:${k}`;
    router.patch(route('members.quick-update', member.id), payload, {
        preserveScroll: true,
        onFinish: () => {
            memberFieldSaving.value = null;
        },
    });
}

function isMemberFieldSaving(member, field) {
    return memberFieldSaving.value === `${member.id}:${field}`;
}
</script>

<template>
    <Head :title="speltakLabel" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }}</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-app-panel px-3 py-2 text-sm font-medium text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        {{ speltakSingular }} toevoegen
                    </button>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-app-ink dark:text-app-ink-dark">
            <form
                v-show="showAddForm"
                class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-5"
                @submit.prevent="submitAdd"
            >
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuw contact</h3>
                <div class="grid gap-4 sm:grid-cols-[10rem_1fr] sm:items-start">
                    <label for="add-member-first-name" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Voornaam
                    </label>
                    <input
                        id="add-member-first-name"
                        v-model="form.first_name"
                        type="text"
                        autocomplete="given-name"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-member-last-name" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Achternaam <span class="font-normal text-app-muted dark:text-app-muted-dark">(optioneel)</span>
                    </label>
                    <input
                        id="add-member-last-name"
                        v-model="form.last_name"
                        type="text"
                        autocomplete="family-name"
                        placeholder="Optioneel"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-member-birthday" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Geboortedatum
                    </label>
                    <input
                        id="add-member-birthday"
                        v-model="form.birthday"
                        type="date"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    />

                    <label for="add-member-age" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Leeftijd
                    </label>
                    <input
                        id="add-member-age"
                        v-model="form.age"
                        type="number"
                        min="0"
                        max="99"
                        placeholder="Optioneel"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-member-phone-mother" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Telefoon moeder
                    </label>
                    <input
                        id="add-member-phone-mother"
                        v-model="form.phone_mother"
                        type="text"
                        autocomplete="tel"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-member-phone-father" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Telefoon vader
                    </label>
                    <input
                        id="add-member-phone-father"
                        v-model="form.phone_father"
                        type="text"
                        autocomplete="tel"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-member-address" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Adres
                    </label>
                    <input
                        id="add-member-address"
                        v-model="form.address"
                        type="text"
                        autocomplete="street-address"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-member-bijzonderheden" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Bijzonderheden
                    </label>
                    <textarea
                        id="add-member-bijzonderheden"
                        v-model="form.bijzonderheden"
                        rows="3"
                        placeholder="Allergiën, medicatie, dieet, andere aandachtspunten…"
                        class="min-h-[5rem] min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button
                            type="submit"
                            class="rounded bg-brand-red px-5 py-2 text-sm font-medium text-white hover:bg-brand-red-dark disabled:opacity-50"
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

            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4">
                <SpeltakSubnav />

                <div
                    class="mb-3 flex w-full flex-col gap-3 border-b border-brand-blue/35 pb-2 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <h3 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">
                            {{ membersTab === 'dolfijnen' ? 'Overzicht' : 'Bijzonderheden' }}
                        </h3>
                        <p
                            v-if="membersTab === 'dolfijnen'"
                            class="mt-0.5 text-xs text-app-muted dark:text-app-muted-dark"
                        >
                            Gesorteerd van oud naar jong (leeftijd).
                        </p>
                    </div>
                    <div class="flex w-full max-w-sm items-center gap-2 self-end sm:ms-auto">
                        <MagnifyingGlassIcon
                            class="h-5 w-5 shrink-0 text-app-muted dark:text-app-muted-dark"
                            aria-hidden="true"
                        />
                        <label class="sr-only" for="members-page-search">Zoeken in alle contactvelden</label>
                        <input
                            id="members-page-search"
                            v-model="memberSearchQuery"
                            type="search"
                            autocomplete="off"
                            :placeholder="
                                membersTab === 'dolfijnen'
                                    ? 'Zoek op naam, adres, telefoon…'
                                    : 'Zoek op naam, adres, telefoon, bijzonderheden…'
                            "
                            class="min-w-0 flex-1 rounded border border-app-border bg-white px-3 py-2 text-sm text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                        />
                    </div>
                </div>

                <p
                    v-if="membersTab === 'bijzonderheden'"
                    class="mb-3 text-xs text-app-muted dark:text-app-muted-dark"
                >
                    Allergiën, medicatie, dieet en andere aandachtspunten. Dubbelklik in een cel om te bewerken.
                    Kinderen met ingevulde bijzonderheden staan bovenaan. Voor leiding: menu Leiding.
                </p>

                <div v-if="!props.members?.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
                    Nog geen {{ speltakLabel }}.
                </div>
                <div v-else-if="!filteredMembers.length" class="py-6 text-center text-sm text-app-muted dark:text-app-muted-dark">
                    Geen resultaten voor deze zoekopdracht.
                </div>

                <div v-else-if="membersTab === 'dolfijnen'" class="space-y-2 md:space-y-0">
                    <div class="md:hidden space-y-2">
                        <div
                            v-for="member in sortedDolfijnenMembers"
                            :key="`m-mob-${member.id}`"
                            class="surface-brand-top rounded-xl border border-brand-blue/30 bg-app-panel px-4 py-3 text-app-ink shadow-sm dark:bg-app-panel-dark/95 dark:text-app-ink-dark"
                        >
                            <Link
                                :href="route('members.show', member.id)"
                                class="flex items-center justify-between gap-3 rounded-lg active:bg-brand-blue/15"
                            >
                                <span class="flex min-w-0 items-center gap-2 truncate">
                                    <span class="truncate font-medium">{{ memberDisplayName(member) }}</span>
                                </span>
                                <ChevronRightIcon class="h-5 w-5 shrink-0 text-app-muted dark:text-app-muted-dark" aria-hidden="true" />
                            </Link>
                            <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-brand-blue/25 pt-3 dark:border-brand-blue/35" @click.stop>
                                <span class="text-xs font-semibold text-app-muted dark:text-app-muted-dark">Geïnstalleerd</span>
                                <button
                                    type="button"
                                    class="rounded px-3 py-1 text-xs font-semibold transition disabled:opacity-50"
                                    :class="installedToggleClass(member, true)"
                                    :disabled="installedSavingId === member.id"
                                    @click="setMemberInstalled(member, true)"
                                >
                                    Ja
                                </button>
                                <button
                                    type="button"
                                    class="rounded px-3 py-1 text-xs font-semibold transition disabled:opacity-50"
                                    :class="installedToggleClass(member, false)"
                                    :disabled="installedSavingId === member.id"
                                    @click="setMemberInstalled(member, false)"
                                >
                                    Nee
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="surface-brand-top-lg hidden overflow-x-auto rounded-lg border border-brand-blue/25 md:block">
                        <table class="w-full min-w-[60rem] border-collapse text-left text-sm text-app-ink dark:text-app-ink-dark">
                        <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Geïnstalleerd</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Voornaam</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Achternaam</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Verjaardag</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Leeftijd</th>
                                <th scope="col" class="min-w-[10rem] px-3 py-2.5">Adres</th>
                                <th scope="col" class="min-w-[9rem] px-3 py-2.5">Telefoon moeder</th>
                                <th scope="col" class="min-w-[9rem] px-3 py-2.5">Telefoon vader</th>
                                <th scope="col" class="min-w-[11rem] whitespace-nowrap px-3 py-2.5 text-end sm:text-start">
                                    Acties
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-blue/25">
                            <tr
                                v-for="member in sortedDolfijnenMembers"
                                :id="`member-row-${member.id}`"
                                :key="member.id"
                                class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                                :class="{ '!bg-brand-blue/15 dark:!bg-app-canvas-dark/90': rowHighlightMemberId === member.id }"
                            >
                                <td class="whitespace-nowrap px-3 py-2.5 align-top text-app-ink dark:text-app-ink-dark">
                                    <div class="flex flex-wrap gap-1.5">
                                        <button
                                            type="button"
                                            class="rounded px-2.5 py-1 text-xs font-semibold transition disabled:opacity-50"
                                            :class="installedToggleClass(member, true)"
                                            :disabled="installedSavingId === member.id"
                                            @click="setMemberInstalled(member, true)"
                                        >
                                            Ja
                                        </button>
                                        <button
                                            type="button"
                                            class="rounded px-2.5 py-1 text-xs font-semibold transition disabled:opacity-50"
                                            :class="installedToggleClass(member, false)"
                                            :disabled="installedSavingId === member.id"
                                            @click="setMemberInstalled(member, false)"
                                        >
                                            Nee
                                        </button>
                                    </div>
                                </td>
                                <td class="max-w-[10rem] px-3 py-2.5 align-top">
                                    <EditableTextCell
                                        :text="member.first_name || ''"
                                        :multiline="false"
                                        :cell-key="`${member.id}:first_name`"
                                        :open-request-key="memberInlineOpen.key"
                                        :open-request-nonce="memberInlineOpen.nonce"
                                        :saving="isMemberFieldSaving(member, 'first_name')"
                                        @save="(v) => patchMemberField(member, 'first_name', v)"
                                    />
                                </td>
                                <td class="max-w-[10rem] px-3 py-2.5 align-top">
                                    <EditableTextCell
                                        :text="member.last_name || ''"
                                        :multiline="false"
                                        :cell-key="`${member.id}:last_name`"
                                        :open-request-key="memberInlineOpen.key"
                                        :open-request-nonce="memberInlineOpen.nonce"
                                        :saving="isMemberFieldSaving(member, 'last_name')"
                                        @save="(v) => patchMemberField(member, 'last_name', v)"
                                    />
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">
                                    <EditableTextCell
                                        :text="member.birthday ? String(member.birthday).slice(0, 10) : ''"
                                        input-kind="date"
                                        :multiline="false"
                                        :cell-key="`${member.id}:birthday`"
                                        :open-request-key="memberInlineOpen.key"
                                        :open-request-nonce="memberInlineOpen.nonce"
                                        :saving="isMemberFieldSaving(member, 'birthday')"
                                        @save="(v) => patchMemberField(member, 'birthday', v)"
                                    />
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">
                                    <EditableTextCell
                                        :text="member.age != null ? String(member.age) : ''"
                                        :multiline="false"
                                        :cell-key="`${member.id}:age`"
                                        :open-request-key="memberInlineOpen.key"
                                        :open-request-nonce="memberInlineOpen.nonce"
                                        :saving="isMemberFieldSaving(member, 'age')"
                                        @save="(v) => patchMemberField(member, 'age', v)"
                                    />
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <EditableTextCell
                                        :text="member.address || ''"
                                        multiline
                                        :cell-key="`${member.id}:address`"
                                        :open-request-key="memberInlineOpen.key"
                                        :open-request-nonce="memberInlineOpen.nonce"
                                        :saving="isMemberFieldSaving(member, 'address')"
                                        @save="(v) => patchMemberField(member, 'address', v)"
                                    />
                                </td>
                                <td class="px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">
                                    <EditableTextCell
                                        :text="member.phone_mother || ''"
                                        :multiline="false"
                                        :cell-key="`${member.id}:phone_mother`"
                                        :open-request-key="memberInlineOpen.key"
                                        :open-request-nonce="memberInlineOpen.nonce"
                                        :saving="isMemberFieldSaving(member, 'phone_mother')"
                                        @save="(v) => patchMemberField(member, 'phone_mother', v)"
                                    />
                                </td>
                                <td class="px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">
                                    <EditableTextCell
                                        :text="member.phone_father || ''"
                                        :multiline="false"
                                        :cell-key="`${member.id}:phone_father`"
                                        :open-request-key="memberInlineOpen.key"
                                        :open-request-nonce="memberInlineOpen.nonce"
                                        :saving="isMemberFieldSaving(member, 'phone_father')"
                                        @save="(v) => patchMemberField(member, 'phone_father', v)"
                                    />
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <button type="button" class="btn-action-delete" @click="deleteMember(member)">
                                        <TrashIcon class="h-4 w-4 shrink-0" />
                                        Verwijderen
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <div v-else-if="membersTab === 'bijzonderheden'" class="space-y-2">
                    <div class="md:hidden space-y-2">
                        <div
                            v-for="member in sortedFilteredMembers"
                            :key="`bijz-mob-${member.id}`"
                            class="surface-brand-top rounded-xl border border-brand-blue/30 bg-app-panel p-4 shadow-sm dark:bg-app-panel-dark/95"
                        >
                            <p class="font-medium text-app-ink dark:text-app-ink-dark">{{ memberDisplayName(member) }}</p>
                            <div class="mt-2 text-sm leading-snug" @click.stop>
                                <EditableTextCell
                                    :text="member.bijzonderheden || ''"
                                    multiline
                                    :cell-key="`${member.id}:bijzonderheden`"
                                    :open-request-key="memberInlineOpen.key"
                                    :open-request-nonce="memberInlineOpen.nonce"
                                    :saving="isMemberFieldSaving(member, 'bijzonderheden')"
                                    @save="(v) => patchMemberField(member, 'bijzonderheden', v)"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="surface-brand-top-lg hidden overflow-x-auto rounded-lg border border-brand-blue/25 md:block">
                        <table class="w-full min-w-[28rem] border-collapse text-left text-sm text-app-ink dark:text-app-ink-dark">
                            <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                                <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                                    <th scope="col" class="min-w-[8rem] px-3 py-2.5">Naam</th>
                                    <th scope="col" class="min-w-[16rem] px-3 py-2.5">Bijzonderheden</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-brand-blue/25">
                                <tr
                                    v-for="member in sortedFilteredMembers"
                                    :id="`member-row-${member.id}`"
                                    :key="`bijz-${member.id}`"
                                    class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                                    :class="{ '!bg-brand-blue/15 dark:!bg-app-canvas-dark/90': rowHighlightMemberId === member.id }"
                                >
                                    <td class="px-3 py-2.5 align-top font-medium text-app-ink dark:text-app-ink-dark">
                                        {{ memberDisplayName(member) }}
                                    </td>
                                    <td class="px-3 py-2.5 align-top break-words leading-snug text-app-ink dark:text-app-ink-dark">
                                        <EditableTextCell
                                            :text="member.bijzonderheden || ''"
                                            multiline
                                            :cell-key="`${member.id}:bijzonderheden`"
                                            :open-request-key="memberInlineOpen.key"
                                            :open-request-nonce="memberInlineOpen.nonce"
                                            :saving="isMemberFieldSaving(member, 'bijzonderheden')"
                                            @save="(v) => patchMemberField(member, 'bijzonderheden', v)"
                                        />
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
