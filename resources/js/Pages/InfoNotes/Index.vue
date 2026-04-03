<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ notes: Array });
const form = useForm({ category: '', content: '' });
const submit = () => form.post(route('info-notes.store'), { onSuccess: () => form.reset() });
</script>

<template>
    <Head title="Belangrijke info" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Belangrijke info</h2></template>
        <div class="space-y-4">
            <form @submit.prevent="submit" class="grid gap-2 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                <input v-model="form.category" placeholder="Categorie (bijv. Kamp, Ouder contact)" class="rounded border-gray-300 dark:bg-gray-900" />
                <textarea v-model="form.content" placeholder="Inhoud..." class="rounded border-gray-300 dark:bg-gray-900" />
                <button class="justify-self-start rounded bg-indigo-600 px-4 py-2 text-white">Toevoegen</button>
            </form>
            <div class="space-y-2">
                <div v-for="note in props.notes" :key="note.id" class="rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase text-gray-500">{{ note.category }}</p>
                            <p class="mt-1 text-sm text-gray-800 dark:text-gray-100">{{ note.content }}</p>
                        </div>
                        <button @click="$inertia.delete(route('info-notes.destroy', note.id))" class="text-red-600">Verwijder</button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
