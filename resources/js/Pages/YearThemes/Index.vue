<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    rows: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <Head title="Jaar Thema" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Jaar Thema</h2>
        </template>

        <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4 text-app-ink dark:text-app-ink-dark">
            <div class="surface-brand-top-lg overflow-x-auto rounded-lg border border-brand-blue/25">
                <table class="w-full min-w-[28rem] border-collapse text-left text-sm">
                    <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                        <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                            <th scope="col" class="min-w-[12rem] px-3 py-2.5">Thema's</th>
                            <th scope="col" class="min-w-[14rem] px-3 py-2.5">Gekozen Thema's</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-blue/25">
                        <tr
                            v-for="(row, idx) in rows"
                            :key="idx"
                            class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                        >
                            <td class="px-3 py-2.5 align-top font-medium">{{ row.label }}</td>
                            <td class="px-3 py-2.5 align-top">{{ row.value }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
