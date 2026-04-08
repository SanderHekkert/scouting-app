<script setup>
import AgendaSubnav from '@/Components/AgendaSubnav.vue';
import EditableTextCell from '@/Components/EditableTextCell.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    rows: {
        type: Array,
        required: true,
    },
});

const savingId = ref(null);

function saveRow(id, value) {
    savingId.value = id;
    router.patch(
        route('jaar-thema.entries.update', id),
        { value },
        {
            preserveScroll: true,
            onFinish: () => {
                savingId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Jaar Thema" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Agenda</h2>
        </template>

        <div class="space-y-4 text-app-ink dark:text-app-ink-dark">
            <div class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-4 text-app-ink shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark dark:text-app-ink-dark">
                <AgendaSubnav />

                <div class="mb-3 flex w-full flex-col gap-3 border-b border-brand-blue/35 pb-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-app-ink dark:text-app-ink-dark">Jaar thema</h3>
                        <p class="mt-0.5 text-xs text-app-muted dark:text-app-muted-dark">
                            Dubbelklik op een gekozen thema om te bewerken. Klik daarna buiten het veld om op te slaan. Esc
                            annuleert.
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-brand-blue/25">
                    <table class="w-full min-w-[22rem] border-collapse text-left text-sm sm:min-w-[26rem]">
                        <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                            <tr class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                                <th scope="col" class="min-w-[8rem] px-3 py-2.5">Thema's</th>
                                <th scope="col" class="min-w-[10rem] px-3 py-2.5">Gekozen Thema's</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-blue/25">
                            <tr
                                v-for="row in rows"
                                :key="row.id"
                                class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                            >
                                <td class="px-3 py-2.5 align-top font-medium">{{ row.label }}</td>
                                <td class="px-3 py-2.5 align-top">
                                    <EditableTextCell
                                        :text="row.value"
                                        :saving="savingId === row.id"
                                        multiline
                                        @save="(v) => saveRow(row.id, v)"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
