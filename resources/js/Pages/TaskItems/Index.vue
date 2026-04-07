<script setup>
import { computed, ref } from 'vue';
import EditableTextCell from '@/Components/EditableTextCell.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    tasks: Array,
    leaders: {
        type: Array,
        default: () => [],
    },
    taskCategories: {
        type: Array,
        default: () => [],
    },
});

function defaultCategory() {
    return props.taskCategories?.length ? props.taskCategories[0] : 'Algemeen';
}

const groupedSections = computed(() => {
    const byCat = Object.fromEntries(
        (props.taskCategories || []).map((c) => [c, []]),
    );
    for (const task of props.tasks || []) {
        const cat = task.category || 'Algemeen';
        if (!byCat[cat]) {
            byCat[cat] = [];
        }
        byCat[cat].push(task);
    }
    return (props.taskCategories || []).map((category) => ({
        category,
        tasks: byCat[category] || [],
    }));
});

const showAddForm = ref(false);
const showCategoryForm = ref(false);

const categoryForm = useForm({
    name: '',
});

const form = useForm({
    category: defaultCategory(),
    title: '',
    owner_user_id: '',
    description: '',
});

const taskFieldSaving = ref(null);

function toggleAddForm() {
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        showCategoryForm.value = false;
        form.reset();
        form.category = defaultCategory();
    }
}

function toggleCategoryForm() {
    showCategoryForm.value = !showCategoryForm.value;
    if (showCategoryForm.value) {
        showAddForm.value = false;
        categoryForm.reset();
    }
}

function submitAdd() {
    form.post(route('task-items.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.category = defaultCategory();
            showAddForm.value = false;
        },
    });
}

function submitCategory() {
    categoryForm.post(route('task-categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showCategoryForm.value = false;
            categoryForm.reset();
        },
    });
}

function isTaskFieldSaving(task, field) {
    return taskFieldSaving.value === `${task.id}:${field}`;
}

function isTaskRowSaving(task) {
    const key = taskFieldSaving.value;
    return key != null && String(key).startsWith(`${task.id}:`);
}

function patchTaskField(task, field, raw) {
    if (!task?.id) return;
    let payload = {};
    if (field === 'owner_user_id') {
        const s = raw === '' || raw == null ? null : Number(raw);
        payload = { owner_user_id: Number.isNaN(s) ? null : s };
    } else if (field === 'category') {
        payload = { category: raw };
    } else if (field === 'title') {
        payload = { title: raw ?? '' };
    } else if (field === 'description') {
        payload = { description: raw ?? '' };
    } else {
        return;
    }
    taskFieldSaving.value = `${task.id}:${field}`;
    router.patch(route('task-items.quick-update', task.id), payload, {
        preserveScroll: true,
        onFinish: () => {
            taskFieldSaving.value = null;
        },
    });
}

