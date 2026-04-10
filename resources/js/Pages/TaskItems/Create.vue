<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, Bars3Icon, ClipboardDocumentListIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

const props = defineProps({
    canCreateCategory: { type: Boolean, default: false },
    taskCategories: { type: Array, default: () => [] },
    leaders: { type: Array, default: () => [] },
    activeSection: { type: String, default: 'dolfijnen' },
    allSections: { type: Array, default: () => [] },
});
const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabels[props.activeSection] || 'Dolfijnen');

const selectedAction = ref(null);
const addDeadlineInput = ref('');
const ownerSelectValue = ref('');
const shareableSections = computed(() => (props.allSections || []).filter((s) => s !== props.activeSection));
const hasCategories = computed(() => (props.taskCategories || []).length > 0);

const taskForm = useForm({
    category: props.taskCategories?.[0] || '',
    title: '',
    owner_user_ids: [],
    description: '',
    deadlines: [],
    shared_sections: [],
});

const categoryForm = useForm({
    name: '',
});

function goToTaskCreate() {
    selectedAction.value = 'task';
}

function goToCategoryCreate() {
    if (!props.canCreateCategory) return;
    selectedAction.value = 'category';
}

function addTaskDeadline() {
    const value = String(addDeadlineInput.value || '').trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return;
    taskForm.deadlines = [...new Set([...(taskForm.deadlines || []), value])].sort();
    addDeadlineInput.value = '';
}

function removeTaskDeadline(value) {
    taskForm.deadlines = (taskForm.deadlines || []).filter((d) => d !== value);
}

function leaderNameById(id) {
    return props.leaders.find((leader) => Number(leader.id) === Number(id))?.name || '';
}

function addTaskOwner(id) {
    const value = Number(id);
    if (!Number.isFinite(value)) return;
    const current = (taskForm.owner_user_ids || []).map((v) => Number(v));
    if (current.includes(value)) return;
    taskForm.owner_user_ids = [...current, value];
}

function removeTaskOwner(id) {
    taskForm.owner_user_ids = (taskForm.owner_user_ids || []).filter((v) => Number(v) !== Number(id));
}

function onTaskOwnerSelectChange(event) {
    const id = event?.target?.value;
    addTaskOwner(id);
    ownerSelectValue.value = '';
}

function submitTask() {
    taskForm.post(route('task-items.store'), {
        preserveScroll: true,
    });
}

function submitCategory() {
    categoryForm.post(route('task-categories.store'), {
        preserveScroll: true,
        onSuccess: () => {
            categoryForm.reset();
        },
    });
}
</script>

