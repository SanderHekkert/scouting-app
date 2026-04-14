<script setup>
import { computed, ref, watch } from 'vue';
import AppConfirmModal from '@/Components/AppConfirmModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowUturnLeftIcon, BellAlertIcon, DocumentCheckIcon, PaperAirplaneIcon, PlusIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline';

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
function toIsoDate(value) {
    const trimmed = String(value ?? '').trim();
    if (!trimmed) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) return trimmed;
    const match = trimmed.match(/^(\d{2})-(\d{2})-(\d{4})$/);
    if (match) {
        const [, day, month, year] = match;
        return `${year}-${month}-${day}`;
    }
    return '';
}
function formatIsoToNlDate(value) {
    const iso = String(value ?? '').trim();
    const match = iso.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return '';
    const [, year, month, day] = match;
    return `${day}-${month}-${year}`;
}
function parseCampDateRange(raw) {
    const text = String(raw ?? '').trim();
    if (!text) return { start: '', end: '' };
    if (text.includes('t/m')) {
        const [left, right] = text.split('t/m').map((part) => part.trim());
        return {
            start: toIsoDate(left),
            end: toIsoDate(right),
        };
    }

    const single = toIsoDate(text);
    return { start: single, end: '' };
}
function composeCampDateRange(start, end) {
    const startNl = formatIsoToNlDate(start);
    const endNl = formatIsoToNlDate(end);
    if (startNl && endNl) return `${startNl} t/m ${endNl}`;
    return startNl || endNl || '';
}

function isoDateEntriesBetween(startIso, endIso) {
    const start = toIsoDate(startIso);
    const end = toIsoDate(endIso);
    if (!start || !end) return [];

    const startDate = new Date(`${start}T00:00:00Z`);
    const endDate = new Date(`${end}T00:00:00Z`);
    if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime()) || startDate > endDate) {
        return [];
    }

    const weekdayNames = ['Zondag', 'Maandag', 'Dinsdag', 'Woensdag', 'Donderdag', 'Vrijdag', 'Zaterdag'];
    const entries = [];
    for (let current = new Date(startDate); current <= endDate; current.setUTCDate(current.getUTCDate() + 1)) {
        const year = current.getUTCFullYear();
        const month = String(current.getUTCMonth() + 1).padStart(2, '0');
        const day = String(current.getUTCDate()).padStart(2, '0');
        const iso = `${year}-${month}-${day}`;
        entries.push({
            iso,
            dateLabel: formatIsoToNlDate(iso),
            dayLabel: weekdayNames[current.getUTCDay()],
        });
    }

    return entries;
}
const initialDateRange = parseCampDateRange(source.camp_dates || '');
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

function planningSpeltakLabels() {
    const plural = String(speltakLabel.value || 'Dolfijnen').trim() || 'Dolfijnen';
    const singularByPlural = {
        Bevers: 'Bever',
        Dolfijnen: 'Dolfijn',
        Zeeverkenners: 'Zeeverkenner',
        'Wilde Vaart': 'Wilde Vaarder',
        Loodsen: 'Loods',
        Bestuur: 'Bestuurslid',
    };
    const singular = singularByPlural[plural] || plural;
    return {
        pluralLower: plural.toLocaleLowerCase('nl-NL'),
        singularLower: singular.toLocaleLowerCase('nl-NL'),
    };
}

function defaultPlanningRows() {
    const { pluralLower, singularLower } = planningSpeltakLabels();
    const fallbackRows = [
        { time: '7:30', program: 'Opstaan dagwacht en dienstvin', game: '', needs: '' },
        { time: '8:00', program: `Opstaan ${pluralLower}`, game: '', needs: '' },
        { time: '8:30', program: 'Ontbijt en corvee', game: '', needs: '' },
        { time: '10:00', program: 'Ochtendprogramma', game: '', needs: '' },
        { time: '12:00', program: 'Einde ochtendprogramma', game: '', needs: '' },
        { time: '12:30', program: 'Lunch en corvee', game: '', needs: '' },
        { time: '14:00', program: 'Middagprogramma', game: '', needs: '' },
        { time: '16:00', program: 'Einde middagprogramma', game: '', needs: '' },
        { time: '17:30', program: 'Avondmaaltijd en corvee', game: '', needs: '' },
        { time: '19:00', program: 'Avondprogramma', game: '', needs: '' },
        { time: '20:30', program: `Einde avondprogramma / ${singularLower} naar bed`, game: '', needs: '' },
        { time: '21:00', program: `${pluralLower} stil`, game: '', needs: '' },
        { time: '22:00', program: 'Stafoverleg', game: '', needs: '' },
    ];

    const candidate = Array.isArray(props.defaultDayPlans?.[0]?.planning_rows) && props.defaultDayPlans[0].planning_rows.length
        ? props.defaultDayPlans[0].planning_rows
        : fallbackRows;

    return candidate.map((row) => ({
        time: String(row?.time ?? ''),
        program: String(row?.program ?? ''),
        game: String(row?.game ?? ''),
        needs: String(row?.needs ?? ''),
    }));
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
            : defaultPlanningRows(),
        game_explanation: String(day?.game_explanation ?? ''),
    }));
}
function normalizeTaskDistributionRows(raw) {
    const incoming = Array.isArray(raw) ? raw : [];
    if (!incoming.length) {
        return JSON.parse(JSON.stringify(props.defaultTaskDistributionRows || [{ task: '', responsibles: [] }]));
    }

    return incoming.map((row) => ({
        task: String(row?.task ?? ''),
        responsibles: normalizeResponsibleNames(row?.responsibles ?? row?.responsible ?? []),
    }));
}

