<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    members: {
        type: Array,
        default: () => [],
    },
});

const opkomstSavingId = ref(null);

function memberDisplayName(m) {
    const fn = m?.first_name ?? '';
    const ln = m?.last_name ?? '';
    return `${fn}${ln ? ` ${ln}` : ''}`.trim() || '–';
}

function sortTier(m) {
    if (m.tipper_topper_opkomst === true) {
        return 0;
    }
    if (m.tipper_topper_opkomst === false) {
        return 2;
    }
    return 1;
}

const sortedMembers = computed(() => {
    const list = [...(props.members || [])];
    const cmpName = (a, b) =>
        memberDisplayName(a).localeCompare(memberDisplayName(b), 'nl', { sensitivity: 'base' });

    return list.sort((a, b) => {
        const tA = sortTier(a);
        const tB = sortTier(b);
        if (tA !== tB) {
            return tA - tB;
        }
        if (tA === 0) {
            const oa = a.tipper_topper_opkomst_order ?? -1;
            const ob = b.tipper_topper_opkomst_order ?? -1;
            if (ob !== oa) {
                return ob - oa;
            }
            return cmpName(a, b);
        }
        if (tA === 2) {
            const oa = a.tipper_topper_opkomst_order ?? 0;
            const ob = b.tipper_topper_opkomst_order ?? 0;
            if (oa !== ob) {
                return oa - ob;
            }
            return cmpName(a, b);
        }
        return cmpName(a, b);
    });
});

function setTipperTopperOpkomst(member, value) {
    if (!member?.id) return;
    opkomstSavingId.value = member.id;
    router.patch(
        route('members.tipper-topper-opkomst', member.id),
        { tipper_topper_opkomst: value },
        {
            preserveScroll: true,
            onFinish: () => {
                opkomstSavingId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Tipper- & Topper opkomst" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Tipper- & Topper opkomst</h2>
        </template>

        <div class="space-y-4 text-white">
            <div class="rounded-xl bg-gray-800 p-5 shadow-sm">
                <h3 class="border-b border-gray-600 pb-2 text-lg font-semibold text-indigo-200">Dolfijnen</h3>
                <p class="mt-2 text-xs text-gray-400">
                    Alle Dolfijnen staan hieronder. Ja’s bovenaan (laatste Ja het hoogst). Daarna nog geen keuze. Onderaan alle
                    Nee’s; wie het laatst op Nee is gezet, staat het laagst.
                </p>

                <div v-if="!props.members?.length" class="mt-6 py-10 text-center text-sm text-gray-500">
                    Nog geen contacten.
                </div>
                <div v-else class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div
                        v-for="m in sortedMembers"
                        :key="m.id"
                        class="rounded-lg border border-gray-600 bg-gray-900/50 p-3"
                    >
                        <p class="text-sm font-medium text-white">
                            {{ memberDisplayName(m) }}
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded px-3 py-1.5 text-xs font-semibold transition disabled:opacity-50"
                                :class="
                                    m.tipper_topper_opkomst === true
                                        ? 'bg-emerald-700 text-white ring-2 ring-emerald-400/80'
                                        : 'border border-gray-500 bg-gray-800 text-gray-200 hover:bg-gray-700'
                                "
                                :disabled="opkomstSavingId === m.id"
                                @click="setTipperTopperOpkomst(m, true)"
                            >
                                Ja
                            </button>
                            <button
                                type="button"
                                class="rounded px-3 py-1.5 text-xs font-semibold transition disabled:opacity-50"
                                :class="
                                    m.tipper_topper_opkomst === false
                                        ? 'bg-rose-900/80 text-rose-100 ring-2 ring-rose-500/70'
                                        : 'border border-gray-500 bg-gray-800 text-gray-200 hover:bg-gray-700'
                                "
                                :disabled="opkomstSavingId === m.id"
                                @click="setTipperTopperOpkomst(m, false)"
                            >
                                Nee
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
