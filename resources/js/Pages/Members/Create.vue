<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const sectionLabelMap = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = sectionLabelMap[page.props.auth?.active_section] || 'Dolfijnen';

const form = useForm({
    installed: false,
    gedoopt: false,
    first_name: '',
    last_name: '',
    birthday: '',
    address: '',
    postal_code: '',
    city: '',
    email_parents: '',
    phone_mother: '',
    phone_father: '',
    bijzonderheden: '',
});

function submit() {
    form.post(route('members.store'));
}
</script>

<template>
    <Head :title="`${speltakLabel} - Lid toevoegen`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Lid toevoegen</h2>
                <Link :href="route('members.index')" class="btn-action-back">Terug</Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5" @submit.prevent="submit">
            <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="form.first_name" type="text" placeholder="Voornaam" class="rounded border border-app-border px-3 py-2" required />
                <input v-model="form.last_name" type="text" placeholder="Achternaam" class="rounded border border-app-border px-3 py-2" />
                <input v-model="form.birthday" type="date" class="rounded border border-app-border px-3 py-2" />
                <input v-model="form.email_parents" type="email" placeholder="E-mail ouders" class="rounded border border-app-border px-3 py-2" />
                <input v-model="form.phone_mother" type="text" placeholder="Telefoon moeder" class="rounded border border-app-border px-3 py-2" />
                <input v-model="form.phone_father" type="text" placeholder="Telefoon vader" class="rounded border border-app-border px-3 py-2" />
                <input v-model="form.address" type="text" placeholder="Adres" class="rounded border border-app-border px-3 py-2 sm:col-span-2" />
                <input v-model="form.postal_code" type="text" placeholder="Postcode" class="rounded border border-app-border px-3 py-2" />
                <input v-model="form.city" type="text" placeholder="Plaats" class="rounded border border-app-border px-3 py-2" />
                <textarea v-model="form.bijzonderheden" rows="4" placeholder="Bijzonderheden" class="rounded border border-app-border px-3 py-2 sm:col-span-2" />
            </div>
            <button type="submit" class="rounded bg-emerald-700 px-4 py-2 text-white">Opslaan</button>
        </form>
    </AuthenticatedLayout>
</template>