function normalizeResponsibleNames(value) {
    const source = Array.isArray(value) ? value : String(value ?? '').split(',');
    return Array.from(
        new Set(
            source
                .map((name) => String(name ?? '').trim())
                .filter((name) => name !== '')
        )
    );
}
function normalizeTaskExplanationItems(raw) {
    const incoming = Array.isArray(raw) ? raw : [];
    if (!incoming.length) {
        return JSON.parse(JSON.stringify(props.defaultTaskExplanationItems || []));
    }

    return incoming.map((item) => ({
        title: String(item?.title ?? ''),
        bullets: Array.isArray(item?.bullets) && item.bullets.length
            ? item.bullets.map((bullet) => String(bullet ?? ''))
            : [''],
    }));
}
function normalizeGeneralAgreementsItems(raw) {
    const incoming = Array.isArray(raw) ? raw : [];
    if (!incoming.length) {
        return JSON.parse(JSON.stringify(props.defaultGeneralAgreementsItems || []));
    }

    return incoming.map((item) => ({
        title: String(item?.title ?? ''),
        bullets: Array.isArray(item?.bullets) && item.bullets.length
            ? item.bullets.map((bullet) => String(bullet ?? ''))
            : [''],
    }));
}
function normalizeSpeltakAgreementsItems(raw) {
    const incoming = Array.isArray(raw) ? raw : [];
    if (!incoming.length) {
        return JSON.parse(JSON.stringify(props.defaultSpeltakAgreementsItems || []));
    }

    return incoming.map((item) => ({
        title: String(item?.title ?? ''),
        bullets: Array.isArray(item?.bullets) && item.bullets.length
            ? item.bullets.map((bullet) => String(bullet ?? ''))
            : [''],
    }));
}
function normalizeSpeltakHygieneRows(raw) {
    const incoming = Array.isArray(raw) ? raw : [];
    if (!incoming.length) {
        return JSON.parse(JSON.stringify(props.defaultSpeltakHygieneRows || [{
            topic: '',
            jerrycans: '',
            kraanwater: '',
            buitenboordwater: '',
            desinfectans: '',
        }]));
    }

    return incoming.map((row) => ({
        topic: String(row?.topic ?? ''),
        jerrycans: String(row?.jerrycans ?? ''),
        kraanwater: String(row?.kraanwater ?? ''),
        buitenboordwater: String(row?.buitenboordwater ?? ''),
        desinfectans: String(row?.desinfectans ?? ''),
    }));
}
function normalizeVinindelingRows(raw) {
    const incoming = Array.isArray(raw) ? raw : [];
    if (!incoming.length) {
        return JSON.parse(JSON.stringify(props.defaultVinindelingRows || [{ role: '', fin_names: [''] }]));
    }

    return incoming.map((row) => ({
        role: String(row?.role ?? ''),
        fin_names: Array.isArray(row?.fin_names) && row.fin_names.length
            ? row.fin_names.map((name) => String(name ?? ''))
            : [''],
    }));
}
function normalizeCorveeRows(raw) {
    const incoming = Array.isArray(raw) ? raw : [];
    if (!incoming.length) {
        return JSON.parse(JSON.stringify(props.defaultCorveeRows || [{
            day: '',
            date: '',
            daywatch: '',
            dienstvin: '',
            dekhuis: '',
            achteronder_en_dekken: '',
            wc_en_klusjes: '',
        }]));
    }

    return incoming.map((row) => ({
        day: String(row?.day ?? ''),
        date: String(row?.date ?? ''),
        daywatch: String(row?.daywatch ?? ''),
        dienstvin: String(row?.dienstvin ?? ''),
        dekhuis: String(row?.dekhuis ?? ''),
        achteronder_en_dekken: String(row?.achteronder_en_dekken ?? ''),
        wc_en_klusjes: String(row?.wc_en_klusjes ?? ''),
    }));
}
function defaultMonsterrolRows() {
    return {
        crew: [{ first_name: '', last_name: '', functie: '', on_board: '', off_board: '' }],
        speltak: [{ first_name: '', last_name: '', functie: '', on_board: '', off_board: '' }],
    };
}
function normalizeMonsterrolRows(raw) {
    const defaults = defaultMonsterrolRows();
    const fallback = props.defaultMonsterrolRows && typeof props.defaultMonsterrolRows === 'object'
        ? props.defaultMonsterrolRows
        : defaults;
    const value = raw && typeof raw === 'object' ? raw : fallback;

    const normalizeRows = (rows) => {
        const incoming = Array.isArray(rows) ? rows : [];
        if (!incoming.length) {
            return [{ first_name: '', last_name: '', functie: '', on_board: '', off_board: '' }];
        }

        return incoming.map((row) => ({
            first_name: String(row?.first_name ?? ''),
            last_name: String(row?.last_name ?? ''),
            functie: String(row?.functie ?? ''),
            on_board: String(row?.on_board ?? ''),
            off_board: String(row?.off_board ?? ''),
        }));
    };

    return {
        crew: normalizeRows([...(Array.isArray(value.crew) ? value.crew : []), ...(Array.isArray(value.staff) ? value.staff : []), ...(Array.isArray(value.vaarbemanning) ? value.vaarbemanning : [])]),
        speltak: normalizeRows(value.speltak),
    };
}

