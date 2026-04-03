<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ events: Array });

const form = useForm({
    theme: '',
    event_date: '',
    event_type: '',
    activity: '',
    program_by: '',
    absent: '',
    notes: '',
});

const submit = () => form.post(route('events.store'), { onSuccess: () => form.reset() });
</script>

<template>
    <Head title="Agenda" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-white">Agenda</h2></template>
        <div class="space-y-4 text-white">
            <form @submit.prevent="submit" class="grid gap-2 rounded-xl bg-gray-800 p-4 shadow-sm md:grid-cols-3">
                <input v-model="form.theme" placeholder="Thema" class="rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-400" />
                <input v-model="form.event_date" type="date" class="rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white" />
                <input v-model="form.event_type" placeholder="Type opkomst" class="rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-400" />
                <input v-model="form.activity" placeholder="Wat ga je doen?" class="rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-400" />
                <input v-model="form.program_by" placeholder="Programma door" class="rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-400" />
                <input v-model="form.absent" placeholder="Afwezig" class="rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-400" />
                <textarea v-model="form.notes" placeholder="Bijzonderheden" class="rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-400 md:col-span-2" />
                <button class="rounded bg-indigo-600 px-4 py-2 text-white">Toevoegen</button>
            </form>
            <div class="rounded-xl bg-gray-800 p-4 shadow-sm">
                <table class="w-full text-sm text-white">
                    <thead><tr class="text-left text-gray-300"><th>Thema</th><th>Datum</th><th>Type</th><th>Programma door</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="event in props.events" :key="event.id" class="border-t border-gray-600">
                            <td class="py-2">{{ event.theme }}</td><td>{{ event.event_date }}</td><td>{{ event.event_type }}</td><td>{{ event.program_by }}</td>
                            <td><button type="button" @click="$inertia.delete(route('events.destroy', event.id))" class="text-red-400 hover:text-red-300">Verwijder</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
