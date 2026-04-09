<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, DocumentCheckIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    user: { type: Object, required: true },
    sections: { type: Array, default: () => [] },
    localRoles: { type: Array, default: () => [] },
    globalRoles: { type: Array, default: () => [] },
});

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
    teamleider: 'Teamleider',
    leiding: 'Leiding',
    ouder_contact: 'Oudercontact',
    lid: 'Lid',
};

const form = useForm({
    name: props.user.name || '',
    email: props.user.email || '',
    first_name: props.user.first_name || '',
    last_name: props.user.last_name || '',
    roles: Array.isArray(props.user.roles) ? props.user.roles.map((r) => ({ section: r.section, role: r.role })) : [],
    selectedSection: 'bevers',
    selectedRole: 'leiding',
});

function rolesForSection(section) {
    return section === '*' ? props.globalRoles : props.localRoles;
}

function submit() {
    router.patch(route('admin.users.update', props.user.id), {
        name: form.name,
        email: form.email,
        first_name: form.first_name || null,
        last_name: form.last_name || null,
        roles: form.roles.map((r) => ({ section: r.section, role: r.role })),
    }, {
        preserveScroll: true,
    });
}

function addRole() {
    const section = form.selectedSection;
    const role = form.selectedRole;
    if (!section || !role) return;
    const exists = form.roles.some((r) => r.section === section);
    if (exists) {
        form.roles = form.roles.map((r) => (r.section === section ? { section, role } : r));
    } else {
        form.roles.push({ section, role });
    }
}

function removeRole(index) {
    form.roles.splice(index, 1);
}

function deleteUser() {
    if (!confirm('Deze gebruiker verwijderen?')) return;
    router.delete(route('admin.users.destroy', props.user.id));
}
</script>

<template>
    <Head :title="`Gebruiker - ${form.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Gebruiker bewerken</h2>
                <Link :href="route('admin.users.index')" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-app-border text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/15" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark" @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-[11rem_1fr] sm:items-start">
                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Naam</label>
                <input v-model="form.name" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">E-mail</label>
                <input v-model="form.email" type="email" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Voornaam</label>
                <input v-model="form.first_name" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Achternaam</label>
                <input v-model="form.last_name" type="text" class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black" />

                <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">Rollen</label>
                <div>
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
                    <select v-model="form.selectedSection" class="rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black">
                        <option v-for="section in props.sections" :key="`add-s-${section}`" :value="section">
                            {{ sectionLabel[section] || section }}
                        </option>
                    </select>
                    <select v-model="form.selectedRole" class="rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black">
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
