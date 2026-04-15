<script setup>
import { DocumentCheckIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    canCreateTasks: { type: Boolean, required: true },
    showCategoryForm: { type: Boolean, required: true },
    showAddForm: { type: Boolean, required: true },
    categoryForm: { type: Object, required: true },
    form: { type: Object, required: true },
    canCreateCrossSection: { type: Boolean, required: true },
    targetSections: { type: Array, required: true },
    sectionLabels: { type: Object, required: true },
    hideCategories: { type: Boolean, required: true },
    createTaskCategories: { type: Array, required: true },
    leaders: { type: Array, required: true },
    shareableSections: { type: Array, required: true },
    addDeadlineInput: { type: String, default: '' },
    firstNameOnly: { type: Function, required: true },
    leaderNameById: { type: Function, required: true },
});

const emit = defineEmits([
    'submit-category',
    'submit-add',
    'add-deadline',
    'remove-deadline',
    'update:add-deadline-input',
]);

const addDeadlineInputModel = computed({
    get: () => props.addDeadlineInput,
    set: (value) => emit('update:add-deadline-input', String(value ?? '')),
});

function removeOwner(id) {
    props.form.owner_user_ids = (props.form.owner_user_ids || []).filter((x) => Number(x) !== Number(id));
}

function addOwnerFromSelect(event) {
    const v = Number(event.target.value);
    if (Number.isFinite(v) && !props.form.owner_user_ids.includes(v)) {
        props.form.owner_user_ids.push(v);
    }
    event.target.value = '';
}
</script>

