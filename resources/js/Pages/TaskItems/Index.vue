<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { Bars3Icon, DocumentCheckIcon, PencilSquareIcon, PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';

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
    events: {
        type: Array,
        default: () => [],
    },
});
const page = usePage();
const taskPerms = computed(() => page.props.auth?.permissions?.task_items ?? {});
const canCreateTasks = computed(() => !!taskPerms.value.create);
const canUpdateTasks = computed(() => !!taskPerms.value.update);
const canDeleteTasks = computed(() => !!taskPerms.value.delete);
const hideCategories = computed(() =>
    ['bevers', 'zeeverkenners', 'loodsen', 'wilde_vaart', 'bestuur'].includes(page.props.auth?.active_section),
);
const activeSection = computed(() => page.props.auth?.active_section ?? 'dolfijnen');
const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const allSections = ['bevers', 'dolfijnen', 'zeeverkenners', 'wilde_vaart', 'loodsen', 'bestuur'];
const shareableSections = computed(() => allSections.filter((s) => s !== activeSection.value));

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
    deadlines: [],
    shared_sections: [],
});
const addDeadlineInput = ref('');

const taskFieldSaving = ref(null);
const editingTaskId = ref(null);
const draggingTaskId = ref(null);
const dragOverCategory = ref('');

function toggleAddForm() {
    if (!canCreateTasks.value) return;
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        showCategoryForm.value = false;
        form.reset();
        form.category = defaultCategory();
        addDeadlineInput.value = '';
    }
}

function toggleCategoryForm() {
    if (!canCreateTasks.value) return;
    showCategoryForm.value = !showCategoryForm.value;
    if (showCategoryForm.value) {
        showAddForm.value = false;
        categoryForm.reset();
    }
}

function submitAdd() {
    if (!canCreateTasks.value) return;
    form.post(route('task-items.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.category = defaultCategory();
            addDeadlineInput.value = '';
            showAddForm.value = false;
        },
    });
}