function splitLeaderName(fullName) {
    const name = String(fullName ?? '').trim();
    if (!name) {
        return { first_name: '', last_name: '' };
    }
    const parts = name.split(/\s+/).filter(Boolean);
    if (parts.length <= 1) {
        return { first_name: parts[0] ?? '', last_name: '' };
    }

    return {
        first_name: parts[0],
        last_name: parts.slice(1).join(' '),
    };
}

function leaderTeamRows() {
    return (props.leaderTeam || [])
        .map((leader) => splitLeaderName(leader?.name))
        .filter((entry) => entry.first_name || entry.last_name)
        .map((entry) => ({
            first_name: entry.first_name,
            last_name: entry.last_name,
            functie: 'Leiding',
            on_board: '',
            off_board: '',
        }));
}
function defaultSpeltakFunction() {
    return page.props.auth?.active_section === 'dolfijnen' ? 'Dolfijn' : '';
}
function sectionMemberRows() {
    return (props.sectionMembers || [])
        .map((member) => splitLeaderName(member?.name))
        .filter((entry) => entry.first_name || entry.last_name)
        .map((entry) => ({
            first_name: entry.first_name,
            last_name: entry.last_name,
            functie: defaultSpeltakFunction(),
            on_board: '',
            off_board: '',
        }));
}
function normalizeVaarschemaRows(raw) {
    const incoming = Array.isArray(raw) ? raw : [];
    if (!incoming.length) {
        return JSON.parse(JSON.stringify(props.defaultVaarschemaRows || []));
    }

    return incoming.map((row) => ({
        date: String(row?.date ?? ''),
        from: String(row?.from ?? ''),
        to: String(row?.to ?? ''),
        depart_at: String(row?.depart_at ?? ''),
        arrive_at: String(row?.arrive_at ?? ''),
        tide_margin_minutes: String(row?.tide_margin_minutes ?? ''),
    }));
}
const form = useForm({
    camp_year: source.camp_year || new Date().getFullYear(),
    title: source.title || '',
    camp_location: source.camp_location === 'clubhuis' ? 'clubhuis' : 'fram',
    camp_place: source.camp_place || '',
    camp_dates: composeCampDateRange(initialDateRange.start, initialDateRange.end),
    camp_date_start: initialDateRange.start,
    camp_date_end: initialDateRange.end,
    cover_photo: null,
    cover_photo_remove: false,
    existing_cover_photo_url: source.cover_photo_url || '',
    task_distribution_rows: normalizeTaskDistributionRows(source.task_distribution_rows),
    task_explanation_items: normalizeTaskExplanationItems(source.task_explanation_items),
    general_agreements_items: normalizeGeneralAgreementsItems(source.general_agreements_items),
    speltak_agreements_items: normalizeSpeltakAgreementsItems(source.speltak_agreements_items),
    speltak_hygiene_rows: normalizeSpeltakHygieneRows(source.speltak_hygiene_rows),
    vinindeling_rows: normalizeVinindelingRows(source.vinindeling_rows),
    corvee_rows: normalizeCorveeRows(source.corvee_rows),
    monsterrol_rows: normalizeMonsterrolRows(source.monsterrol_rows),
    emergency_contacts: normalizeEmergencyContacts(source.emergency_contacts),
    day_plans: normalizeDayPlans(source.day_plans),
    vaarschema_rows: normalizeVaarschemaRows(source.vaarschema_rows),
    playbook_sections: JSON.parse(JSON.stringify(initialSections)),
});
const coverPreviewUrl = ref(source.cover_photo_url || '');
const isFramCamp = computed(() => form.camp_location === 'fram');

if (!source?.monsterrol_rows) {
    const autoCrew = leaderTeamRows();
    if (autoCrew.length) {
        form.monsterrol_rows.crew = autoCrew;
    }
    const autoSpeltak = sectionMemberRows();
    if (autoSpeltak.length) {
        form.monsterrol_rows.speltak = autoSpeltak;
    }
}
const responsibleOptions = computed(() => {
    const crewNames = (form.monsterrol_rows?.crew || [])
        .map((row) => `${String(row?.first_name ?? '').trim()} ${String(row?.last_name ?? '').trim()}`.trim())
        .filter((name) => name !== '');
    return ['n.v.t.', ...Array.from(new Set(crewNames))];
});
const VAARSCHEMA_SECTION_TITLE = 'Vaarschema';
const HULPDIENSTEN_SECTION_TITLE = 'Hulpdiensten';

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

const activeSectionIndex = ref(0);
const activeSection = computed(() => form.playbook_sections[activeSectionIndex.value] || null);
const deleteModalOpen = ref(false);
const draggedPlanningRow = ref(null);

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

