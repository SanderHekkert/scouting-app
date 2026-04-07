<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import EditableTextCell from '@/Components/EditableTextCell.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ChevronRightIcon, MagnifyingGlassIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

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
const rowHighlightLeaderId = ref(null);
const leaderInlineOpen = ref({ key: '', nonce: 0 });

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
    ]);
}

const filteredLeaders = computed(() =>
    (props.leaders || []).filter((l) => leaderMatchesSearch(l, leaderSearchQuery.value)),
);

function toggleAddForm() {
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        rowHighlightLeaderId.value = null;
        form.reset();
        form.clearErrors();
    }
}

function requestLeaderInlineEdit(leader, field = 'first_name') {
    if (!leader) return;
    showAddForm.value = false;
    rowHighlightLeaderId.value = leader.id;
    leaderInlineOpen.value = {
        key: `${leader.id}:${field}`,
        nonce: leaderInlineOpen.value.nonce + 1,
    };
    nextTick(() => {
        document.getElementById(`leader-row-${leader.id}`)?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    });
}

onMounted(() => {
    const id = props.open_edit_leader_id;
    if (!id) return;
    const l = props.leaders?.find((x) => x.id === id);
    if (l) requestLeaderInlineEdit(l, 'first_name');
});

function leaderListName(l) {
    return [l.first_name, l.last_name].filter(Boolean).join(' ').trim() || '–';
}

