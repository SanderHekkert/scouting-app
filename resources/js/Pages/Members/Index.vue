<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ members: Array });
const form = useForm({
    installed: true,
    first_name: '',
    last_name: '',
    birthday: '',
    age: '',
    address: '',
    phone_mother: '',
    phone_father: '',
    active: true,
});
const submit = () => form.post(route('members.store'), { onSuccess: () => form.reset('first_name', 'last_name', 'birthday', 'age', 'address', 'phone_mother', 'phone_father') });
</script>

<template>
    <Head title="Contacten" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Contacten</h2></template>
        <div class="space-y-4">
            <form @submit.prevent="submit" class="grid gap-2 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800 md:grid-cols-3">
                <input v-model="form.first_name" placeholder="Voornaam" class="rounded border-gray-300 dark:bg-gray-900" />
                <input v-model="form.last_name" placeholder="Achternaam" class="rounded border-gray-300 dark:bg-gray-900" />
                <input v-model="form.birthday" type="date" class="rounded border-gray-300 dark:bg-gray-900" />
                <input v-model="form.age" type="number" placeholder="Leeftijd" class="rounded border-gray-300 dark:bg-gray-900" />
                <input v-model="form.phone_mother" placeholder="Telefoon moeder" class="rounded border-gray-300 dark:bg-gray-900" />
                <input v-model="form.phone_father" placeholder="Telefoon vader" class="rounded border-gray-300 dark:bg-gray-900" />
                <input v-model="form.address" placeholder="Adres" class="rounded border-gray-300 dark:bg-gray-900 md:col-span-2" />
                <button class="rounded bg-indigo-600 px-4 py-2 text-white">Toevoegen</button>
            </form>
            <div class="rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                <table class="w-full text-sm">
                    <thead><tr class="text-left"><th>Naam</th><th>Leeftijd</th><th>Contact</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="member in props.members" :key="member.id" class="border-t border-gray-200 dark:border-gray-700">
                            <td>{{ member.first_name }} {{ member.last_name }}</td>
                            <td>{{ member.age }}</td>
                            <td>{{ member.phone_mother || member.phone_father }}</td>
                            <td><button @click="$inertia.delete(route('members.destroy', member.id))" class="text-red-600">Verwijder</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
