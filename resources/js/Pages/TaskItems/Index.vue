<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ tasks: Array });
const form = useForm({ title: '', owner: '', description: '' });
const submit = () => form.post(route('task-items.store'), { onSuccess: () => form.reset() });
</script>

<template>
    <Head title="Taakverdeling" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-white">Taakverdeling</h2></template>
        <div class="space-y-4 text-white">
            <form @submit.prevent="submit" class="grid gap-2 rounded-xl bg-gray-800 p-4 shadow-sm md:grid-cols-3">
                <input v-model="form.title" placeholder="Taak" class="rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-400" />
                <input v-model="form.owner" placeholder="Wie" class="rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-400" />
                <button class="rounded bg-indigo-600 px-4 py-2 text-white">Toevoegen</button>
                <textarea v-model="form.description" placeholder="Uitleg" class="rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-400 md:col-span-3" />
            </form>
            <div class="rounded-xl bg-gray-800 p-4 shadow-sm">
                <table class="w-full text-sm text-white">
                    <thead><tr class="text-left text-gray-300"><th>Taak</th><th>Wie</th><th>Uitleg</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="task in props.tasks" :key="task.id" class="border-t border-gray-600">
                            <td class="py-2">{{ task.title }}</td><td>{{ task.owner }}</td><td>{{ task.description }}</td>
                            <td><button type="button" @click="$inertia.delete(route('task-items.destroy', task.id))" class="text-red-400 hover:text-red-300">Verwijder</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
