<script setup>
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CampPlaybookCorveeSection from '@/Pages/CampPlaybooks/Partials/CampPlaybookCorveeSection.vue';
import CampPlaybookEmergencyContactsSection from '@/Pages/CampPlaybooks/Partials/CampPlaybookEmergencyContactsSection.vue';
import CampPlaybookGeneralSection from '@/Pages/CampPlaybooks/Partials/CampPlaybookGeneralSection.vue';
import CampPlaybookGeneralAgreementsSection from '@/Pages/CampPlaybooks/Partials/CampPlaybookGeneralAgreementsSection.vue';
import CampPlaybookMonsterrolSection from '@/Pages/CampPlaybooks/Partials/CampPlaybookMonsterrolSection.vue';
import CampPlaybookPlanningSection from '@/Pages/CampPlaybooks/Partials/CampPlaybookPlanningSection.vue';
import CampPlaybookSectionTabs from '@/Pages/CampPlaybooks/Partials/CampPlaybookSectionTabs.vue';
import CampPlaybookSpeltakAgreementsSection from '@/Pages/CampPlaybooks/Partials/CampPlaybookSpeltakAgreementsSection.vue';
import CampPlaybookTaskDistributionSection from '@/Pages/CampPlaybooks/Partials/CampPlaybookTaskDistributionSection.vue';
import CampPlaybookTaskExplanationSection from '@/Pages/CampPlaybooks/Partials/CampPlaybookTaskExplanationSection.vue';
import CampPlaybookVaarschemaSection from '@/Pages/CampPlaybooks/Partials/CampPlaybookVaarschemaSection.vue';
import CampPlaybookVinindelingSection from '@/Pages/CampPlaybooks/Partials/CampPlaybookVinindelingSection.vue';
import { useCampPlaybookEditor } from '@/Pages/CampPlaybooks/useCampPlaybookEditor';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, BellAlertIcon, DocumentCheckIcon, PaperAirplaneIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
    mode: { type: String, default: 'create' },
    item: { type: Object, default: null },
    copyItem: { type: Object, default: null },
    leaderTeam: { type: Array, default: () => [] },
    sectionMembers: { type: Array, default: () => [] },
    defaultSections: { type: Array, default: () => [] },
    defaultTaskDistributionRows: { type: Array, default: () => [] },
    defaultTaskExplanationItems: { type: Array, default: () => [] },
    defaultGeneralAgreementsItems: { type: Array, default: () => [] },
    defaultSpeltakAgreementsItems: { type: Array, default: () => [] },
    defaultSpeltakHygieneRows: { type: Array, default: () => [] },
    defaultVinindelingRows: { type: Array, default: () => [] },
    defaultCorveeRows: { type: Array, default: () => [] },
    defaultMonsterrolRows: { type: Object, default: () => ({}) },
    defaultDayPlans: { type: Array, default: () => [] },
    defaultVaarschemaRows: { type: Array, default: () => [] },
});

const {
    canUpdate,
    isEdit,
    speltakLabel,
    form,
    coverPreviewUrl,
    isFramCamp,
    corveeVinOptions,
    vinHeaderValues,
    activeSectionIndex,
    activeSection,
    deleteModalOpen,
    submit,
    destroyItem,
    openDeleteModal,
    closeDeleteModal,
    statusLabel,
    statusClass,
    setCampLocation,
    onCoverPhotoSelected,
    removeCoverPhoto,
    isAlgemeenSection,
    isHulpdienstenSection,
    isTaakverdelingSection,
    isTaakUitlegSection,
    isAlgemeneAfsprakenSection,
    isSpeltakAfsprakenSection,
    isCorveeroosterSection,
    isVinindelingSection,
    isMonsterrolSection,
    isPlanningPerDagSection,
    isVaarschemaSection,
    isStructuredSection,
    addPlanningDay,
    removePlanningDay,
    addPlanningRow,
    removePlanningRow,
    autosizeTextarea,
    startPlanningRowDrag,
    allowPlanningRowDrop,
    dropPlanningRow,
    endPlanningRowDrag,
    normalizedDaywatchIds,
    daywatchNameById,
    availableDaywatchOptions,
    addDaywatchFromSelect,
    removeDaywatch,
    addVaarschemaRow,
    removeVaarschemaRow,
    addMonsterrolRow,
    removeMonsterrolRow,
    normalizeResponsibleNames,
    isDagverloopTask,
    availableResponsibleOptions,
    addResponsibleToTask,
    removeResponsibleFromTask,
    addTaskExplanationItem,
    removeTaskExplanationItem,
    addTaskBullet,
    removeTaskBullet,
    addGeneralAgreementItem,
    removeGeneralAgreementItem,
    addGeneralAgreementBullet,
    removeGeneralAgreementBullet,
    addSpeltakAgreementItem,
    removeSpeltakAgreementItem,
    addSpeltakAgreementBullet,
    removeSpeltakAgreementBullet,
    addSpeltakHygieneRow,
    removeSpeltakHygieneRow,
    addVinindelingRow,
    removeVinindelingRow,
    updateVinHeader,
    vinCellSelection,
    availableVinCellOptions,
    setVinCellSelection,
    normalizeWholeMinutes,
    addCorveeRow,
    removeCorveeRow,
    addEmergencyContactRow,
    removeEmergencyContactRow,
} = useCampPlaybookEditor(props);
</script>

