<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { CheckBadgeIcon } from '@heroicons/vue/24/outline';
import { computed, reactive } from 'vue';

const props = defineProps({
    users: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    rolesBySection: { type: Object, default: () => ({}) },
});

const labels = {
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    loodsen: 'Loodsen',
    bevers: 'Bevers',
    wilde_vaart: 'Wilde Vaart',
    bestuur: 'Bestuur',
    bestuurslid: 'Bestuurslid',
    penningmeester: 'Penningmeester',
    secretaresse: 'Secretaresse',
    voorzitter: 'Voorzitter',
    teamleider: 'Teamleider',
    leiding: 'Leiding',
    ouder_contact: 'Oudercontact',
    lid: 'Lid',
};
const sectionOrder = ['bevers', 'dolfijnen', 'zeeverkenners', 'wilde_vaart', 'loodsen', 'bestuur'];
const orderedSections = computed(() => {
    const input = Array.isArray(props.sections) ? props.sections : [];
    const allowed = new Set(input);
    const sortedKnown = sectionOrder.filter((section) => allowed.has(section));
    const rest = input.filter((section) => !sectionOrder.includes(section));
    return [...sortedKnown, ...rest];
});

const stateByUser = reactive(
    Object.fromEntries(
        (props.users || []).map((u) => [
            u.id,
            {
                section_roles: { ...(u.section_roles || {}) },
                selected_section: u.selected_section || 'dolfijnen',
                saving: false,
            },
        ]),
    ),
);
const autosaveTimers = new Map();

function availableRolesForSection(section) {
    return props.rolesBySection?.[section] || [];
}

function saveUser(userId) {
    const state = stateByUser[userId];
    if (!state) return;
    const availableRoles = availableRolesForSection(state.selected_section);
    const selectedRole = state.section_roles[state.selected_section];
    const roleToSave = availableRoles.includes(selectedRole) ? selectedRole : (availableRoles[0] || 'leiding');
    state.section_roles[state.selected_section] = roleToSave;
    state.saving = true;
    router.patch(
        route('admin.roles.update', userId),
        {
            selected_section: state.selected_section,
            selected_role: roleToSave,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                state.saving = false;
            },
        },
    );
}

function scheduleRoleSave(userId) {
    const key = `roles:${userId}`;
    clearTimeout(autosaveTimers.get(key));
    autosaveTimers.set(
        key,
        setTimeout(() => {
            saveUser(userId);
            autosaveTimers.delete(key);
        }, 250),
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

            <div class="space-y-2 md:space-y-0">
                <div class="md:hidden space-y-2">
                    <div
                        v-for="user in users"
                        :key="`mob-user-${user.id}`"
                        class="surface-brand-top rounded-xl border border-brand-blue/30 bg-app-panel px-4 py-3 text-app-ink shadow-sm dark:bg-app-panel-dark/95 dark:text-app-ink-dark"
                    >
                        <div class="flex items-center gap-1 font-medium">
                            <span>{{ user.name }}</span>
                            <CheckBadgeIcon v-if="user.email_verified" class="h-4 w-4 text-emerald-600" title="E-mail geverifieerd" />
                        </div>
                        <div class="text-xs text-app-muted dark:text-app-muted-dark">{{ user.email }}</div>
                        <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Speltak</p>
                        <select
                            v-model="stateByUser[user.id].selected_section"
                            class="mt-1 w-full rounded border border-app-border bg-white px-2 py-1.5 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black"
                            @change="scheduleRoleSave(user.id)"
                        >
                            <option v-for="section in orderedSections" :key="`mob-s-${user.id}-${section}`" :value="section">
                                {{ labels[section] || section }}
                            </option>
                        </select>

                        <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Rol</p>
                        <select
                            v-model="stateByUser[user.id].section_roles[stateByUser[user.id].selected_section]"
                            class="mt-1 w-full rounded border border-app-border bg-white px-2 py-1.5 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black"
                            @change="scheduleRoleSave(user.id)"
                        >
                            <option v-for="role in availableRolesForSection(stateByUser[user.id].selected_section)" :key="`mob-r-${user.id}-${role}`" :value="role">
                                {{ labels[role] || role }}
                            </option>
                        </select>

                        <div class="mt-3 border-t border-brand-blue/25 pt-3 dark:border-brand-blue/35">
                            <span v-if="stateByUser[user.id].saving" class="text-xs text-app-muted dark:text-app-muted-dark">Opslaan...</span>
                        </div>
                    </div>
                </div>
                <div class="-mx-1 hidden overflow-x-auto sm:mx-0 md:block">
                <table class="w-full min-w-[44rem] border-collapse text-left text-sm lg:min-w-[52rem]">
                    <thead class="border-b border-brand-blue/35 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                        <tr>
                            <th class="px-3 py-2">Gebruiker</th>
                            <th class="px-3 py-2">Speltak</th>
                            <th class="px-3 py-2">Rol</th>
                            <th class="px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-blue/25">
                        <tr v-for="user in users" :key="user.id" class="bg-brand-blue/5">
                            <td class="px-3 py-2 align-top">
                                <div class="flex items-center gap-1 font-medium text-app-ink dark:text-app-ink-dark">
                                    <span>{{ user.name }}</span>
                                    <CheckBadgeIcon v-if="user.email_verified" class="h-4 w-4 text-emerald-600" title="E-mail geverifieerd" />
                                </div>
                                <div class="text-xs text-app-muted dark:text-app-muted-dark">{{ user.email }}</div>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <select
                                    v-model="stateByUser[user.id].selected_section"
                                    class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black"
                                    @change="scheduleRoleSave(user.id)"
                                >
                                    <option v-for="section in orderedSections" :key="`s-${user.id}-${section}`" :value="section">
                                        {{ labels[section] || section }}
                                    </option>
                                </select>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <select
                                    v-model="stateByUser[user.id].section_roles[stateByUser[user.id].selected_section]"
                                    class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black"
                                    @change="scheduleRoleSave(user.id)"
                                >
                                    <option v-for="role in availableRolesForSection(stateByUser[user.id].selected_section)" :key="`r-${user.id}-${role}`" :value="role">
                                        {{ labels[role] || role }}
                                    </option>
                                </select>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <span v-if="stateByUser[user.id].saving" class="text-xs text-app-muted dark:text-app-muted-dark">Opslaan...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
