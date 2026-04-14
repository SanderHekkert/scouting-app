<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, DocumentCheckIcon, DocumentDuplicateIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    mode: { type: String, default: 'create' },
    item: { type: Object, default: null },
    copyItem: { type: Object, default: null },
    leaderTeam: { type: Array, default: () => [] },
    defaultSections: { type: Array, default: () => [] },
    defaultDayPlans: { type: Array, default: () => [] },
});

const page = usePage();
const perms = computed(() => page.props.auth?.permissions?.camp_playbooks ?? {});
const canCreate = computed(() => !!perms.value.create);
const canUpdate = computed(() => !!perms.value.update);
const isEdit = computed(() => props.mode === 'edit' && !!props.item?.id);

const sectionLabels = {
    bevers: 'Bevers',
    dolfijnen: 'Dolfijnen',
    zeeverkenners: 'Zeeverkenners',
    wilde_vaart: 'Wilde Vaart',
    loodsen: 'Loodsen',
    bestuur: 'Bestuur',
};
const speltakLabel = computed(() => sectionLabels[page.props.auth?.active_section] || 'Dolfijnen');

const source = props.item || props.copyItem || {};
const initialSections = source.playbook_sections || props.defaultSections || [];
const defaultEmergencyContacts = () => ({
    huisartsen: { name: '', address: '', postal_code: '', city: '', phone_010: '', website: '', extra_info: '' },
    ziekenhuizen: { name: '', address: '', postal_code: '', city: '', phone_010: '', website: '', extra_info: '' },
    tandartsen: { name: '', address: '', postal_code: '', city: '', phone_010: '', website: '', extra_info: '' },
});
function normalizeEmergencyContacts(raw) {
    const defaults = defaultEmergencyContacts();
    const value = raw && typeof raw === 'object' ? raw : {};
    for (const key of Object.keys(defaults)) {
        const entry = value[key] && typeof value[key] === 'object' ? value[key] : {};
        defaults[key] = {
            name: String(entry.name ?? ''),
            address: String(entry.address ?? ''),
            postal_code: String(entry.postal_code ?? ''),
            city: String(entry.city ?? ''),
            phone_010: String(entry.phone_010 ?? ''),
            website: String(entry.website ?? ''),
            extra_info: String(entry.extra_info ?? ''),
        };
    }
    return defaults;
}
function normalizeDayPlans(raw) {
    const incoming = Array.isArray(raw) ? raw : [];
    if (!incoming.length) {
        return JSON.parse(JSON.stringify(props.defaultDayPlans || []));
    }

    return incoming.map((day) => ({
        day_label: String(day?.day_label ?? ''),
        daywatch_ids: Array.isArray(day?.daywatch_ids) ? day.daywatch_ids.map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0) : [],
        planning_rows: Array.isArray(day?.planning_rows) && day.planning_rows.length
            ? day.planning_rows.map((row) => ({
                time: String(row?.time ?? ''),
                program: String(row?.program ?? ''),
                game: String(row?.game ?? ''),
                needs: String(row?.needs ?? ''),
            }))
            : [{ time: '', program: '', game: '', needs: '' }],
        game_explanation: String(day?.game_explanation ?? ''),
    }));
}
const form = useForm({
    camp_year: source.camp_year || new Date().getFullYear(),
    title: source.title || '',
    camp_location: source.camp_location === 'clubhuis' ? 'clubhuis' : 'fram',
    camp_place: source.camp_place || '',
    camp_dates: source.camp_dates || '',
    emergency_contacts: normalizeEmergencyContacts(source.emergency_contacts),
    day_plans: normalizeDayPlans(source.day_plans),
    playbook_sections: JSON.parse(JSON.stringify(initialSections)),
});
const activeSectionIndex = ref(0);
const activeSection = computed(() => form.playbook_sections[activeSectionIndex.value] || null);

function submit() {
    if (isEdit.value) {
        if (!canUpdate.value) return;
        form.patch(route('camp-playbooks.update', props.item.id));
        return;
    }
    if (!canCreate.value) return;
    form.post(route('camp-playbooks.store'));
}

function destroyItem() {
    if (!isEdit.value || !canUpdate.value) return;
    if (!confirm(`Draaiboek "${props.item.title}" verwijderen?`)) return;
    router.delete(route('camp-playbooks.destroy', props.item.id));
}

function copyItem() {
    if (!isEdit.value || !canCreate.value) return;
    router.post(route('camp-playbooks.copy', props.item.id));
}

function setCampLocation(location) {
    form.camp_location = location === 'clubhuis' ? 'clubhuis' : 'fram';
}

function isAlgemeenSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'algemeen';
}

function isHulpdienstenSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'hulpdiensten';
}

function isPlanningPerDagSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'planning per dag';
}

function addPlanningDay() {
    form.day_plans.push({
        day_label: `Dag ${form.day_plans.length + 1}`,
        daywatch_ids: [],
        planning_rows: [{ time: '', program: '', game: '', needs: '' }],
        game_explanation: '',
    });
}

function removePlanningDay(index) {
    if (!Array.isArray(form.day_plans) || form.day_plans.length <= 1) return;
    form.day_plans.splice(index, 1);
}

function addPlanningRow(dayIndex) {
    const day = form.day_plans?.[dayIndex];
    if (!day) return;
    day.planning_rows.push({ time: '', program: '', game: '', needs: '' });
}

function removePlanningRow(dayIndex, rowIndex) {
    const day = form.day_plans?.[dayIndex];
    if (!day || day.planning_rows.length <= 1) return;
    day.planning_rows.splice(rowIndex, 1);
}

function toggleDaywatch(day, leaderId) {
    const id = Number(leaderId);
    if (!Array.isArray(day.daywatch_ids)) {
        day.daywatch_ids = [];
    }
    const idx = day.daywatch_ids.findIndex((entry) => Number(entry) === id);
    if (idx >= 0) {
        day.daywatch_ids.splice(idx, 1);
    } else {
        day.daywatch_ids.push(id);
    }
}
</script>