function leaderHasBijzonderheden(l) {
    return l?.bijzonderheden != null && String(l.bijzonderheden).trim() !== '';
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

function deleteLeader(leader) {
    if (!leader?.id) return;
    if (!confirm('Deze leiding verwijderen?')) return;
    if (rowHighlightLeaderId.value === leader.id) {
        rowHighlightLeaderId.value = null;
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

const leaderFieldSaving = ref(null);

function normalizeLeaderQuickPayload(field, raw) {
    if (field === 'birthday') {
        const s = String(raw ?? '').trim();
        return { birthday: s === '' ? null : s };
    }
    return { [field]: raw ?? '' };
}

function patchLeaderField(leader, field, raw) {
    const payload = normalizeLeaderQuickPayload(field, raw);
    const k = Object.keys(payload)[0];
    leaderFieldSaving.value = `${leader.id}:${k}`;
    router.patch(route('leaders.quick-update', leader.id), payload, {
        preserveScroll: true,
        onFinish: () => {
            leaderFieldSaving.value = null;
        },
    });
}

function isLeaderFieldSaving(leader, field) {
    return leaderFieldSaving.value === `${leader.id}:${field}`;
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
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-app-panel px-3 py-2 text-sm font-medium text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Leiding toevoegen
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
                                Rol: {{ leader.section_role_label || '–' }}
                            </div>
                            <div
                                class="mt-2 border-t border-brand-blue/25 pt-2 text-sm dark:border-brand-blue/35"
                                @click.stop
                            >
                                <EditableTextCell
                                    :text="leader.bijzonderheden || ''"
                                    multiline
                                    :cell-key="`${leader.id}:bijzonderheden`"
                                    :open-request-key="leaderInlineOpen.key"
                                    :open-request-nonce="leaderInlineOpen.nonce"
                                    :saving="isLeaderFieldSaving(leader, 'bijzonderheden')"
                                    @save="(v) => patchLeaderField(leader, 'bijzonderheden', v)"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="surface-brand-top-lg hidden overflow-x-auto rounded-lg border border-brand-blue/25 md:block">
                        <table class="w-full min-w-[64rem] border-collapse text-left text-sm text-app-ink dark:text-app-ink-dark">
                        <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Voornaam</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Achternaam</th>
                                <th scope="col" class="whitespace-nowrap px-3 py-2.5">Rol</th>
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
                        <tbody class="divide-y divide-brand-blue/25">
                            <tr
                                v-for="leader in filteredLeaders"
                                :id="`leader-row-${leader.id}`"
                                :key="leader.id"
                                class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                                :class="{ '!bg-brand-blue/15 dark:!bg-app-canvas-dark/90': rowHighlightLeaderId === leader.id }"
                            >
                                <td class="max-w-[10rem] px-3 py-2.5 align-top">
                                    <EditableTextCell
                                        :text="leader.first_name || ''"
                                        :multiline="false"
                                        :cell-key="`${leader.id}:first_name`"
                                        :open-request-key="leaderInlineOpen.key"
                                        :open-request-nonce="leaderInlineOpen.nonce"
                                        :saving="isLeaderFieldSaving(leader, 'first_name')"
                                        @save="(v) => patchLeaderField(leader, 'first_name', v)"
                                    />
                                </td>
                                <td class="max-w-[10rem] px-3 py-2.5 align-top">
                                    <EditableTextCell
                                        :text="leader.last_name || ''"
                                        :multiline="false"
                                        :cell-key="`${leader.id}:last_name`"
                                        :open-request-key="leaderInlineOpen.key"
                                        :open-request-nonce="leaderInlineOpen.nonce"
                                        :saving="isLeaderFieldSaving(leader, 'last_name')"
                                        @save="(v) => patchLeaderField(leader, 'last_name', v)"
                                    />
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top">
                                    {{ leader.section_role_label || '–' }}
                                </td>
                                <td class="max-w-[16rem] px-3 py-2.5 align-top break-words">
                                    <EditableTextCell
                                        :text="leader.bijzonderheden || ''"
                                        multiline
                                        :cell-key="`${leader.id}:bijzonderheden`"
                                        :open-request-key="leaderInlineOpen.key"
                                        :open-request-nonce="leaderInlineOpen.nonce"
                                        :saving="isLeaderFieldSaving(leader, 'bijzonderheden')"
                                        @save="(v) => patchLeaderField(leader, 'bijzonderheden', v)"
                                    />
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <EditableTextCell
                                        :text="leader.address || ''"
                                        multiline
                                        :cell-key="`${leader.id}:address`"
                                        :open-request-key="leaderInlineOpen.key"
                                        :open-request-nonce="leaderInlineOpen.nonce"
                                        :saving="isLeaderFieldSaving(leader, 'address')"
                                        @save="(v) => patchLeaderField(leader, 'address', v)"
                                    />
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top text-app-ink dark:text-app-ink-dark">
                                    <EditableTextCell
                                        :text="leader.postal_code || ''"
                                        :multiline="false"
                                        :cell-key="`${leader.id}:postal_code`"
                                        :open-request-key="leaderInlineOpen.key"
                                        :open-request-nonce="leaderInlineOpen.nonce"
                                        :saving="isLeaderFieldSaving(leader, 'postal_code')"
                                        @save="(v) => patchLeaderField(leader, 'postal_code', v)"
                                    />
                                </td>
                                <td class="max-w-[9rem] px-3 py-2.5 align-top">
                                    <EditableTextCell
                                        :text="leader.city || ''"
                                        :multiline="false"
                                        :cell-key="`${leader.id}:city`"
                                        :open-request-key="leaderInlineOpen.key"
                                        :open-request-nonce="leaderInlineOpen.nonce"
                                        :saving="isLeaderFieldSaving(leader, 'city')"
                                        @save="(v) => patchLeaderField(leader, 'city', v)"
                                    />
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">
                                    <EditableTextCell
                                        :text="leader.birthday ? String(leader.birthday).slice(0, 10) : ''"
                                        input-kind="date"
                                        :multiline="false"
                                        :cell-key="`${leader.id}:birthday`"
                                        :open-request-key="leaderInlineOpen.key"
                                        :open-request-nonce="leaderInlineOpen.nonce"
                                        :saving="isLeaderFieldSaving(leader, 'birthday')"
                                        @save="(v) => patchLeaderField(leader, 'birthday', v)"
                                    />
                                </td>
                                <td class="whitespace-nowrap px-3 py-2.5 align-top tabular-nums text-app-ink dark:text-app-ink-dark">
                                    <EditableTextCell
                                        :text="leader.phone_number || ''"
                                        :multiline="false"
                                        :cell-key="`${leader.id}:phone_number`"
                                        :open-request-key="leaderInlineOpen.key"
                                        :open-request-nonce="leaderInlineOpen.nonce"
                                        :saving="isLeaderFieldSaving(leader, 'phone_number')"
                                        @save="(v) => patchLeaderField(leader, 'phone_number', v)"
                                    />
                                </td>
                                <td class="max-w-[14rem] px-3 py-2.5 align-top break-all">
                                    <EditableTextCell
                                        :text="leader.email || ''"
                                        :multiline="false"
                                        :cell-key="`${leader.id}:email`"
                                        :open-request-key="leaderInlineOpen.key"
                                        :open-request-nonce="leaderInlineOpen.nonce"
                                        :saving="isLeaderFieldSaving(leader, 'email')"
                                        @save="(v) => patchLeaderField(leader, 'email', v)"
                                    />
                                </td>
                                <td class="px-3 py-2.5 align-top">
                                    <button type="button" class="btn-action-delete" @click="deleteLeader(leader)">
                                        <TrashIcon class="h-4 w-4 shrink-0" />
                                        Verwijderen
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