function syncCorveeDaywatchFromPlanning() {
    const daywatchByIsoDate = new Map(
        (Array.isArray(form.day_plans) ? form.day_plans : [])
            .map((day) => {
                const isoDate = isoDateFromDayLabel(day?.day_label);
                const names = normalizedDaywatchIds(day)
                    .map((id) => daywatchNameById(id))
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
        tide_margin_minutes: '',
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

function sectionTitleKey(section) {
    return String(section?.title || '').trim().toLowerCase();
}

function findSectionIndexByTitle(title) {
    const key = String(title || '').trim().toLowerCase();
    return form.playbook_sections.findIndex((section) => sectionTitleKey(section) === key);
}

function ensureVaarschemaSectionForLocation() {
    const vaarschemaIndex = findSectionIndexByTitle(VAARSCHEMA_SECTION_TITLE);

    if (form.camp_location === 'fram') {
        if (vaarschemaIndex === -1) {
            const hulpdienstenIndex = findSectionIndexByTitle(HULPDIENSTEN_SECTION_TITLE);
            const insertIndex = hulpdienstenIndex >= 0 ? hulpdienstenIndex : form.playbook_sections.length;
            form.playbook_sections.splice(insertIndex, 0, { title: VAARSCHEMA_SECTION_TITLE, content: '' });
            if (activeSectionIndex.value >= insertIndex) {
                activeSectionIndex.value += 1;
            }
        }
    } else if (vaarschemaIndex !== -1) {
        form.playbook_sections.splice(vaarschemaIndex, 1);
        if (activeSectionIndex.value === vaarschemaIndex) {
            activeSectionIndex.value = 0;
        } else if (activeSectionIndex.value > vaarschemaIndex) {
            activeSectionIndex.value -= 1;
        }
    }

    if (activeSectionIndex.value >= form.playbook_sections.length) {
        activeSectionIndex.value = Math.max(form.playbook_sections.length - 1, 0);
    }
}

ensureVaarschemaSectionForLocation();

function submit(action = 'save') {
    const normalizedAction = action === 'submit' ? 'submit' : 'save';
    form.transform((data) => ({
        ...data,
        camp_dates: composeCampDateRange(data.camp_date_start, data.camp_date_end),
        action: normalizedAction,
        ...(isEdit.value ? { _method: 'patch' } : {}),
    }));

    if (isEdit.value) {
        if (!canUpdate.value) return;
        form.post(route('camp-playbooks.update', props.item.id), {
            forceFormData: true,
            onFinish: () => form.transform((data) => data),
        });
        return;
    }
    if (!canCreate.value) return;
    form.post(route('camp-playbooks.store'), {
        forceFormData: true,
        onFinish: () => form.transform((data) => data),
    });
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

function isAlgemeenSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'algemeen';
}

function isHulpdienstenSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'hulpdiensten';
}

function isTaakverdelingSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'taakverdeling';
}

function isTaakUitlegSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'taak uitleg';
}

function isAlgemeneAfsprakenSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'algemene afspraken';
}

function isSpeltakAfsprakenSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'speltak afspraken';
}

function isCorveeroosterSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'corveerooster';
}

function isVinindelingSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'vinindeling';
}

function isMonsterrolSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'monsterrol';
}

function isPlanningPerDagSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'planning per dag';
}

function isVaarschemaSection(section) {
    return String(section?.title || '').trim().toLowerCase() === 'vaarschema';
}

