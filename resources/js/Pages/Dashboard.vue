<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    upcomingEvents: Array,
    upcomingBirthdays: Array,
    taskCount: Number,
    memberCount: Number,
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header><h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200">Dashboard</h2></template>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Leden</h3>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ memberCount }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                <h3 class="text-sm font-semibold text-gray-600 dark:text-gray-300">Taken</h3>
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">{{ taskCount }}</p>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Aankomende opkomsten</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li v-for="event in upcomingEvents" :key="event.id" class="text-gray-700 dark:text-gray-200">
                        {{ event.event_date }} - {{ event.theme }} ({{ event.event_type || 'Opkomst' }})
                    </li>
                </ul>
            </div>
            <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">Komende verjaardagen</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    <li v-for="member in upcomingBirthdays" :key="member.id" class="text-gray-700 dark:text-gray-200">
                        {{ member.first_name }} {{ member.last_name }} - {{ member.birthday }}
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
