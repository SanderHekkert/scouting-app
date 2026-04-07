<script setup>
import { computed, ref, watch } from 'vue';
import SpeltakSubnav from '@/Components/SpeltakSubnav.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    pods: Array,
    unassignedMembers: {
        type: Array,
        default: () => [],
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
const groupWord = computed(() => page.props.auth?.active_section === 'zeeverkenners' ? 'bak' : 'vin');
const groupWordCapitalized = computed(() => groupWord.value.charAt(0).toUpperCase() + groupWord.value.slice(1));
const groupingTitle = computed(() => page.props.auth?.active_section === 'zeeverkenners' ? 'Bakindeling' : 'Vinindeling');

const showLinkForm = ref(false);
const showGroupForm = ref(false);

const memberForm = useForm({
    pod_id: '',
    member_id: '',
    role: 'Vinlid',
});
const groupForm = useForm({
    name: '',
});

const selectedPod = computed(() =>
    props.pods?.find((p) => String(p.id) === String(memberForm.pod_id)),
);

const podHasTopper = computed(() =>
    selectedPod.value?.memberships?.some((m) => m.role === 'Topper'),
);

const podHasTipper = computed(() =>
    selectedPod.value?.memberships?.some((m) => m.role === 'Tipper'),
);

watch(
    () => memberForm.pod_id,
    () => {
        memberForm.role = 'Vinlid';
        memberForm.clearErrors();
    },
);

function toggleLinkForm() {
    showLinkForm.value = !showLinkForm.value;
    if (showLinkForm.value) {
        memberForm.reset();
        memberForm.role = 'Vinlid';
    }
}

function toggleGroupForm() {
    showGroupForm.value = !showGroupForm.value;
    if (showGroupForm.value) {
        groupForm.reset();
        groupForm.clearErrors();
    }
}

function tier(role) {
    if (role === 'Topper') return 0;
    if (role === 'Tipper') return 1;
    return 2;
}

function sortedMemberships(memberships) {
    if (!memberships?.length) return [];
    return [...memberships].sort((a, b) => {
        const t = tier(a.role) - tier(b.role);
        if (t !== 0) return t;
        const ageA = a.member?.age;
        const ageB = b.member?.age;
        if (ageA != null && ageB != null && ageA !== ageB) return ageB - ageA;
        if (ageA != null && ageB == null) return -1;
        if (ageA == null && ageB != null) return 1;
        const name = (m) =>
            `${m?.member?.last_name ?? ''} ${m?.member?.first_name ?? ''}`.trim();
        return name(a).localeCompare(name(b), 'nl', { sensitivity: 'base' });
    });
}

/** Per groep: Topper → Tipper → Vinleden (zoals besproken / seed-kolommen). */
function podSections(memberships) {
    const sorted = sortedMemberships(memberships ?? []);
    const topper = sorted.filter((m) => m.role === 'Topper');
    const tipper = sorted.filter((m) => m.role === 'Tipper');
    const vinlids = sorted.filter((m) => m.role === 'Vinlid');
    return [
        { key: 'topper', label: 'Topper', items: topper },
        { key: 'tipper', label: 'Tipper', items: tipper },
        { key: 'vinlid', label: 'Vinleden', items: vinlids },
    ];
}

function roleBadgeClass(role) {
    if (role === 'Topper') {
        return 'bg-brand-yellow/25 text-brand-blue-dark dark:bg-brand-yellow/35 dark:text-app-ink-dark';
    }
    if (role === 'Tipper') {
        return 'bg-brand-blue/30 text-app-ink dark:text-app-ink-dark';
    }
    return 'bg-brand-green/25 text-brand-green dark:text-app-ink dark:text-app-ink-dark';
}

function submitLink() {
    if (!memberForm.pod_id) return;
    memberForm.post(route('pods.members.store', memberForm.pod_id), {
        preserveScroll: true,
        onSuccess: () => {
            memberForm.reset();
            memberForm.role = 'Vinlid';
            showLinkForm.value = false;
        },
    });
}

function submitGroup() {
    const name = String(groupForm.name ?? '').trim();
    if (!name) return;
    groupForm.name = name;
    groupForm.post(route('pods.store'), {
        preserveScroll: true,
        onSuccess: () => {
            groupForm.reset();
            showGroupForm.value = false;
        },
    });
}

function removeMembership(membership) {
    if (!membership?.id) return;
    if (!confirm(`Deze ${speltakSingular.value} uit de ${groupWord.value} halen?`)) return;
    router.delete(route('pods.members.destroy', membership.id), {
        preserveScroll: true,
    });
}

function removeGroup(pod) {
    if (!pod?.id) return;
    if (!confirm(`Deze ${groupWord.value} verwijderen?`)) return;
    router.delete(route('pods.destroy', pod.id), {
        preserveScroll: true,
    });
}

function memberOptionLabel(m) {
    const base = `${m.first_name} ${m.last_name}`.trim();
    return m.age != null ? `${base} (${m.age})` : base;
}
</script>

<template>
    <Head :title="groupingTitle" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }}</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-app-panel px-3 py-2 text-sm font-medium text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15"
                        @click="toggleGroupForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Nieuwe {{ groupWordCapitalized }}
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-app-panel px-3 py-2 text-sm font-medium text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15"
                        @click="toggleLinkForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        {{ speltakSingular }} koppelen aan {{ groupWordCapitalized }}
                    </button>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-app-ink dark:text-app-ink-dark">
            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4">
                <SpeltakSubnav />
            </div>

            <form
                v-show="showGroupForm"
                class="surface-brand-top grid gap-4 rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-5 sm:grid-cols-[8rem_1fr_auto]"
                @submit.prevent="submitGroup"
            >
                <label for="group-name" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                    {{ groupWordCapitalized }}
                </label>
                <div>
                    <input
                        id="group-name"
                        v-model="groupForm.name"
                        type="text"
                        class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                        :placeholder="`Naam van de ${groupWord}`"
                        required
                    />
                    <p v-if="groupForm.errors.name" class="mt-1 text-sm text-red-400">
                        {{ groupForm.errors.name }}
                    </p>
                </div>
                <div class="flex gap-2 sm:justify-end">
                    <button
                        type="submit"
                        class="rounded bg-brand-blue px-4 py-2 text-sm font-medium text-white hover:bg-brand-blue-dark disabled:opacity-50"
                        :disabled="groupForm.processing"
                    >
                        Toevoegen
                    </button>
                    <button
                        type="button"
                        class="rounded border border-brand-blue-light/50 px-4 py-2 text-sm font-medium text-app-ink dark:text-app-ink-dark transition hover:bg-brand-blue/20"
                        @click="toggleGroupForm"
                    >
                        Annuleren
                    </button>
                </div>
            </form>

            <form
                v-show="showLinkForm"
                class="surface-brand-top grid gap-4 rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-5 md:grid-cols-2"
                @submit.prevent="submitLink"
            >
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark md:col-span-2">{{ speltakSingular }} aan een {{ groupWord }} koppelen</h3>
                <div class="md:col-span-2 grid gap-4 sm:grid-cols-[7rem_1fr] sm:items-start">
                    <label for="link-pod" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">{{ groupWordCapitalized }}</label>
                    <select
                        id="link-pod"
                        v-model="memberForm.pod_id"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                        required
                    >
                        <option value="" disabled>Kies {{ groupWord }}</option>
                        <option v-for="pod in pods" :key="pod.id" :value="String(pod.id)">{{ pod.name }}</option>
                    </select>

                    <label for="link-member" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">{{ speltakSingular }}</label>
                    <select
                        id="link-member"
                        v-model="memberForm.member_id"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                        required
                    >
                        <option value="" disabled>Kies {{ speltakSingular }}</option>
                        <option
                            v-for="m in unassignedMembers"
                            :key="m.id"
                            :value="String(m.id)"
                        >
                            {{ memberOptionLabel(m) }}
                        </option>
                    </select>
                    <p v-if="!unassignedMembers.length" class="text-sm text-app-muted dark:text-app-muted-dark sm:col-span-2">
                        Alle leden zitten al in een {{ groupWord }}.
                    </p>

                    <label for="link-role" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Rol</label>
                    <select
                        id="link-role"
                        v-model="memberForm.role"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    >
                        <option value="Topper" :disabled="podHasTopper">
                            Topper{{ podHasTopper ? ' (bezet)' : '' }}
                        </option>
                        <option value="Tipper" :disabled="podHasTipper">
                            Tipper{{ podHasTipper ? ' (bezet)' : '' }}
                        </option>
                        <option value="Vinlid">Vinlid</option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-2 md:col-span-2">
                    <button
                        type="submit"
                        class="rounded bg-brand-red px-5 py-2 text-sm font-medium text-white hover:bg-brand-red-dark disabled:opacity-50"
                        :disabled="memberForm.processing || !unassignedMembers.length"
                    >
                        Koppelen
                    </button>
                    <button
                        type="button"
                        class="rounded border border-brand-blue-light/50 px-5 py-2 text-sm font-medium text-app-ink dark:text-app-ink-dark transition hover:bg-brand-blue/20"
                        @click="toggleLinkForm"
                    >
                        Annuleren
                    </button>
                </div>
                <p v-if="memberForm.errors.member_id" class="text-sm text-red-400 md:col-span-2">
                    {{ memberForm.errors.member_id }}
                </p>
                <p v-if="memberForm.errors.role" class="text-sm text-red-400 md:col-span-2">
                    {{ memberForm.errors.role }}
                </p>
            </form>

            <!-- Vier vaste kolommen (Narwals, Orinoco's, Tuimelaars, Grampers) op brede schermen -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4 xl:items-start">
                <div
                    v-for="pod in pods"
                    :key="pod.id"
                    class="surface-brand-top flex min-w-0 flex-col rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4"
                >
                    <div class="flex items-start justify-between gap-2 border-b border-brand-blue/35 pb-2">
                        <h3 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">
                            {{ pod.name }}
                        </h3>
                        <button
                            type="button"
                            class="btn-action-delete shrink-0"
                            @click="removeGroup(pod)"
                        >
                            <TrashIcon class="h-4 w-4" />
                            Verwijderen
                        </button>
                    </div>

                    <div v-if="!pod.memberships?.length" class="py-4 text-sm text-app-muted dark:text-app-muted-dark">
                        Nog geen leden in deze {{ groupWord }}.
                    </div>

                    <div v-else class="mt-3 flex flex-col gap-4 text-sm">
                        <section
                            v-for="section in podSections(pod.memberships)"
                            :key="`${pod.id}-${section.key}`"
                            class="min-w-0"
                        >
                            <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                                {{ section.label }}
                            </h4>
                            <ul class="space-y-2">
                                <li
                                    v-for="membership in section.items"
                                    :key="membership.id"
                                    class="flex items-start justify-between gap-2"
                                >
                                    <div class="min-w-0 text-app-ink dark:text-app-ink-dark">
                                        <span
                                            class="mr-2 inline-block rounded px-2 py-0.5 text-xs font-semibold"
                                            :class="roleBadgeClass(membership.role)"
                                        >
                                            {{ membership.role }}
                                        </span>
                                        <span>
                                            {{ membership.member?.first_name }} {{ membership.member?.last_name }}
                                        </span>
                                        <span
                                            v-if="membership.member?.age != null"
                                            class="text-app-muted dark:text-app-muted-dark"
                                        >
                                            ({{ membership.member.age }})
                                        </span>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn-action-delete shrink-0"
                                        @click="removeMembership(membership)"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                        Verwijderen
                                    </button>
                                </li>
                                <li
                                    v-if="!section.items.length"
                                    class="text-app-muted dark:text-app-muted-dark"
                                >
                                    —
                                </li>
                            </ul>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