<template>
    <Head :title="`${speltakLabel} - ${isEdit ? 'Draaiboek bewerken' : 'Draaiboek toevoegen'}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - {{ isEdit ? 'Draaiboek bewerken' : 'Draaiboek toevoegen' }}</h2>
                <Link :href="route('camp-playbooks.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                    <ArrowUturnLeftIcon class="h-5 w-5" />
                </Link>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark" @submit.prevent="submit">
            <div class="grid gap-3 sm:grid-cols-1">
                <input v-model="form.camp_year" type="number" min="2020" max="2100" class="rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Jaar" required />
            </div>

            <div class="space-y-3 rounded-xl border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-app-canvas-dark">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="(section, idx) in form.playbook_sections"
                        :key="`playbook-section-${idx}`"
                        type="button"
                        class="rounded-md border px-3 py-1.5 text-sm"
                        :class="idx === activeSectionIndex ? 'border-brand-blue bg-brand-blue/10 text-app-ink dark:text-app-ink-dark' : 'border-app-border bg-white text-app-ink dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark'"
                        @click="activeSectionIndex = idx"
                    >
                        {{ section.title || `Sectie ${idx + 1}` }}
                    </button>
                </div>

                <div v-if="activeSection" class="space-y-2">
                    <h3 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ activeSection.title }}</h3>

                    <div v-if="isAlgemeenSection(activeSection)" class="grid gap-3 sm:grid-cols-2">
                        <div class="space-y-1 sm:col-span-2">
                            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Titel</label>
                            <input
                                v-model="form.title"
                                type="text"
                                class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark"
                                placeholder="Titel (bijv. Pinksterkamp 2026)"
                                required
                            />
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Kamptype</label>
                            <div class="inline-flex items-center rounded-full border border-app-border bg-slate-100 p-1 dark:border-app-border-dark dark:bg-slate-800">
                                <button
                                    type="button"
                                    class="rounded-full px-4 py-1.5 text-xs font-semibold transition"
                                    :class="form.camp_location === 'clubhuis' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink dark:text-app-ink-dark'"
                                    @click="setCampLocation('clubhuis')"
                                >
                                    Clubhuis
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full px-4 py-1.5 text-xs font-semibold transition"
                                    :class="form.camp_location === 'fram' ? 'bg-brand-blue text-white shadow-sm' : 'text-app-ink dark:text-app-ink-dark'"
                                    @click="setCampLocation('fram')"
                                >
                                    Fram
                                </button>
                            </div>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Plaats</label>
                            <input
                                v-model="form.camp_place"
                                type="text"
                                class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark"
                                placeholder="Bijv. Zwolle"
                            />
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Datum</label>
                            <input
                                v-model="form.camp_dates"
                                type="text"
                                class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark"
                                placeholder="Bijv. 17-05-2026 t/m 20-05-2026"
                            />
                        </div>
                    </div>

                    <div v-if="isPlanningPerDagSection(activeSection)" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Dagen</h4>
                            <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="addPlanningDay">
                                Dag toevoegen
                            </button>
                        </div>

                        <div
                            v-for="(day, dayIdx) in form.day_plans"
                            :key="`planning-day-${dayIdx}`"
                            class="rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <input
                                    v-model="day.day_label"
                                    type="text"
                                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                    placeholder="Bijv. Dag 1 - Vrijdag"
                                />
                                <button type="button" class="btn-action-delete" title="Dag verwijderen" @click="removePlanningDay(dayIdx)">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="mt-3 space-y-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Dagwacht (leidingteam)</p>
                                <div class="flex flex-wrap gap-2">
                                    <label
                                        v-for="leader in props.leaderTeam"
                                        :key="`daywatch-${dayIdx}-${leader.id}`"
                                        class="inline-flex items-center gap-1 rounded border border-app-border px-2 py-1 text-xs text-app-ink dark:border-app-border-dark dark:text-app-ink-dark"
                                    >
                                        <input
                                            :checked="day.daywatch_ids?.includes(leader.id)"
                                            type="checkbox"
                                            @change="toggleDaywatch(day, leader.id)"
                                        />
                                        {{ leader.name }}
                                    </label>
                                    <span v-if="!(props.leaderTeam || []).length" class="text-xs text-app-muted dark:text-app-muted-dark">Geen leidingteam gevonden in deze speltak.</span>
                                </div>
                            </div>

                            <div class="mt-3 overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                                <table class="w-full min-w-[680px] text-sm">
                                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                                        <tr>
                                            <th class="px-2 py-2 text-left">Tijden</th>
                                            <th class="px-2 py-2 text-left">Programma</th>
                                            <th class="px-2 py-2 text-left">Spel</th>
                                            <th class="px-2 py-2 text-left">Benodigdheden</th>
                                            <th class="px-2 py-2 text-left">Actie</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                                        <tr v-for="(row, rowIdx) in day.planning_rows" :key="`planning-row-${dayIdx}-${rowIdx}`">
                                            <td class="px-2 py-2"><input v-model="row.time" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="08:00 - 09:00" /></td>
                                            <td class="px-2 py-2"><input v-model="row.program" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Programma" /></td>
                                            <td class="px-2 py-2"><input v-model="row.game" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Spel" /></td>
                                            <td class="px-2 py-2"><input v-model="row.needs" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Benodigdheden" /></td>
                                            <td class="px-2 py-2">
                                                <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="removePlanningRow(dayIdx, rowIdx)">
                                                    <TrashIcon class="h-5 w-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-2">
                                <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="addPlanningRow(dayIdx)">
                                    Rij toevoegen
                                </button>
                            </div>

                            <div class="mt-3 space-y-1">
                                <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Speluitleg</label>
                                <textarea v-model="day.game_explanation" rows="4" class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Leg spelregels, doelen en aandachtspunten uit..." />
                            </div>
                        </div>
                    </div>

                    <div v-if="isHulpdienstenSection(activeSection)" class="space-y-3">
                        <div
                            v-for="(label, key) in { huisartsen: 'Huisartsen', ziekenhuizen: 'Ziekenhuizen', tandartsen: 'Tandartsen' }"
                            :key="`emergency-${key}`"
                            class="rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900"
                        >
                            <h4 class="mb-2 text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ label }}</h4>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <input v-model="form.emergency_contacts[key].name" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Naam" />
                                <input v-model="form.emergency_contacts[key].address" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Adres" />
                                <input v-model="form.emergency_contacts[key].postal_code" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Postcode" />
                                <input v-model="form.emergency_contacts[key].city" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Plaats" />
                                <input v-model="form.emergency_contacts[key].phone_010" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="010 nummer" />
                                <input v-model="form.emergency_contacts[key].website" type="text" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Site" />
                                <textarea v-model="form.emergency_contacts[key].extra_info" rows="3" class="rounded border border-app-border bg-white px-3 py-2 text-sm text-black sm:col-span-2 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Extra informatie" />
                            </div>
                        </div>
                    </div>

                    <textarea
                        v-else
                        v-model="activeSection.content"
                        rows="14"
                        class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark"
                        placeholder="Werk deze sectie van het draaiboek uit..."
                    />
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-app-border pt-3">
                <button type="submit" class="btn-action-save" :disabled="form.processing" title="Opslaan" aria-label="Opslaan">
                    <DocumentCheckIcon class="h-5 w-5" />
                </button>
                <button v-if="isEdit && canCreate" type="button" class="btn-action-save" title="Kopie maken" aria-label="Kopie maken" @click="copyItem">
                    <DocumentDuplicateIcon class="h-5 w-5" />
                </button>
                <button v-if="isEdit && canUpdate" type="button" class="btn-action-delete" title="Verwijderen" aria-label="Verwijderen" @click="destroyItem">
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