function deleteTask(task) {
    if (!task?.id) return;
    if (!confirm('Deze taak verwijderen?')) return;
    router.delete(route('task-items.destroy', task.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Taakverdeling" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex w-full flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Taakverdeling</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-app-panel px-3 py-2 text-sm font-medium text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Taak toevoegen
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-app-border bg-app-panel px-3 py-2 text-sm font-medium text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15"
                        @click="toggleCategoryForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Takenlijst toevoegen
                    </button>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-app-ink dark:text-app-ink-dark">
            <form
                v-show="showCategoryForm"
                class="surface-brand-top space-y-3 rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-5"
                @submit.prevent="submitCategory"
            >
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuwe takenlijst</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <label for="category-name" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Naam
                    </label>
                    <input
                        id="category-name"
                        v-model="categoryForm.name"
                        type="text"
                        placeholder="Bijv. Materiaal"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />
                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button
                            type="submit"
                            class="rounded bg-brand-red px-5 py-2 text-sm font-medium text-white hover:bg-brand-red-dark disabled:opacity-50"
                            :disabled="categoryForm.processing"
                        >
                            Aanmaken
                        </button>
                    </div>
                </div>
                <p v-if="categoryForm.errors.name" class="text-sm text-red-400">{{ categoryForm.errors.name }}</p>
            </form>

            <form
                v-show="showAddForm"
                class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-5"
                @submit.prevent="submitAdd"
            >
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuwe taak</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <span class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-1">
                        Kopje
                    </span>
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Kies kopje voor deze taak">
                        <label
                            v-for="cat in taskCategories"
                            :key="`add-${cat}`"
                            class="cursor-pointer rounded-lg border px-3 py-2 text-sm transition"
                            :class="
                                form.category === cat
                                    ? 'border-brand-yellow bg-brand-blue/45 text-white ring-2 ring-brand-yellow/70'
                                    : 'border-brand-blue/35 bg-white text-app-ink hover:border-brand-blue/55 dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:border-brand-blue/55'
                            "
                        >
                            <input v-model="form.category" type="radio" class="sr-only" :value="cat" />
                            {{ cat }}
                        </label>
                    </div>

                    <label for="add-title" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Taak
                    </label>
                    <input
                        id="add-title"
                        v-model="form.title"
                        type="text"
                        autocomplete="off"
                        placeholder="bv. Agenda bijhouden"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-owner" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Wie
                    </label>
                    <select
                        id="add-owner"
                        v-model="form.owner_user_id"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                    >
                        <option value="">Geen toegewezen</option>
                        <option v-for="leader in leaders" :key="`add-leader-${leader.id}`" :value="String(leader.id)">
                            {{ leader.name }}
                        </option>
                    </select>

                    <label for="add-description" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Uitleg
                    </label>
                    <textarea
                        id="add-description"
                        v-model="form.description"
                        rows="4"
                        placeholder="Wat houdt deze taak in?"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button
                            type="submit"
                            class="rounded bg-brand-red px-5 py-2 text-sm font-medium text-white hover:bg-brand-red-dark disabled:opacity-50"
                            :disabled="form.processing"
                        >
                            Opslaan
                        </button>
                    </div>
                </div>
                <p v-if="form.errors.category" class="text-sm text-red-400">{{ form.errors.category }}</p>
                <p v-if="form.errors.title" class="text-sm text-red-400">
                    {{ form.errors.title }}
                </p>
            </form>

            <div class="space-y-6">
                <div
                    v-for="section in groupedSections"
                    :key="section.category"
                    class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4"
                >
                    <h3 class="mb-3 border-b border-brand-blue/35 pb-2 text-lg font-semibold text-app-ink dark:text-app-ink-dark">
                        {{ section.category }}
                    </h3>
                    <div v-if="section.tasks.length === 0" class="py-3 text-sm text-app-muted dark:text-app-muted-dark">
                        Geen taken in deze categorie.
                    </div>
                    <table v-else class="w-full table-fixed text-sm text-app-ink dark:text-app-ink-dark">
                        <colgroup>
                            <col class="w-[14%]" />
                            <col class="w-[22%]" />
                            <col class="w-[18%]" />
                            <col class="w-[36%]" />
                            <col class="w-[10%]" />
                        </colgroup>
                        <thead>
                            <tr class="text-left text-app-muted dark:text-app-muted-dark">
                                <th class="pb-2">Kopje</th>
                                <th class="pb-2">Taak</th>
                                <th class="pb-2">Wie</th>
                                <th class="pb-2">Uitleg</th>
                                <th class="pb-2 text-right sm:text-left">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="task in section.tasks"
                                :key="task.id"
                                class="border-t border-brand-blue/35"
                            >
                                <td class="py-2 pr-2 align-top">
                                    <label class="sr-only" :for="`task-cat-${task.id}`">Kopje</label>
                                    <select
                                        :id="`task-cat-${task.id}`"
                                        class="w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :value="task.category"
                                        :disabled="isTaskRowSaving(task)"
                                        @change="patchTaskField(task, 'category', $event.target.value)"
                                    >
                                        <option v-for="c in taskCategories" :key="`${task.id}-${c}`" :value="c">
                                            {{ c }}
                                        </option>
                                    </select>
                                </td>
                                <td class="py-2 pr-3 align-top">
                                    <EditableTextCell
                                        :text="task.title || ''"
                                        :multiline="false"
                                        :saving="isTaskFieldSaving(task, 'title')"
                                        @save="(v) => patchTaskField(task, 'title', v)"
                                    />
                                </td>
                                <td class="pr-2 align-top">
                                    <label class="sr-only" :for="`task-owner-${task.id}`">Wie</label>
                                    <select
                                        :id="`task-owner-${task.id}`"
                                        class="w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :value="task.owner_user_id != null ? String(task.owner_user_id) : ''"
                                        :disabled="isTaskRowSaving(task)"
                                        @change="
                                            patchTaskField(task, 'owner_user_id', $event.target.value || null)
                                        "
                                    >
                                        <option value="">Geen toegewezen</option>
                                        <option
                                            v-for="leader in leaders"
                                            :key="`row-leader-${task.id}-${leader.id}`"
                                            :value="String(leader.id)"
                                        >
                                            {{ leader.name }}
                                        </option>
                                    </select>
                                </td>
                                <td class="align-top">
                                    <EditableTextCell
                                        :text="task.description || ''"
                                        multiline
                                        :saving="isTaskFieldSaving(task, 'description')"
                                        @save="(v) => patchTaskField(task, 'description', v)"
                                    />
                                </td>
                                <td class="py-2 align-top">
                                    <button type="button" class="btn-action-delete" @click="deleteTask(task)">
                                        <TrashIcon class="h-4 w-4" />
                                        Verwijderen
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
