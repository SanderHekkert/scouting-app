<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    manageableSections: { type: Array, default: () => [] },
    selectedSection: { type: String, required: true },
    isAdmin: { type: Boolean, default: false },
    rows: { type: Array, default: () => [] },
});

const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const sectionOrder = ['bevers', 'dolfijnen', 'zeeverkenners', 'wilde_vaart', 'loodsen', 'bestuur'];

const roleLabels = {
    leiding: 'Leiding',
    ouder_contact: 'Oudercontact',
    teamleider: 'Teamleider',
    lid: 'Lid',
    bestuurslid: 'Bestuurslid',
};

const moduleLabels = {
    dashboard: 'Dashboard',
    events: 'Opkomsten',
    members: 'Leden',
    leaders: 'Leiding',
    pods: 'Vin/Bakindeling',
    info_notes: 'Belangrijke info',
    task_items: 'Taakverdeling',
    year_theme: 'Jaar thema',
    tipper_topper: 'Tipper/Topper opkomst',
    profile: 'Profiel',
};

const localRows = ref([]);
watch(
    () => props.rows,
    (rows) => {
        localRows.value = (rows || []).map((row) => ({ ...row }));
    },
    { immediate: true },
);

const groupedRows = computed(() => {
    const groups = {};
    for (const row of localRows.value || []) {
        if (!groups[row.role]) groups[row.role] = [];
        groups[row.role].push(row);
    }
    const roleOrder = ['teamleider', 'lid', 'leiding', 'ouder_contact', 'bestuurslid'];
    const sorted = {};
    Object.keys(groups)
        .sort((a, b) => {
            const ai = roleOrder.indexOf(a);
            const bi = roleOrder.indexOf(b);
            const av = ai === -1 ? 99 : ai;
            const bv = bi === -1 ? 99 : bi;
            if (av !== bv) return av - bv;
            return a.localeCompare(b);
        })
        .forEach((role) => {
            sorted[role] = groups[role];
        });
    return sorted;
});

const orderedManageableSections = computed(() => {
    const input = Array.isArray(props.manageableSections) ? props.manageableSections : [];
    const allowed = new Set(input);
    const sortedKnown = sectionOrder.filter((section) => allowed.has(section));
    const rest = input.filter((section) => !sectionOrder.includes(section));
    return [...sortedKnown, ...rest];
});

function setSection(section) {
    if (!section || section === props.selectedSection) return;
    router.get(route('permissions.index'), { section }, { preserveState: true, preserveScroll: true });
}

function updatePermission(row, field, value) {
    row[field] = !!value;

    router.patch(
        route('permissions.update', row.id),
        {
            can_view: !!row.can_view,
            can_create: !!row.can_create,
            can_update: !!row.can_update,
            can_delete: !!row.can_delete,
        },
        {
            preserveScroll: true,
            onError: () => {
                // Bij fout terug naar server-state.
                localRows.value = (props.rows || []).map((r) => ({ ...r }));
            },
        },
    );
}

function roleLabelFor(role) {
    if (role === 'leiding' && ['wilde_vaart', 'loodsen'].includes(props.selectedSection)) {
        return 'Leidinglid';
    }
    return roleLabels[role] || role;
}
</script>

<template>
    <Head title="Rechtenbeheer" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Rechtenbeheer</h2>
        </template>

        <div class="space-y-4">
            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="me-1 text-sm font-semibold text-app-muted dark:text-app-muted-dark">Speltak</span>
                    <button
                        v-for="section in orderedManageableSections"
                        :key="`perm-s-btn-${section}`"
                        type="button"
                        class="rounded-md border px-3 py-1.5 text-sm font-medium transition"
                        :class="section === selectedSection
                            ? 'border-brand-blue bg-brand-blue/15 text-brand-blue-dark dark:border-brand-blue/60 dark:bg-brand-blue/25 dark:text-brand-blue-light'
                            : 'border-app-border bg-white text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15'"
                        :disabled="!isAdmin"
                        @click="setSection(section)"
                    >
                        {{ sectionLabels[section] || section }}
                    </button>
                </div>
            </div>

            <div class="space-y-4">
                <section
                    v-for="role in Object.keys(groupedRows)"
                    :key="`perm-role-${role}`"
                    class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
                >
                    <h3 class="mb-3 text-base font-semibold text-app-ink dark:text-app-ink-dark">{{ roleLabelFor(role) }}</h3>
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                            <tr>
                                <th class="py-2 text-left">Module</th>
                                <th class="py-2 text-center">Bekijken</th>
                                <th class="py-2 text-center">Toevoegen</th>
                                <th class="py-2 text-center">Bewerken</th>
                                <th class="py-2 text-center">Verwijderen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-blue/20">
                            <tr v-for="row in groupedRows[role]" :key="`perm-row-${row.id}`">
                                <td class="py-2 text-app-ink dark:text-app-ink-dark">{{ moduleLabels[row.module] || row.module }}</td>
                                <td class="py-2 text-center">
                                    <input type="checkbox" :checked="row.can_view" @change="updatePermission(row, 'can_view', $event.target.checked)" />
                                </td>
                                <td class="py-2 text-center">
                                    <input type="checkbox" :checked="row.can_create" @change="updatePermission(row, 'can_create', $event.target.checked)" />
                                </td>
                                <td class="py-2 text-center">
                                    <input type="checkbox" :checked="row.can_update" @change="updatePermission(row, 'can_update', $event.target.checked)" />
                                </td>
                                <td class="py-2 text-center">
                                    <input type="checkbox" :checked="row.can_delete" @change="updatePermission(row, 'can_delete', $event.target.checked)" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
