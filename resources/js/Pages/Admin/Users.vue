<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    users: { type: Array, default: () => [] },
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

function openUser(user) {
    if (!user?.id) return;
    router.get(route('admin.users.show', user.id));
}

function deleteUser(user) {
    if (!confirm(`Gebruiker "${user.name}" verwijderen?`)) return;
    router.delete(route('admin.users.destroy', user.id), {
        preserveScroll: true,
    });
}

function roleEntries(user) {
    const order = ['*', 'bevers', 'dolfijnen', 'zeeverkenners', 'wilde_vaart', 'loodsen', 'bestuur'];
    const roles = user.section_roles || {};
    return Object.entries(roles).sort((a, b) => order.indexOf(a[0]) - order.indexOf(b[0]));
}

</script>

<template>
    <Head title="Gebruikers" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Gebruikers</h2>
                <Link
                    :href="route('admin.users.invite.create')"
                    class="inline-flex items-center gap-2 rounded bg-brand-blue px-4 py-2 text-sm font-semibold text-white hover:bg-brand-blue-dark"
                >
                    <PlusIcon class="h-4 w-4" />
                    Gebruiker uitnodigen
                </Link>
            </div>
        </template>

        <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark">
            <div class="mb-3 border-b border-brand-blue/35 pb-2">
                <h3 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">Alle gebruikers in het systeem</h3>
            </div>

            <div class="space-y-3 md:hidden">
                <div
                    v-for="user in props.users"
                    :key="`mob-user-${user.id}`"
                    class="rounded-xl border border-brand-blue/25 bg-brand-blue/5 p-3 dark:bg-app-panel-dark/50 cursor-pointer"
                    @click="openUser(user)"
                >
                    <div class="text-sm font-semibold">{{ user.name }}</div>
                    <div class="text-xs text-app-muted dark:text-app-muted-dark">{{ user.email }}</div>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <span
                            v-for="[section, role] in roleEntries(user)"
                            :key="`mob-badge-${user.id}-${section}`"
                            class="rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                        >
                            {{ sectionLabel[section] || section }}: {{ roleLabel[role] || role }}
                        </span>
                    </div>
                    <div class="mt-3 flex items-center gap-2">
                        <button type="button" class="btn-action-edit" title="Bewerken" @click.stop="openUser(user)">
                            <PencilSquareIcon class="h-4 w-4 shrink-0" />
                        </button>
                        <button type="button" class="btn-action-delete" @click.stop="deleteUser(user)">
                            <TrashIcon class="h-4 w-4 shrink-0" />
                        </button>
                    </div>
                </div>
            </div>

            <div class="-mx-1 hidden overflow-x-auto rounded-lg border border-brand-blue/25 md:block sm:mx-0">
                <table class="w-full min-w-[52rem] border-collapse text-left text-sm text-app-ink lg:min-w-[64rem] dark:text-app-ink-dark">
                    <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                            <th class="px-3 py-2.5">Naam</th>
                            <th class="px-3 py-2.5">E-mail</th>
                            <th class="px-3 py-2.5">Huidige rollen</th>
                            <th class="px-3 py-2.5">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-blue/25">
                        <tr
                            v-for="user in props.users"
                            :key="`desk-user-${user.id}`"
                            class="bg-brand-blue/5 dark:bg-app-panel-dark/50 cursor-pointer hover:bg-brand-blue/10"
                            @click="openUser(user)"
                        >
                            <td class="px-3 py-2.5">
                                {{ user.name }}
                            </td>
                            <td class="px-3 py-2.5">
                                {{ user.email }}
                            </td>
                            <td class="px-3 py-2.5">
                                <div class="flex flex-wrap gap-1.5">
                                    <span
                                        v-for="[section, role] in roleEntries(user)"
                                        :key="`desk-badge-${user.id}-${section}`"
                                        class="rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                    >
                                        {{ sectionLabel[section] || section }}: {{ roleLabel[role] || role }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2.5">
                                <button type="button" class="btn-action-edit me-2" title="Bewerken" @click.stop="openUser(user)">
                                    <PencilSquareIcon class="h-4 w-4 shrink-0" />
                                </button>
                                <button type="button" class="btn-action-delete" @click.stop="deleteUser(user)">
                                    <TrashIcon class="h-4 w-4 shrink-0" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
