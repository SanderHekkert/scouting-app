<script setup>
import { Link } from '@inertiajs/vue3';
import { ChevronLeftIcon, ChevronRightIcon, MagnifyingGlassIcon, PlusIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
    canBrowseAllAgendas: { type: Boolean, required: true },
    sectionOptions: { type: Array, required: true },
    filterUsers: { type: Array, required: true },
    sectionLabels: { type: Object, required: true },
    selectedFilterUserName: { type: String, default: '' },
    canCreateAgendaItem: { type: Boolean, required: true },
    showSearchPanel: { type: Boolean, required: true },
    viewMode: { type: String, required: true },
    periodLabel: { type: String, required: true },
    agendaSearch: { type: String, required: true },
    searchResults: { type: Array, required: true },
    sectionFilter: { type: String, required: true },
    userFilter: { type: Number, required: true },
});

const emit = defineEmits([
    'apply-filters',
    'go-today',
    'toggle-search',
    'go-create',
    'set-view-mode',
    'previous-period',
    'next-period',
    'update:agenda-search',
    'update:section-filter',
    'update:user-filter',
]);

const sectionFilterModel = computed({
    get: () => props.sectionFilter,
    set: (value) => emit('update:section-filter', String(value ?? '')),
});

const userFilterModel = computed({
    get: () => props.userFilter,
    set: (value) => emit('update:user-filter', Number(value || 0)),
});

const agendaSearchModel = computed({
    get: () => props.agendaSearch,
    set: (value) => emit('update:agenda-search', String(value ?? '')),
});
</script>