<template>
    <div>
        <form
            v-if="props.canCreateTasks"
            v-show="props.showCategoryForm"
            class="surface-brand-top space-y-3 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
            @submit.prevent="emit('submit-category')"
        >
            <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuwe sectie</h3>
            <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                <label for="category-name" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                    Naam
                </label>
                <input
                    id="category-name"
                    v-model="props.categoryForm.name"
                    type="text"
                    placeholder="Bijv. Materiaal"
                    class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted dark:text-app-muted-dark"
                />
                <span class="hidden sm:block" aria-hidden="true" />
                <div>
                    <button
                        type="submit"
                        class="rounded bg-brand-blue px-5 py-2 text-sm font-medium text-white hover:bg-brand-blue-dark disabled:opacity-50"
                        :disabled="props.categoryForm.processing"
                    >
                        Aanmaken
                    </button>
                </div>
            </div>
            <p v-if="props.categoryForm.errors.name" class="text-sm text-red-400">{{ props.categoryForm.errors.name }}</p>
        </form>

        <form
            v-if="props.canCreateTasks"
            v-show="props.showAddForm"
            class="surface-brand-top mt-4 space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
            @submit.prevent="emit('submit-add')"
        >
            <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuwe taak</h3>
            <div class="grid gap-4 sm:grid-cols-[8rem_1fr] sm:items-start">
                <label v-if="props.canCreateCrossSection" for="add-target-section" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                    Speltak
                </label>
                <select
                    v-if="props.canCreateCrossSection"
                    id="add-target-section"
                    v-model="props.form.target_section"
                    class="min-w-0 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                >
                    <option value="">Kies speltak</option>
                    <option v-for="section in props.targetSections" :key="`task-target-${section}`" :value="section">
                        {{ props.sectionLabels[section] || section }}
                    </option>
                </select>

                <span v-if="!props.hideCategories" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-1">
                    Kopje
                </span>
                <div v-if="!props.hideCategories" class="flex flex-wrap gap-2" role="radiogroup" aria-label="Kies kopje voor deze taak">
                    <label
                        v-for="cat in props.createTaskCategories"
                        :key="`add-${cat}`"
                        class="cursor-pointer rounded-lg border px-3 py-2 text-sm transition"
                        :class="
                            props.form.category === cat
                                ? 'border-brand-yellow bg-brand-blue/45 text-white ring-2 ring-brand-yellow/70'
                                : 'border-brand-blue/35 bg-white text-app-ink hover:border-brand-blue/55 dark:bg-app-canvas-dark dark:text-app-ink-dark dark:hover:border-brand-blue/55'
                        "
                    >
                        <input
                            v-model="props.form.category"
                            type="radio"
                            class="sr-only"
                            :value="cat"
                            :required="props.createTaskCategories[0] === cat"
                        />
                        {{ cat }}
                    </label>
                </div>

                <label for="add-title" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                    Taak
                </label>
                <input
                    id="add-title"
                    v-model="props.form.title"
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
                            v-for="id in props.form.owner_user_ids"
                            :key="`add-owner-chip-${id}`"
                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                        >
                            {{ props.firstNameOnly(props.leaderNameById(id)) }}
                            <button
                                type="button"
                                class="rounded p-0.5 hover:bg-brand-blue/25"
                                @click="removeOwner(id)"
                            >
                                <XMarkIcon class="h-3.5 w-3.5" />
                            </button>
                        </span>
                    </div>
                    <select
                        id="add-owners"
                        class="mt-2 min-w-0 w-full rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                        @change="addOwnerFromSelect"
                    >
                        <option value="">Naam toevoegen...</option>
                        <option v-for="leader in props.leaders" :key="`add-leader-${leader.id}`" :value="String(leader.id)">
                            {{ props.firstNameOnly(leader.name) }}
                        </option>
                    </select>
                </div>

                <label for="add-description" class="text-sm font-semibold tracking-wide text-app-muted dark:text-app-muted-dark sm:pt-2.5">
                    Uitleg
                </label>
                <textarea
                    id="add-description"
                    v-model="props.form.description"
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
                            v-for="d in props.form.deadlines"
                            :key="`add-deadline-chip-${d}`"
                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs"
                        >
                            {{ d }}
                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" @click="emit('remove-deadline', d)">
                                <XMarkIcon class="h-3.5 w-3.5" />
                            </button>
                        </span>
                    </div>
                    <div class="mt-2 flex gap-2">
                        <input
                            id="add-deadline"
                            v-model="addDeadlineInputModel"
                            type="date"
                            class="min-w-0 flex-1 rounded border border-app-border bg-white px-3 py-2 text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                        />
                        <button
                            type="button"
                            class="rounded border border-brand-blue-light/50 px-3 py-2 text-sm font-medium text-app-ink transition hover:bg-brand-blue/10 dark:text-app-ink-dark"
                            @click="emit('add-deadline')"
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
                        v-for="section in props.shareableSections"
                        :key="`add-task-share-${section}`"
                        class="inline-flex items-center gap-2 rounded border border-app-border bg-white px-2 py-1 text-xs dark:border-app-border-dark dark:bg-app-canvas-dark"
                    >
                        <input
                            v-model="props.form.shared_sections"
                            type="checkbox"
                            :value="section"
                            class="rounded border-app-border"
                        />
                        {{ props.sectionLabels[section] || section }}
                    </label>
                </div>

                <span class="hidden sm:block" aria-hidden="true" />
                <div>
                    <button
                        type="submit"
                        class="btn-action-save"
                        :disabled="props.form.processing"
                        title="Opslaan"
                        aria-label="Opslaan"
                    >
                        <DocumentCheckIcon class="h-5 w-5" />
                    </button>
                </div>
            </div>
            <p v-if="props.form.errors.category" class="text-sm text-red-400">{{ props.form.errors.category }}</p>
            <p v-if="props.form.errors.target_section" class="text-sm text-red-400">{{ props.form.errors.target_section }}</p>
            <p v-if="props.form.errors.title" class="text-sm text-red-400">
                {{ props.form.errors.title }}
            </p>
            <p v-if="props.form.errors.description" class="text-sm text-red-400">
                {{ props.form.errors.description }}
            </p>
        </form>
    </div>
</template>
