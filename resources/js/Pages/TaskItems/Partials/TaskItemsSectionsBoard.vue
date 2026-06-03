<script setup>
import { Bars3Icon, PencilSquareIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    groupedSections: { type: Array, required: true },
    dragOverCategory: { type: String, default: '' },
    hideCategories: { type: Boolean, required: true },
    canUpdateTasks: { type: Boolean, required: true },
    leaders: { type: Array, required: true },
    shareableSections: { type: Array, required: true },
    sectionLabels: { type: Object, required: true },
    onCategoryDragOver: { type: Function, required: true },
    onCategoryDrop: { type: Function, required: true },
    onSectionDragStart: { type: Function, required: true },
    onSectionDragEnd: { type: Function, required: true },
    canEditTask: { type: Function, required: true },
    canCompleteTask: { type: Function, required: true },
    canDeleteTask: { type: Function, required: true },
    onTaskDragStart: { type: Function, required: true },
    onTaskDragEnd: { type: Function, required: true },
    isTaskEditing: { type: Function, required: true },
    isTaskRowSaving: { type: Function, required: true },
    patchTaskField: { type: Function, required: true },
    ownerIds: { type: Function, required: true },
    firstNameOnly: { type: Function, required: true },
    leaderNameById: { type: Function, required: true },
    removeTaskOwner: { type: Function, required: true },
    onTaskOwnerSelectChange: { type: Function, required: true },
    deadlinesForTask: { type: Function, required: true },
    removeTaskDeadline: { type: Function, required: true },
    addTaskDeadline: { type: Function, required: true },
    eventIdsForTask: { type: Function, required: true },
    eventLabelById: { type: Function, required: true },
    removeTaskEvent: { type: Function, required: true },
    onTaskEventSelectChange: { type: Function, required: true },
    availableEvents: { type: Function, required: true },
    sharedSectionsForTask: { type: Function, required: true },
    removeTaskSharedSection: { type: Function, required: true },
    addTaskSharedSection: { type: Function, required: true },
    toggleTaskEdit: { type: Function, required: true },
    deleteTask: { type: Function, required: true },
    formatCompletedAt: { type: Function, required: true },
    formatDeadlineLabel: { type: Function, required: true },
    toggleTaskCompleted: { type: Function, required: true },
    toggleDeadlineCompleted: { type: Function, required: true },
    taskHasDeadlines: { type: Function, required: true },
    isTaskFullyCompleted: { type: Function, required: true },
    isDeadlineCompleted: { type: Function, required: true },
    deadlineCompletedAt: { type: Function, required: true },
    deadlineCompletedByName: { type: Function, required: true },
    isDeadlineSaving: { type: Function, required: true },
});
</script>

