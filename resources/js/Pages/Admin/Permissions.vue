<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

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
};

const roleLabels = {
    leiding: 'Leiding',
    ouder_contact: 'Oudercontact',
};

const moduleLabels = {
    dashboard: 'Dashboard',
    events: 'Agenda',
    members: 'Leden',
    leaders: 'Leiding',
    pods: 'Vin/Bakindeling',
    info_notes: 'Belangrijke info',
    task_items: 'Taakverdeling',
    year_theme: 'Jaar thema',
    tipper_topper: 'Tipper/Topper opkomst',
    profile: 'Profiel',
};

const groupedRows = computed(() => {
    const groups = {};
    for (const row of props.rows || []) {
        if (!groups[row.role]) groups[row.role] = [];
        groups[row.role].push(row);
    }
    return groups;
});

function onSectionChange(event) {
    const section = event?.target?.value;
    if (!section) return;
    router.get(route('permissions.index'), { section }, { preserveState: true, preserveScroll: true });
}

function updatePermission(row, field, value) {
    router.patch(
        route('permissions.update', row.id),
        { [field]: !!value, can_view: row.can_view, can_create: row.can_create, can_update: row.can_update, can_delete: row.can_delete },
        { preserveScroll: true },
    );
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
                <div class="flex flex-wrap items-center gap-3">
                    <label class="text-sm font-semibold text-app-muted dark:text-app-muted-dark">Speltak</label>
                    <select
                        class="rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                        :value="selectedSection"
                        :disabled="!isAdmin"
                        @change="onSectionChange"
                    >
                        <option v-for="section in manageableSections" :key="`perm-s-${section}`" :value="section">
                            {{ sectionLabels[section] || section }}
                        </option>
                    </select>
                </div>
            </div>

            <div class="space-y-4">
                <section
                    v-for="role in Object.keys(groupedRows)"
                    :key="`perm-role-${role}`"
                    class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
                >
                    <h3 class="mb-3 text-base font-semibold text-app-ink dark:text-app-ink-dark">{{ roleLabels[role] || role }}</h3>
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