<template>
    <Head :title="`${speltakLabel} - Taakverdeling toevoegen`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - Taakverdeling toevoegen</h2>
                <Link
                    :href="route('task-items.index')"
                    class="btn-action-back"
                    title="Terug"
                    aria-label="Terug"
                >
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <div class="grid grid-cols-2 gap-3">
            <button
                type="button"
                class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-5 text-left shadow-sm transition hover:border-slate-300 hover:bg-slate-100 dark:border-brand-blue/30 dark:bg-app-panel-dark dark:hover:bg-slate-800/60"
                :class="selectedAction === 'task' ? 'ring-2 ring-brand-blue/60' : ''"
                @click="goToTaskCreate"
            >
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-blue text-white">
                    <ClipboardDocumentListIcon class="h-5 w-5" />
                </span>
                <h3 class="mt-3 text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuwe taak toevoegen</h3>
                <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">Open direct het formulier om een taak aan te maken.</p>
            </button>

            <button
                type="button"
                class="surface-brand-top rounded-xl border border-app-border bg-app-panel p-5 text-left shadow-sm transition hover:border-slate-300 hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50 dark:border-brand-blue/30 dark:bg-app-panel-dark dark:hover:bg-slate-800/60"
                :class="selectedAction === 'category' ? 'ring-2 ring-brand-blue/60' : ''"
                :disabled="!props.canCreateCategory"
                @click="goToCategoryCreate"
            >
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-blue text-white">
                    <Bars3Icon class="h-5 w-5" />
                </span>
                <h3 class="mt-3 text-base font-semibold text-app-ink dark:text-app-ink-dark">Sectie toevoegen</h3>
                <p class="mt-1 text-sm text-app-muted dark:text-app-muted-dark">
                    Maak een nieuw kopje aan voor taken binnen je speltak.
                </p>
            </button>
        </div>

        <form
            v-if="selectedAction === 'task'"
            class="surface-brand-top mt-4 space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
            @submit.prevent="submitTask"
        >
            <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Nieuwe taak toevoegen</h3>
            <div v-if="!hasCategories" class="rounded-md border border-amber-300 bg-amber-100 px-3 py-2 text-sm text-amber-900">
                Er zijn nog geen secties beschikbaar. Maak eerst een sectie aan.
            </div>
            <div class="grid gap-4 sm:grid-cols-[9rem_1fr] sm:items-start">
                <label class="text-sm font-semibold text-app-muted dark:text-app-muted-dark sm:pt-2.5">Sectie</label>
                <select v-model="taskForm.category" class="rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black" :disabled="!hasCategories">
                    <option v-for="cat in props.taskCategories" :key="`create-task-cat-${cat}`" :value="cat">{{ cat }}</option>
                </select>

                <label class="text-sm font-semibold text-app-muted dark:text-app-muted-dark sm:pt-2.5">Taak</label>
                <input v-model="taskForm.title" type="text" required class="rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black" />

                <label class="text-sm font-semibold text-app-muted dark:text-app-muted-dark sm:pt-2.5">Eigenaren</label>
                <div>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="id in taskForm.owner_user_ids"
                            :key="`create-task-owner-chip-${id}`"
                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs text-black"
                        >
                            {{ leaderNameById(id) || `Leiding #${id}` }}
                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/20" @click="removeTaskOwner(id)">x</button>
                        </span>
                    </div>
                    <select
                        v-model="ownerSelectValue"
                        class="mt-2 w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black"
                        @change="onTaskOwnerSelectChange"
                    >
                        <option value="">Naam toevoegen…</option>
                        <option
                            v-for="leader in props.leaders.filter((l) => !(taskForm.owner_user_ids || []).map((v) => Number(v)).includes(Number(l.id)))"
                            :key="`create-task-owner-option-${leader.id}`"
                            :value="leader.id"
                        >
                            {{ leader.name }}
                        </option>
                    </select>
                </div>

                <label class="text-sm font-semibold text-app-muted dark:text-app-muted-dark sm:pt-2.5">Omschrijving</label>
                <textarea v-model="taskForm.description" rows="4" required class="rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black" />

                <label class="text-sm font-semibold text-app-muted dark:text-app-muted-dark sm:pt-2.5">Deadlines</label>
                <div>
                    <div class="flex items-center gap-2">
                        <input v-model="addDeadlineInput" type="date" class="rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black" />
                        <button type="button" class="rounded bg-brand-blue px-3 py-2 text-sm text-white hover:bg-brand-blue-dark" @click="addTaskDeadline">Toevoegen</button>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <span v-for="d in taskForm.deadlines" :key="`create-task-deadline-${d}`" class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs text-black">
                            {{ d }}
                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/20" @click="removeTaskDeadline(d)">x</button>
                        </span>
                    </div>
                </div>

                <label class="text-sm font-semibold text-app-muted dark:text-app-muted-dark sm:pt-2.5">Delen met</label>
                <div class="flex flex-wrap gap-2">
                    <label v-for="section in shareableSections" :key="`create-task-share-${section}`" class="inline-flex items-center gap-2 rounded border border-app-border bg-white px-2 py-1 text-xs text-black dark:border-app-border-dark dark:bg-app-canvas-dark">
                        <input v-model="taskForm.shared_sections" type="checkbox" :value="section" />
                        {{ section }}
                    </label>
                </div>

                <span class="hidden sm:block" aria-hidden="true" />
                <button type="submit" class="rounded bg-brand-blue px-5 py-2 text-sm font-medium text-white hover:bg-brand-blue-dark disabled:opacity-50" :disabled="taskForm.processing || !hasCategories">
                    Opslaan
                </button>
            </div>
        </form>

        <form
            v-if="selectedAction === 'category' && props.canCreateCategory"
            class="surface-brand-top mt-4 space-y-3 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark"
            @submit.prevent="submitCategory"
        >
            <h3 class="text-base font-semibold text-app-ink dark:text-app-ink-dark">Sectie toevoegen</h3>
            <div class="grid gap-4 sm:grid-cols-[9rem_1fr] sm:items-start">
                <label class="text-sm font-semibold text-app-muted dark:text-app-muted-dark sm:pt-2.5">Naam</label>
                <input v-model="categoryForm.name" type="text" required class="rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-black" />
                <span class="hidden sm:block" aria-hidden="true" />
                <button type="submit" class="rounded bg-brand-blue px-5 py-2 text-sm font-medium text-white hover:bg-brand-blue-dark disabled:opacity-50" :disabled="categoryForm.processing">
                    Aanmaken
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