function isStructuredSection(section) {
    return isAlgemeenSection(section)
        || isTaakverdelingSection(section)
        || isTaakUitlegSection(section)
        || isAlgemeneAfsprakenSection(section)
        || isSpeltakAfsprakenSection(section)
        || isVinindelingSection(section)
        || isCorveeroosterSection(section)
        || isMonsterrolSection(section)
        || isVaarschemaSection(section)
        || isPlanningPerDagSection(section)
        || isHulpdienstenSection(section);
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

function normalizedDaywatchIds(day) {
    if (!Array.isArray(day?.daywatch_ids)) {
        return [];
    }
    return Array.from(new Set(day.daywatch_ids.map((id) => Number(id)).filter((id) => Number.isFinite(id) && id > 0)));
}

function daywatchNameById(leaderId) {
    return (props.leaderTeam || []).find((leader) => Number(leader?.id) === Number(leaderId))?.name || `Leiding #${leaderId}`;
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
    form.vinindeling_rows.push({
        role: '',
        fin_names: [''],
    });
}

function removeVinindelingRow(index) {
    if (!Array.isArray(form.vinindeling_rows) || form.vinindeling_rows.length <= 1) return;
    form.vinindeling_rows.splice(index, 1);
}

function addVinName(rowIndex) {
    const row = form.vinindeling_rows?.[rowIndex];
    if (!row) return;
    if (!Array.isArray(row.fin_names)) {
        row.fin_names = [];
    }
    row.fin_names.push('');
}

function removeVinName(rowIndex, vinIndex) {
    const row = form.vinindeling_rows?.[rowIndex];
    if (!row || !Array.isArray(row.fin_names) || row.fin_names.length <= 1) return;
    row.fin_names.splice(vinIndex, 1);
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

function openDeleteModal() {
    if (!isEdit.value || !canUpdate.value) return;
    deleteModalOpen.value = true;
}

function closeDeleteModal() {
    deleteModalOpen.value = false;
}

function statusLabel(status) {
    if (status === 'draft') return 'Concept';
    if (status === 'submitted') return 'Wacht op goedkeuring';
    if (status === 'approved') return 'Goedgekeurd';
    if (status === 'needs_changes') return 'Aanpassing(en) nodig';
    return status || 'Concept';
}

function statusClass(status) {
    if (status === 'draft') return 'bg-slate-100 text-slate-700';
    if (status === 'approved') return 'bg-emerald-100 text-emerald-800';
    if (status === 'needs_changes') return 'bg-amber-100 text-amber-800';
    return 'bg-sky-100 text-sky-800';
}
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
                                placeholder="Titel (bijv. Hollywood Kamp)"
                                required
                            />
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Kamptype</label>
                            <div class="mt-2 inline-flex items-center rounded-full border border-app-border bg-slate-100 p-1 dark:border-app-border-dark dark:bg-slate-800">
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
                                placeholder="Bijv. Rotterdam"
                            />
                        </div>
                        <div class="space-y-1 sm:col-span-2">
                            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Datum (daterange)</label>
                            <div class="flex items-center gap-2 rounded border border-app-border bg-white px-2 py-2 dark:border-app-border-dark dark:bg-slate-900">
                                <input
                                    v-model="form.camp_date_start"
                                    type="date"
                                    :max="form.camp_date_end || undefined"
                                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                />
                                <span class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">t/m</span>
                                <input
                                    v-model="form.camp_date_end"
                                    type="date"
                                    :min="form.camp_date_start || undefined"
                                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                />
                            </div>
                        </div>
                        <div class="space-y-2 sm:col-span-2">
                            <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Cover foto (voorpagina PDF)</label>
                            <div class="rounded border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900">
                                <div v-if="coverPreviewUrl" class="mb-3">
                                    <img :src="coverPreviewUrl" alt="Cover preview" class="h-44 w-full rounded object-cover" />
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <input type="file" accept="image/*" class="block w-full text-sm text-app-ink file:mr-3 file:rounded file:border-0 file:bg-brand-blue/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-blue dark:text-app-ink-dark dark:file:bg-brand-blue/20 dark:file:text-brand-blue" @change="onCoverPhotoSelected" />
                                    <button v-if="coverPreviewUrl || form.existing_cover_photo_url || form.cover_photo" type="button" class="btn-action-delete" title="Cover foto verwijderen" aria-label="Cover foto verwijderen" @click="removeCoverPhoto">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="isMonsterrolSection(activeSection)" class="space-y-4">
                        <div class="space-y-2 rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Staf en vaarbemanning</h4>
                            </div>
                            <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                                <table class="w-full min-w-[920px] text-sm">
                                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                                        <tr>
                                            <th class="px-2 py-2 text-left">Voornaam</th>
                                            <th class="px-2 py-2 text-left">Achternaam</th>
                                            <th class="px-2 py-2 text-left">Functie</th>
                                            <th v-if="isFramCamp" class="px-2 py-2 text-left">Aan boord</th>
                                            <th v-if="isFramCamp" class="px-2 py-2 text-left">Van boord</th>
                                            <th class="px-2 py-2 text-left">Actie</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                                        <tr v-for="(row, rowIdx) in form.monsterrol_rows.crew" :key="`monsterrol-crew-${rowIdx}`">
                                            <td class="px-2 py-2"><input v-model="row.first_name" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Voornaam" /></td>
                                            <td class="px-2 py-2"><input v-model="row.last_name" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Achternaam" /></td>
                                            <td class="px-2 py-2"><input v-model="row.functie" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Functie" /></td>
                                            <td v-if="isFramCamp" class="px-2 py-2"><input v-model="row.on_board" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Aan boord" /></td>
                                            <td v-if="isFramCamp" class="px-2 py-2"><input v-model="row.off_board" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Van boord" /></td>
                                            <td class="px-2 py-2">
                                                <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="removeMonsterrolRow('crew', rowIdx)">
                                                    <TrashIcon class="h-5 w-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div>
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="addMonsterrolRow('crew')">
                                    <PlusIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2 rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">{{ speltakLabel }}</h4>
                                </div>
                            </div>
                            <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                                <table class="w-full min-w-[920px] text-sm">
                                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                                        <tr>
                                            <th class="px-2 py-2 text-left">Voornaam</th>
                                            <th class="px-2 py-2 text-left">Achternaam</th>
                                            <th class="px-2 py-2 text-left">Functie</th>
                                            <th v-if="isFramCamp" class="px-2 py-2 text-left">Aan boord</th>
                                            <th v-if="isFramCamp" class="px-2 py-2 text-left">Van boord</th>
                                            <th class="px-2 py-2 text-left">Actie</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                                        <tr v-for="(row, rowIdx) in form.monsterrol_rows.speltak" :key="`monsterrol-speltak-${rowIdx}`">
                                            <td class="px-2 py-2"><input v-model="row.first_name" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Voornaam" /></td>
                                            <td class="px-2 py-2"><input v-model="row.last_name" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Achternaam" /></td>
                                            <td class="px-2 py-2"><input v-model="row.functie" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Functie" /></td>
                                            <td v-if="isFramCamp" class="px-2 py-2"><input v-model="row.on_board" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Aan boord" /></td>
                                            <td v-if="isFramCamp" class="px-2 py-2"><input v-model="row.off_board" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Van boord" /></td>
                                            <td class="px-2 py-2">
                                                <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="removeMonsterrolRow('speltak', rowIdx)">
                                                    <TrashIcon class="h-5 w-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div>
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="addMonsterrolRow('speltak')">
                                    <PlusIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="isTaakverdelingSection(activeSection)" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Taakverdeling</h4>
                        </div>
                        <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                            <table class="w-full min-w-[840px] text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800/70">
                                    <tr>
                                        <th class="px-2 py-2 text-left">Taak</th>
                                        <th class="px-2 py-2 text-left">Verantwoordelijke</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                                    <tr v-for="(row, rowIdx) in form.task_distribution_rows" :key="`task-distribution-row-${rowIdx}`">
                                        <td class="px-2 py-2"><input v-model="row.task" type="text" readonly class="w-full rounded border border-app-border bg-slate-100 px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark" placeholder="Taak" /></td>
                                        <td class="px-2 py-2">
                                            <div class="space-y-2">
                                                <div class="flex flex-wrap gap-1.5">
                                                    <span
                                                        v-for="name in normalizeResponsibleNames(row.responsibles)"
                                                        :key="`task-responsible-chip-${rowIdx}-${name}`"
                                                        class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs text-app-ink dark:text-app-ink-dark"
                                                    >
                                                        {{ name }}
                                                        <button v-if="!isDagverloopTask(row.task)" type="button" class="rounded p-0.5 hover:bg-brand-blue/25" @click="removeResponsibleFromTask(row, name)">
                                                            <XMarkIcon class="h-3.5 w-3.5" />
                                                        </button>
                                                    </span>
                                                </div>
                                                <select class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black disabled:bg-slate-100 disabled:text-slate-500 dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark dark:disabled:bg-slate-800 dark:disabled:text-slate-400" :disabled="isDagverloopTask(row.task)" @change="addResponsibleToTask(row, $event)">
                                                    <option value="">Verantwoordelijke toevoegen...</option>
                                                    <option v-for="name in availableResponsibleOptions(row)" :key="`responsible-option-${rowIdx}-${name}`" :value="name">
                                                        {{ name }}
                                                    </option>
                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div v-if="isTaakUitlegSection(activeSection)" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Taak uitleg</h4>
                            <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="addTaskExplanationItem">
                                Taak toevoegen
                            </button>
                        </div>

                        <div
                            v-for="(item, itemIdx) in form.task_explanation_items"
                            :key="`task-explanation-item-${itemIdx}`"
                            class="rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <input
                                    v-model="item.title"
                                    type="text"
                                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                    :placeholder="`Taak ${itemIdx + 1}`"
                                />
                                <button type="button" class="btn-action-delete" title="Taak verwijderen" @click="removeTaskExplanationItem(itemIdx)">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="mt-3 space-y-2">
                                <div
                                    v-for="(bullet, bulletIdx) in item.bullets"
                                    :key="`task-bullet-${itemIdx}-${bulletIdx}`"
                                    class="flex items-center gap-2"
                                >
                                    <span class="text-sm text-app-muted dark:text-app-muted-dark">•</span>
                                    <input
                                        v-model="item.bullets[bulletIdx]"
                                        type="text"
                                        class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        placeholder="Bulletpoint"
                                    />
                                    <button type="button" class="btn-action-delete" title="Bulletpoint verwijderen" @click="removeTaskBullet(itemIdx, bulletIdx)">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                                <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="addTaskBullet(itemIdx)">
                                    Bulletpoint toevoegen
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="isAlgemeneAfsprakenSection(activeSection)" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Algemene afspraken</h4>
                            <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="addGeneralAgreementItem">
                                Blok toevoegen
                            </button>
                        </div>

                        <div
                            v-for="(item, itemIdx) in form.general_agreements_items"
                            :key="`general-agreement-item-${itemIdx}`"
                            class="rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <input
                                    v-model="item.title"
                                    type="text"
                                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                    :placeholder="`Kop ${itemIdx + 1}`"
                                />
                                <button type="button" class="btn-action-delete" title="Blok verwijderen" @click="removeGeneralAgreementItem(itemIdx)">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="mt-3 space-y-2">
                                <div
                                    v-for="(bullet, bulletIdx) in item.bullets"
                                    :key="`general-agreement-bullet-${itemIdx}-${bulletIdx}`"
                                    class="flex items-center gap-2"
                                >
                                    <span class="text-sm text-app-muted dark:text-app-muted-dark">•</span>
                                    <input
                                        v-model="item.bullets[bulletIdx]"
                                        type="text"
                                        class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        placeholder="Bulletpoint"
                                    />
                                    <button type="button" class="btn-action-delete" title="Bulletpoint verwijderen" @click="removeGeneralAgreementBullet(itemIdx, bulletIdx)">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                                <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="addGeneralAgreementBullet(itemIdx)">
                                    Bulletpoint toevoegen
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="isSpeltakAfsprakenSection(activeSection)" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Speltak afspraken</h4>
                            <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="addSpeltakAgreementItem">
                                Blok toevoegen
                            </button>
                        </div>

                        <div
                            v-for="(item, itemIdx) in form.speltak_agreements_items"
                            :key="`speltak-agreement-item-${itemIdx}`"
                            class="rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <input
                                    v-model="item.title"
                                    type="text"
                                    class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                    :placeholder="`Kop ${itemIdx + 1}`"
                                />
                                <button type="button" class="btn-action-delete" title="Blok verwijderen" @click="removeSpeltakAgreementItem(itemIdx)">
                                    <TrashIcon class="h-5 w-5" />
                                </button>
                            </div>

                            <div class="mt-3 space-y-2">
                                <div
                                    v-for="(bullet, bulletIdx) in item.bullets"
                                    :key="`speltak-agreement-bullet-${itemIdx}-${bulletIdx}`"
                                    class="flex items-center gap-2"
                                >
                                    <span class="text-sm text-app-muted dark:text-app-muted-dark">•</span>
                                    <input
                                        v-model="item.bullets[bulletIdx]"
                                        type="text"
                                        class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                        placeholder="Bulletpoint"
                                    />
                                    <button type="button" class="btn-action-delete" title="Bulletpoint verwijderen" @click="removeSpeltakAgreementBullet(itemIdx, bulletIdx)">
                                        <TrashIcon class="h-5 w-5" />
                                    </button>
                                </div>
                                <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="addSpeltakAgreementBullet(itemIdx)">
                                    Bulletpoint toevoegen
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2 rounded-lg border border-app-border bg-white p-3 dark:border-app-border-dark dark:bg-slate-900">
                            <div class="flex items-center justify-between">
                                <h5 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Hygiëne en gezondheid tabel</h5>
                            </div>
                            <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                                <table class="w-full min-w-[980px] text-sm">
                                    <thead class="bg-slate-50 dark:bg-slate-800/70">
                                        <tr>
                                            <th class="px-2 py-2 text-left">Onderwerp</th>
                                            <th class="px-2 py-2 text-left">Jerrycans</th>
                                            <th class="px-2 py-2 text-left">Kraanwater</th>
                                            <th class="px-2 py-2 text-left">Buitenboordwater</th>
                                            <th class="px-2 py-2 text-left">Desinfectans</th>
                                            <th class="px-2 py-2 text-left">Actie</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                                        <tr v-for="(row, rowIdx) in form.speltak_hygiene_rows" :key="`speltak-hygiene-row-${rowIdx}`">
                                            <td class="px-2 py-2"><input v-model="row.topic" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Onderwerp" /></td>
                                            <td class="px-2 py-2"><input v-model="row.jerrycans" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Ja/Nee" /></td>
                                            <td class="px-2 py-2"><input v-model="row.kraanwater" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Ja/Nee" /></td>
                                            <td class="px-2 py-2"><input v-model="row.buitenboordwater" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Ja/Nee" /></td>
                                            <td class="px-2 py-2"><input v-model="row.desinfectans" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Ja/Nee" /></td>
                                            <td class="px-2 py-2">
                                                <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="removeSpeltakHygieneRow(rowIdx)">
                                                    <TrashIcon class="h-5 w-5" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div>
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="addSpeltakHygieneRow">
                                    <PlusIcon class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-if="isCorveeroosterSection(activeSection)" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Corveerooster</h4>
                        </div>
                        <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                            <table class="w-full min-w-[1300px] text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800/70">
                                    <tr>
                                        <th class="px-2 py-2 text-left">Dag</th>
                                        <th class="px-2 py-2 text-left">Datum</th>
                                        <th class="px-2 py-2 text-left">Dagwacht</th>
                                        <th class="px-2 py-2 text-left">Dienstvin</th>
                                        <th class="px-2 py-2 text-left">Dekhuis</th>
                                        <th class="px-2 py-2 text-left">Achteronder &amp; Dekken</th>
                                        <th class="px-2 py-2 text-left">WC &amp; klusjes</th>
                                        <th class="px-2 py-2 text-left">Actie</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                                    <tr v-for="(row, rowIdx) in form.corvee_rows" :key="`corvee-row-${rowIdx}`">
                                        <td class="px-2 py-2"><input v-model="row.day" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Dag" /></td>
                                        <td class="px-2 py-2"><input v-model="row.date" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Datum" /></td>
                                        <td class="px-2 py-2"><input v-model="row.daywatch" type="text" readonly class="w-full rounded border border-app-border bg-slate-100 px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-slate-800 dark:text-app-ink-dark" placeholder="Dagwacht" /></td>
                                        <td class="px-2 py-2"><input v-model="row.dienstvin" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Dienstvin" /></td>
                                        <td class="px-2 py-2"><input v-model="row.dekhuis" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Dekhuis" /></td>
                                        <td class="px-2 py-2"><input v-model="row.achteronder_en_dekken" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Achteronder &amp; Dekken" /></td>
                                        <td class="px-2 py-2"><input v-model="row.wc_en_klusjes" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="WC &amp; klusjes" /></td>
                                        <td class="px-2 py-2">
                                            <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="removeCorveeRow(rowIdx)">
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="addCorveeRow">
                                <PlusIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="isVinindelingSection(activeSection)" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Vinindeling</h4>
                        </div>

                        <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                            <table class="w-full min-w-[860px] text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800/70">
                                    <tr>
                                        <th class="px-2 py-2 text-left">Rol</th>
                                        <th class="px-2 py-2 text-left">Vinnamen</th>
                                        <th class="px-2 py-2 text-left">Actie</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                                    <tr v-for="(row, rowIdx) in form.vinindeling_rows" :key="`vinindeling-row-${rowIdx}`">
                                        <td class="px-2 py-2 align-top">
                                            <input
                                                v-model="row.role"
                                                type="text"
                                                class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                                placeholder="Rol"
                                            />
                                        </td>
                                        <td class="px-2 py-2">
                                            <div class="space-y-2">
                                                <div
                                                    v-for="(vinName, vinIdx) in row.fin_names"
                                                    :key="`vinindeling-vin-${rowIdx}-${vinIdx}`"
                                                    class="flex items-center gap-2"
                                                >
                                                    <input
                                                        v-model="row.fin_names[vinIdx]"
                                                        type="text"
                                                        class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark"
                                                        placeholder="Vinnaam"
                                                    />
                                                    <button type="button" class="btn-action-delete" title="Vinnaam verwijderen" @click="removeVinName(rowIdx, vinIdx)">
                                                        <TrashIcon class="h-5 w-5" />
                                                    </button>
                                                </div>
                                                <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="addVinName(rowIdx)">
                                                    Vinnaam toevoegen
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-2 py-2 align-top">
                                            <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="removeVinindelingRow(rowIdx)">
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="addVinindelingRow">
                                <PlusIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="isVaarschemaSection(activeSection)" class="space-y-3">
                        <div class="rounded-lg border border-brand-blue/25 bg-brand-blue/5 p-3 text-sm text-app-ink dark:border-brand-blue/40 dark:bg-brand-blue/10 dark:text-app-ink-dark">
                            <p class="font-semibold">Website getij</p>
                            <a
                                href="https://waterinfo.rws.nl/publiek/astronomische-getij/heinenoord.goidschalxoord/details"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mt-1 inline-block break-all text-brand-blue underline"
                            >
                                https://waterinfo.rws.nl/publiek/astronomische-getij/heinenoord.goidschalxoord/details
                            </a>
                            <p class="mt-2 text-xs">
                                Note: We kunnen met 60 NAP net wel naar binnen in de Koedood. Voor de veiligheid 75 NAP aanhouden.
                            </p>
                        </div>

                        <div class="overflow-x-auto rounded border border-app-border dark:border-app-border-dark">
                            <table class="w-full min-w-[880px] text-sm">
                                <thead class="bg-slate-50 dark:bg-slate-800/70">
                                    <tr>
                                        <th class="px-2 py-2 text-left">Datum</th>
                                        <th class="px-2 py-2 text-left">Van</th>
                                        <th class="px-2 py-2 text-left">Naar</th>
                                        <th class="px-2 py-2 text-left">Wegvaren</th>
                                        <th class="px-2 py-2 text-left">Aankomen</th>
                                        <th class="px-2 py-2 text-left">Speling (minuten)</th>
                                        <th class="px-2 py-2 text-left">Actie</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-app-border dark:divide-app-border-dark">
                                    <tr v-for="(row, rowIdx) in form.vaarschema_rows" :key="`vaarschema-row-${rowIdx}`">
                                        <td class="px-2 py-2"><input v-model="row.date" type="date" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" /></td>
                                        <td class="px-2 py-2"><input v-model="row.from" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Van" /></td>
                                        <td class="px-2 py-2"><input v-model="row.to" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Naar" /></td>
                                        <td class="px-2 py-2"><input v-model="row.depart_at" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Wegvaren" /></td>
                                        <td class="px-2 py-2"><input v-model="row.arrive_at" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Aankomen" /></td>
                                        <td class="px-2 py-2"><input v-model="row.tide_margin_minutes" type="text" class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Bijv. 75" /></td>
                                        <td class="px-2 py-2">
                                            <button type="button" class="btn-action-delete" title="Rij verwijderen" @click="removeVaarschemaRow(rowIdx)">
                                                <TrashIcon class="h-5 w-5" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div>
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="addVaarschemaRow">
                                <PlusIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="isPlanningPerDagSection(activeSection)" class="space-y-3">
                        <div class="flex items-center">
                            <h4 class="text-sm font-semibold text-app-ink dark:text-app-ink-dark">Dagen</h4>
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
                                <div class="space-y-2">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="leaderId in normalizedDaywatchIds(day)"
                                            :key="`daywatch-chip-${dayIdx}-${leaderId}`"
                                            class="inline-flex items-center gap-1 rounded-full bg-brand-blue/15 px-2 py-0.5 text-xs text-app-ink dark:text-app-ink-dark"
                                        >
                                            {{ daywatchNameById(leaderId) }}
                                            <button type="button" class="rounded p-0.5 hover:bg-brand-blue/25" @click="removeDaywatch(day, leaderId)">
                                                <XMarkIcon class="h-3.5 w-3.5" />
                                            </button>
                                        </span>
                                    </div>
                                    <select class="w-full rounded border border-app-border bg-white px-2 py-1.5 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" @change="addDaywatchFromSelect(day, $event)">
                                        <option value="">Dagwacht toevoegen...</option>
                                        <option v-for="leader in availableDaywatchOptions(day)" :key="`daywatch-option-${dayIdx}-${leader.id}`" :value="leader.id">
                                            {{ leader.name }}
                                        </option>
                                    </select>
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
                                        <tr
                                            v-for="(row, rowIdx) in day.planning_rows"
                                            :key="`planning-row-${dayIdx}-${rowIdx}`"
                                            draggable="true"
                                            class="cursor-move"
                                            @dragstart="startPlanningRowDrag(dayIdx, rowIdx, $event)"
                                            @dragover="allowPlanningRowDrop($event)"
                                            @drop="dropPlanningRow(dayIdx, rowIdx, $event)"
                                            @dragend="endPlanningRowDrag"
                                        >
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
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-emerald-700 text-white" title="Rij toevoegen" aria-label="Rij toevoegen" @click="addPlanningRow(dayIdx)">
                                    <PlusIcon class="h-4 w-4" />
                                </button>
                            </div>

                            <div class="mt-3 space-y-1">
                                <label class="text-xs font-semibold uppercase tracking-wide text-app-muted dark:text-app-muted-dark">Speluitleg</label>
                                <textarea v-model="day.game_explanation" rows="4" class="w-full rounded border border-app-border bg-white px-3 py-2 text-sm text-black dark:border-app-border-dark dark:bg-app-canvas-dark dark:text-app-ink-dark" placeholder="Leg spelregels, doelen en aandachtspunten uit..." />
                            </div>
                        </div>

                        <div>
                            <button type="button" class="rounded bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800" @click="addPlanningDay">
                                Dag toevoegen
                            </button>
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
