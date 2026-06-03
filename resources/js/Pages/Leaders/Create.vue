<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon } from '@heroicons/vue/24/outline';

const fieldClass =
    'rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark';

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
    address: '',
    postal_code: '',
    city: '',
    birthday: '',
    phone_number: '',
    emergency_contact: '',
    email: '',
    bijzonderheden: '',
});

function submit() {
    form.post(route('leaders.store'));
}
</script>

<template>
    <Head :title="`${speltakLabel} - Leiding toevoegen`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Leiding toevoegen</h2>
                <Link :href="route('leaders.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form
            class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark"
            @submit.prevent="submit"
        >
            <div class="grid gap-3 sm:grid-cols-2">
                <input v-model="form.first_name" type="text" placeholder="Voornaam" :class="fieldClass" required />
                <input v-model="form.last_name" type="text" placeholder="Achternaam" :class="fieldClass" />
                <input v-model="form.birthday" type="date" :class="fieldClass" />
                <input v-model="form.email" type="email" placeholder="E-mail" :class="fieldClass" />
                <input v-model="form.phone_number" type="text" placeholder="Telefoon" :class="fieldClass" />
                <input v-model="form.emergency_contact" type="text" placeholder="Noodcontact" :class="fieldClass" />
                <input v-model="form.address" type="text" placeholder="Adres" :class="[fieldClass, 'sm:col-span-2']" />
                <input v-model="form.postal_code" type="text" placeholder="Postcode" :class="fieldClass" />
                <input v-model="form.city" type="text" placeholder="Plaats" :class="fieldClass" />
                <textarea v-model="form.bijzonderheden" rows="4" placeholder="Bijzonderheden" :class="[fieldClass, 'sm:col-span-2']" />
            </div>
            <button type="submit" class="rounded bg-emerald-700 px-4 py-2 text-white">Opslaan</button>
        </form>
    </AuthenticatedLayout>
</template>

