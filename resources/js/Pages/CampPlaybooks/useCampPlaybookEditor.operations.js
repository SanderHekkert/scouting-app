import { nextTick, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { withSaveRedirect } from '@/utils/saveForm';

export function createCampPlaybookEditorOperations(props, core) {
    const {
        source,
        canCreate,
        canUpdate,
        isEdit,
        page,
        form,
        coverPreviewUrl,
        isFramCamp,
        vinHeaderValues,
        vinMemberOptions,
        responsibleOptions,
        activeSectionIndex,
        activeSection,
        deleteModalOpen,
        draggedPlanningRow,
        toIsoDate,
        composeCampDateRange,
        isoDateEntriesBetween,
        defaultPlanningRows,
        normalizeResponsibleNames,
        normalizeVinMembers,
        normalizeWholeMinutes,
        emptyEmergencyContact,
        ensureVaarschemaSectionForLocation,
    } = core;

    function isDagverloopTask(taskTitle) {
        return String(taskTitle ?? '').trim().toLowerCase() === 'dagverloop';
    }

    function syncTaskDistributionRowsWithTaskExplanationTitles() {
        const titles = (form.task_explanation_items || [])
            .map((item) => String(item?.title ?? '').trim())
            .filter((title) => title !== '');

        const existingRows = Array.isArray(form.task_distribution_rows) ? form.task_distribution_rows : [];
        const existingByTask = new Map(
            existingRows
                .map((row) => [String(row?.task ?? '').trim().toLowerCase(), row])
                .filter(([task]) => task !== '')
        );

        form.task_distribution_rows = titles.map((title, index) => {
            const existingByIndex = existingRows[index];
            const existing = existingByIndex && String(existingByIndex?.task ?? '').trim() !== ''
                ? existingByIndex
                : existingByTask.get(title.toLowerCase());
            const isLockedDagverloop = isDagverloopTask(title);

            return {
                task: title,
                responsibles: isLockedDagverloop
                    ? ['Dagwacht']
                    : normalizeResponsibleNames(existing?.responsibles ?? existing?.responsible ?? []),
            };
        });
    }

    function syncMonsterrolBoardingDates(previousStart = '', previousEnd = '') {
        if (!isFramCamp.value) {
            return;
        }

        const nextStart = String(form.camp_date_start || '');
        const nextEnd = String(form.camp_date_end || '');
        const sections = ['crew', 'speltak'];

        sections.forEach((section) => {
            const rows = Array.isArray(form.monsterrol_rows?.[section]) ? form.monsterrol_rows[section] : [];
            rows.forEach((row) => {
                const currentOnBoard = String(row?.on_board ?? '');
                const currentOffBoard = String(row?.off_board ?? '');

                if (currentOnBoard === '' || currentOnBoard === String(previousStart || '')) {
                    row.on_board = nextStart;
                }
                if (currentOffBoard === '' || currentOffBoard === String(previousEnd || '')) {
                    row.off_board = nextEnd;
                }
            });
        });
    }

    function syncCorveeRowsWithCampDates() {
        const dateEntries = isoDateEntriesBetween(form.camp_date_start, form.camp_date_end);
        if (!dateEntries.length) {
            return;
        }

        const existingRowsByIsoDate = new Map(
            (Array.isArray(form.corvee_rows) ? form.corvee_rows : [])
                .map((row) => [toIsoDate(row?.date), row])
                .filter(([iso]) => iso !== '')
        );

        form.corvee_rows = dateEntries.map((entry) => {
            const existing = existingRowsByIsoDate.get(entry.iso);
            return {
                day: String(existing?.day ?? '').trim() || entry.dayLabel,
                date: String(existing?.date ?? '').trim() || entry.dateLabel,
                daywatch: String(existing?.daywatch ?? ''),
                dienstvin: String(existing?.dienstvin ?? ''),
                dekhuis: String(existing?.dekhuis ?? ''),
                achteronder_en_dekken: String(existing?.achteronder_en_dekken ?? ''),
                wc_en_klusjes: String(existing?.wc_en_klusjes ?? ''),
            };
        });
    }

    function dayLabelFromDateEntry(entry, index) {
        return `Dag ${index + 1} - ${entry.dayLabel} (${entry.dateLabel})`;
    }

    function isoDateFromDayLabel(dayLabel) {
        const text = String(dayLabel ?? '').trim();
        if (!text) return '';
        const isoMatch = text.match(/(\d{4}-\d{2}-\d{2})/);
        if (isoMatch) {
            return toIsoDate(isoMatch[1]);
        }
        const nlMatch = text.match(/(\d{2}-\d{2}-\d{4})/);
        if (nlMatch) {
            return toIsoDate(nlMatch[1]);
        }
        return '';
    }

    function syncDayPlansWithCampDates() {
        const dateEntries = isoDateEntriesBetween(form.camp_date_start, form.camp_date_end);
        if (!dateEntries.length) {
            return;
        }

        const existingPlans = Array.isArray(form.day_plans) ? form.day_plans : [];
        const existingByIsoDate = new Map(
            existingPlans
                .map((day) => [isoDateFromDayLabel(day?.day_label), day])
                .filter(([iso]) => iso !== '')
        );

        form.day_plans = dateEntries.map((entry, index) => {
            const existingByDate = existingByIsoDate.get(entry.iso);
            const existingByIndex = existingPlans[index];
            const existing = existingByDate || existingByIndex || {};

            return {
                day_label: dayLabelFromDateEntry(entry, index),
                daywatch_ids: Array.isArray(existing?.daywatch_ids)
                    ? existing.daywatch_ids.map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0)
                    : [],
                planning_rows: Array.isArray(existing?.planning_rows) && existing.planning_rows.length
                    ? existing.planning_rows.map((row) => ({
                        time: String(row?.time ?? ''),
                        program: String(row?.program ?? ''),
                        game: String(row?.game ?? ''),
                        needs: String(row?.needs ?? ''),
                    }))
                    : defaultPlanningRows(),
                game_explanation: String(existing?.game_explanation ?? ''),
            };
        });
    }

    function normalizedDaywatchIds(day) {
        if (!Array.isArray(day?.daywatch_ids)) {
            return [];
        }
        return Array.from(new Set(day.daywatch_ids.map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0)));
    }

    function daywatchNameById(leaderId) {
        return (props.leaderTeam || []).find((leader) => Number(leader?.id) === Number(leaderId))?.name || `Leiding #${leaderId}`;
    }

    function firstNameOnly(name) {
        const text = String(name ?? '').trim();
        if (!text) return '';
        return text.split(/\s+/)[0] || text;
    }

    function syncCorveeDaywatchFromPlanning() {
        const daywatchByIsoDate = new Map(
            (Array.isArray(form.day_plans) ? form.day_plans : [])
                .map((day) => {
                    const isoDate = isoDateFromDayLabel(day?.day_label);
                    const names = normalizedDaywatchIds(day)
                        .map((id) => firstNameOnly(daywatchNameById(id)))
                        .filter((name) => String(name).trim() !== '');
                    return [isoDate, names.join(', ')];
                })
                .filter(([isoDate]) => isoDate !== '')
        );

        form.corvee_rows = (Array.isArray(form.corvee_rows) ? form.corvee_rows : []).map((row) => {
            const isoDate = toIsoDate(row?.date);
            return {
                ...row,
                daywatch: daywatchByIsoDate.get(isoDate) || '',
            };
        });
    }

    function emptyVaarschemaRow() {
        return {
            date: '',
            from: '',
            to: '',
            depart_at: '',
            arrive_at: '',
            tide_margin_minutes: '0',
        };
    }

    function syncVaarschemaRowsWithCampDates(previousStart = '', previousEnd = '') {
        if (!Array.isArray(form.vaarschema_rows)) {
            form.vaarschema_rows = [];
        }

        while (form.vaarschema_rows.length < 2) {
            form.vaarschema_rows.push(emptyVaarschemaRow());
        }

        const start = String(form.camp_date_start || '');
        const end = String(form.camp_date_end || '');
        const rowOne = form.vaarschema_rows[0] || emptyVaarschemaRow();
        const rowTwo = form.vaarschema_rows[1] || emptyVaarschemaRow();

        if (String(rowOne.date || '') === '' || String(rowOne.date || '') === String(previousStart || '')) {
            rowOne.date = start;
        }
        if (String(rowTwo.date || '') === '' || String(rowTwo.date || '') === String(previousEnd || '')) {
            rowTwo.date = end;
        }

        if (String(rowOne.from || '').trim() === '') {
            rowOne.from = 'Koedood';
        }
        if (String(rowTwo.to || '').trim() === '') {
            rowTwo.to = 'Koedood';
        }

        form.vaarschema_rows[0] = rowOne;
        form.vaarschema_rows[1] = rowTwo;
    }

    function ensureVinMatrixColumns() {
        const headers = vinHeaderValues.value;
        if (!Array.isArray(form.vinindeling_rows)) return;
        form.vinindeling_rows.forEach((row) => {
            if (!Array.isArray(row.vins)) {
                row.vins = [];
            }
            row.vins = headers.map((header, index) => {
                const existing = row.vins[index] || {};
                return {
                    vin_name: header,
                    member_names: normalizeVinMembers(existing.member_names ?? []),
                };
            });
        });
    }

    function autosizeTextarea(target) {
        if (!(target instanceof HTMLTextAreaElement)) {
            return;
        }
        target.style.height = 'auto';
        target.style.height = `${target.scrollHeight}px`;
    }

    function autosizeSpeluitlegTextareas() {
        if (typeof document === 'undefined') {
            return;
        }
        document.querySelectorAll('[data-speluitleg-autoresize="true"]').forEach((element) => autosizeTextarea(element));
    }

    function onCoverPhotoSelected(event) {
        const [file] = event?.target?.files || [];
        form.cover_photo = file ?? null;
        if (file) {
            form.cover_photo_remove = false;
            form.existing_cover_photo_url = '';
            const reader = new FileReader();
            reader.onload = () => {
                coverPreviewUrl.value = typeof reader.result === 'string' ? reader.result : '';
            };
            reader.readAsDataURL(file);
        }
    }

    function removeCoverPhoto() {
        form.cover_photo = null;
        form.cover_photo_remove = true;
        form.existing_cover_photo_url = '';
        coverPreviewUrl.value = '';
    }

    function submit(action = 'save') {
        const normalizedAction = action === 'submit' ? 'submit' : 'save';
        form.transform((data) => withSaveRedirect({
            ...data,
            camp_dates: composeCampDateRange(data.camp_date_start, data.camp_date_end),
            action: normalizedAction,
            ...(isEdit.value ? { _method: 'patch' } : {}),
        }, page.props.returnUrl));

        const options = {
            forceFormData: true,
            preserveScroll: false,
            onFinish: () => form.transform((data) => data),
        };

        if (isEdit.value) {
            if (!canUpdate.value) return;
            form.post(route('camp-playbooks.update', props.item.id), options);
            return;
        }
        if (!canCreate.value) return;
        form.post(route('camp-playbooks.store'), options);
    }

    function destroyItem() {
        if (!isEdit.value || !canUpdate.value) return;
        deleteModalOpen.value = false;
        router.delete(route('camp-playbooks.destroy', props.item.id));
    }

    function setCampLocation(location) {
        form.camp_location = location === 'clubhuis' ? 'clubhuis' : 'fram';
        ensureVaarschemaSectionForLocation();
    }

    function addPlanningDay() {
        form.day_plans.push({
            day_label: `Dag ${form.day_plans.length + 1}`,
            daywatch_ids: [],
            planning_rows: defaultPlanningRows(),
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

    function startPlanningRowDrag(dayIndex, rowIndex, event) {
        draggedPlanningRow.value = { dayIndex, rowIndex };
        if (event?.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', `${dayIndex}:${rowIndex}`);
        }
    }

    function allowPlanningRowDrop(event) {
        event?.preventDefault();
        if (event?.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
    }

    function dropPlanningRow(dayIndex, rowIndex, event) {
        allowPlanningRowDrop(event);
        const source = draggedPlanningRow.value;
        if (!source || source.dayIndex !== dayIndex) {
            draggedPlanningRow.value = null;
            return;
        }

        const rows = form.day_plans?.[dayIndex]?.planning_rows;
        if (!Array.isArray(rows)) {
            draggedPlanningRow.value = null;
            return;
        }

        const from = Number(source.rowIndex);
        const to = Number(rowIndex);
        if (!Number.isInteger(from) || !Number.isInteger(to) || from < 0 || to < 0 || from >= rows.length || to >= rows.length || from === to) {
            draggedPlanningRow.value = null;
            return;
        }

        const [moved] = rows.splice(from, 1);
        rows.splice(to, 0, moved);
        draggedPlanningRow.value = { dayIndex, rowIndex: to };
    }

    function endPlanningRowDrag() {
        draggedPlanningRow.value = null;
    }

    function availableDaywatchOptions(day) {
        const selected = new Set(normalizedDaywatchIds(day));
        return (props.leaderTeam || []).filter((leader) => !selected.has(Number(leader?.id)));
    }

    function addDaywatchFromSelect(day, event) {
        const selectedId = Number(event?.target?.value);
        if (!Number.isFinite(selectedId) || selectedId <= 0) return;
        day.daywatch_ids = [...normalizedDaywatchIds(day), selectedId];
        event.target.value = '';
    }

    function removeDaywatch(day, leaderId) {
        const target = Number(leaderId);
        day.daywatch_ids = normalizedDaywatchIds(day).filter((id) => id !== target);
    }

    function addVaarschemaRow() {
        form.vaarschema_rows.push(emptyVaarschemaRow());
    }

    function removeVaarschemaRow(index) {
        if (!Array.isArray(form.vaarschema_rows) || form.vaarschema_rows.length <= 1) return;
        form.vaarschema_rows.splice(index, 1);
    }

    function addMonsterrolRow(type) {
        if (!['crew', 'speltak'].includes(type)) return;
        form.monsterrol_rows[type].push({
            first_name: '',
            last_name: '',
            functie: '',
            on_board: isFramCamp.value ? String(form.camp_date_start || '') : '',
            off_board: isFramCamp.value ? String(form.camp_date_end || '') : '',
        });
    }

    function removeMonsterrolRow(type, index) {
        if (!['crew', 'speltak'].includes(type)) return;
        if (!Array.isArray(form.monsterrol_rows[type]) || form.monsterrol_rows[type].length <= 1) return;
        form.monsterrol_rows[type].splice(index, 1);
    }

    function availableResponsibleOptions(row) {
        if (isDagverloopTask(row?.task)) {
            return [];
        }
        const selected = new Set(normalizeResponsibleNames(row?.responsibles ?? []));
        return responsibleOptions.value.filter((name) => !selected.has(name));
    }

    function addResponsibleToTask(row, event) {
        if (isDagverloopTask(row?.task)) {
            row.responsibles = ['Dagwacht'];
            if (event?.target) {
                event.target.value = '';
            }
            return;
        }
        const selected = String(event?.target?.value ?? '').trim();
        if (!selected) return;
        row.responsibles = normalizeResponsibleNames([...(Array.isArray(row?.responsibles) ? row.responsibles : []), selected]);
        event.target.value = '';
    }

    function removeResponsibleFromTask(row, name) {
        if (isDagverloopTask(row?.task)) {
            row.responsibles = ['Dagwacht'];
            return;
        }
        const target = String(name ?? '').trim();
        row.responsibles = normalizeResponsibleNames((row?.responsibles || []).filter((entry) => String(entry).trim() !== target));
    }

    function addTaskExplanationItem() {
        form.task_explanation_items.push({
            title: '',
            bullets: [''],
        });
    }

    function removeTaskExplanationItem(index) {
        if (!Array.isArray(form.task_explanation_items) || form.task_explanation_items.length <= 1) return;
        form.task_explanation_items.splice(index, 1);
    }

    function addTaskBullet(taskIndex) {
        const item = form.task_explanation_items?.[taskIndex];
        if (!item) return;
        if (!Array.isArray(item.bullets)) {
            item.bullets = [];
        }
        item.bullets.push('');
    }

    function removeTaskBullet(taskIndex, bulletIndex) {
        const item = form.task_explanation_items?.[taskIndex];
        if (!item || !Array.isArray(item.bullets) || item.bullets.length <= 1) return;
        item.bullets.splice(bulletIndex, 1);
    }

    function addGeneralAgreementItem() {
        form.general_agreements_items.push({
            title: '',
            bullets: [''],
        });
    }

    function removeGeneralAgreementItem(index) {
        if (!Array.isArray(form.general_agreements_items) || form.general_agreements_items.length <= 1) return;
        form.general_agreements_items.splice(index, 1);
    }

    function addGeneralAgreementBullet(itemIndex) {
        const item = form.general_agreements_items?.[itemIndex];
        if (!item) return;
        if (!Array.isArray(item.bullets)) {
            item.bullets = [];
        }
        item.bullets.push('');
    }

    function removeGeneralAgreementBullet(itemIndex, bulletIndex) {
        const item = form.general_agreements_items?.[itemIndex];
        if (!item || !Array.isArray(item.bullets) || item.bullets.length <= 1) return;
        item.bullets.splice(bulletIndex, 1);
    }

    function addSpeltakAgreementItem() {
        form.speltak_agreements_items.push({
            title: '',
            bullets: [''],
        });
    }

    function removeSpeltakAgreementItem(index) {
        if (!Array.isArray(form.speltak_agreements_items) || form.speltak_agreements_items.length <= 1) return;
        form.speltak_agreements_items.splice(index, 1);
    }

    function addSpeltakAgreementBullet(itemIndex) {
        const item = form.speltak_agreements_items?.[itemIndex];
        if (!item) return;
        if (!Array.isArray(item.bullets)) {
            item.bullets = [];
        }
        item.bullets.push('');
    }

    function removeSpeltakAgreementBullet(itemIndex, bulletIndex) {
        const item = form.speltak_agreements_items?.[itemIndex];
        if (!item || !Array.isArray(item.bullets) || item.bullets.length <= 1) return;
        item.bullets.splice(bulletIndex, 1);
    }

    function addSpeltakHygieneRow() {
        form.speltak_hygiene_rows.push({
            topic: '',
            jerrycans: '',
            kraanwater: '',
            buitenboordwater: '',
            desinfectans: '',
        });
    }

    function removeSpeltakHygieneRow(index) {
        if (!Array.isArray(form.speltak_hygiene_rows) || form.speltak_hygiene_rows.length <= 1) return;
        form.speltak_hygiene_rows.splice(index, 1);
    }

    function addVinindelingRow() {
        const headers = vinHeaderValues.value;
        form.vinindeling_rows.push({
            role: 'Vinlid',
            vins: headers.map((header) => ({ vin_name: header, member_names: [] })),
        });
    }

    function removeVinindelingRow(index) {
        if (!Array.isArray(form.vinindeling_rows) || form.vinindeling_rows.length <= 1) return;
        form.vinindeling_rows.splice(index, 1);
    }

    function updateVinHeader(vinIndex, value) {
        const incoming = String(value ?? '').trim();
        const fallback = ['', '', ''];
        const header = incoming || fallback[vinIndex] || `Vin ${vinIndex + 1}`;
        if (!Array.isArray(form.vinindeling_rows)) return;
        form.vinindeling_rows.forEach((row) => {
            if (!Array.isArray(row.vins)) {
                row.vins = [];
            }
            while (row.vins.length <= vinIndex) {
                row.vins.push({ vin_name: '', member_names: [] });
            }
            row.vins[vinIndex].vin_name = header;
        });
    }

    function vinCellSelection(row, vinIndex) {
        const members = normalizeVinMembers(row?.vins?.[vinIndex]?.member_names ?? []);
        return members[0] || '';
    }

    function selectedVinMembers(excludeRowIdx = null, excludeVinIdx = null) {
        const selected = new Set();
        if (!Array.isArray(form.vinindeling_rows)) {
            return selected;
        }

        form.vinindeling_rows.forEach((row, rowIdx) => {
            const vins = Array.isArray(row?.vins) ? row.vins : [];
            vins.forEach((vin, vinIdx) => {
                if (excludeRowIdx === rowIdx && excludeVinIdx === vinIdx) {
                    return;
                }
                const value = normalizeVinMembers(vin?.member_names ?? [])[0] || '';
                if (!value || value === 'n.v.t.') {
                    return;
                }
                selected.add(value);
            });
        });

        return selected;
    }

    function availableVinCellOptions(rowIdx, vinIdx) {
        const taken = selectedVinMembers(rowIdx, vinIdx);
        const currentRow = form.vinindeling_rows?.[rowIdx];
        const currentValue = vinCellSelection(currentRow, vinIdx);

        return vinMemberOptions.value.filter((name) => name === currentValue || !taken.has(name));
    }

    function setVinCellSelection(row, vinIndex, value) {
        const selected = String(value ?? '').trim();
        if (!Array.isArray(row?.vins)) {
            row.vins = [];
        }
        while (row.vins.length <= vinIndex) {
            row.vins.push({ vin_name: vinHeaderValues.value[vinIndex] || `Vin ${vinIndex + 1}`, member_names: [] });
        }
        row.vins[vinIndex].vin_name = vinHeaderValues.value[vinIndex] || row.vins[vinIndex].vin_name || `Vin ${vinIndex + 1}`;
        row.vins[vinIndex].member_names = selected ? [selected] : [];
    }

    function addCorveeRow() {
        form.corvee_rows.push({
            day: '',
            date: '',
            daywatch: '',
            dienstvin: '',
            dekhuis: '',
            achteronder_en_dekken: '',
            wc_en_klusjes: '',
        });
    }

    function removeCorveeRow(index) {
        if (!Array.isArray(form.corvee_rows) || form.corvee_rows.length <= 1) return;
        form.corvee_rows.splice(index, 1);
    }

    function addEmergencyContactRow(category) {
        if (!['huisartsen', 'ziekenhuizen', 'tandartsen'].includes(String(category))) return;
        if (!Array.isArray(form.emergency_contacts?.[category])) {
            form.emergency_contacts[category] = [];
        }
        form.emergency_contacts[category].push(emptyEmergencyContact());
    }

    function removeEmergencyContactRow(category, index) {
        if (!['huisartsen', 'ziekenhuizen', 'tandartsen'].includes(String(category))) return;
        if (!Array.isArray(form.emergency_contacts?.[category]) || form.emergency_contacts[category].length <= 1) return;
        form.emergency_contacts[category].splice(index, 1);
    }

    function openDeleteModal() {
        if (!isEdit.value || !canUpdate.value) return;
        deleteModalOpen.value = true;
    }

    function closeDeleteModal() {
        deleteModalOpen.value = false;
    }

    watch(
        () => (form.task_explanation_items || []).map((item) => String(item?.title ?? '').trim()),
        () => syncTaskDistributionRowsWithTaskExplanationTitles(),
        { deep: true, immediate: true }
    );

    watch(
        () => [form.camp_location, form.camp_date_start, form.camp_date_end],
        ([, currentStart, currentEnd], previousValues = []) => {
            const [, previousStart, previousEnd] = Array.isArray(previousValues) ? previousValues : [];
            syncMonsterrolBoardingDates(String(previousStart || ''), String(previousEnd || ''));
            syncCorveeRowsWithCampDates();
            syncDayPlansWithCampDates();
            syncCorveeDaywatchFromPlanning();
            syncVaarschemaRowsWithCampDates(String(previousStart || ''), String(previousEnd || ''));
            if (String(currentStart || '') !== String(previousStart || '') || String(currentEnd || '') !== String(previousEnd || '')) {
                form.camp_dates = composeCampDateRange(currentStart, currentEnd);
            }
        },
        { immediate: true }
    );

    watch(
        () => (form.day_plans || []).map((day) => ({
            day_label: String(day?.day_label ?? ''),
            daywatch_ids: normalizedDaywatchIds(day),
        })),
        () => syncCorveeDaywatchFromPlanning(),
        { deep: true }
    );

    watch(
        () => (form.vinindeling_rows || []).length,
        () => ensureVinMatrixColumns(),
        { immediate: true }
    );

    watch(
        () => (form.day_plans || []).map((day) => String(day?.game_explanation ?? '')).join('|'),
        () => nextTick(() => autosizeSpeluitlegTextareas()),
        { immediate: true }
    );

    onMounted(() => {
        nextTick(() => autosizeSpeluitlegTextareas());
    });

    watch(
        () => activeSection.value?.title,
        (title) => {
            if (String(title ?? '').trim().toLowerCase() !== 'planning per dag') {
                return;
            }
            nextTick(() => autosizeSpeluitlegTextareas());
        }
    );

    return {
        submit,
        destroyItem,
        openDeleteModal,
        closeDeleteModal,
        setCampLocation,
        onCoverPhotoSelected,
        removeCoverPhoto,
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
    };
}