<template>
    <div>
        <div class="flex w-full flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">Persoonlijke Agenda</h2>
            <div class="flex w-full flex-col gap-2 sm:w-auto sm:flex-1 sm:flex-row sm:flex-wrap sm:items-center sm:justify-end">
                <template v-if="props.canBrowseAllAgendas">
                    <select
                        v-model="sectionFilterModel"
                        class="w-full rounded-full border border-app-border bg-app-panel px-3.5 py-2 text-sm font-semibold text-app-ink shadow-sm transition hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/20 sm:w-auto sm:py-1.5"
                        @change="emit('apply-filters')"
                    >
                        <option v-for="section in props.sectionOptions" :key="`section-filter-${section}`" :value="section">
                            {{ props.sectionLabels[section] || section }}
                        </option>
                    </select>
                    <select
                        v-model="userFilterModel"
                        class="w-full rounded-full border border-app-border bg-app-panel px-3.5 py-2 text-sm font-semibold text-app-ink shadow-sm transition hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/20 sm:w-auto sm:py-1.5"
                        @change="emit('apply-filters')"
                    >
                        <option v-for="u in props.filterUsers" :key="`user-filter-${u.id}`" :value="u.id">
                            {{ u.name }}
                        </option>
                    </select>
                    <p v-if="props.selectedFilterUserName" class="px-1 text-xs text-app-muted dark:text-app-muted-dark sm:hidden">
                        Gebruiker: {{ props.selectedFilterUserName }}
                    </p>
                </template>
                <div class="flex w-full items-center gap-2 sm:w-auto">
                    <button type="button" class="w-full rounded-full border border-app-border bg-app-panel px-3.5 py-2 text-sm font-semibold text-app-ink shadow-sm transition hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:bg-brand-blue/20 sm:w-auto sm:py-1.5" @click="emit('go-today')">Vandaag</button>
                    <button
                        type="button"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-app-border bg-app-panel text-app-ink shadow-sm transition hover:border-brand-blue/40 hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark dark:hover:border-brand-blue/45 dark:hover:bg-brand-blue/15"
                        :title="props.showSearchPanel ? 'Zoeken sluiten' : 'Zoeken'"
                        :aria-label="props.showSearchPanel ? 'Zoeken sluiten' : 'Zoeken'"
                        @click="emit('toggle-search')"
                    >
                        <MagnifyingGlassIcon class="h-5 w-5" />
                    </button>
                    <button v-if="props.canCreateAgendaItem" type="button" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-white shadow-sm transition hover:bg-emerald-800" title="Toevoegen" aria-label="Toevoegen" @click="emit('go-create')">
                        <PlusIcon class="h-5 w-5" />
                    </button>
                </div>
                <div class="inline-flex w-full items-center rounded-full border border-app-border bg-app-panel p-1 shadow-sm dark:border-app-border-dark dark:bg-app-panel-dark sm:w-auto">
                    <button type="button" class="rounded-full px-3.5 py-1.5 text-sm font-semibold transition" :class="props.viewMode === 'day' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink hover:bg-brand-blue/10 dark:text-app-ink-dark dark:hover:bg-brand-blue/20'" @click="emit('set-view-mode', 'day')">Dag</button>
                    <button type="button" class="hidden rounded-full px-3.5 py-1.5 text-sm font-semibold transition sm:inline-flex" :class="props.viewMode === 'week' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink hover:bg-brand-blue/10 dark:text-app-ink-dark dark:hover:bg-brand-blue/20'" @click="emit('set-view-mode', 'week')">Week</button>
                    <button type="button" class="rounded-full px-3.5 py-1.5 text-sm font-semibold transition" :class="props.viewMode === 'month' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink hover:bg-brand-blue/10 dark:text-app-ink-dark dark:hover:bg-brand-blue/20'" @click="emit('set-view-mode', 'month')">Maand</button>
                    <button type="button" class="rounded-full px-3.5 py-1.5 text-sm font-semibold transition" :class="props.viewMode === 'year' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink hover:bg-brand-blue/10 dark:text-app-ink-dark dark:hover:bg-brand-blue/20'" @click="emit('set-view-mode', 'year')">Jaar</button>
                </div>
            </div>
        </div>

        <div class="mt-4 mb-3 flex items-center justify-between gap-3">
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-full border border-app-border bg-app-panel p-2 text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark"
                @click="emit('previous-period')"
            >
                <ChevronLeftIcon class="h-5 w-5" />
            </button>
            <h3 class="text-lg font-semibold tracking-tight text-app-ink dark:text-app-ink-dark">{{ props.periodLabel }}</h3>
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-full border border-app-border bg-app-panel p-2 text-app-ink hover:bg-brand-blue/10 dark:border-app-border-dark dark:bg-app-panel-dark dark:text-app-ink-dark"
                @click="emit('next-period')"
            >
                <ChevronRightIcon class="h-5 w-5" />
            </button>
        </div>

        <div v-if="props.showSearchPanel" class="mb-3 rounded-xl border border-app-border bg-app-panel p-3 dark:border-app-border-dark dark:bg-app-canvas-dark/70">
            <input
                v-model="agendaSearchModel"
                type="search"
                placeholder="Zoek in agenda en opkomsten..."
                class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-app-ink placeholder:text-app-muted dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:placeholder:text-app-muted-dark"
            />
            <div class="mt-3">
                <table class="w-full text-sm text-app-ink dark:text-app-ink-dark">
                    <tbody class="divide-y divide-brand-blue/20">
                        <tr v-for="entry in props.searchResults" :key="`search-${entry.sourceType}-${entry.sourceId}`">
                            <td class="py-2 pe-2 whitespace-nowrap text-xs text-app-muted dark:text-app-muted-dark">{{ entry.date || '-' }}</td>
                            <td class="py-2">
                                <Link :href="entry.href" class="hover:underline">
                                    <span class="font-semibold">{{ entry.tag }}</span>
                                    <span class="ms-1">{{ entry.title }}</span>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p v-if="props.agendaSearch.trim() !== '' && !props.searchResults.length" class="py-2 text-sm text-app-muted dark:text-app-muted-dark">
                    Geen resultaten gevonden.
                </p>
            </div>
        </div>
    </div>
</template>
