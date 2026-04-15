<script setup>
import { computed, ref, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TaskItemsCreateForms from '@/Pages/TaskItems/Partials/TaskItemsCreateForms.vue';
import TaskItemsSectionsBoard from '@/Pages/TaskItems/Partials/TaskItemsSectionsBoard.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';

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
    taskCategoriesBySection: {
        type: Object,
        default: () => ({}),
    },
    canCreateCrossSection: {
        type: Boolean,
        default: false,
    },
    targetSections: {
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

const categoryOrder = ref([...(props.taskCategories || [])]);
watch(
    () => props.taskCategories,
    (next) => {
        categoryOrder.value = [...(next || [])];
    },
    { immediate: true },
);

const groupedSections = computed(() => {
    if (hideCategories.value) {
        return [
            {
                category: 'Taken',
                tasks: [...(props.tasks || [])],
            },
        ];
    }

    const byCat = Object.fromEntries((categoryOrder.value || []).map((c) => [c, []]));
    for (const task of props.tasks || []) {
        const cat = task.category || 'Algemeen';
        if (!byCat[cat]) {
            byCat[cat] = [];
        }
        byCat[cat].push(task);
    }
    return (categoryOrder.value || []).map((category) => ({
        category,
        tasks: byCat[category] || [],
    }));
});

const initialCreateAction = (() => {
    const query = page.url.includes('?') ? page.url.split('?')[1] : '';
    return new URLSearchParams(query).get('create');
})();
const showAddForm = ref(initialCreateAction === 'task' || (initialCreateAction === 'category' && hideCategories.value));
const showCategoryForm = ref(initialCreateAction === 'category' && !hideCategories.value);

const categoryForm = useForm({
    name: '',
});

const form = useForm({
    category: defaultCategory(),
    target_section: '',
    title: '',
    owner_user_ids: [],
    description: '',
    deadlines: [],
    shared_sections: [],
});
const addDeadlineInput = ref('');
watch(
    () => form.target_section,
    (section) => {
        if (!props.canCreateCrossSection) return;
        const list = section ? (props.taskCategoriesBySection?.[section] || []) : (props.taskCategories || []);
        form.category = list[0] || '';
    },
);

const createTaskCategories = computed(() => {
    if (props.canCreateCrossSection && form.target_section) {
        return props.taskCategoriesBySection?.[form.target_section] || [];
    }
    return props.taskCategories || [];
});

const taskFieldSaving = ref(null);
const editingTaskId = ref(null);
const draggingTaskId = ref(null);
const draggingSectionName = ref('');
const dragOverCategory = ref('');

function toggleAddForm() {
    if (!canCreateTasks.value) return;
    showAddForm.value = !showAddForm.value;
    if (showAddForm.value) {
        showCategoryForm.value = false;
        form.reset();
        form.target_section = '';
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

function toggleCreateShortcut() {
    if (!canCreateTasks.value) return;
    router.get(route('task-items.create'));
}

const createButtonLabel = computed(() => {
    return 'Toevoegen';
});

function submitAdd() {
    if (!canCreateTasks.value) return;
    form.post(route('task-items.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.target_section = '';
            form.category = defaultCategory();
            addDeadlineInput.value = '';
            showAddForm.value = false;
        },
    });
}

function canEditTask(task) {
    return !!canUpdateTasks.value && !!task?.can_update;
}

function canDeleteTask(task) {
    return !!canDeleteTasks.value && !!task?.can_delete;
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
    if (!canEditTask(task)) return;
    editingTaskId.value = editingTaskId.value === task.id ? null : task.id;
}

function patchTaskField(task, field, raw) {
    if (!canEditTask(task)) return;
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
    if (!canEditTask(task)) return;
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
    if (!canDeleteTask(task)) return;
    if (!task?.id) return;
    if (!confirm('Deze taak verwijderen?')) return;
    router.delete(route('task-items.destroy', task.id), {
        preserveScroll: true,
    });
}

function onTaskDragStart(task) {
    if (!canEditTask(task)) return;
    draggingTaskId.value = task?.id ?? null;
}

function onTaskDragEnd() {
    draggingTaskId.value = null;
    draggingSectionName.value = '';
    dragOverCategory.value = '';
}

function onSectionDragStart(category) {
    if (!canUpdateTasks.value || hideCategories.value) return;
    draggingSectionName.value = String(category || '');
}

function onSectionDragEnd() {
    draggingSectionName.value = '';
    dragOverCategory.value = '';
}

function persistCategoryOrder(nextOrder, previousOrder) {
    router.patch(route('task-categories.reorder'), {
        ordered_categories: nextOrder,
    }, {
        preserveScroll: true,
        onError: () => {
            categoryOrder.value = [...previousOrder];
        },
    });
}

function moveSectionTo(targetCategory) {
    if (hideCategories.value) return;
    const source = String(draggingSectionName.value || '');
    const target = String(targetCategory || '');
    if (!source || !target || source === target) return;
    const previous = [...categoryOrder.value];
    const sourceIndex = previous.indexOf(source);
    const targetIndex = previous.indexOf(target);
    if (sourceIndex === -1 || targetIndex === -1) return;
    const next = [...previous];
    const [moved] = next.splice(sourceIndex, 1);
    next.splice(targetIndex, 0, moved);
    categoryOrder.value = next;
    persistCategoryOrder(next, previous);
}

function onCategoryDragOver(category, event) {
    event.preventDefault();
    dragOverCategory.value = category;
}

function onCategoryDrop(category, event) {
    if (!canUpdateTasks.value) return;
    event.preventDefault();
    if (draggingSectionName.value) {
        moveSectionTo(category);
        onSectionDragEnd();
        return;
    }
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
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ sectionLabels[activeSection] || activeSection }} - Taakverdeling</h2>
                <div class="flex flex-wrap items-center justify-end gap-2 sm:ms-auto">
                    <div v-if="canCreateTasks" class="relative">
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-emerald-700 text-white shadow-sm transition hover:bg-emerald-800"
                            :title="createButtonLabel"
                            :aria-label="createButtonLabel"
                            @click="toggleCreateShortcut"
                        >
                            <PlusIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
        </template>
        <div class="space-y-4 text-app-ink dark:text-app-ink-dark">
            <TaskItemsCreateForms
                :can-create-tasks="canCreateTasks"
                :show-category-form="showCategoryForm"
                :show-add-form="showAddForm"
                :category-form="categoryForm"
                :form="form"
                :can-create-cross-section="props.canCreateCrossSection"
                :target-sections="props.targetSections"
                :section-labels="sectionLabels"
                :hide-categories="hideCategories"
                :create-task-categories="createTaskCategories"
                :leaders="leaders"
                :shareable-sections="shareableSections"
                :add-deadline-input="addDeadlineInput"
                :first-name-only="firstNameOnly"
                :leader-name-by-id="leaderNameById"
                @submit-category="submitCategory"
                @submit-add="submitAdd"
                @add-deadline="addFormDeadline"
                @remove-deadline="removeFormDeadline"
                @update:add-deadline-input="addDeadlineInput = $event"
            />

            <TaskItemsSectionsBoard
                :grouped-sections="groupedSections"
                :drag-over-category="dragOverCategory"
                :hide-categories="hideCategories"
                :can-update-tasks="canUpdateTasks"
                :leaders="leaders"
                :shareable-sections="shareableSections"
                :section-labels="sectionLabels"
                :on-category-drag-over="onCategoryDragOver"
                :on-category-drop="onCategoryDrop"
                :on-section-drag-start="onSectionDragStart"
                :on-section-drag-end="onSectionDragEnd"
                :can-edit-task="canEditTask"
                :can-delete-task="canDeleteTask"
                :on-task-drag-start="onTaskDragStart"
                :on-task-drag-end="onTaskDragEnd"
                :is-task-editing="isTaskEditing"
                :is-task-row-saving="isTaskRowSaving"
                :patch-task-field="patchTaskField"
                :owner-ids="ownerIds"
                :first-name-only="firstNameOnly"
                :leader-name-by-id="leaderNameById"
                :remove-task-owner="removeTaskOwner"
                :on-task-owner-select-change="onTaskOwnerSelectChange"
                :deadlines-for-task="deadlinesForTask"
                :remove-task-deadline="removeTaskDeadline"
                :add-task-deadline="addTaskDeadline"
                :event-ids-for-task="eventIdsForTask"
                :event-label-by-id="eventLabelById"
                :remove-task-event="removeTaskEvent"
                :on-task-event-select-change="onTaskEventSelectChange"
                :available-events="availableEvents"
                :shared-sections-for-task="sharedSectionsForTask"
                :remove-task-shared-section="removeTaskSharedSection"
                :add-task-shared-section="addTaskSharedSection"
                :toggle-task-edit="toggleTaskEdit"
                :delete-task="deleteTask"
            />
        </div>
    </AuthenticatedLayout>
</template>