<template>
    <div class="space-y-6">
        <div
            v-for="section in props.groupedSections"
            :key="section.category"
            class="surface-brand-top rounded-xl border border-app-border bg-app-panel shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark p-4"
            :class="{ 'ring-2 ring-brand-blue/50': props.dragOverCategory === section.category }"
            @dragover="props.onCategoryDragOver(section.category, $event)"
            @drop="props.onCategoryDrop(section.category, $event)"
        >
            <h3
                v-if="!props.hideCategories"
                class="mb-3 flex items-center gap-2 border-b border-brand-blue/35 pb-2 text-lg font-semibold text-app-ink dark:text-app-ink-dark"
                :draggable="props.canUpdateTasks"
                @dragstart="props.onSectionDragStart(section.category)"
                @dragend="props.onSectionDragEnd"
            >
                <Bars3Icon v-if="props.canUpdateTasks" class="h-4 w-4 text-app-muted dark:text-app-muted-dark" />
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
                        :class="{ 'opacity-70': props.isTaskFullyCompleted(task) }"
                        :draggable="props.canEditTask(task)"
                        @dragstart="props.onTaskDragStart(task)"
                        @dragend="props.onTaskDragEnd"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="mb-1 inline-flex items-center gap-1 rounded bg-brand-blue/10 px-2 py-1 text-xs text-app-muted dark:text-app-muted-dark">
                                <Bars3Icon class="h-4 w-4" />
                                Sleep naar ander kopje
                            </div>
                            <label
                                v-if="props.canCompleteTask(task) && !props.taskHasDeadlines(task)"
                                class="inline-flex shrink-0 items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    class="h-5 w-5 rounded border-app-border text-brand-blue focus:ring-brand-blue dark:border-app-border-dark"
                                    :checked="!!task.completed_at"
                                    :disabled="props.isTaskRowSaving(task)"
                                    @change="props.toggleTaskCompleted(task)"
                                />
                                <span class="text-xs text-app-muted dark:text-app-muted-dark">Klaar</span>
                            </label>
                            <span
                                v-else-if="!props.taskHasDeadlines(task) && task.completed_at"
                                class="shrink-0 text-xs text-app-muted dark:text-app-muted-dark"
                            >
                                Klaar
                            </span>
                        </div>

                        <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Taak</p>
                        <input
                            v-if="props.isTaskEditing(task)"
                            type="text"
                            class="mt-1 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                            :value="task.title || ''"
                            :disabled="props.isTaskRowSaving(task)"
                            @change="props.patchTaskField(task, 'title', $event.target.value)"
                        />
                        <p
                            v-else
                            class="mt-1 text-sm text-app-ink dark:text-app-ink-dark"
                            :class="{ 'line-through text-app-muted dark:text-app-muted-dark': props.isTaskFullyCompleted(task) }"
                        >
                            {{ task.title || '—' }}
                        </p>
                        <p
                            v-if="!props.taskHasDeadlines(task) && task.completed_at"
                            class="mt-1 text-xs text-app-muted dark:text-app-muted-dark"
                        >
                            Afgevinkt op {{ props.formatCompletedAt(task.completed_at) }}
                        </p>

                        <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Wie</p>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            <span
                                v-for="id in props.ownerIds(task)"
                                :key="`mob-owner-chip-${task.id}-${id}`"
                                class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                            >
                                {{ props.firstNameOnly(props.leaderNameById(id)) }}
                                <button
                                    type="button"
                                    class="rounded p-0.5 hover:bg-brand-blue/25"
                                    :disabled="!props.isTaskEditing(task) || props.isTaskRowSaving(task)"
                                    @click="props.removeTaskOwner(task, id)"
                                >
                                    <XMarkIcon class="h-3.5 w-3.5" />
                                </button>
                            </span>
                        </div>
                        <select
                            v-if="props.isTaskEditing(task)"
                            class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                            :disabled="props.isTaskRowSaving(task)"
                            @change="props.onTaskOwnerSelectChange(task, $event)"
                        >
                            <option value="">Naam toevoegen…</option>
                            <option
                                v-for="leader in props.leaders"
                                :key="`mob-row-leader-${task.id}-${leader.id}`"
                                :value="String(leader.id)"
                            >
                                {{ props.firstNameOnly(leader.name) }}
                            </option>
                        </select>

                        <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Uitleg</p>
                        <textarea
                            v-if="props.isTaskEditing(task)"
                            rows="3"
                            class="mt-1 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                            :value="task.description || ''"
                            :disabled="props.isTaskRowSaving(task)"
                            @change="props.patchTaskField(task, 'description', $event.target.value)"
                        />
                        <p v-else class="mt-1 whitespace-pre-wrap text-sm text-app-ink dark:text-app-ink-dark">{{ task.description || '—' }}</p>

                        <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Deadlines</p>
                        <div class="mt-1 space-y-1.5">
                            <div
                                v-for="d in props.deadlinesForTask(task)"
                                :key="`mob-deadline-chip-${task.id}-${d}`"
                                class="flex flex-wrap items-center gap-2 rounded-lg border border-brand-blue/20 bg-brand-blue/5 px-2 py-1.5 text-xs dark:border-brand-blue/30 dark:bg-brand-blue/10"
                                :class="{ 'opacity-70': props.isDeadlineCompleted(task, d) }"
                            >
                                <input
                                    v-if="props.canCompleteTask(task)"
                                    type="checkbox"
                                    class="h-4 w-4 shrink-0 rounded border-app-border text-brand-blue focus:ring-brand-blue dark:border-app-border-dark"
                                    :checked="props.isDeadlineCompleted(task, d)"
                                    :disabled="props.isDeadlineSaving(task, d) || props.isTaskEditing(task)"
                                    @change="props.toggleDeadlineCompleted(task, d)"
                                />
                                <div class="min-w-0 flex-1">
                                    <span :class="{ 'line-through text-app-muted dark:text-app-muted-dark': props.isDeadlineCompleted(task, d) }">
                                        {{ props.formatDeadlineLabel(d) }}
                                    </span>
                                    <p
                                        v-if="props.isDeadlineCompleted(task, d)"
                                        class="text-[11px] text-app-muted dark:text-app-muted-dark"
                                    >
                                        <template v-if="props.deadlineCompletedByName(task, d)">
                                            Afgevinkt door {{ props.deadlineCompletedByName(task, d) }}
                                            op {{ props.formatCompletedAt(props.deadlineCompletedAt(task, d)) }}
                                        </template>
                                        <template v-else>
                                            Afgevinkt op {{ props.formatCompletedAt(props.deadlineCompletedAt(task, d)) }}
                                        </template>
                                    </p>
                                </div>
                                <button
                                    v-if="props.isTaskEditing(task)"
                                    type="button"
                                    class="rounded p-0.5 hover:bg-brand-blue/25"
                                    :disabled="props.isTaskRowSaving(task)"
                                    @click="props.removeTaskDeadline(task, d)"
                                >
                                    <XMarkIcon class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                        <input
                            v-if="props.isTaskEditing(task)"
                            type="date"
                            class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                            :disabled="props.isTaskRowSaving(task)"
                            @change="props.addTaskDeadline(task, $event.target.value); $event.target.value = ''"
                        />

                        <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Opkomsten</p>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            <span
                                v-for="eventId in props.eventIdsForTask(task)"
                                :key="`mob-event-chip-${task.id}-${eventId}`"
                                class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                            >
                                {{ props.eventLabelById(eventId) }}
                                <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" :disabled="!props.isTaskEditing(task) || props.isTaskRowSaving(task)" @click="props.removeTaskEvent(task, eventId)">
                                    <XMarkIcon class="h-3.5 w-3.5" />
                                </button>
                            </span>
                        </div>
                        <select
                            v-if="props.isTaskEditing(task)"
                            class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                            :disabled="props.isTaskRowSaving(task)"
                            @change="props.onTaskEventSelectChange(task, $event)"
                        >
                            <option value="">Opkomst toevoegen…</option>
                            <option
                                v-for="ev in props.availableEvents(task)"
                                :key="`mob-event-${task.id}-${ev.id}`"
                                :value="String(ev.id)"
                            >
                                {{ props.eventLabelById(ev.id) }}
                            </option>
                        </select>

                        <p class="mt-2 text-xs uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Gezamenlijk met</p>
                        <div class="mt-1 flex flex-wrap gap-1.5">
                            <span
                                v-for="sharedSection in props.sharedSectionsForTask(task)"
                                :key="`mob-shared-chip-${task.id}-${sharedSection}`"
                                class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                            >
                                {{ props.sectionLabels[sharedSection] || sharedSection }}
                                <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" :disabled="!props.isTaskEditing(task) || props.isTaskRowSaving(task)" @click="props.removeTaskSharedSection(task, sharedSection)">
                                    <XMarkIcon class="h-3.5 w-3.5" />
                                </button>
                            </span>
                        </div>
                        <select
                            v-if="props.isTaskEditing(task)"
                            class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                            :disabled="props.isTaskRowSaving(task)"
                            @change="props.addTaskSharedSection(task, $event.target.value); $event.target.value = ''"
                        >
                            <option value="">Speltak toevoegen…</option>
                            <option
                                v-for="sharedSection in props.shareableSections.filter((s) => !props.sharedSectionsForTask(task).includes(s))"
                                :key="`mob-shared-${task.id}-${sharedSection}`"
                                :value="sharedSection"
                            >
                                {{ props.sectionLabels[sharedSection] || sharedSection }}
                            </option>
                        </select>

                        <div class="mt-3 border-t border-brand-blue/25 pt-3 dark:border-brand-blue/35">
                            <button
                                v-if="props.canEditTask(task)"
                                type="button"
                                class="btn-action-edit mr-2"
                                @click="props.toggleTaskEdit(task)"
                                title="Bewerken"
                            >
                                <PencilSquareIcon class="h-4 w-4" />
                            </button>
                            <button
                                v-if="props.canDeleteTask(task)"
                                type="button"
                                class="btn-action-delete"
                                @click="props.deleteTask(task)"
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
                                <th class="px-3 py-2.5">Klaar</th>
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
                                :class="{ 'opacity-70': props.isTaskFullyCompleted(task) }"
                                :draggable="props.canEditTask(task)"
                                @dragstart="props.onTaskDragStart(task)"
                                @dragend="props.onTaskDragEnd"
                            >
                                <td class="px-3 py-2.5 align-middle">
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-if="props.canCompleteTask(task) && !props.taskHasDeadlines(task)"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-app-border text-brand-blue focus:ring-brand-blue dark:border-app-border-dark"
                                            :checked="!!task.completed_at"
                                            :disabled="props.isTaskRowSaving(task)"
                                            @change="props.toggleTaskCompleted(task)"
                                        />
                                        <span
                                            v-else-if="!props.taskHasDeadlines(task) && task.completed_at"
                                            class="inline-flex h-4 w-4 items-center justify-center rounded border border-brand-blue/40 bg-brand-blue/15 text-[10px] font-bold text-brand-blue"
                                            title="Afgevinkt"
                                        >
                                            ✓
                                        </span>
                                        <span
                                            v-else-if="props.taskHasDeadlines(task) && props.isTaskFullyCompleted(task)"
                                            class="text-xs font-medium text-brand-green dark:text-brand-green"
                                            title="Alle deadlines afgevinkt"
                                        >
                                            ✓
                                        </span>
                                        <Bars3Icon
                                            v-if="props.canEditTask(task)"
                                            class="h-5 w-5 cursor-grab text-app-muted dark:text-app-muted-dark"
                                        />
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 align-middle">
                                    <input
                                        v-if="props.isTaskEditing(task)"
                                        type="text"
                                        class="w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :value="task.title || ''"
                                        :disabled="props.isTaskRowSaving(task)"
                                        @change="props.patchTaskField(task, 'title', $event.target.value)"
                                    />
                                    <div v-else>
                                        <span
                                            :class="{ 'line-through text-app-muted dark:text-app-muted-dark': props.isTaskFullyCompleted(task) }"
                                        >
                                            {{ task.title || '—' }}
                                        </span>
                                        <p
                                            v-if="!props.taskHasDeadlines(task) && task.completed_at"
                                            class="mt-0.5 text-xs text-app-muted dark:text-app-muted-dark"
                                        >
                                            Afgevinkt op {{ props.formatCompletedAt(task.completed_at) }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-3 py-2.5 align-middle">
                                    <label class="sr-only" :for="`task-owner-${task.id}`">Wie</label>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="id in props.ownerIds(task)"
                                            :key="`desk-owner-chip-${task.id}-${id}`"
                                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                        >
                                            {{ props.firstNameOnly(props.leaderNameById(id)) }}
                                            <button
                                                type="button"
                                                class="rounded p-0.5 hover:bg-brand-blue/25"
                                                :disabled="!props.isTaskEditing(task) || props.isTaskRowSaving(task)"
                                                @click="props.removeTaskOwner(task, id)"
                                            >
                                                <XMarkIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    </div>
                                    <select
                                        v-if="props.isTaskEditing(task)"
                                        :id="`task-owner-${task.id}`"
                                        class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :disabled="props.isTaskRowSaving(task)"
                                        @change="props.onTaskOwnerSelectChange(task, $event)"
                                    >
                                        <option value="">Naam toevoegen…</option>
                                        <option
                                            v-for="leader in props.leaders"
                                            :key="`row-leader-${task.id}-${leader.id}`"
                                            :value="String(leader.id)"
                                        >
                                            {{ props.firstNameOnly(leader.name) }}
                                        </option>
                                    </select>
                                </td>
                                <td class="px-3 py-2.5 align-middle">
                                    <textarea
                                        v-if="props.isTaskEditing(task)"
                                        rows="3"
                                        class="w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :value="task.description || ''"
                                        :disabled="props.isTaskRowSaving(task)"
                                        @change="props.patchTaskField(task, 'description', $event.target.value)"
                                    />
                                    <span v-else class="whitespace-pre-wrap">{{ task.description || '—' }}</span>
                                </td>
                                <td class="px-3 py-2.5 align-middle">
                                    <label class="sr-only" :for="`task-deadline-${task.id}`">Deadlines</label>
                                    <div class="space-y-1.5">
                                        <div
                                            v-for="d in props.deadlinesForTask(task)"
                                            :key="`desk-deadline-chip-${task.id}-${d}`"
                                            class="flex items-start gap-2 rounded-md border border-brand-blue/20 bg-brand-blue/5 px-2 py-1 text-xs dark:border-brand-blue/30 dark:bg-brand-blue/10"
                                            :class="{ 'opacity-70': props.isDeadlineCompleted(task, d) }"
                                        >
                                            <input
                                                v-if="props.canCompleteTask(task)"
                                                type="checkbox"
                                                class="mt-0.5 h-4 w-4 shrink-0 rounded border-app-border text-brand-blue focus:ring-brand-blue dark:border-app-border-dark"
                                                :checked="props.isDeadlineCompleted(task, d)"
                                                :disabled="props.isDeadlineSaving(task, d) || props.isTaskEditing(task)"
                                                @change="props.toggleDeadlineCompleted(task, d)"
                                            />
                                            <div class="min-w-0 flex-1">
                                                <span :class="{ 'line-through text-app-muted dark:text-app-muted-dark': props.isDeadlineCompleted(task, d) }">
                                                    {{ props.formatDeadlineLabel(d) }}
                                                </span>
                                                <p
                                                    v-if="props.isDeadlineCompleted(task, d)"
                                                    class="text-[11px] leading-tight text-app-muted dark:text-app-muted-dark"
                                                >
                                                    <template v-if="props.deadlineCompletedByName(task, d)">
                                                        Afgevinkt door {{ props.deadlineCompletedByName(task, d) }}
                                                        op {{ props.formatCompletedAt(props.deadlineCompletedAt(task, d)) }}
                                                    </template>
                                                    <template v-else>
                                                        Afgevinkt op {{ props.formatCompletedAt(props.deadlineCompletedAt(task, d)) }}
                                                    </template>
                                                </p>
                                            </div>
                                            <button
                                                v-if="props.isTaskEditing(task)"
                                                type="button"
                                                class="rounded p-0.5 hover:bg-brand-blue/25"
                                                :disabled="props.isTaskRowSaving(task)"
                                                @click="props.removeTaskDeadline(task, d)"
                                            >
                                                <XMarkIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                    <input
                                        v-if="props.isTaskEditing(task)"
                                        :id="`task-deadline-${task.id}`"
                                        type="date"
                                        class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :disabled="props.isTaskRowSaving(task)"
                                        @change="props.addTaskDeadline(task, $event.target.value); $event.target.value = ''"
                                    />
                                </td>
                                <td class="px-3 py-2.5 align-middle">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="eventId in props.eventIdsForTask(task)"
                                            :key="`desk-event-chip-${task.id}-${eventId}`"
                                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                        >
                                            {{ props.eventLabelById(eventId) }}
                                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" :disabled="!props.isTaskEditing(task) || props.isTaskRowSaving(task)" @click="props.removeTaskEvent(task, eventId)">
                                                <XMarkIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    </div>
                                    <select
                                        v-if="props.isTaskEditing(task)"
                                        class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :disabled="props.isTaskRowSaving(task)"
                                        @change="props.onTaskEventSelectChange(task, $event)"
                                    >
                                        <option value="">Opkomst toevoegen…</option>
                                        <option
                                            v-for="ev in props.availableEvents(task)"
                                            :key="`desk-event-${task.id}-${ev.id}`"
                                            :value="String(ev.id)"
                                        >
                                            {{ props.eventLabelById(ev.id) }}
                                        </option>
                                    </select>
                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="sharedSection in props.sharedSectionsForTask(task)"
                                            :key="`desk-shared-chip-${task.id}-${sharedSection}`"
                                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                                        >
                                            {{ props.sectionLabels[sharedSection] || sharedSection }}
                                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" :disabled="!props.isTaskEditing(task) || props.isTaskRowSaving(task)" @click="props.removeTaskSharedSection(task, sharedSection)">
                                                <XMarkIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    </div>
                                    <select
                                        v-if="props.isTaskEditing(task)"
                                        class="mt-2 w-full min-w-0 rounded border border-app-border bg-white px-2 py-1.5 text-app-ink shadow-sm outline-none focus:border-brand-blue dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        :disabled="props.isTaskRowSaving(task)"
                                        @change="props.addTaskSharedSection(task, $event.target.value); $event.target.value = ''"
                                    >
                                        <option value="">Speltak toevoegen…</option>
                                        <option
                                            v-for="sharedSection in props.shareableSections.filter((s) => !props.sharedSectionsForTask(task).includes(s))"
                                            :key="`desk-shared-${task.id}-${sharedSection}`"
                                            :value="sharedSection"
                                        >
                                            {{ props.sectionLabels[sharedSection] || sharedSection }}
                                        </option>
                                    </select>
                                </td>
                                <td class="px-3 py-2.5 align-middle">
                                    <button
                                        v-if="props.canEditTask(task)"
                                        type="button"
                                        class="btn-action-edit mr-2"
                                        @click="props.toggleTaskEdit(task)"
                                        title="Bewerken"
                                    >
                                        <PencilSquareIcon class="h-4 w-4" />
                                    </button>
                                    <button
                                        v-if="props.canDeleteTask(task)"
                                        type="button"
                                        class="btn-action-delete"
                                        @click="props.deleteTask(task)"
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
</template>
