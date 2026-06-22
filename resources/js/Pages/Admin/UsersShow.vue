<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, DocumentCheckIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { computed, nextTick, watch } from 'vue';
import { useSaveRedirect } from '@/utils/saveForm';

const props = defineProps({
    user: { type: Object, required: true },
    sections: { type: Array, default: () => [] },
    localRolesBySection: { type: Object, default: () => ({}) },
    globalRoles: { type: Array, default: () => [] },
});
const { applySaveRedirect, saveFormOptions } = useSaveRedirect();

const sectionLabel = {
    '*': 'Globaal',
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const roleLabel = {
    admin: 'Admin',
    bestuurslid: 'Bestuurslid',
    penningmeester: 'Penningmeester',
    secretaresse: 'Secretaresse',
    voorzitter: 'Voorzitter',
    teamleider: 'Teamleider',
    leiding: 'Leiding',
    ouder_contact: 'Oudercontact',
    lid: 'Lid',
};

function mapUserRoles(user) {
    return Array.isArray(user?.roles) ? user.roles.map((r) => ({ section: r.section, role: r.role })) : [];
}

const page = usePage();

const form = useForm({
    name: props.user.name || '',
    email: props.user.email || '',
    first_name: props.user.first_name || '',
    last_name: props.user.last_name || '',
    roles: mapUserRoles(props.user),
    selectedSection: 'bevers',
    selectedRole: 'leiding',
});

const roleError = computed(() => {
    if (form.errors.roles) {
        return form.errors.roles;
    }

    const key = Object.keys(form.errors).find((field) => field === 'roles' || field.startsWith('roles.'));
    return key ? form.errors[key] : null;
});

watch(
    () => props.user.id,
    () => {
        form.name = props.user.name || '';
        form.email = props.user.email || '';
        form.first_name = props.user.first_name || '';
        form.last_name = props.user.last_name || '';
        form.roles = mapUserRoles(props.user);
        form.clearErrors();
    },
);

watch(
    () => props.user.roles,
    () => {
        form.roles = mapUserRoles(props.user);
    },
    { deep: true },
);

function rolesForSection(section) {
    return section === '*' ? props.globalRoles : (props.localRolesBySection?.[section] || []);
}

watch(
    () => form.selectedSection,
    (section) => {
        const allowed = rolesForSection(section);
        if (!allowed.includes(form.selectedRole)) {
            form.selectedRole = allowed[0] || '';
        }
    },
    { immediate: true },
);

function submit() {
    return form
        .transform((data) => applySaveRedirect({
            name: data.name,
            email: data.email,
            first_name: data.first_name,
            last_name: data.last_name,
            roles: data.roles,
        }))
        .patch(route('admin.users.update', props.user.id), saveFormOptions());
}

async function addRole() {
    const section = form.selectedSection;
    const role = form.selectedRole;
    if (!section || !role) {
        form.setError('roles', 'Kies een speltak en rol.');
        return;
    }

    form.clearErrors('roles');
    const exists = form.roles.some((r) => r.section === section);
    form.roles = exists
        ? form.roles.map((r) => (r.section === section ? { section, role } : r))
        : [...form.roles, { section, role }];

    await nextTick();
    submit();
}

async function removeRole(index) {
    form.roles = form.roles.filter((_, roleIndex) => roleIndex !== index);
    await nextTick();
    submit();
}

function deleteUser() {
    if (!confirm('Deze gebruiker verwijderen?')) return;
    router.delete(route('admin.users.destroy', props.user.id), {
        onError: (errors) => {
            const message = errors.user || 'Gebruiker kon niet worden verwijderd.';
            alert(message);
        },
    });
}
</script>

<template>
    <Head :title="`Gebruiker - ${form.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Gebruiker bewerken</h2>
                <Link :href="route('admin.users.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark" @submit.prevent="submit">
            <p v-if="page.props.flash?.status" class="text-sm text-emerald-700 dark:text-emerald-300">{{ page.props.flash.status }}</p>
            <div class="grid gap-4 sm:grid-cols-[11rem_1fr] sm:items-start">
                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Naam</label>
                <div>
                    <input v-model="form.name" type="text" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                </div>

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">E-mail</label>
                <div>
                    <input v-model="form.email" type="email" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
                </div>

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Voornaam</label>
                <div>
                    <input v-model="form.first_name" type="text" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.first_name }}</p>
                </div>

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Achternaam</label>
                <div>
                    <input v-model="form.last_name" type="text" class="min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" />
                    <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.last_name }}</p>
                </div>

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Rollen</label>
                <div>
                    <p v-if="roleError" class="mb-2 text-sm text-red-600 dark:text-red-400">{{ roleError }}</p>
                    <div class="flex flex-wrap gap-2">
                    <span
                        v-for="(entry, idx) in form.roles"
                        :key="`role-chip-${entry.section}-${idx}`"
                        class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs text-black"
                    >
                        {{ sectionLabel[entry.section] || entry.section }}: {{ roleLabel[entry.role] || entry.role }}
                        <button type="button" class="rounded p-0.5 text-black hover:bg-brand-blue/25" @click="removeRole(idx)">
                            <XMarkIcon class="h-3.5 w-3.5" />
                        </button>
                    </span>
                    <span v-if="!form.roles.length" class="text-sm text-app-muted dark:text-app-muted-dark">Geen rollen</span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <select v-model="form.selectedSection" class="rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:[&>option]:bg-app-canvas-dark dark:[&>option]:text-app-ink-dark">
                        <option v-for="section in props.sections" :key="`add-s-${section}`" :value="section">
                            {{ sectionLabel[section] || section }}
                        </option>
                    </select>
                    <select v-model="form.selectedRole" class="rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:[&>option]:bg-app-canvas-dark dark:[&>option]:text-app-ink-dark">
                        <option v-for="role in rolesForSection(form.selectedSection)" :key="`add-r-${role}`" :value="role">
                            {{ roleLabel[role] || role }}
                        </option>
                    </select>
                    <button type="button" class="rounded bg-brand-blue px-3 py-1.5 text-sm text-white" @click="addRole">
                        Rol toevoegen
                    </button>
                </div>
                </div>

                <span class="hidden sm:block" aria-hidden="true" />
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn-action-save" :disabled="form.processing" title="Opslaan" aria-label="Opslaan">
                        <DocumentCheckIcon class="h-5 w-5" />
                    </button>
                    <button type="button" class="btn-action-delete btn-action-delete--lg" title="Verwijderen" @click="deleteUser">
                        <TrashIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
