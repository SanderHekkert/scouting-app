import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

export function createCampPlaybookEditorCore(props, page) {
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
    const emptyEmergencyContact = () => ({
        name: '',
        address: '',
        postal_code: '',
        city: '',
        phone_010: '',
        website: '',
        extra_info: '',
    });
    const defaultEmergencyContacts = () => ({
        huisartsen: [emptyEmergencyContact()],
        ziekenhuizen: [emptyEmergencyContact()],
        tandartsen: [emptyEmergencyContact()],
    });

    function normalizeEmergencyContacts(raw) {
        const defaults = defaultEmergencyContacts();
        const value = raw && typeof raw === 'object' ? raw : {};
        for (const key of Object.keys(defaults)) {
            const sourceRows = Array.isArray(value[key])
                ? value[key]
                : value[key] && typeof value[key] === 'object'
                    ? [value[key]]
                    : [];
            const rows = sourceRows
                .filter((entry) => entry && typeof entry === 'object')
                .map((entry) => ({
                    name: String(entry.name ?? ''),
                    address: String(entry.address ?? ''),
                    postal_code: String(entry.postal_code ?? ''),
                    city: String(entry.city ?? ''),
                    phone_010: String(entry.phone_010 ?? ''),
                    website: String(entry.website ?? ''),
                    extra_info: String(entry.extra_info ?? ''),
                }))
                .filter((entry) => Object.values(entry).some((val) => String(val).trim() !== ''));
            defaults[key] = rows.length ? rows : [emptyEmergencyContact()];
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

    function normalizeVinMembers(value) {
        const source = Array.isArray(value) ? value : String(value ?? '').split(',');
        return Array.from(
            new Set(
                source
                    .map((name) => String(name ?? '').trim())
                    .filter((name) => name !== '')
            )
        );
    }

    function normalizeVinindelingRows(raw) {
        const defaultHeaders = ['', '', ''];
        const defaultRoles = ['Topper', 'Tipper', 'Vinlid', 'Vinlid', 'Vinlid'];
        const buildDefaultRows = () => defaultRoles.map((role) => ({
            role,
            vins: defaultHeaders.map((header) => ({ vin_name: header, member_names: [] })),
        }));
        const defaultRows = () => JSON.parse(JSON.stringify(props.defaultVinindelingRows?.length ? props.defaultVinindelingRows : buildDefaultRows()));
        const incoming = Array.isArray(raw) ? raw : [];
        if (!incoming.length) {
            return defaultRows();
        }

        const rows = incoming.map((row, rowIndex) => {
            const vins = Array.isArray(row?.vins) && row.vins.length
                ? row.vins.map((vin) => ({
                    vin_name: String(vin?.vin_name ?? vin?.name ?? ''),
                    member_names: normalizeVinMembers(vin?.member_names ?? vin?.members ?? []),
                }))
                : Array.isArray(row?.fin_names) && row.fin_names.length
                    ? row.fin_names.map((name) => ({ vin_name: String(name ?? ''), member_names: [] }))
                    : [];

            return {
                role: String(row?.role ?? defaultRoles[rowIndex] ?? 'Vinlid'),
                vins,
            };
        });

        const headerCandidates = rows
            .flatMap((row) => (Array.isArray(row?.vins) ? row.vins : []))
            .map((vin) => String(vin?.vin_name ?? '').trim())
            .filter((name) => name !== '');

        const headers = [];
        for (const name of headerCandidates) {
            if (!headers.includes(name)) {
                headers.push(name);
            }
            if (headers.length >= defaultHeaders.length) break;
        }
        while (headers.length < defaultHeaders.length) {
            headers.push(defaultHeaders[headers.length]);
        }

        const normalizedRows = rows.map((row, rowIndex) => {
            const vins = headers.map((header, vinIndex) => {
                const sourceVin = Array.isArray(row?.vins) ? row.vins[vinIndex] : null;
                return {
                    vin_name: header,
                    member_names: normalizeVinMembers(sourceVin?.member_names ?? []),
                };
            });

            return {
                role: String(row?.role ?? defaultRoles[rowIndex] ?? 'Vinlid'),
                vins,
            };
        });

        return normalizedRows.length ? normalizedRows : defaultRows();
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

    function normalizeWholeMinutes(value) {
        const raw = String(value ?? '').trim();
        const normalized = Number.parseInt(raw.replace(/[^\d-]/g, ''), 10);
        if (!Number.isFinite(normalized) || normalized < 0) {
            return '0';
        }
        return String(normalized);
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
            tide_margin_minutes: normalizeWholeMinutes(row?.tide_margin_minutes),
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
    const vinMemberOptions = computed(() => {
        const speltakNames = (form.monsterrol_rows?.speltak || [])
            .map((row) => `${String(row?.first_name ?? '').trim()} ${String(row?.last_name ?? '').trim()}`.trim())
            .filter((name) => name !== '');
        return Array.from(new Set(speltakNames));
    });
    const vinHeaderValues = computed(() => {
        const fallback = ['De Regisseurs', 'De Acteurs', 'De Cameraploeg'];
        const firstRow = Array.isArray(form.vinindeling_rows) ? form.vinindeling_rows[0] : null;
        const values = Array.isArray(firstRow?.vins)
            ? firstRow.vins.map((vin) => String(vin?.vin_name ?? '').trim()).filter((name) => name !== '')
            : [];
        if (values.length >= fallback.length) {
            return values.slice(0, fallback.length);
        }
        return [...values, ...fallback.slice(values.length)];
    });
    const corveeVinOptions = computed(() => {
        const names = vinHeaderValues.value
            .map((name) => String(name ?? '').trim())
            .filter((name) => name !== '');
        return ['n.v.t.', 'Leiding', ...Array.from(new Set(names))];
    });

    const activeSectionIndex = ref(0);
    const activeSection = computed(() => form.playbook_sections[activeSectionIndex.value] || null);
    const deleteModalOpen = ref(false);
    const draggedPlanningRow = ref(null);

    const VAARSCHEMA_SECTION_TITLE = 'Vaarschema';
    const HULPDIENSTEN_SECTION_TITLE = 'Hulpdiensten';

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

    ensureVaarschemaSectionForLocation();

    return {
        page,
        source,
        canCreate,
        canUpdate,
        isEdit,
        speltakLabel,
        form,
        coverPreviewUrl,
        isFramCamp,
        corveeVinOptions,
        vinHeaderValues,
        vinMemberOptions,
        responsibleOptions,
        activeSectionIndex,
        activeSection,
        deleteModalOpen,
        draggedPlanningRow,
        toIsoDate,
        formatIsoToNlDate,
        parseCampDateRange,
        composeCampDateRange,
        isoDateEntriesBetween,
        defaultPlanningRows,
        normalizeResponsibleNames,
        normalizeVinMembers,
        normalizeWholeMinutes,
        emptyEmergencyContact,
        ensureVaarschemaSectionForLocation,
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
        statusLabel,
        statusClass,
    };
}
