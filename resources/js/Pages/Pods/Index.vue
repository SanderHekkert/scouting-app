<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ pods: Array, members: Array });
const podForm = useForm({ name: '' });
const memberForm = useForm({ pod_id: '', member_id: '', role: 'Vinlid' });

const addPod = () => podForm.post(route('pods.store'), { onSuccess: () => podForm.reset() });
const addMember = () => memberForm.post(route('pods.members.store', memberForm.pod_id), { onSuccess: () => memberForm.reset('member_id', 'role') });
</script>

<template>
    <Head title="Vinindeling" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Vinindeling</h2></template>
        <div class="space-y-4">
            <form @submit.prevent="addPod" class="flex gap-2 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                <input v-model="podForm.name" placeholder="Nieuwe vin/groep" class="flex-1 rounded border-gray-300 dark:bg-gray-900" />
                <button class="rounded bg-indigo-600 px-4 py-2 text-white">Toevoegen</button>
            </form>
            <form @submit.prevent="addMember" class="grid gap-2 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800 md:grid-cols-4">
                <select v-model="memberForm.pod_id" class="rounded border-gray-300 dark:bg-gray-900"><option value="">Kies vin</option><option v-for="pod in props.pods" :key="pod.id" :value="pod.id">{{ pod.name }}</option></select>
                <select v-model="memberForm.member_id" class="rounded border-gray-300 dark:bg-gray-900"><option value="">Kies lid</option><option v-for="member in props.members" :key="member.id" :value="member.id">{{ member.first_name }} {{ member.last_name }}</option></select>
                <input v-model="memberForm.role" class="rounded border-gray-300 dark:bg-gray-900" />
                <button class="rounded bg-indigo-600 px-4 py-2 text-white">Lid koppelen</button>
            </form>
            <div class="grid gap-3 md:grid-cols-2">
                <div v-for="pod in props.pods" :key="pod.id" class="rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">{{ pod.name }}</h3>
                    <ul class="mt-2 space-y-1 text-sm">
                        <li v-for="membership in pod.memberships" :key="membership.id" class="flex items-center justify-between text-gray-700 dark:text-gray-200">
                            <span>{{ membership.role }} - {{ membership.member?.first_name }} {{ membership.member?.last_name }}</span>
                            <button @click="$inertia.delete(route('pods.members.destroy', membership.id))" class="text-red-600">x</button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
