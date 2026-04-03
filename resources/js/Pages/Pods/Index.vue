<script setup>
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    pods: Array,
    unassignedMembers: {
        type: Array,
        default: () => [],
    },
});

const showLinkForm = ref(false);

const memberForm = useForm({
    pod_id: '',
    member_id: '',
    role: 'Vinlid',
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

function removeMembership(membership) {
    if (!membership?.id) return;
    if (!confirm('Deze Dolfijn uit de vin halen?')) return;
    router.delete(route('pods.members.destroy', membership.id), {
        preserveScroll: true,
    });
}

function memberOptionLabel(m) {
    const base = `${m.first_name} ${m.last_name}`.trim();
    return m.age != null ? `${base} (${m.age})` : base;
}
</script>

<template>
    <Head title="Vinindeling" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Vinindeling</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-app-panel px-3 py-2 text-sm font-medium text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15"
                        @click="toggleLinkForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Dolfijn koppelen aan Vin
                    </button>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-app-ink-dark">
            <form
                v-show="showLinkForm"
                class="grid gap-4 rounded-xl border border-brand-blue/20 bg-app-panel-dark p-5 shadow-sm md:grid-cols-2"
                @submit.prevent="submitLink"
            >
                <h3 class="text-base font-semibold text-brand-yellow-soft md:col-span-2">Dolfijn aan een vin koppelen</h3>
                <div class="md:col-span-2 grid gap-4 sm:grid-cols-[7rem_1fr] sm:items-start">
                    <label for="link-pod" class="text-sm font-semibold tracking-wide text-app-muted-dark sm:pt-2.5">Vin</label>
                    <select
                        id="link-pod"
                        v-model="memberForm.pod_id"
                        class="min-w-0 rounded border border-app-border-dark bg-app-canvas-dark px-3 py-2 text-app-ink-dark"
                        required
                    >
                        <option value="" disabled>Kies vin</option>
                        <option v-for="pod in pods" :key="pod.id" :value="String(pod.id)">{{ pod.name }}</option>
                    </select>

                    <label for="link-member" class="text-sm font-semibold tracking-wide text-app-muted-dark sm:pt-2.5">Dolfijn</label>
                    <select
                        id="link-member"
                        v-model="memberForm.member_id"
                        class="min-w-0 rounded border border-app-border-dark bg-app-canvas-dark px-3 py-2 text-app-ink-dark"
                        required
                    >
                        <option value="" disabled>Kies Dolfijn</option>
                        <option
                            v-for="m in unassignedMembers"
                            :key="m.id"
                            :value="String(m.id)"
                        >
                            {{ memberOptionLabel(m) }}
                        </option>
                    </select>
                    <p v-if="!unassignedMembers.length" class="text-sm text-amber-200/90 sm:col-span-2">
                        Alle leden zitten al in een vin.
                    </p>

                    <label for="link-role" class="text-sm font-semibold tracking-wide text-app-muted-dark sm:pt-2.5">Rol</label>
                    <select
                        id="link-role"
                        v-model="memberForm.role"
                        class="min-w-0 rounded border border-app-border-dark bg-app-canvas-dark px-3 py-2 text-app-ink-dark"
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
                        class="rounded border border-brand-blue-light/50 px-5 py-2 text-sm font-medium text-app-ink-dark transition hover:bg-brand-blue/20"
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

            <div class="grid gap-4 md:grid-cols-2">
                <div
                    v-for="pod in pods"
                    :key="pod.id"
                    class="rounded-xl border border-brand-blue/20 bg-app-panel-dark p-4 shadow-sm"
                >
                    <h3 class="border-b border-brand-blue/35 pb-2 text-lg font-semibold text-brand-yellow-soft">
                        {{ pod.name }}
                    </h3>
                    <p v-if="!pod.memberships?.length" class="py-4 text-sm text-app-muted-dark">
                        Nog geen leden in deze vin.
                    </p>
                    <ul v-else class="mt-3 space-y-2 text-sm">
                        <li
                            v-for="membership in sortedMemberships(pod.memberships)"
                            :key="membership.id"
                            class="flex items-start justify-between gap-2 border-t border-brand-blue/35 pt-2 first:border-t-0 first:pt-0"
                        >
                            <div class="min-w-0 text-app-ink-dark">
                                <span
                                    class="mr-2 inline-block rounded px-2 py-0.5 text-xs font-semibold"
                                    :class="{
                                        'bg-brand-yellow/25 text-brand-blue-dark dark:bg-brand-yellow/35 dark:text-app-ink-dark': membership.role === 'Topper',
                                        'bg-brand-blue/30 text-brand-yellow-soft': membership.role === 'Tipper',
                                        'bg-brand-green/25 text-brand-green dark:text-brand-yellow-soft': membership.role !== 'Topper' && membership.role !== 'Tipper',
                                    }"
                                >
                                    {{ membership.role }}
                                </span>
                                <span class="text-app-ink-dark">
                                    {{ membership.member?.first_name }} {{ membership.member?.last_name }}
                                </span>
                                <span v-if="membership.member?.age != null" class="text-app-muted-dark">
                                    ({{ membership.member.age }})
                                </span>
                            </div>
                            <button
                                type="button"
                                class="inline-flex shrink-0 items-center gap-1 rounded border border-red-800/60 bg-red-950/35 px-2 py-1 text-xs font-medium text-red-300 hover:bg-red-950/55"
                                @click="removeMembership(membership)"
                            >
                                <TrashIcon class="h-4 w-4" />
                                Verwijderen
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
