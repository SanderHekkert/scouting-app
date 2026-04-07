<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    roles: { type: Array, default: () => [] },
});

const labels = {
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    teamleider: 'Teamleider',
    leiding: 'Leiding',
    ouder_contact: 'Oudercontact',
};

const stateByUser = reactive(
    Object.fromEntries(
        (props.users || []).map((u) => [
            u.id,
            {
                is_admin: !!u.is_admin,
                section_roles: { ...(u.section_roles || {}) },
                selected_section: u.selected_section || 'dolfijnen',
                saving: false,
            },
        ]),
    ),
);

function saveUser(userId) {
    const state = stateByUser[userId];
    if (!state) return;
    state.saving = true;
    router.patch(
        route('admin.roles.update', userId),
        {
            is_admin: !!state.is_admin,
            selected_section: state.selected_section,
            selected_role: state.section_roles[state.selected_section] || 'leiding',
        },
        {
            preserveScroll: true,
            onFinish: () => {
                state.saving = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Rollenbeheer" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Admin · Rollenbeheer</h2>
        </template>

        <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
            <p class="mb-4 text-sm text-app-muted dark:text-app-muted-dark">
                Beheer per gebruiker de rol per speltak. `Admin` heeft overal toegang.
            </p>

            <div class="-mx-1 overflow-x-auto sm:mx-0">
                <table class="w-full min-w-[56rem] border-collapse text-left text-sm">
                    <thead class="border-b border-brand-blue/35 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                        <tr>
                            <th class="px-3 py-2">Gebruiker</th>
                            <th class="px-3 py-2">Speltak</th>
                            <th class="px-3 py-2">Rol</th>
                            <th class="px-3 py-2">Admin</th>
                            <th class="px-3 py-2">Actie</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-blue/25">
                        <tr v-for="user in users" :key="user.id" class="bg-brand-blue/5">
                            <td class="px-3 py-2 align-top">
                                <div class="font-medium text-app-ink dark:text-app-ink-dark">{{ user.name }}</div>
                                <div class="text-xs text-app-muted dark:text-app-muted-dark">{{ user.email }}</div>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <select
                                    v-model="stateByUser[user.id].selected_section"
                                    class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black"
                                >
                                    <option v-for="section in sections" :key="`s-${user.id}-${section}`" :value="section">
                                        {{ labels[section] || section }}
                                    </option>
                                </select>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <select
                                    v-model="stateByUser[user.id].section_roles[stateByUser[user.id].selected_section]"
                                    class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black"
                                >
                                    <option v-for="role in roles" :key="`r-${user.id}-${role}`" :value="role">
                                        {{ labels[role] || role }}
                                    </option>
                                </select>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <label class="inline-flex items-center gap-2">
                                    <input v-model="stateByUser[user.id].is_admin" type="checkbox" class="rounded border-app-border" />
                                    <span class="text-sm text-app-ink dark:text-app-ink-dark">Admin</span>
                                </label>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <button
                                    type="button"
                                    class="rounded bg-brand-blue px-3 py-1.5 text-xs font-semibold text-white hover:bg-brand-blue-dark disabled:opacity-50"
                                    :disabled="stateByUser[user.id].saving"
                                    @click="saveUser(user.id)"
                                >
                                    Opslaan
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