function submitCategory() {
    if (!canCreateTasks.value) return;
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

function isTaskEditing(task) {
    return editingTaskId.value === task?.id;
}

function toggleTaskEdit(task) {
    if (!task?.id) return;
    editingTaskId.value = editingTaskId.value === task.id ? null : task.id;
}

function patchTaskField(task, field, raw) {
    if (!canUpdateTasks.value) return;
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
    } else if (field === 'deadlines') {
        payload = { deadlines: Array.isArray(raw) ? raw : [] };
    } else if (field === 'shared_sections') {
        payload = { shared_sections: Array.isArray(raw) ? raw : [] };
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

function normalizeDeadlines(values) {
    return [...new Set((Array.isArray(values) ? values : [])
        .map((v) => String(v || '').trim())
        .filter((v) => /^\d{4}-\d{2}-\d{2}$/.test(v)))].sort();
}

function deadlinesForTask(task) {
    return normalizeDeadlines(task?.deadlines);
}

function eventIdsForTask(task) {
    return [...new Set((Array.isArray(task?.event_ids) ? task.event_ids : [])
        .map((v) => Number(v))
        .filter((n) => Number.isFinite(n)))];
}

function sharedSectionsForTask(task) {
    return [...new Set((Array.isArray(task?.shared_sections) ? task.shared_sections : [])
        .map((v) => String(v || '').trim())
        .filter(Boolean))];
}

function addTaskSharedSection(task, section) {
    if (!section || section === activeSection.value) return;
    const next = [...new Set([...sharedSectionsForTask(task), section])];
    patchTaskField(task, 'shared_sections', next);
}

function removeTaskSharedSection(task, section) {
    const next = sharedSectionsForTask(task).filter((s) => s !== section);
    patchTaskField(task, 'shared_sections', next);
}

function eventLabelById(id) {
    const ev = (props.events || []).find((e) => Number(e.id) === Number(id));
    if (!ev) return `Opkomst #${id}`;
    const date = String(ev.event_date || '').slice(0, 10);
    const theme = String(ev.theme || '').trim();
    return theme ? `${date} - ${theme}` : date;
}

function availableEvents(task) {
    const selected = new Set(eventIdsForTask(task));
    return (props.events || []).filter((ev) => !selected.has(Number(ev.id)));
}

function patchTaskEvents(task, ids) {
    if (!canUpdateTasks.value) return;
    if (!task?.id) return;
    taskFieldSaving.value = `${task.id}:events`;
    router.patch(route('task-items.linked-events.update', task.id), { event_ids: ids }, {
        preserveScroll: true,
        onFinish: () => {
            taskFieldSaving.value = null;
        },
    });
}

function addTaskEvent(task, eventId) {
    const id = Number(eventId);
    if (!Number.isFinite(id)) return;
    const next = [...new Set([...eventIdsForTask(task), id])];
    patchTaskEvents(task, next);
}

function removeTaskEvent(task, eventId) {
    const next = eventIdsForTask(task).filter((id) => id !== Number(eventId));
    patchTaskEvents(task, next);
}

function onTaskEventSelectChange(task, domEvent) {
    const id = domEvent?.target?.value;
    addTaskEvent(task, id);
    if (domEvent?.target) domEvent.target.value = '';
}

function addFormDeadline() {
    const value = String(addDeadlineInput.value || '').trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return;
    form.deadlines = normalizeDeadlines([...(form.deadlines || []), value]);
    addDeadlineInput.value = '';
}

function removeFormDeadline(value) {
    form.deadlines = normalizeDeadlines((form.deadlines || []).filter((d) => d !== value));
}

function addTaskDeadline(task, value) {
    const next = normalizeDeadlines([...(deadlinesForTask(task) || []), value]);
    patchTaskField(task, 'deadlines', next);
}

function removeTaskDeadline(task, value) {
    const next = normalizeDeadlines(deadlinesForTask(task).filter((d) => d !== value));
    patchTaskField(task, 'deadlines', next);
}

function deleteTask(task) {
    if (!canDeleteTasks.value) return;
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
    if (!canUpdateTasks.value) return;
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
                        v-if="canCreateTasks"
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-blue text-white shadow-sm transition hover:bg-brand-blue-dark"
                        title="Taak toevoegen"
                        aria-label="Taak toevoegen"
                        @click="toggleAddForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-blue text-white shadow-sm transition hover:bg-brand-blue-dark"
                        v-if="!hideCategories && canCreateTasks"
                        title="Sectie toevoegen"
                        aria-label="Sectie toevoegen"
                        @click="toggleCategoryForm"
                    >
                        <PlusIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-app-ink dark:text-app-ink-dark">
            <form
                v-if="canCreateTasks"
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
                v-if="canCreateTasks"
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
                        Deadlines
                    </label>
                    <div>
                        <div class="flex flex-wrap gap-1.5">
                            <span
                                v-for="d in form.deadlines"
                                :key="`add-deadline-chip-${d}`"
                                class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                            >
                                {{ d }}
                                <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" @click="removeFormDeadline(d)">
                                    <XMarkIcon class="h-3.5 w-3.5" />
                                </button>
                            </span>
                        </div>
                        <div class="mt-2 flex gap-2">
                            <input
                                id="add-deadline"
                                v-model="addDeadlineInput"
                                type="date"
                                class="min-w-0 flex-1 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                            />
                            <button
                                type="button"
                                class="rounded border border-brand-blue-light/50 px-3 py-2 text-sm font-medium text-app-ink transition hover:bg-brand-blue/10 dark:text-app-ink-dark"
                                @click="addFormDeadline"
                            >
                                Toevoegen
                            </button>
                        </div>
                    </div>

                    <label class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                        Gezamenlijk met
                    </label>
                    <div class="flex flex-wrap gap-2">
                        <label
                            v-for="section in shareableSections"
                            :key="`add-task-share-${section}`"
                            class="inline-flex items-center gap-2 rounded border border-app-border bg-white px-2 py-1 text-xs dark:border-app-border-dark dark:bg-app-canvas-dark"
                        >
                            <input
                                v-model="form.shared_sections"
                                type="checkbox"
                                :value="section"
                                class="rounded border-app-border"
                            />
                            {{ sectionLabels[section] || section }}
                        </label>
                    </div>

                    <span class="hidden sm:block" aria-hidden="true" />
                    <div>
                        <button
                            type="submit"
                            class="btn-action-save"
                            :disabled="form.processing"
                            title="Opslaan"
                            aria-label="Opslaan"
                        >
                            <DocumentCheckIcon class="h-5 w-5" />
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
                                :draggable="canUpdateTasks"
                                @dragstart="onTaskDragStart(task)"
                                @dragend="onTaskDragEnd"
                            >
                                <div class="mb-1 inline-flex items-center gap-1 rounded bg-brand-blue/10 px-2 py-1 text-xs text-app-muted dark:text-app-muted-dark">
                                    <Bars3Icon class="h-4 w-4" />
                                    Sleep naar ander kopje
                                </div>

                                <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Taak</p>
                                <input
                                    v-if="isTaskEditing(task)"
                                    type="text"
                                    class="mt-1 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                    :value="task.title || ''"
                                    :disabled="isTaskRowSaving(task)"
                                    @change="patchTaskField(task, 'title', $event.target.value)"
                                />
                                <p v-else class="mt-1 text-sm text-app-ink dark:text-app-ink-dark">{{ task.title || '—' }}</p>

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
                                            :disabled="!isTaskEditing(task) || isTaskRowSaving(task)"
                                            @click="removeTaskOwner(task, id)"
                                        >
                                            <XMarkIcon class="h-3.5 w-3.5" />
                                        </button>
                                    </span>
                                </div>
                                <select
                                    v-if="isTaskEditing(task)"
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
                                <textarea
                                    v-if="isTaskEditing(task)"
                                    rows="3"
                                    class="mt-1 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                    :value="task.description || ''"
                                    :disabled="isTaskRowSaving(task)"
                                    @change="patchTaskField(task, 'description', $event.target.value)"
                                />
                                <p v-else class="mt-1 whitespace-pre-wrap text-sm text-app-ink dark:text-app-ink-dark">{{ task.description || '—' }}</p>

                                <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Deadlines</p>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="d in deadlinesForTask(task)"
                                        :key="`mob-deadline-chip-${task.id}-${d}`"
                                        class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                    >
                                        {{ d }}
                                        <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" :disabled="!isTaskEditing(task) || isTaskRowSaving(task)" @click="removeTaskDeadline(task, d)">
                                            <XMarkIcon class="h-3.5 w-3.5" />
                                        </button>
                                    </span>
                                </div>
                                <input
                                    v-if="isTaskEditing(task)"
                                    type="date"
                                    class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                    :disabled="isTaskRowSaving(task)"
                                    @change="addTaskDeadline(task, $event.target.value); $event.target.value = ''"
                                />

                                <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Opkomsten</p>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="eventId in eventIdsForTask(task)"
                                        :key="`mob-event-chip-${task.id}-${eventId}`"
                                        class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                    >
                                        {{ eventLabelById(eventId) }}
                                        <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" :disabled="!isTaskEditing(task) || isTaskRowSaving(task)" @click="removeTaskEvent(task, eventId)">
                                            <XMarkIcon class="h-3.5 w-3.5" />
                                        </button>
                                    </span>
                                </div>
                                <select
                                    v-if="isTaskEditing(task)"
                                    class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                    :disabled="isTaskRowSaving(task)"
                                    @change="onTaskEventSelectChange(task, $event)"
                                >
                                    <option value="">Opkomst toevoegen…</option>
                                    <option
                                        v-for="ev in availableEvents(task)"
                                        :key="`mob-event-${task.id}-${ev.id}`"
                                        :value="String(ev.id)"
                                    >
                                        {{ eventLabelById(ev.id) }}
                                    </option>
                                </select>

                                <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Gezamenlijk met</p>
                                <div class="mt-1 flex flex-wrap gap-1.5">
                                    <span
                                        v-for="section in sharedSectionsForTask(task)"
                                        :key="`mob-shared-chip-${task.id}-${section}`"
                                        class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                    >
                                        {{ sectionLabels[section] || section }}
                                        <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" :disabled="!isTaskEditing(task) || isTaskRowSaving(task)" @click="removeTaskSharedSection(task, section)">
                                            <XMarkIcon class="h-3.5 w-3.5" />
                                        </button>
                                    </span>
                                </div>
                                <select
                                    v-if="isTaskEditing(task)"
                                    class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                    :disabled="isTaskRowSaving(task)"
                                    @change="addTaskSharedSection(task, $event.target.value); $event.target.value = ''"
                                >
                                    <option value="">Speltak toevoegen…</option>
                                    <option
                                        v-for="section in shareableSections.filter((s) => !sharedSectionsForTask(task).includes(s))"
                                        :key="`mob-shared-${task.id}-${section}`"
                                        :value="section"
                                    >
                                        {{ sectionLabels[section] || section }}
                                    </option>
                                </select>

                                <div class="mt-3 border-t border-brand-blue/25 pt-3 dark:border-brand-blue/35">
                                    <button
                                        v-if="canUpdateTasks"
                                        type="button"
                                        class="btn-action-edit mr-2"
                                        @click="toggleTaskEdit(task)"
                                        title="Bewerken"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="canDeleteTasks"
                                        type="button"
                                        class="btn-action-delete"
                                        @click="deleteTask(task)"
                                        title="Verwijderen"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="hidden overflow-x-auto rounded-lg border border-brand-blue/25 lg:block">
                        <table class="w-full min-w-[64rem] border-collapse text-sm text-app-ink dark:text-app-ink-dark">
                        <colgroup>
                            <col class="w-[6%]" />
                            <col class="w-[24%]" />
                            <col class="w-[18%]" />
                            <col class="w-[32%]" />
                            <col class="w-[10%]" />
                            <col class="w-[14%]" />
                            <col class="w-[10%]" />
                        </colgroup>
                        <thead class="border-b border-brand-blue/35 bg-app-sidebar dark:bg-app-canvas-dark/80">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">
                                <th class="px-3 py-2.5"></th>
                                <th class="px-3 py-2.5">Taak</th>
                                <th class="px-3 py-2.5">Wie</th>
                                <th class="px-3 py-2.5">Uitleg</th>
                                <th class="px-3 py-2.5">Deadlines</th>
                                <th class="px-3 py-2.5">Opkomsten</th>
                                <th class="px-3 py-2.5 text-right sm:text-left">Acties</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-brand-blue/25">
                            <tr
                                v-for="task in section.tasks"
                                :key="task.id"
                                class="bg-brand-blue/5 transition-colors hover:bg-brand-blue/12 dark:bg-app-panel-dark/50 dark:hover:bg-brand-blue/15"
                                :draggable="canUpdateTasks"
                                @dragstart="onTaskDragStart(task)"
                                @dragend="onTaskDragEnd"
                            >
                                <td class="px-3 py-2.5 align-middle text-app-muted dark:text-app-muted-dark">
                                    <Bars3Icon v-if="canUpdateTasks" class="h-5 w-5 cursor-grab" />
                                </td>
                                <td class="px-3 py-2.5 align-middle">
                                    <input
                                        v-if="isTaskEditing(task)"
                                        type="text"
                                        class="w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :value="task.title || ''"
                                        :disabled="isTaskRowSaving(task)"
                                        @change="patchTaskField(task, 'title', $event.target.value)"
                                    />
                                    <span v-else>{{ task.title || '—' }}</span>
                                </td>
                                <td class="px-3 py-2.5 align-middle">
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
                                                :disabled="!isTaskEditing(task) || isTaskRowSaving(task)"
                                                @click="removeTaskOwner(task, id)"
                                            >
                                                <XMarkIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    </div>
                                    <select
                                        v-if="isTaskEditing(task)"
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
                                <td class="px-3 py-2.5 align-middle">
                                    <textarea
                                        v-if="isTaskEditing(task)"
                                        rows="3"
                                        class="w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :value="task.description || ''"
                                        :disabled="isTaskRowSaving(task)"
                                        @change="patchTaskField(task, 'description', $event.target.value)"
                                    />
                                    <span v-else class="whitespace-pre-wrap">{{ task.description || '—' }}</span>
                                </td>
                                <td class="px-3 py-2.5 align-middle">
                                    <label class="sr-only" :for="`task-deadline-${task.id}`">Deadlines</label>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="d in deadlinesForTask(task)"
                                            :key="`desk-deadline-chip-${task.id}-${d}`"
                                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                        >
                                            {{ d }}
                                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" :disabled="!isTaskEditing(task) || isTaskRowSaving(task)" @click="removeTaskDeadline(task, d)">
                                                <XMarkIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    </div>
                                    <input
                                        v-if="isTaskEditing(task)"
                                        :id="`task-deadline-${task.id}`"
                                        type="date"
                                        class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :disabled="isTaskRowSaving(task)"
                                        @change="addTaskDeadline(task, $event.target.value); $event.target.value = ''"
                                    />
                                </td>
                                <td class="px-3 py-2.5 align-middle">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="eventId in eventIdsForTask(task)"
                                            :key="`desk-event-chip-${task.id}-${eventId}`"
                                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                        >
                                            {{ eventLabelById(eventId) }}
                                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" :disabled="!isTaskEditing(task) || isTaskRowSaving(task)" @click="removeTaskEvent(task, eventId)">
                                                <XMarkIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    </div>
                                    <select
                                        v-if="isTaskEditing(task)"
                                        class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :disabled="isTaskRowSaving(task)"
                                        @change="onTaskEventSelectChange(task, $event)"
                                    >
                                        <option value="">Opkomst toevoegen…</option>
                                        <option
                                            v-for="ev in availableEvents(task)"
                                            :key="`desk-event-${task.id}-${ev.id}`"
                                            :value="String(ev.id)"
                                        >
                                            {{ eventLabelById(ev.id) }}
                                        </option>
                                    </select>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="section in sharedSectionsForTask(task)"
                                            :key="`desk-shared-chip-${task.id}-${section}`"
                                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                        >
                                            {{ sectionLabels[section] || section }}
                                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" :disabled="!isTaskEditing(task) || isTaskRowSaving(task)" @click="removeTaskSharedSection(task, section)">
                                                <XMarkIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    </div>
                                    <select
                                        v-if="isTaskEditing(task)"
                                        class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :disabled="isTaskRowSaving(task)"
                                        @change="addTaskSharedSection(task, $event.target.value); $event.target.value = ''"
                                    >
                                        <option value="">Speltak toevoegen…</option>
                                        <option
                                            v-for="section in shareableSections.filter((s) => !sharedSectionsForTask(task).includes(s))"
                                            :key="`desk-shared-${task.id}-${section}`"
                                            :value="section"
                                        >
                                            {{ sectionLabels[section] || section }}
                                        </option>
                                    </select>
                                </td>
                                <td class="px-3 py-2.5 align-middle">
                                    <button
                                        v-if="canUpdateTasks"
                                        type="button"
                                        class="btn-action-edit mr-2"
                                        @click="toggleTaskEdit(task)"
                                        title="Bewerken"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="canDeleteTasks"
                                        type="button"
                                        class="btn-action-delete"
                                        @click="deleteTask(task)"
                                        title="Verwijderen"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                        </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
