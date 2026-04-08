<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { PencilSquareIcon, XMarkIcon } from '@heroicons/vue/24/outline';

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

const state = reactive({
    name: props.user.name || '',
    email: props.user.email || '',
    first_name: props.user.first_name || '',
    last_name: props.user.last_name || '',
    roles: Array.isArray(props.user.roles) ? props.user.roles.map((r) => ({ section: r.section, role: r.role })) : [],
    editing: false,
    selectedSection: 'bevers',
    selectedRole: 'leiding',
    saving: false,
});

const autosaveTimers = new Map();

function rolesForSection(section) {
    return section === '*' ? props.globalRoles : props.localRoles;
}

function scheduleSave() {
    const key = `user:${props.user.id}`;
    clearTimeout(autosaveTimers.get(key));
    autosaveTimers.set(
        key,
        setTimeout(() => {
            saveNow();
            autosaveTimers.delete(key);
        }, 350),
    );
}

function saveNow() {
    state.saving = true;
    router.patch(route('admin.users.update', props.user.id), {
        name: state.name,
        email: state.email,
        first_name: state.first_name || null,
        last_name: state.last_name || null,
        roles: state.roles.map((r) => ({ section: r.section, role: r.role })),
    }, {
        preserveScroll: true,
        onFinish: () => {
            state.saving = false;
        },
    });
}

function toggleEdit() {
    state.editing = !state.editing;
}

function addRole() {
    const section = state.selectedSection;
    const role = state.selectedRole;
    if (!section || !role) return;
    const exists = state.roles.some((r) => r.section === section);
    if (exists) {
        state.roles = state.roles.map((r) => (r.section === section ? { section, role } : r));
    } else {
        state.roles.push({ section, role });
    }
    scheduleSave();
}

function removeRole(index) {
    state.roles.splice(index, 1);
    scheduleSave();
}

function onEditFocusOut(domEvent) {
    if (!state.editing) return;
    const next = domEvent?.relatedTarget;
    const container = domEvent?.currentTarget;
    if (next && container && typeof container.contains === 'function' && container.contains(next)) {
        return;
    }
    saveNow();
    state.editing = false;
}
</script>

<template>
    <Head :title="`Gebruiker - ${state.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-black">Gebruiker</h2>
                <div class="flex items-center gap-2">
                    <Link :href="route('admin.users.index')" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-sm text-black">
                        Terug
                    </Link>
                    <button type="button" class="rounded border border-slate-300 bg-white px-2 py-1.5 text-sm text-black" @click="toggleEdit" title="Aanpassen">
                        <PencilSquareIcon class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </template>

        <div class="rounded-xl border border-slate-300 bg-white p-4 text-black shadow-sm">
            <div class="grid gap-4 md:grid-cols-2" @focusout.capture="onEditFocusOut($event)">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-600">Naam</p>
                    <template v-if="state.editing">
                        <input v-model="state.name" type="text" class="mt-1 w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-black" @input="scheduleSave" />
                    </template>
                    <template v-else>
                        <p class="mt-1 text-black">{{ state.name }}</p>
                    </template>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-600">E-mail</p>
                    <template v-if="state.editing">
                        <input v-model="state.email" type="email" class="mt-1 w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-black" @input="scheduleSave" />
                    </template>
                    <template v-else>
                        <p class="mt-1 text-black">{{ state.email }}</p>
                    </template>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-600">Voornaam</p>
                    <template v-if="state.editing">
                        <input v-model="state.first_name" type="text" class="mt-1 w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-black" @input="scheduleSave" />
                    </template>
                    <template v-else>
                        <p class="mt-1 text-black">{{ state.first_name || '-' }}</p>
                    </template>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-600">Achternaam</p>
                    <template v-if="state.editing">
                        <input v-model="state.last_name" type="text" class="mt-1 w-full rounded border border-slate-300 bg-white px-2 py-1.5 text-black" @input="scheduleSave" />
                    </template>
                    <template v-else>
                        <p class="mt-1 text-black">{{ state.last_name || '-' }}</p>
                    </template>
                </div>
            </div>

            <div class="mt-5 border-t border-slate-300 pt-4">
                <p class="text-sm font-semibold text-black">Rollen</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span
                        v-for="(entry, idx) in state.roles"
                        :key="`role-chip-${entry.section}-${idx}`"
                        class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs text-black"
                    >
                        {{ sectionLabel[entry.section] || entry.section }}: {{ roleLabel[entry.role] || entry.role }}
                        <button v-if="state.editing" type="button" class="rounded p-0.5 text-black hover:bg-blue-200" @click="removeRole(idx)">
                            <XMarkIcon class="h-3.5 w-3.5" />
                        </button>
                    </span>
                    <span v-if="!state.roles.length" class="text-sm text-slate-600">Geen rollen</span>
                </div>

                <div v-if="state.editing" class="mt-3 flex flex-wrap items-center gap-2">
                    <select v-model="state.selectedSection" class="rounded border border-slate-300 bg-white px-2 py-1.5 text-sm text-black">
                        <option v-for="section in props.sections" :key="`add-s-${section}`" :value="section">
                            {{ sectionLabel[section] || section }}
                        </option>
                    </select>
                    <select v-model="state.selectedRole" class="rounded border border-slate-300 bg-white px-2 py-1.5 text-sm text-black">
                        <option v-for="role in rolesForSection(state.selectedSection)" :key="`add-r-${role}`" :value="role">
                            {{ roleLabel[role] || role }}
                        </option>
                    </select>
                    <button type="button" class="rounded bg-brand-blue px-3 py-1.5 text-sm text-white" @click="addRole">
                        Rol toevoegen
                    </button>
                </div>
            </div>

            <p v-if="state.saving" class="mt-3 text-xs text-slate-600">Opslaan...</p>
        </div>
    </AuthenticatedLayout>
</template>