<template>
    <Head :title="`${speltakLabel} - ${isEdit ? 'Draaiboek bewerken' : 'Draaiboek toevoegen'}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xl font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }} - {{ isEdit ? 'Draaiboek bewerken' : 'Draaiboek toevoegen' }}</h2>
                <div class="flex items-center gap-2">
                    <span v-if="isEdit" :class="['inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs', statusClass(props.item?.status)]">
                        {{ statusLabel(props.item?.status) }}
                        <BellAlertIcon class="h-3.5 w-3.5" />
                    </span>
                    <Link :href="route('camp-playbooks.index')" class="btn-action-back" title="Terug" aria-label="Terug">
                        <ArrowUturnLeftIcon class="h-5 w-5" />
                    </Link>
                </div>
            </div>
        </template>

        <form class="surface-brand-top space-y-4 rounded-xl border border-app-border bg-app-panel p-5 shadow-sm dark:border-brand-blue/30 dark:bg-app-panel-dark" @submit.prevent="submit">
            <div class="space-y-3 rounded-xl border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-app-canvas-dark">
                <CampPlaybookSectionTabs
                    :sections="form.playbook_sections"
                    :active-section-index="activeSectionIndex"
                    @update:active-section-index="activeSectionIndex = $event"
                />

                <div v-if="activeSection" class="space-y-2">
                    <h3 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ activeSection.title }}</h3>

                    <CampPlaybookGeneralSection
                        v-if="isAlgemeenSection(activeSection)"
                        :form="form"
                        :cover-preview-url="coverPreviewUrl"
                        :set-camp-location="setCampLocation"
                        :on-cover-photo-selected="onCoverPhotoSelected"
                        :remove-cover-photo="removeCoverPhoto"
                    />

                    <CampPlaybookMonsterrolSection
                        v-if="isMonsterrolSection(activeSection)"
                        :form="form"
                        :is-fram-camp="isFramCamp"
                        :speltak-label="speltakLabel"
                        :add-monsterrol-row="addMonsterrolRow"
                        :remove-monsterrol-row="removeMonsterrolRow"
                    />

                    <CampPlaybookTaskDistributionSection
                        v-if="isTaakverdelingSection(activeSection)"
                        :rows="form.task_distribution_rows"
                        :normalize-responsible-names="normalizeResponsibleNames"
                        :is-dagverloop-task="isDagverloopTask"
                        :remove-responsible-from-task="removeResponsibleFromTask"
                        :add-responsible-to-task="addResponsibleToTask"
                        :available-responsible-options="availableResponsibleOptions"
                    />

                    <CampPlaybookTaskExplanationSection
                        v-if="isTaakUitlegSection(activeSection)"
                        :items="form.task_explanation_items"
                        :add-task-explanation-item="addTaskExplanationItem"
                        :remove-task-explanation-item="removeTaskExplanationItem"
                        :add-task-bullet="addTaskBullet"
                        :remove-task-bullet="removeTaskBullet"
                    />

                    <CampPlaybookGeneralAgreementsSection
                        v-if="isAlgemeneAfsprakenSection(activeSection)"
                        :items="form.general_agreements_items"
                        :add-general-agreement-item="addGeneralAgreementItem"
                        :remove-general-agreement-item="removeGeneralAgreementItem"
                        :add-general-agreement-bullet="addGeneralAgreementBullet"
                        :remove-general-agreement-bullet="removeGeneralAgreementBullet"
                    />

                    <CampPlaybookSpeltakAgreementsSection
                        v-if="isSpeltakAfsprakenSection(activeSection)"
                        :agreement-items="form.speltak_agreements_items"
                        :hygiene-rows="form.speltak_hygiene_rows"
                        :add-speltak-agreement-item="addSpeltakAgreementItem"
                        :remove-speltak-agreement-item="removeSpeltakAgreementItem"
                        :add-speltak-agreement-bullet="addSpeltakAgreementBullet"
                        :remove-speltak-agreement-bullet="removeSpeltakAgreementBullet"
                        :add-speltak-hygiene-row="addSpeltakHygieneRow"
                        :remove-speltak-hygiene-row="removeSpeltakHygieneRow"
                    />

                    <CampPlaybookCorveeSection
                        v-if="isCorveeroosterSection(activeSection)"
                        :corvee-rows="form.corvee_rows"
                        :corvee-vin-options="corveeVinOptions"
                        @add-row="addCorveeRow"
                        @remove-row="removeCorveeRow"
                    />

                    <CampPlaybookVinindelingSection
                        v-if="isVinindelingSection(activeSection)"
                        :vinindeling-rows="form.vinindeling_rows"
                        :vin-header-values="vinHeaderValues"
                        :speltak-label="speltakLabel"
                        :available-vin-cell-options="availableVinCellOptions"
                        :vin-cell-selection="vinCellSelection"
                        @update-header="updateVinHeader"
                        @set-cell="setVinCellSelection"
                        @remove-row="removeVinindelingRow"
                        @add-row="addVinindelingRow"
                    />

                    <CampPlaybookVaarschemaSection
                        v-if="isVaarschemaSection(activeSection)"
                        :vaarschema-rows="form.vaarschema_rows"
                        :normalize-whole-minutes="normalizeWholeMinutes"
                        @add-row="addVaarschemaRow"
                        @remove-row="removeVaarschemaRow"
                    />

                    <CampPlaybookPlanningSection
                        v-if="isPlanningPerDagSection(activeSection)"
                        :day-plans="form.day_plans"
                        :leader-team="props.leaderTeam || []"
                        :remove-planning-day="removePlanningDay"
                        :normalized-daywatch-ids="normalizedDaywatchIds"
                        :daywatch-name-by-id="daywatchNameById"
                        :remove-daywatch="removeDaywatch"
                        :add-daywatch-from-select="addDaywatchFromSelect"
                        :available-daywatch-options="availableDaywatchOptions"
                        :start-planning-row-drag="startPlanningRowDrag"
                        :allow-planning-row-drop="allowPlanningRowDrop"
                        :drop-planning-row="dropPlanningRow"
                        :end-planning-row-drag="endPlanningRowDrag"
                        :remove-planning-row="removePlanningRow"
                        :add-planning-row="addPlanningRow"
                        :autosize-textarea="autosizeTextarea"
                        :add-planning-day="addPlanningDay"
                    />

                    <CampPlaybookEmergencyContactsSection
                        v-if="isHulpdienstenSection(activeSection)"
                        :emergency-contacts="form.emergency_contacts"
                        @add-row="addEmergencyContactRow"
                        @remove-row="removeEmergencyContactRow"
                    />

                    <textarea
                        v-if="!isStructuredSection(activeSection)"
                        v-model="activeSection.content"
                        rows="14"
                        class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-slate-900 dark:text-app-ink-dark"
                        placeholder="Werk deze sectie van het draaiboek uit..."
                    />
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 border-t border-app-border pt-3">
                <button type="button" class="btn-action-save" :disabled="form.processing" title="Opslaan als concept" aria-label="Opslaan als concept" @click="submit('save')">
                    <DocumentCheckIcon class="h-5 w-5" />
                </button>
                <button type="button" class="btn-action-save" :disabled="form.processing" title="Draaiboek inleveren" aria-label="Draaiboek inleveren" @click="submit('submit')">
                    <PaperAirplaneIcon class="h-5 w-5" />
                </button>
                <button v-if="isEdit && canUpdate" type="button" class="btn-action-delete" title="Verwijderen" aria-label="Verwijderen" @click="openDeleteModal">
                    <TrashIcon class="h-5 w-5" />
                </button>
            </div>
        </form>
    </AuthenticatedLayout>

    <AppConfirmModal
        :show="deleteModalOpen"
        title="Draaiboek verwijderen?"
        :message="`Weet je zeker dat je draaiboek '${props.item?.title || ''}' wilt verwijderen?`"
        confirm-text="Ja, verwijderen"
        cancel-text="Annuleren"
        @close="closeDeleteModal"
        @confirm="destroyItem"
    />
</template>
