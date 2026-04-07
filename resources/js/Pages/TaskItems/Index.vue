<script setup>
import { computed, ref } from 'vue';
import EditableTextCell from '@/Components/EditableTextCell.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { Bars3Icon, PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';

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
const page = usePage();
const hideCategories = computed(() =>
    ['bevers', 'zeeverkenners', 'wilde_vaart'].includes(page.props.auth?.active_section),
);

function defaultCategory() {
    return props.taskCategories?.length ? props.taskCategories[0] : 'Algemeen';
}

const groupedSections = computed(() => {
    if (hideCategories.value) {
        return [
            {
                category: 'Taken',
                tasks: [...(props.tasks || [])],
            },
        ];
    }

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
    owner_user_ids: [],
    description: '',
    deadline: '',
});

const taskFieldSaving = ref(null);
const draggingTaskId = ref(null);
const dragOverCategory = ref('');

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
    if (field === 'owner_user_ids') {
        payload = { owner_user_ids: Array.isArray(raw) ? raw : [] };
    } else if (field === 'category') {
        payload = { category: raw };
    } else if (field === 'title') {
        payload = { title: raw ?? '' };
    } else if (field === 'description') {
        payload = { description: raw ?? '' };
    } else if (field === 'deadline') {
        payload = { deadline: raw || null };
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

function firstNameOnly(name) {
    const s = String(name ?? '').trim();
    if (!s) return '';
    return s.split(/\s+/)[0] || s;
}

function leaderNameById(id) {
    const match = (props.leaders || []).find((l) => Number(l.id) === Number(id));
    return match?.name || '';
}

function ownerIds(task) {
    if (Array.isArray(task?.owner_user_ids)) {
        return [...new Set(task.owner_user_ids.map((v) => Number(v)).filter((n) => Number.isFinite(n)))];
    }
    if (task?.owner_user_id != null && task.owner_user_id !== '') {
        return [Number(task.owner_user_id)];
    }
    return [];
}

function addTaskOwner(task, id) {
    const candidate = Number(id);
    if (!Number.isFinite(candidate)) return;
    const current = ownerIds(task);
    if (current.includes(candidate)) return;
    patchTaskField(task, 'owner_user_ids', [...current, candidate]);
}

function removeTaskOwner(task, id) {
    const current = ownerIds(task);
    patchTaskField(
        task,
        'owner_user_ids',
        current.filter((x) => x !== Number(id)),
    );
}

function onTaskOwnerSelectChange(task, domEvent) {
    const id = domEvent?.target?.value;
    addTaskOwner(task, id);
    if (domEvent?.target) {
        domEvent.target.value = '';
    }
}

function deleteTask(task) {
    if (!task?.id) return;
    if (!confirm('Deze taak verwijderen?')) return;
    router.delete(route('task-items.destroy', task.id), {
        preserveScroll: true,
    });
}

function onTaskDragStart(task) {
    draggingTaskId.value = task?.id ?? null;
}

function onTaskDragEnd() {
    draggingTaskId.value = null;
    dragOverCategory.value = '';
}

function onCategoryDragOver(category, event) {
    event.preventDefault();
    dragOverCategory.value = category;
}

function onCategoryDrop(category, event) {
    event.preventDefault();
    const id = draggingTaskId.value;
    if (!id) return;
    const task = (props.tasks || []).find((t) => t.id === id);
    if (!task || task.category === category) {
        onTaskDragEnd();
        return;
    }
    patchTaskField(task, 'category', category);
    onTaskDragEnd();
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
                        v-if="!hideCategories"
                        @click="toggleCategoryForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                        Sectie toevoegen
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
                <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuwe sectie</h3>
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
                            class="rounded bg-brand-blue px-5 py-2 text-sm font-medium text-white hover:bg-brand-blue-dark disabled:opacity-50"
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
                    <span v-if="!hideCategories" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-1">
                        Kopje
                    </span>
                    <div v-if="!hideCategories" class="flex flex-wrap gap-2" role="radiogroup" aria-label="Kies kopje voor deze taak">
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
                            <input
                                v-model="form.category"
                                type="radio"
                                class="sr-only"
                                :value="cat"
                                :required="taskCategories[0] === cat"
                            />
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
                        required
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-owners" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Wie
                    </label>
                    <div>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="id in form.owner_user_ids"
                                :key="`add-owner-chip-${id}`"
                                class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                            >
                                {{ firstNameOnly(leaderNameById(id)) }}
                                <button
                                    type="button"
                                    class="rounded p-0.5 hover:bg-brand-blue/25"
                                    @click="form.owner_user_ids = form.owner_user_ids.filter((x) => Number(x) !== Number(id))"
                                >
                                    <XMarkIcon class="h-3.5 w-3.5" />
                                </button>
                            </span>
                        </div>
                        <select
                            id="add-owners"
                            class="mt-2 min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                            @change="
                                (e) => {
                                    const v = Number(e.target.value);
                                    if (Number.isFinite(v) && !form.owner_user_ids.includes(v)) form.owner_user_ids.push(v);
                                    e.target.value = '';
                                }
                            "
                        >
                            <option value="">Naam toevoegen…</option>
                            <option v-for="leader in leaders" :key="`add-leader-${leader.id}`" :value="String(leader.id)">
                                {{ firstNameOnly(leader.name) }}
                            </option>
                        </select>
                    </div>

                    <label for="add-description" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Uitleg
                    </label>
                    <textarea
                        id="add-description"
                        v-model="form.description"
                        rows="4"
                        placeholder="Wat houdt deze taak in?"
                        required
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                    />

                    <label for="add-deadline" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Deadline
                    </label>
                    <input
                        id="add-deadline"
                        v-model="form.deadline"
                        type="date"
                        class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
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
                <p v-if="form.errors.description" class="text-sm text-red-400">
                    {{ form.errors.description }}
                </p>
            </form>

            <div class="space-y-6">
                <div
                    v-for="section in groupedSections"
                    :key="section.category"
                    class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4"
                    :class="{ 'ring-2 ring-brand-blue/50': dragOverCategory === section.category }"
                    @dragover="onCategoryDragOver(section.category, $event)"
                    @drop="onCategoryDrop(section.category, $event)"
                >
                    <h3
                        v-if="!hideCategories"
                        class="mb-3 border-b border-brand-blue/35 pb-2 text-lg font-semibold text-app-ink dark:text-app-ink-dark"
                    >
                        {{ section.category }}
                    </h3>
                    <div v-if="section.tasks.length === 0" class="py-3 text-sm text-app-muted dark:text-app-muted-dark">
                        Geen taken in deze categorie.
                    </div>
                    <div v-else class="space-y-2 md:space-y-0">
                        <div class="md:hidden space-y-2">
                            <div
                                v-for="task in section.tasks"
                                :key="`task-mob-${task.id}`"
                                class="surface-brand-top rounded-xl border border-brand-blue/30 bg-app-panel px-4 py-3 text-app-ink shadow-sm dark:bg-app-panel-dark/95 dark:text-app-ink-dark"
                                draggable="true"
                                @dragstart="onTaskDragStart(task)"
                                @dragend="onTaskDragEnd"
                            >
                                <div class="mb-1 inline-flex items-center gap-1 rounded bg-brand-blue/10 px-2 py-1 text-xs text-app-muted dark:text-app-muted-dark">
                                    <Bars3Icon class="h-4 w-4" />
                                    Sleep naar ander kopje
                                </div>

                                <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Taak</p>
                                <EditableTextCell
                                    :text="task.title || ''"
                                    :multiline="false"
                                    :saving="isTaskFieldSaving(task, 'title')"
                                    @save="(v) => patchTaskField(task, 'title', v)"
                                />

                                <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Wie</p>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="id in ownerIds(task)"
                                        :key="`mob-owner-chip-${task.id}-${id}`"
                                        class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                    >
                                        {{ firstNameOnly(leaderNameById(id)) }}
                                        <button
                                            type="button"
                                            class="rounded p-0.5 hover:bg-brand-blue/25"
                                            @click="removeTaskOwner(task, id)"
                                        >
                                            <XMarkIcon class="h-3.5 w-3.5" />
                                        </button>
                                    </span>
                                </div>
                                <select
                                    class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                    :disabled="isTaskRowSaving(task)"
                                    @change="onTaskOwnerSelectChange(task, $event)"
                                >
                                    <option value="">Naam toevoegen…</option>
                                    <option
                                        v-for="leader in leaders"
                                        :key="`mob-row-leader-${task.id}-${leader.id}`"
                                        :value="String(leader.id)"
                                    >
                                        {{ firstNameOnly(leader.name) }}
                                    </option>
                                </select>

                                <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Uitleg</p>
                                <EditableTextCell
                                    :text="task.description || ''"
                                    multiline
                                    :saving="isTaskFieldSaving(task, 'description')"
                                    @save="(v) => patchTaskField(task, 'description', v)"
                                />

                                <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Deadline</p>
                                <input
                                    type="date"
                                    class="mt-1 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                    :value="task.deadline || ''"
                                    :disabled="isTaskRowSaving(task)"
                                    @change="patchTaskField(task, 'deadline', $event.target.value)"
                                />

                                <div class="mt-3 border-t border-brand-blue/25 pt-3 dark:border-brand-blue/35">
                                    <button
                                        type="button"
                                        class="btn-action-delete h-[34px] px-2 py-1.5 text-sm"
                                        @click="deleteTask(task)"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                        Verwijderen
                                    </button>
                                </div>
                            </div>
                        </div>
                        <table class="hidden w-full table-fixed text-sm text-app-ink dark:text-app-ink-dark md:table">
                        <colgroup>
                            <col class="w-[6%]" />
                            <col class="w-[24%]" />
                            <col class="w-[18%]" />
                            <col class="w-[32%]" />
                            <col class="w-[10%]" />
                            <col class="w-[10%]" />
                        </colgroup>
                        <thead>
                            <tr class="text-left text-app-muted dark:text-app-muted-dark">
                                <th class="pb-2"></th>
                                <th class="pb-2">Taak</th>
                                <th class="pb-2">Wie</th>
                                <th class="pb-2">Uitleg</th>
                                <th class="pb-2">Deadline</th>
                                <th class="pb-2 text-right sm:text-left">Acties</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="task in section.tasks"
                                :key="task.id"
                                class="border-t border-brand-blue/35"
                                draggable="true"
                                @dragstart="onTaskDragStart(task)"
                                @dragend="onTaskDragEnd"
                            >
                                <td class="py-2 pr-2 align-middle text-app-muted dark:text-app-muted-dark">
                                    <Bars3Icon class="h-5 w-5 cursor-grab" />
                                </td>
                                <td class="py-2 pr-3 align-middle">
                                    <EditableTextCell
                                        :text="task.title || ''"
                                        :multiline="false"
                                        :saving="isTaskFieldSaving(task, 'title')"
                                        @save="(v) => patchTaskField(task, 'title', v)"
                                    />
                                </td>
                                <td class="py-2 pr-2 align-middle">
                                    <label class="sr-only" :for="`task-owner-${task.id}`">Wie</label>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="id in ownerIds(task)"
                                            :key="`desk-owner-chip-${task.id}-${id}`"
                                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                        >
                                            {{ firstNameOnly(leaderNameById(id)) }}
                                            <button
                                                type="button"
                                                class="rounded p-0.5 hover:bg-brand-blue/25"
                                                @click="removeTaskOwner(task, id)"
                                            >
                                                <XMarkIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    </div>
                                    <select
                                        :id="`task-owner-${task.id}`"
                                        class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :disabled="isTaskRowSaving(task)"
                                        @change="onTaskOwnerSelectChange(task, $event)"
                                    >
                                        <option value="">Naam toevoegen…</option>
                                        <option
                                            v-for="leader in leaders"
                                            :key="`row-leader-${task.id}-${leader.id}`"
                                            :value="String(leader.id)"
                                        >
                                            {{ firstNameOnly(leader.name) }}
                                        </option>
                                    </select>
                                </td>
                                <td class="align-middle">
                                    <EditableTextCell
                                        :text="task.description || ''"
                                        multiline
                                        :saving="isTaskFieldSaving(task, 'description')"
                                        @save="(v) => patchTaskField(task, 'description', v)"
                                    />
                                </td>
                                <td class="py-2 align-middle">
                                    <label class="sr-only" :for="`task-deadline-${task.id}`">Deadline</label>
                                    <input
                                        :id="`task-deadline-${task.id}`"
                                        type="date"
                                        class="w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :value="task.deadline || ''"
                                        :disabled="isTaskRowSaving(task)"
                                        @change="patchTaskField(task, 'deadline', $event.target.value)"
                                    />
                                </td>
                                <td class="py-2 align-middle">
                                    <button
                                        type="button"
                                        class="btn-action-delete h-[34px] px-2 py-1.5 text-sm"
                                        @click="deleteTask(task)"
                                    >
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
        </div>
    </AuthenticatedLayout>
</template>
