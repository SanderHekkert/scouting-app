<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { PlusIcon, PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    tasks: Array,
    users: {
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
const showEditForm = ref(false);
const showCategoryForm = ref(false);
const editingTaskId = ref(null);

const categoryForm = useForm({
    name: '',
});

const form = useForm({
    category: defaultCategory(),
    title: '',
    owner_user_id: '',
    description: '',
});
const editForm = useForm({
    category: defaultCategory(),
    title: '',
    owner_user_id: '',
    description: '',
});

function toggleAddForm() {
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        showEditForm.value = false;
        showCategoryForm.value = false;
        form.reset();
        form.category = defaultCategory();
    }
}

function toggleCategoryForm() {
    showCategoryForm.value = !showCategoryForm.value;
    if (showCategoryForm.value) {
        showAddForm.value = false;
        showEditForm.value = false;
        categoryForm.reset();
    }
}

function openEditForm(task) {
    if (!task) return;
    editingTaskId.value = task.id;
    editForm.category = task.category ?? defaultCategory();
    editForm.title = task.title;
    editForm.owner_user_id = task.owner_user_id ? String(task.owner_user_id) : '';
    editForm.description = task.description ?? '';
    editForm.clearErrors();
    showEditForm.value = true;
    showAddForm.value = false;
    showCategoryForm.value = false;
}

function closeEditForm() {
    showEditForm.value = false;
    editingTaskId.value = null;
    editForm.reset();
    editForm.category = defaultCategory();
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

function submitEdit() {
    if (!editingTaskId.value) return;
    editForm.put(route('task-items.update', editingTaskId.value), {
        preserveScroll: true,
        onSuccess: () => {
            closeEditForm();
        },
    });
}

function deleteTask(task) {
    if (!task?.id) return;
    if (!confirm('Deze taak verwijderen?')) return;
    if (editingTaskId.value === task.id) {
        closeEditForm();
    }
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
                <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">Taakverdeling</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-700"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Taak toevoegen
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-700"
                        @click="toggleCategoryForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Takenlijst toevoegen
                    </button>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-white">
            <form
                v-show="showCategoryForm"
                class="space-y-3 rounded-xl bg-gray-800 p-5 shadow-sm"
                @submit.prevent="submitCategory"
            >
                <h3 class="text-base font-semibold text-white">Nieuwe takenlijst</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <label for="category-name" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Naam
                    </label>
                    <input
                        id="category-name"
                        v-model="categoryForm.name"
                        type="text"
                        placeholder="Bijv. Materiaal"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />
                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button
                            type="submit"
                            class="rounded bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
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
                class="space-y-4 rounded-xl bg-gray-800 p-5 shadow-sm"
                @submit.prevent="submitAdd"
            >
                <h3 class="text-base font-semibold text-white">Nieuwe taak</h3>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <span class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-1">
                        Kopje
                    </span>
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Kies kopje voor deze taak">
                        <label
                            v-for="cat in taskCategories"
                            :key="`add-${cat}`"
                            class="cursor-pointer rounded-lg border px-3 py-2 text-sm transition"
                            :class="
                                form.category === cat
                                    ? 'border-indigo-400 bg-indigo-950/60 text-white ring-2 ring-indigo-400/80'
                                    : 'border-gray-600 bg-gray-900 text-gray-200 hover:border-gray-500'
                            "
                        >
                            <input v-model="form.category" type="radio" class="sr-only" :value="cat" />
                            {{ cat }}
                        </label>
                    </div>

                    <label for="add-title" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Taak
                    </label>
                    <input
                        id="add-title"
                        v-model="form.title"
                        type="text"
                        autocomplete="off"
                        placeholder="bv. Agenda bijhouden"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="add-owner" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Wie
                    </label>
                    <select
                        id="add-owner"
                        v-model="form.owner_user_id"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    >
                        <option value="">Geen toegewezen</option>
                        <option v-for="user in users" :key="`add-user-${user.id}`" :value="String(user.id)">
                            {{ user.name }}
                        </option>
                    </select>

                    <label for="add-description" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Uitleg
                    </label>
                    <textarea
                        id="add-description"
                        v-model="form.description"
                        rows="4"
                        placeholder="Wat houdt deze taak in?"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button
                            type="submit"
                            class="rounded bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
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

            <form
                v-show="showEditForm"
                class="space-y-4 rounded-xl border border-amber-900/40 bg-gray-800/90 p-5 shadow-sm"
                @submit.prevent="submitEdit"
            >
                <h3 class="text-base font-semibold text-amber-100">Taak bewerken</h3>
                <p class="text-xs text-amber-200/80">
                    Kies het kopje waar deze taak onder hoort, pas de teksten aan en klik op Bijwerken.
                </p>
                <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                    <span class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-1">
                        Kopje
                    </span>
                    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Verplaats taak naar ander kopje">
                        <label
                            v-for="cat in taskCategories"
                            :key="`edit-${cat}`"
                            class="cursor-pointer rounded-lg border px-3 py-2 text-sm transition"
                            :class="
                                editForm.category === cat
                                    ? 'border-amber-400 bg-amber-950/40 text-amber-50 ring-2 ring-amber-400/70'
                                    : 'border-gray-600 bg-gray-900 text-gray-200 hover:border-gray-500'
                            "
                        >
                            <input
                                v-model="editForm.category"
                                type="radio"
                                class="sr-only"
                                :value="cat"
                            />
                            {{ cat }}
                        </label>
                    </div>

                    <label for="edit-title" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Taak
                    </label>
                    <input
                        id="edit-title"
                        v-model="editForm.title"
                        type="text"
                        autocomplete="off"
                        placeholder="bv. Agenda bijhouden"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <label for="edit-owner" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Wie
                    </label>
                    <select
                        id="edit-owner"
                        v-model="editForm.owner_user_id"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white"
                    >
                        <option value="">Geen toegewezen</option>
                        <option v-for="user in users" :key="`edit-user-${user.id}`" :value="String(user.id)">
                            {{ user.name }}
                        </option>
                    </select>

                    <label for="edit-description" class="text-sm font-semibold tracking-wide text-gray-300 sm:pt-2.5">
                        Uitleg
                    </label>
                    <textarea
                        id="edit-description"
                        v-model="editForm.description"
                        rows="4"
                        placeholder="Wat houdt deze taak in?"
                        class="min-w-0 rounded border border-gray-600 bg-gray-900 px-3 py-2 text-white placeholder:text-gray-500"
                    />

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="submit"
                            class="rounded bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
                            :disabled="editForm.processing"
                        >
                            Bijwerken
                        </button>
                        <button
                            type="button"
                            class="rounded border border-gray-500 px-5 py-2 text-sm font-medium text-white hover:bg-gray-700"
                            @click="closeEditForm"
                        >
                            Annuleren
                        </button>
                    </div>
                </div>
                <p v-if="editForm.errors.category" class="text-sm text-red-400">{{ editForm.errors.category }}</p>
                <p v-if="editForm.errors.title" class="text-sm text-red-400">
                    {{ editForm.errors.title }}
                </p>
            </form>

            <div class="space-y-6">
                <div
                    v-for="section in groupedSections"
                    :key="section.category"
                    class="rounded-xl bg-gray-800 p-4 shadow-sm"
                >
                    <h3 class="mb-3 border-b border-gray-600 pb-2 text-lg font-semibold text-indigo-200">
                        {{ section.category }}
                    </h3>
                    <div v-if="section.tasks.length === 0" class="py-3 text-sm text-gray-500">
                        Geen taken in deze categorie.
                    </div>
                    <table v-else class="w-full table-fixed text-sm text-white">
                        <colgroup>
                            <col class="w-[28%]" />
                            <col class="w-[18%]" />
                            <col class="w-[39%]" />
                            <col class="w-[15%]" />
                        </colgroup>
                        <thead>
                            <tr class="text-left text-gray-300">
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
                                class="border-t border-gray-600"
                                :class="{ 'bg-gray-900/50': editingTaskId === task.id }"
                            >
                                <td class="py-2 pr-3 align-top">{{ task.title }}</td>
                                <td class="pr-3 align-top">{{ task.owner_user?.name || task.owner || '-' }}</td>
                                <td class="align-top whitespace-pre-wrap">{{ task.description }}</td>
                                <td class="py-2 align-top">
                                    <div class="flex flex-wrap items-center justify-end gap-2 sm:justify-start">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded border border-gray-500 bg-gray-900 px-2 py-1 text-xs font-medium text-white hover:bg-gray-700"
                                            @click="openEditForm(task)"
                                        >
                                            <PencilSquareIcon class="h-4 w-4" />
                                            Bewerken
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded border border-red-800/60 bg-red-950/35 px-2 py-1 text-xs font-medium text-red-300 hover:bg-red-950/55"
                                            @click="deleteTask(task)"
                                        >
                                            <TrashIcon class="h-4 w-4" />
                                            Verwijderen
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
