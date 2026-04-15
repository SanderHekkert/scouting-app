<?php

namespace App\Http\Controllers;

use App\Models\CampPlaybook;
use App\Models\Member;
use App\Models\User;
use App\Models\UserSectionRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CampPlaybookController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        $activeSection = (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
        $canReview = $this->canReviewPlaybooks($user, $activeSection);

        $query = CampPlaybook::query();
        if ($canReview && $activeSection === UserSectionRole::SECTION_BESTUUR) {
            $query = CampPlaybook::withoutGlobalScope('section')
                ->where('section', '!=', UserSectionRole::SECTION_BESTUUR);
        }

        return Inertia::render('CampPlaybooks/Index', [
            'items' => $query
                ->with(['createdBy:id,name', 'updatedBy:id,name'])
                ->latest('camp_year')
                ->latest('id')
                ->get()
                ->map(fn (CampPlaybook $item): array => $this->indexItemPayload($item, $canReview))
                ->values()
                ->all(),
            'canReview' => $canReview,
        ]);
    }

    /**
     * @return array{
     *   id:int,
     *   section:string,
     *   camp_year:int,
     *   title:string,
     *   camp_location:string,
     *   camp_place:string,
     *   camp_dates:string,
     *   cover_photo_url:?string,
     *   status:string,
     *   review_note:string,
     *   review_notes:array<int,array{note:string,user_name:string,at:string}>,
     *   created_by_name:string,
     *   updated_by_name:string,
     *   updated_at:?string,
     *   can_review:bool
     * }
     */
    private function indexItemPayload(CampPlaybook $item, bool $canReview): array
    {
        return [
            'id' => (int) $item->id,
            'section' => (string) $item->section,
            'camp_year' => (int) $item->camp_year,
            'title' => (string) $item->title,
            'camp_location' => $this->normalizeCampLocation((string) data_get($item->meta, 'camp_location', 'fram')),
            'camp_place' => trim((string) data_get($item->meta, 'camp_place', '')),
            'camp_dates' => trim((string) data_get($item->meta, 'camp_dates', '')),
            'cover_photo_url' => $this->coverPhotoUrl((string) data_get($item->meta, 'cover_photo_path', '')),
            'status' => (string) ($item->status ?: CampPlaybook::STATUS_DRAFT),
            'review_note' => (string) ($item->review_note ?? ''),
            'review_notes' => $this->reviewNotesForPayload((array) data_get($item->meta, 'review_notes', [])),
            'created_by_name' => (string) optional($item->createdBy)->name,
            'updated_by_name' => (string) optional($item->updatedBy)->name,
            'updated_at' => optional($item->updated_at)?->toIso8601String(),
            'can_review' => $canReview && in_array((string) $item->status, [CampPlaybook::STATUS_SUBMITTED], true),
        ];
    }

    public function create(Request $request): Response
    {
        $copyId = (int) $request->query('copy', 0);
        $copyItem = null;
        if ($copyId > 0) {
            $source = CampPlaybook::query()->find($copyId);
            if ($source && (string) $source->section === (string) session('active_section', 'dolfijnen')) {
                $sections = $this->normalizePlaybookSections(
                    (array) data_get($source->meta, 'sections', []),
                    (string) ($source->content ?? '')
                );

                $copyItem = [
                    'camp_year' => (int) $source->camp_year,
                    'title' => (string) $source->title,
                    'camp_location' => $this->normalizeCampLocation((string) data_get($source->meta, 'camp_location', 'fram')),
                    'camp_place' => (string) data_get($source->meta, 'camp_place', ''),
                    'camp_dates' => (string) data_get($source->meta, 'camp_dates', ''),
                    'cover_photo_url' => $this->coverPhotoUrl((string) data_get($source->meta, 'cover_photo_path', '')),
                    'task_distribution_rows' => $this->normalizeTaskDistributionRows((array) data_get($source->meta, 'task_distribution_rows', [])),
                    'task_explanation_items' => $this->normalizeTaskExplanationItems(
                        (array) data_get($source->meta, 'task_explanation_items', []),
                        (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
                    ),
                    'general_agreements_items' => $this->normalizeGeneralAgreementsItems(
                        (array) data_get($source->meta, 'general_agreements_items', []),
                        (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
                    ),
                    'speltak_agreements_items' => $this->normalizeSpeltakAgreementsItems(
                        (array) data_get($source->meta, 'speltak_agreements_items', []),
                        (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
                    ),
                    'speltak_hygiene_rows' => $this->normalizeSpeltakHygieneRows((array) data_get($source->meta, 'speltak_hygiene_rows', [])),
                    'vinindeling_rows' => $this->normalizeVinindelingRows((array) data_get($source->meta, 'vinindeling_rows', [])),
                    'corvee_rows' => $this->normalizeCorveeRows((array) data_get($source->meta, 'corvee_rows', [])),
                    'monsterrol_rows' => $this->normalizeMonsterrolRows((array) data_get($source->meta, 'monsterrol_rows', [])),
                    'emergency_contacts' => $this->normalizeEmergencyContacts((array) data_get($source->meta, 'emergency_contacts', [])),
                    'day_plans' => $this->normalizeDayPlans((array) data_get($source->meta, 'day_plans', [])),
                    'vaarschema_rows' => $this->normalizeVaarschemaRows((array) data_get($source->meta, 'vaarschema_rows', [])),
                    'playbook_sections' => $sections,
                ];
            }
        }

        return Inertia::render('CampPlaybooks/Show', [
            'mode' => 'create',
            'item' => null,
            'copyItem' => $copyItem,
            'leaderTeam' => $this->leaderTeamOptions(),
            'sectionMembers' => $this->sectionMemberOptions((string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)),
            'defaultSections' => $this->defaultPlaybookSections((string) session('active_section', 'dolfijnen')),
            'defaultTaskDistributionRows' => $this->defaultTaskDistributionRows((string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)),
            'defaultTaskExplanationItems' => $this->defaultTaskExplanationItems((string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)),
            'defaultGeneralAgreementsItems' => $this->defaultGeneralAgreementsItems((string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)),
            'defaultSpeltakAgreementsItems' => $this->defaultSpeltakAgreementsItems((string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)),
            'defaultSpeltakHygieneRows' => $this->defaultSpeltakHygieneRows(),
            'defaultVinindelingRows' => $this->defaultVinindelingRows(),
            'defaultCorveeRows' => $this->defaultCorveeRows(),
            'defaultMonsterrolRows' => $this->defaultMonsterrolRows(),
            'defaultDayPlans' => $this->defaultDayPlans(),
            'defaultVaarschemaRows' => $this->defaultVaarschemaRows(),
        ]);
    }

    public function show(CampPlaybook $campPlaybook): Response
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        $activeSection = (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
        $isOwnSection = (string) $campPlaybook->section === $activeSection;
        $canReview = $this->canReviewPlaybooks($user, $activeSection);
        $isBestuurReview = $canReview
            && $activeSection === UserSectionRole::SECTION_BESTUUR
            && (string) $campPlaybook->section !== UserSectionRole::SECTION_BESTUUR;
        abort_unless($isOwnSection || $isBestuurReview, 403);

        return Inertia::render('CampPlaybooks/Show', [
            'mode' => 'edit',
            'item' => [
                'id' => (int) $campPlaybook->id,
                'camp_year' => (int) $campPlaybook->camp_year,
                'title' => (string) $campPlaybook->title,
                'camp_location' => $this->normalizeCampLocation((string) data_get($campPlaybook->meta, 'camp_location', 'fram')),
                'camp_place' => (string) data_get($campPlaybook->meta, 'camp_place', ''),
                'camp_dates' => (string) data_get($campPlaybook->meta, 'camp_dates', ''),
                'cover_photo_url' => $this->coverPhotoUrl((string) data_get($campPlaybook->meta, 'cover_photo_path', '')),
                'status' => (string) ($campPlaybook->status ?: CampPlaybook::STATUS_DRAFT),
                'review_note' => (string) ($campPlaybook->review_note ?? ''),
                'task_distribution_rows' => $this->normalizeTaskDistributionRows((array) data_get($campPlaybook->meta, 'task_distribution_rows', [])),
                'task_explanation_items' => $this->normalizeTaskExplanationItems(
                    (array) data_get($campPlaybook->meta, 'task_explanation_items', []),
                    (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
                ),
                'general_agreements_items' => $this->normalizeGeneralAgreementsItems(
                    (array) data_get($campPlaybook->meta, 'general_agreements_items', []),
                    (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
                ),
                'speltak_agreements_items' => $this->normalizeSpeltakAgreementsItems(
                    (array) data_get($campPlaybook->meta, 'speltak_agreements_items', []),
                    (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
                ),
                'speltak_hygiene_rows' => $this->normalizeSpeltakHygieneRows((array) data_get($campPlaybook->meta, 'speltak_hygiene_rows', [])),
                'vinindeling_rows' => $this->normalizeVinindelingRows((array) data_get($campPlaybook->meta, 'vinindeling_rows', [])),
                'corvee_rows' => $this->normalizeCorveeRows((array) data_get($campPlaybook->meta, 'corvee_rows', [])),
                'monsterrol_rows' => $this->normalizeMonsterrolRows((array) data_get($campPlaybook->meta, 'monsterrol_rows', [])),
                'emergency_contacts' => $this->normalizeEmergencyContacts((array) data_get($campPlaybook->meta, 'emergency_contacts', [])),
                'day_plans' => $this->normalizeDayPlans((array) data_get($campPlaybook->meta, 'day_plans', [])),
                'vaarschema_rows' => $this->normalizeVaarschemaRows((array) data_get($campPlaybook->meta, 'vaarschema_rows', [])),
                'playbook_sections' => $this->normalizePlaybookSections(
                    (array) data_get($campPlaybook->meta, 'sections', []),
                    (string) ($campPlaybook->content ?? '')
                ),
            ],
            'copyItem' => null,
            'leaderTeam' => $this->leaderTeamOptions(),
            'sectionMembers' => $this->sectionMemberOptions((string) $campPlaybook->section),
            'defaultSections' => $this->defaultPlaybookSections((string) session('active_section', 'dolfijnen')),
            'defaultTaskDistributionRows' => $this->defaultTaskDistributionRows((string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)),
            'defaultTaskExplanationItems' => $this->defaultTaskExplanationItems((string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)),
            'defaultGeneralAgreementsItems' => $this->defaultGeneralAgreementsItems((string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)),
            'defaultSpeltakAgreementsItems' => $this->defaultSpeltakAgreementsItems((string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)),
            'defaultSpeltakHygieneRows' => $this->defaultSpeltakHygieneRows(),
            'defaultVinindelingRows' => $this->defaultVinindelingRows(),
            'defaultCorveeRows' => $this->defaultCorveeRows(),
            'defaultMonsterrolRows' => $this->defaultMonsterrolRows(),
            'defaultDayPlans' => $this->defaultDayPlans(),
            'defaultVaarschemaRows' => $this->defaultVaarschemaRows(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'camp_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'camp_location' => ['nullable', 'string', 'in:clubhuis,fram'],
            'camp_place' => ['nullable', 'string', 'max:255'],
            'camp_dates' => ['nullable', 'string', 'max:255'],
            'cover_photo' => ['nullable', 'image', 'max:5120'],
            'cover_photo_remove' => ['nullable', 'boolean'],
            'task_distribution_rows' => ['nullable', 'array'],
            'task_explanation_items' => ['nullable', 'array'],
            'general_agreements_items' => ['nullable', 'array'],
            'speltak_agreements_items' => ['nullable', 'array'],
            'speltak_hygiene_rows' => ['nullable', 'array'],
            'vinindeling_rows' => ['nullable', 'array'],
            'corvee_rows' => ['nullable', 'array'],
            'monsterrol_rows' => ['nullable', 'array'],
            'emergency_contacts' => ['nullable', 'array'],
            'day_plans' => ['nullable', 'array'],
            'vaarschema_rows' => ['nullable', 'array'],
            'playbook_sections' => ['nullable', 'array'],
            'action' => ['nullable', 'string'],
        ]);

        $userId = $request->user()?->id;
        $sections = $this->normalizePlaybookSections((array) ($data['playbook_sections'] ?? []), (string) ($data['content'] ?? ''));
        $content = $this->flattenSectionsToContent($sections);
        $dayPlans = $this->normalizeDayPlans((array) ($data['day_plans'] ?? []));
        $vaarschemaRows = $this->normalizeVaarschemaRows((array) ($data['vaarschema_rows'] ?? []));
        $coverPhotoPath = $this->storeCoverPhoto($request->file('cover_photo'));
        $status = $this->statusFromAction((string) ($data['action'] ?? 'save'));
        CampPlaybook::create([
            'camp_year' => (int) $data['camp_year'],
            'title' => (string) $data['title'],
            'content' => $content,
            'meta' => [
                'sections' => $sections,
                'camp_location' => $this->normalizeCampLocation((string) ($data['camp_location'] ?? 'fram')),
                'camp_place' => trim((string) ($data['camp_place'] ?? '')),
                'camp_dates' => trim((string) ($data['camp_dates'] ?? '')),
                'cover_photo_path' => $coverPhotoPath,
                'task_distribution_rows' => $this->normalizeTaskDistributionRows((array) ($data['task_distribution_rows'] ?? [])),
                'task_explanation_items' => $this->normalizeTaskExplanationItems(
                    (array) ($data['task_explanation_items'] ?? []),
                    (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
                ),
                'general_agreements_items' => $this->normalizeGeneralAgreementsItems(
                    (array) ($data['general_agreements_items'] ?? []),
                    (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
                ),
                'speltak_agreements_items' => $this->normalizeSpeltakAgreementsItems(
                    (array) ($data['speltak_agreements_items'] ?? []),
                    (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
                ),
                'speltak_hygiene_rows' => $this->normalizeSpeltakHygieneRows((array) ($data['speltak_hygiene_rows'] ?? [])),
                'vinindeling_rows' => $this->normalizeVinindelingRows((array) ($data['vinindeling_rows'] ?? [])),
                'corvee_rows' => $this->normalizeCorveeRows((array) ($data['corvee_rows'] ?? [])),
                'monsterrol_rows' => $this->normalizeMonsterrolRows((array) ($data['monsterrol_rows'] ?? [])),
                'emergency_contacts' => $this->normalizeEmergencyContacts((array) ($data['emergency_contacts'] ?? [])),
                'day_plans' => $dayPlans,
                'vaarschema_rows' => $vaarschemaRows,
            ],
            'status' => $status,
            'submitted_by_user_id' => $status === CampPlaybook::STATUS_SUBMITTED ? $userId : null,
            'submitted_at' => $status === CampPlaybook::STATUS_SUBMITTED ? now() : null,
            'review_note' => null,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return to_route('camp-playbooks.index');
    }

    public function update(Request $request, CampPlaybook $campPlaybook)
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);

        $data = $request->validate([
            'camp_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'camp_location' => ['nullable', 'string', 'in:clubhuis,fram'],
            'camp_place' => ['nullable', 'string', 'max:255'],
            'camp_dates' => ['nullable', 'string', 'max:255'],
            'cover_photo' => ['nullable', 'image', 'max:5120'],
            'cover_photo_remove' => ['nullable', 'boolean'],
            'task_distribution_rows' => ['nullable', 'array'],
            'task_explanation_items' => ['nullable', 'array'],
            'general_agreements_items' => ['nullable', 'array'],
            'speltak_agreements_items' => ['nullable', 'array'],
            'speltak_hygiene_rows' => ['nullable', 'array'],
            'vinindeling_rows' => ['nullable', 'array'],
            'corvee_rows' => ['nullable', 'array'],
            'monsterrol_rows' => ['nullable', 'array'],
            'emergency_contacts' => ['nullable', 'array'],
            'day_plans' => ['nullable', 'array'],
            'vaarschema_rows' => ['nullable', 'array'],
            'playbook_sections' => ['nullable', 'array'],
            'action' => ['nullable', 'string'],
        ]);

        $sections = $this->normalizePlaybookSections((array) ($data['playbook_sections'] ?? []), (string) ($data['content'] ?? ''));
        $content = $this->flattenSectionsToContent($sections);
        $meta = (array) ($campPlaybook->meta ?? []);
        $meta['sections'] = $sections;
        $meta['camp_location'] = $this->normalizeCampLocation((string) ($data['camp_location'] ?? 'fram'));
        $meta['camp_place'] = trim((string) ($data['camp_place'] ?? ''));
        $meta['camp_dates'] = trim((string) ($data['camp_dates'] ?? ''));
        $existingCoverPhotoPath = (string) data_get($meta, 'cover_photo_path', '');
        $coverPhotoPath = $existingCoverPhotoPath;
        if (($data['cover_photo_remove'] ?? false) === true) {
            if ($existingCoverPhotoPath !== '') {
                Storage::disk('public')->delete($existingCoverPhotoPath);
            }
            $coverPhotoPath = '';
        }
        if ($request->hasFile('cover_photo')) {
            $coverPhotoPath = (string) ($this->storeCoverPhoto($request->file('cover_photo'), $coverPhotoPath) ?? '');
        }
        $meta['cover_photo_path'] = $coverPhotoPath;
        $meta['task_distribution_rows'] = $this->normalizeTaskDistributionRows((array) ($data['task_distribution_rows'] ?? []));
        $meta['task_explanation_items'] = $this->normalizeTaskExplanationItems(
            (array) ($data['task_explanation_items'] ?? []),
            (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
        );
        $meta['general_agreements_items'] = $this->normalizeGeneralAgreementsItems(
            (array) ($data['general_agreements_items'] ?? []),
            (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
        );
        $meta['speltak_agreements_items'] = $this->normalizeSpeltakAgreementsItems(
            (array) ($data['speltak_agreements_items'] ?? []),
            (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
        );
        $meta['speltak_hygiene_rows'] = $this->normalizeSpeltakHygieneRows((array) ($data['speltak_hygiene_rows'] ?? []));
        $meta['vinindeling_rows'] = $this->normalizeVinindelingRows((array) ($data['vinindeling_rows'] ?? []));
        $meta['corvee_rows'] = $this->normalizeCorveeRows((array) ($data['corvee_rows'] ?? []));
        $meta['monsterrol_rows'] = $this->normalizeMonsterrolRows((array) ($data['monsterrol_rows'] ?? []));
        $meta['emergency_contacts'] = $this->normalizeEmergencyContacts((array) ($data['emergency_contacts'] ?? []));
        $meta['day_plans'] = $this->normalizeDayPlans((array) ($data['day_plans'] ?? []));
        $meta['vaarschema_rows'] = $this->normalizeVaarschemaRows((array) ($data['vaarschema_rows'] ?? []));
        $status = $this->statusFromAction((string) ($data['action'] ?? 'save'));
        $actorId = $request->user()?->id;
        $submittedById = $campPlaybook->submitted_by_user_id;
        $submittedAt = $campPlaybook->submitted_at;
        if ($status === CampPlaybook::STATUS_SUBMITTED) {
            $submittedById = $actorId;
            $submittedAt = now();
        }

        $campPlaybook->update([
            'camp_year' => (int) $data['camp_year'],
            'title' => (string) $data['title'],
            'content' => $content,
            'meta' => $meta,
            'status' => $status,
            'review_note' => null,
            'submitted_by_user_id' => $submittedById,
            'submitted_at' => $submittedAt,
            'processed_by_user_id' => null,
            'processed_at' => null,
            'updated_by_user_id' => $actorId,
        ]);

        return to_route('camp-playbooks.index');
    }

    public function destroy(CampPlaybook $campPlaybook)
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);
        $campPlaybook->delete();

        return to_route('camp-playbooks.index');
    }

    public function copy(CampPlaybook $campPlaybook)
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', 'dolfijnen'), 403);

        $userId = request()->user()?->id;
        $sections = $this->normalizePlaybookSections(
            (array) data_get($campPlaybook->meta, 'sections', []),
            (string) ($campPlaybook->content ?? '')
        );
        $meta = [
            'sections' => $sections,
            'camp_location' => $this->normalizeCampLocation((string) data_get($campPlaybook->meta, 'camp_location', 'fram')),
            'camp_place' => (string) data_get($campPlaybook->meta, 'camp_place', ''),
            'camp_dates' => (string) data_get($campPlaybook->meta, 'camp_dates', ''),
            'cover_photo_path' => (string) data_get($campPlaybook->meta, 'cover_photo_path', ''),
            'task_distribution_rows' => $this->normalizeTaskDistributionRows((array) data_get($campPlaybook->meta, 'task_distribution_rows', [])),
            'task_explanation_items' => $this->normalizeTaskExplanationItems(
                (array) data_get($campPlaybook->meta, 'task_explanation_items', []),
                (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
            ),
            'general_agreements_items' => $this->normalizeGeneralAgreementsItems(
                (array) data_get($campPlaybook->meta, 'general_agreements_items', []),
                (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
            ),
            'speltak_agreements_items' => $this->normalizeSpeltakAgreementsItems(
                (array) data_get($campPlaybook->meta, 'speltak_agreements_items', []),
                (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN),
            ),
            'speltak_hygiene_rows' => $this->normalizeSpeltakHygieneRows((array) data_get($campPlaybook->meta, 'speltak_hygiene_rows', [])),
            'vinindeling_rows' => $this->normalizeVinindelingRows((array) data_get($campPlaybook->meta, 'vinindeling_rows', [])),
            'corvee_rows' => $this->normalizeCorveeRows((array) data_get($campPlaybook->meta, 'corvee_rows', [])),
            'monsterrol_rows' => $this->normalizeMonsterrolRows((array) data_get($campPlaybook->meta, 'monsterrol_rows', [])),
            'emergency_contacts' => $this->normalizeEmergencyContacts((array) data_get($campPlaybook->meta, 'emergency_contacts', [])),
            'day_plans' => $this->normalizeDayPlans((array) data_get($campPlaybook->meta, 'day_plans', [])),
            'vaarschema_rows' => $this->normalizeVaarschemaRows((array) data_get($campPlaybook->meta, 'vaarschema_rows', [])),
            'review_notes' => [],
        ];

        CampPlaybook::create([
            'section' => (string) $campPlaybook->section,
            'camp_year' => (int) $campPlaybook->camp_year,
            'title' => (string) $campPlaybook->title.' (kopie)',
            'content' => $this->flattenSectionsToContent($sections),
            'meta' => $meta,
            'status' => CampPlaybook::STATUS_DRAFT,
            'review_note' => null,
            'submitted_by_user_id' => null,
            'submitted_at' => null,
            'processed_by_user_id' => null,
            'processed_at' => null,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return to_route('camp-playbooks.index');
    }

    public function submit(Request $request, CampPlaybook $campPlaybook)
    {
        abort_unless((string) $campPlaybook->section === (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN), 403);

        $actor = $request->user();
        $campPlaybook->update([
            'status' => CampPlaybook::STATUS_SUBMITTED,
            'review_note' => null,
            'submitted_by_user_id' => $actor?->id,
            'submitted_at' => now(),
            'processed_by_user_id' => null,
            'processed_at' => null,
            'updated_by_user_id' => $actor?->id,
        ]);

        return to_route('camp-playbooks.index');
    }

    public function approve(Request $request, int $campPlaybook)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canReviewPlaybooks($user, (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)), 403);
        $campPlaybook = CampPlaybook::withoutGlobalScope('section')->findOrFail($campPlaybook);
        abort_unless((string) $campPlaybook->section !== UserSectionRole::SECTION_BESTUUR, 403);
        abort_unless((string) $campPlaybook->status === CampPlaybook::STATUS_SUBMITTED, 422);

        $campPlaybook->update([
            'status' => CampPlaybook::STATUS_APPROVED,
            'review_note' => null,
            'processed_by_user_id' => (int) $user->id,
            'processed_at' => now(),
            'updated_by_user_id' => (int) $user->id,
        ]);

        return back();
    }

    public function reject(Request $request, int $campPlaybook)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canReviewPlaybooks($user, (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)), 403);
        $campPlaybook = CampPlaybook::withoutGlobalScope('section')->findOrFail($campPlaybook);
        abort_unless((string) $campPlaybook->section !== UserSectionRole::SECTION_BESTUUR, 403);
        abort_unless((string) $campPlaybook->status === CampPlaybook::STATUS_SUBMITTED, 422);

        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        $meta = (array) ($campPlaybook->meta ?? []);
        $reviewNote = trim((string) $data['review_note']);
        $meta = $this->appendReviewNote($meta, $reviewNote, $user);

        $campPlaybook->update([
            'meta' => $meta,
            'status' => CampPlaybook::STATUS_NEEDS_CHANGES,
            'review_note' => $reviewNote,
            'processed_by_user_id' => (int) $user->id,
            'processed_at' => now(),
            'updated_by_user_id' => (int) $user->id,
        ]);

        return back();
    }

    public function downloadPdf(CampPlaybook $campPlaybook)
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        $activeSection = (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
        $isOwnSection = (string) $campPlaybook->section === $activeSection;
        $canReview = $this->canReviewPlaybooks($user, $activeSection);
        $isBestuurReview = $canReview
            && $activeSection === UserSectionRole::SECTION_BESTUUR
            && (string) $campPlaybook->section !== UserSectionRole::SECTION_BESTUUR;
        abort_unless($isOwnSection || $isBestuurReview, 403);

        $sections = $this->normalizePlaybookSections(
            (array) data_get($campPlaybook->meta, 'sections', []),
            (string) ($campPlaybook->content ?? '')
        );

        $pdf = Pdf::loadView('pdf.camp-playbook', [
            'playbook' => $campPlaybook,
            'sections' => $sections,
            'taskDistributionRows' => $this->normalizeTaskDistributionRows((array) data_get($campPlaybook->meta, 'task_distribution_rows', [])),
            'taskExplanationItems' => $this->normalizeTaskExplanationItems(
                (array) data_get($campPlaybook->meta, 'task_explanation_items', []),
                (string) $campPlaybook->section,
            ),
            'generalAgreementsItems' => $this->normalizeGeneralAgreementsItems(
                (array) data_get($campPlaybook->meta, 'general_agreements_items', []),
                (string) $campPlaybook->section,
            ),
            'speltakAgreementsItems' => $this->normalizeSpeltakAgreementsItems(
                (array) data_get($campPlaybook->meta, 'speltak_agreements_items', []),
                (string) $campPlaybook->section,
            ),
            'speltakHygieneRows' => $this->normalizeSpeltakHygieneRows((array) data_get($campPlaybook->meta, 'speltak_hygiene_rows', [])),
            'vinindelingRows' => $this->normalizeVinindelingRows((array) data_get($campPlaybook->meta, 'vinindeling_rows', [])),
            'corveeRows' => $this->normalizeCorveeRows((array) data_get($campPlaybook->meta, 'corvee_rows', [])),
            'monsterrolRows' => $this->normalizeMonsterrolRows((array) data_get($campPlaybook->meta, 'monsterrol_rows', [])),
            'emergencyContacts' => $this->normalizeEmergencyContacts((array) data_get($campPlaybook->meta, 'emergency_contacts', [])),
            'dayPlans' => $this->normalizeDayPlans((array) data_get($campPlaybook->meta, 'day_plans', [])),
            'vaarschemaRows' => $this->normalizeVaarschemaRows((array) data_get($campPlaybook->meta, 'vaarschema_rows', [])),
            'leaderTeamMap' => $this->leaderTeamMapById(),
            'logoDataUri' => $this->logoDataUri(),
            'coverPhotoDataUri' => $this->coverPhotoDataUri((string) data_get($campPlaybook->meta, 'cover_photo_path', '')),
        ])->setPaper('a4');

        $filename = $this->playbookPdfFilename($campPlaybook);

        return $pdf->download($filename);
    }

    private function playbookPdfFilename(CampPlaybook $campPlaybook): string
    {
        $sectionSlug = Str::slug(str_replace('_', ' ', (string) $campPlaybook->section), '-');
        $titleSlug = Str::slug((string) $campPlaybook->title, '-');
        if ($titleSlug === '') {
            $titleSlug = 'zonder-titel';
        }

        return sprintf(
            'draaiboek-%s-%d-%s-%s.pdf',
            $sectionSlug !== '' ? $sectionSlug : 'speltak',
            (int) $campPlaybook->camp_year,
            $titleSlug,
            now()->format('Ymd-His')
        );
    }

    private function logoDataUri(): string
    {
        $path = public_path('images/logo.png');
        if (! is_file($path)) {
            return '';
        }

        $binary = file_get_contents($path);
        if ($binary === false) {
            return '';
        }

        $mime = function_exists('mime_content_type')
            ? (mime_content_type($path) ?: 'image/png')
            : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    private function coverPhotoUrl(string $path): ?string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return null;
        }

        if (! Storage::disk('public')->exists($trimmed)) {
            return null;
        }

        return asset('storage/'.ltrim($trimmed, '/'));
    }

    private function coverPhotoDataUri(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '' || ! Storage::disk('public')->exists($trimmed)) {
            return '';
        }

        $binary = Storage::disk('public')->get($trimmed);
        if ($binary === '') {
            return '';
        }

        $extension = strtolower(pathinfo($trimmed, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    private function storeCoverPhoto(?UploadedFile $file, ?string $oldPath = null): ?string
    {
        if (! $file instanceof UploadedFile) {
            return $oldPath;
        }

        $storedPath = $file->store('camp-playbooks/covers', 'public');
        if ($oldPath && $oldPath !== $storedPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $storedPath;
    }

    /**
     * @param  array<int,mixed>  $rawSections
     * @return array<int,array{title:string,content:string}>
     */
    private function normalizePlaybookSections(array $rawSections, string $fallbackContent = ''): array
    {
        $defaults = $this->defaultPlaybookSections((string) session('active_section', 'dolfijnen'));
        $normalized = collect($rawSections)
            ->map(function ($section): array {
                return [
                    'title' => trim((string) data_get($section, 'title', '')),
                    'content' => trim((string) data_get($section, 'content', '')),
                ];
            })
            ->filter(fn (array $section): bool => $section['title'] !== '' || $section['content'] !== '')
            ->values()
            ->all();

        if ($normalized !== []) {
            $normalizedByTitle = collect($normalized)
                ->mapWithKeys(fn (array $section): array => [mb_strtolower(trim((string) ($section['title'] ?? ''))) => $section]);

            $orderedDefaults = collect($defaults)
                ->map(function (array $defaultSection) use ($normalizedByTitle): array {
                    $titleKey = mb_strtolower(trim((string) ($defaultSection['title'] ?? '')));
                    $matched = $normalizedByTitle->get($titleKey);

                    return $matched ?? $defaultSection;
                })
                ->values();

            $defaultKeys = collect($defaults)
                ->map(fn (array $section): string => mb_strtolower(trim((string) ($section['title'] ?? ''))))
                ->filter(fn (string $key): bool => $key !== '')
                ->values()
                ->all();

            $extras = collect($normalized)
                ->filter(function (array $section) use ($defaultKeys): bool {
                    $key = mb_strtolower(trim((string) ($section['title'] ?? '')));

                    return $key !== '' && ! in_array($key, $defaultKeys, true);
                })
                ->values();

            return $orderedDefaults
                ->concat($extras)
                ->values()
                ->all();
        }

        $content = trim($fallbackContent);
        if ($content !== '') {
            $defaults[0]['content'] = $content;
        }

        return $defaults;
    }

    /**
     * @return array<int,array{title:string,content:string}>
     */
    private function defaultPlaybookSections(string $activeSection): array
    {
        $sections = [
            ['title' => 'Algemeen', 'content' => ''],
            ['title' => 'Monsterrol', 'content' => ''],
            ['title' => 'Taak uitleg', 'content' => ''],
            ['title' => 'Taakverdeling', 'content' => ''],
            ['title' => 'Algemene afspraken', 'content' => ''],
            ['title' => 'Speltak afspraken', 'content' => ''],
            ['title' => 'Planning per dag', 'content' => ''],
        ];

        if ($activeSection === 'dolfijnen') {
            $sections[] = ['title' => 'Vinindeling', 'content' => ''];
        } elseif ($activeSection === 'zeeverkenners') {
            $sections[] = ['title' => 'Bakindeling', 'content' => ''];
        }

        $sections[] = ['title' => 'Corveerooster', 'content' => ''];

        $sections = [
            ...$sections,
            ['title' => 'Vaarschema', 'content' => ''],
            ['title' => 'Hulpdiensten', 'content' => ''],
        ];

        return $sections;
    }

    /**
     * @param  array<int,array{title:string,content:string}>  $sections
     */
    private function flattenSectionsToContent(array $sections): string
    {
        return collect($sections)
            ->map(function (array $section): string {
                $title = trim((string) ($section['title'] ?? ''));
                $content = trim((string) ($section['content'] ?? ''));
                if ($title === '' && $content === '') {
                    return '';
                }

                if ($title === '') {
                    return $content;
                }

                if ($content === '') {
                    return $title;
                }

                return $title.":\n".$content;
            })
            ->filter(fn (string $chunk): bool => trim($chunk) !== '')
            ->implode("\n\n");
    }

    private function normalizeCampLocation(string $campLocation): string
    {
        return in_array($campLocation, ['clubhuis', 'fram'], true) ? $campLocation : 'fram';
    }

    /**
     * @return array<int,array{task:string,responsibles:array<int,string>}>
     */
    private function defaultTaskDistributionRows(string $activeSection): array
    {
        $titles = collect($this->defaultTaskExplanationItems($activeSection))
            ->map(fn (array $item): string => trim((string) ($item['title'] ?? '')))
            ->filter(fn (string $title): bool => $title !== '')
            ->values();

        if ($titles->isEmpty()) {
            return [[
                'task' => '',
                'responsibles' => [],
            ]];
        }

        return $titles
            ->map(fn (string $title): array => [
                'task' => $title,
                'responsibles' => [],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{task:string,responsibles:array<int,string>}>
     */
    private function normalizeTaskDistributionRows(array $raw): array
    {
        $rows = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                $responsiblesRaw = $entry['responsibles'] ?? $entry['responsible'] ?? [];
                $responsibles = collect(is_array($responsiblesRaw) ? $responsiblesRaw : explode(',', (string) $responsiblesRaw))
                    ->map(fn ($name): string => trim((string) $name))
                    ->filter(fn (string $name): bool => $name !== '')
                    ->unique(fn (string $name): string => mb_strtolower($name))
                    ->values()
                    ->all();
                $task = trim((string) ($entry['task'] ?? ''));
                if (mb_strtolower($task) === 'dagverloop') {
                    $responsibles = ['Dagwacht'];
                }

                return [
                    'task' => $task,
                    'responsibles' => $responsibles,
                ];
            })
            ->filter(fn (array $row): bool => $row['task'] !== '' || $row['responsibles'] !== [])
            ->values()
            ->all();

        return $rows !== [] ? $rows : $this->defaultTaskDistributionRows((string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN));
    }

    /**
     * @return array<int,array{title:string,bullets:array<int,string>}>
     */
    private function defaultTaskExplanationItems(string $activeSection): array
    {
        $speltak = $this->sectionLabel($activeSection);

        return [
            ['title' => 'Eindverantwoording', 'bullets' => [
                'Heeft de algemene eindverantwoording voor alles wat er op het kamp gebeurt.',
                'Houdt de voorbereiding in de gaten en corrigeert waar nodig.',
                'Organiseert een reflectiemoment na het kamp.',
                'Beslist of er eventueel met ouders contact opgenomen dient te worden.',
                'Is bij calamiteiten het aanspreekpunt voor de andere leiding.',
            ]],
            ['title' => 'Draaiboek', 'bullets' => [
                'Maakt het draaiboek tijdens de voorbereiding.',
                'Is verantwoordelijk voor het aanwezig zijn van het draaiboek tijdens het kamp.',
            ]],
            ['title' => 'Contactpersoon ouders', 'bullets' => [
                'Is verantwoordelijk dat ouders tijdig op de hoogte zijn van de benodigde informatie over het kamp.',
                'Is het aanspreekpunt voor ouders wanneer zij vragen of opmerkingen hebben over het kamp.',
                'Organiseert de ouderavond indien deze gehouden wordt.',
                'Houdt de inschrijvingen en betalingen bij.',
            ]],
            ['title' => 'EHBO', 'bullets' => [
                "Aanspreekpunt voor {$speltak} en stafleden bij verwondingen en ziekten.",
                'Verzorgt zo mogelijk verwondingen of zieke.',
                'Begeleidt een jeugdlid of staf naar EHBO-post, dokter of ziekenhuis.',
                'Zoekt uit waar huisartsen en ziekenhuizen zitten op de route gedurende het kamp.',
                'Verzamelt gezondheidsformulieren en is verantwoordelijk voor de aanwezigheid hiervan.',
                'Let op de veiligheid tijdens activiteiten.',
            ]],
            ['title' => 'Medicijnen', 'bullets' => [
                'Neemt de medicijnen van de kinderen in bewaring.',
                'Zorgt ervoor dat iedereen zijn/haar medicijnen op tijd inneemt.',
            ]],
            ['title' => "Algemene verzorging {$speltak}", 'bullets' => [
                "Houdt toezicht op het welzijn van de {$speltak}.",
                "Houdt in de gaten of de {$speltak} zich voldoende verschonen op het gebied van wassen en schone kleding.",
                'Let op de kleding in het dekhuis en aan de waslijn.',
            ]],
            ['title' => 'Beheer kasgeld', 'bullets' => [
                'Heeft verantwoording over de inkomsten en uitgaven en de balans hiertussen.',
                'Maakt een begroting voor het kamp.',
                'Heeft een beslissende stem over de uitgaven.',
                'Zorgt voor uitwisseling van de begroting met de penningmeester.',
            ]],
            ['title' => 'Beheer zakgeld', 'bullets' => [
                'Neemt het zakgeld van de kinderen in bewaring.',
                'Zorgt ervoor dat het zakgeld bijgehouden wordt bij inleggen, opnemen en uitgeven aan de toko.',
            ]],
            ['title' => 'Proviand', 'bullets' => [
                'Zorgt ervoor dat er boodschappen gedaan worden.',
                'Zorgt ervoor dat bij dagtochten en meerdaagse hikes genoeg eten en drinken aan boord van de vletten is.',
            ]],
            ['title' => 'Koken', 'bullets' => [
                'Zorgt ervoor dat tijdig gegeten kan worden.',
                'Houdt in de gaten of iedereen voldoende eet.',
                'Beoordeelt de keuken na het corvee.',
            ]],
            ['title' => 'Toko', 'bullets' => [
                'Zorgt ervoor dat er snoep aan boord is voor de toko.',
                'Zet de toko klaar en ruimt hem weer op.',
            ]],
            ['title' => 'Sleep', 'bullets' => [
                'Organiseert de sleep.',
                'Houdt tijdens de sleep contact met schipper en kader in de vletten.',
                'Blijft in principe bij de sleep op het achterdek van de Fram of op de Viking.',
            ]],
            ['title' => 'ms Viking', 'bullets' => [
                'Is verantwoordelijk voor de bevaarbaarheid van de Viking (technisch, opgeruimd, uitrusting compleet).',
                'Is verantwoordelijk voor wie er met de Viking varen tijdens het kamp.',
            ]],
            ['title' => 'Vletten', 'bullets' => [
                'Draagt zorg voor het correct afmeren van de vletten.',
                'Controleert regelmatig de bakskisten.',
                'Controleert de kookkisten en vult ze zo nodig aan.',
                'Controleert de vletten op mankementen.',
            ]],
            ['title' => 'Dagverloop', 'bullets' => [
                'Is verantwoordelijk voor het nalopen van het programma dat in het draaiboek beschreven staat.',
                'Is verantwoordelijk voor het op tijd opstaan van iedereen.',
                'Is verantwoordelijk voor het controleren van het corvee.',
                'Bespreekt de dag aan het einde met de rest van de staf.',
                'Past zo nodig het dagprogramma aan in overleg met de andere staf.',
            ]],
            ['title' => 'Algemene spel coördinator', 'bullets' => [
                'Zorgt ervoor dat alle benodigde spullen voor de spellen aan boord zijn.',
                'Is verantwoordelijk voor het inventariseren van het benodigde spelmateriaal bij de andere staf.',
            ]],
        ];
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{title:string,bullets:array<int,string>}>
     */
    private function normalizeTaskExplanationItems(array $raw, string $activeSection): array
    {
        $items = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                $bullets = collect((array) ($entry['bullets'] ?? []))
                    ->map(fn ($bullet): string => trim((string) $bullet))
                    ->filter(fn (string $bullet): bool => $bullet !== '')
                    ->values()
                    ->all();

                return [
                    'title' => trim((string) ($entry['title'] ?? '')),
                    'bullets' => $bullets !== [] ? $bullets : [''],
                ];
            })
            ->filter(fn (array $item): bool => $item['title'] !== '' || collect($item['bullets'])->filter(fn (string $bullet): bool => trim($bullet) !== '')->isNotEmpty())
            ->values()
            ->all();

        return $items !== [] ? $items : $this->defaultTaskExplanationItems($activeSection);
    }

    /**
     * @return array<int,array{title:string,bullets:array<int,string>}>
     */
    private function defaultGeneralAgreementsItems(string $activeSection): array
    {
        $speltak = $this->sectionLabel($activeSection);

        return [
            ['title' => 'Algemeen', 'bullets' => [
                'Mocht je iets dwars zitten, vertel dit dan.',
                'Houd de Fram en de vletten schoon. Gooi rommel in de prullenbak.',
            ]],
            ['title' => 'Leiding - Eten en drinken', 'bullets' => [
                "Tijdens het eten zit de leiding verspreid tussen de {$speltak}.",
                "Er wordt niet gerookt in het zicht van de {$speltak}.",
                "Er mag, nadat de {$speltak} op bed liggen, beperkt gedronken worden. Dit gaat op eigen inzicht. Vuistregel is dat je de volgende dag normaal moet kunnen functioneren. Er is altijd 1 persoon die 's nachts functioneel moet zijn.",
                'Drugs zijn verboden.',
                'Onder de 18 wordt er niet gedronken.',
            ]],
            ['title' => 'Dagverloop', 'bullets' => [
                "De dagwacht staat 's ochtends eerder op en is verantwoordelijk voor het opstarten van de dag.",
                'Iedereen is aanwezig bij het ontbijt.',
                "Als de {$speltak} op bed liggen, wordt door alle leiding het programma van de volgende dag doorgesproken.",
            ]],
            ['title' => 'Plaatsen op de Fram', 'bullets' => [
                "Ga nooit zonder reden het achteronder in. Dit is het privegebied van de {$speltak}.",
                'Ga nooit in je eentje het achteronder in, maar zorg ervoor dat een ander persoon van de staf je kan zien.',
            ]],
            ['title' => 'Materiaal', 'bullets' => [
                'Laat het draaiboek nooit rondslingeren.',
                'Materialen die tijdens het programma worden gebruikt, moeten door de personen die dat spel leiden weer ordelijk opgeruimd worden op de plaats waar het hoort.',
            ]],
            ['title' => 'Omgang', 'bullets' => [
                'Er bemoeit zich maar een leiding met een jeugdlid dat heimwee heeft. Deze leiding zondert zich niet alleen met het kind af.',
                'Het kamp is in de eerste plaats voor de kinderen. Relaties of onenigheden mogen geen invloed hebben op het programma.',
            ]],
        ];
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{title:string,bullets:array<int,string>}>
     */
    private function normalizeGeneralAgreementsItems(array $raw, string $activeSection): array
    {
        $items = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                $bullets = collect((array) ($entry['bullets'] ?? []))
                    ->map(fn ($bullet): string => trim((string) $bullet))
                    ->filter(fn (string $bullet): bool => $bullet !== '')
                    ->values()
                    ->all();

                return [
                    'title' => trim((string) ($entry['title'] ?? '')),
                    'bullets' => $bullets !== [] ? $bullets : [''],
                ];
            })
            ->filter(fn (array $item): bool => $item['title'] !== '' || collect($item['bullets'])->filter(fn (string $bullet): bool => trim($bullet) !== '')->isNotEmpty())
            ->values()
            ->all();

        return $items !== [] ? $items : $this->defaultGeneralAgreementsItems($activeSection);
    }

    /**
     * @return array<int,array{title:string,bullets:array<int,string>}>
     */
    private function defaultSpeltakAgreementsItems(string $activeSection): array
    {
        $speltak = $this->sectionLabel($activeSection);
        $speltakLower = mb_strtolower($speltak);

        return [
            ['title' => "{$speltak} afspraken", 'bullets' => [
                'Er zijn vaste vinnen voor het hele kamp.',
            ]],
            ['title' => 'Eten en drinken', 'bullets' => [
                'Eerst wordt er een boterham met kaas of vlees gegeten, daarna pas zoet.',
            ]],
            ['title' => 'Dagverloop', 'bullets' => [
                'Elke dag openen we gezamenlijk.',
                "Alle {$speltakLower} doen aan alle activiteiten mee.",
                "Iedere {$speltakLower} doet het corvee zonder mopperen totdat iedereen klaar is.",
                'Wanneer er geslapen moet worden, wordt er ook geslapen en is het dus stil.',
            ]],
            ['title' => 'Plaatsen op de Fram', 'bullets' => [
                'Het achteronder dient overdag zo min mogelijk te worden bezocht.',
                "{$speltakLower} mogen niet in de poep komen.",
                "Het vooronder, de plek van het materiaal, is verboden voor de {$speltakLower}. Deze mag alleen betreden worden met toestemming van de vaarbemanning of staf.",
                'Tijdens de vaart mag er, met toestemming van de schipper van de Fram, een kijkje worden genomen in de stuurhut. In de stuurhut mogen maximaal 2 kinderen.',
                'De machinekamer is te allen tijde verboden terrein tenzij je toestemming hebt van de vaarbemanning.',
                'Tijdens af- en aanmeren en ankeren bevind je je niet op de dekken en het dak zonder nadrukkelijke vraag van iemand van de vaarbemanning.',
                "Geen enkele {$speltakLower} verlaat ongevraagd de Fram.",
            ]],
            ['title' => 'Materiaal', 'bullets' => [
                'Elektrische apparaten, stopcontacten en lampen zijn niet om mee te spelen. Ruim opladers en apparaten na het opladen meteen op.',
                'Zorg dat spullen elke ochtend in je tas zitten en op je kooi liggen zodat het opgeruimd blijft.',
                'Leg geen natte spullen in het achteronder, maar hang ze uit op dek of in het dekhuis.',
            ]],
            ['title' => 'Veiligheid', 'bullets' => [
                'Rennen is te allen tijde verboden op de Fram.',
                'Op de dekken en in het dekhuis heb je altijd dichte schoenen aan.',
                'Leg je reddingsvest op je kooi en bind het niet vast.',
                'Alle medicijnen en zakmessen worden aan het begin van het kamp in bewaring genomen door de leiding.',
            ]],
            ['title' => 'Hygiëne en gezondheid', 'bullets' => [
                'Houd de Fram en de vletten schoon. Gooi rommel in de prullenbak.',
                'Alle ziektes en wondjes, hoe klein ook, worden gemeld bij de leiding.',
                'Drinkwater zit in jerrycans op het voordek en wordt alleen gebruikt om te drinken.',
                'Kraanwater kan gebruikt worden om tanden te poetsen maar is niet drinkbaar. Gebruik zuinig water.',
            ]],
        ];
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{title:string,bullets:array<int,string>}>
     */
    private function normalizeSpeltakAgreementsItems(array $raw, string $activeSection): array
    {
        $items = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                $bullets = collect((array) ($entry['bullets'] ?? []))
                    ->map(fn ($bullet): string => trim((string) $bullet))
                    ->filter(fn (string $bullet): bool => $bullet !== '')
                    ->values()
                    ->all();

                return [
                    'title' => trim((string) ($entry['title'] ?? '')),
                    'bullets' => $bullets !== [] ? $bullets : [''],
                ];
            })
            ->filter(fn (array $item): bool => $item['title'] !== '' || collect($item['bullets'])->filter(fn (string $bullet): bool => trim($bullet) !== '')->isNotEmpty())
            ->values()
            ->all();

        return $items !== [] ? $items : $this->defaultSpeltakAgreementsItems($activeSection);
    }

    /**
     * @return array<int,array{topic:string,jerrycans:string,kraanwater:string,buitenboordwater:string,desinfectans:string}>
     */
    private function defaultSpeltakHygieneRows(): array
    {
        return [
            ['topic' => 'Drinken', 'jerrycans' => 'Ja', 'kraanwater' => 'Nee', 'buitenboordwater' => 'Nee', 'desinfectans' => 'Nee'],
            ['topic' => 'Tandenpoetsen', 'jerrycans' => 'Nee', 'kraanwater' => 'Ja', 'buitenboordwater' => 'Nee', 'desinfectans' => 'Nee'],
            ['topic' => 'Wassen', 'jerrycans' => 'Nee', 'kraanwater' => 'Nee', 'buitenboordwater' => 'Ja', 'desinfectans' => 'Nee'],
            ['topic' => 'Koken', 'jerrycans' => 'Nee', 'kraanwater' => 'Ja', 'buitenboordwater' => 'Nee', 'desinfectans' => 'Nee'],
            ['topic' => 'Handen wassen + zeep', 'jerrycans' => '', 'kraanwater' => '', 'buitenboordwater' => '', 'desinfectans' => ''],
            ['topic' => 'Na wc', 'jerrycans' => 'Nee', 'kraanwater' => 'Nee', 'buitenboordwater' => 'Ja', 'desinfectans' => 'Ja'],
            ['topic' => 'Voor eten maken', 'jerrycans' => 'Nee', 'kraanwater' => 'Ja', 'buitenboordwater' => 'Nee', 'desinfectans' => 'Nee'],
            ['topic' => 'Zichtbaar vuil voor eten', 'jerrycans' => 'Nee', 'kraanwater' => 'Nee', 'buitenboordwater' => 'Ja', 'desinfectans' => 'Nee'],
        ];
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{topic:string,jerrycans:string,kraanwater:string,buitenboordwater:string,desinfectans:string}>
     */
    private function normalizeSpeltakHygieneRows(array $raw): array
    {
        $rows = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                return [
                    'topic' => trim((string) ($entry['topic'] ?? '')),
                    'jerrycans' => trim((string) ($entry['jerrycans'] ?? '')),
                    'kraanwater' => trim((string) ($entry['kraanwater'] ?? '')),
                    'buitenboordwater' => trim((string) ($entry['buitenboordwater'] ?? '')),
                    'desinfectans' => trim((string) ($entry['desinfectans'] ?? '')),
                ];
            })
            ->filter(fn (array $row): bool => $row['topic'] !== '' || $row['jerrycans'] !== '' || $row['kraanwater'] !== '' || $row['buitenboordwater'] !== '' || $row['desinfectans'] !== '')
            ->values()
            ->all();

        return $rows !== [] ? $rows : $this->defaultSpeltakHygieneRows();
    }

    /**
     * @return array<int,array{role:string,vins:array<int,array{vin_name:string,member_names:array<int,string>}>}>
     */
    private function defaultVinindelingRows(): array
    {
        $defaultHeaders = ['De Regisseurs', 'De Acteurs', 'De Cameraploeg'];
        $defaultRoles = ['Topper', 'Tipper', 'Vinlid', 'Vinlid', 'Vinlid'];

        return collect($defaultRoles)
            ->map(fn (string $role): array => [
                'role' => $role,
                'vins' => collect($defaultHeaders)
                    ->map(fn (string $header): array => [
                        'vin_name' => $header,
                        'member_names' => [],
                    ])
                    ->all(),
            ])
            ->all();
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{role:string,vins:array<int,array{vin_name:string,member_names:array<int,string>}>}>
     */
    private function normalizeVinindelingRows(array $raw): array
    {
        $defaultHeaders = ['De Regisseurs', 'De Acteurs', 'De Cameraploeg'];
        $defaultRoles = ['Topper', 'Tipper', 'Vinlid', 'Vinlid', 'Vinlid'];

        $rows = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                $vins = collect((array) ($entry['vins'] ?? []))
                    ->filter(fn ($vin): bool => is_array($vin))
                    ->map(function (array $vin): array {
                        $memberNames = collect((array) ($vin['member_names'] ?? $vin['members'] ?? []))
                            ->map(fn ($name): string => trim((string) $name))
                            ->filter(fn (string $name): bool => $name !== '')
                            ->unique(fn (string $name): string => mb_strtolower($name))
                            ->values()
                            ->all();

                        return [
                            'vin_name' => trim((string) ($vin['vin_name'] ?? $vin['name'] ?? '')),
                            'member_names' => $memberNames,
                        ];
                    })
                    ->filter(fn (array $vin): bool => $vin['vin_name'] !== '' || $vin['member_names'] !== [])
                    ->values()
                    ->all();

                if ($vins === []) {
                    $legacyFinNames = collect((array) ($entry['fin_names'] ?? []))
                        ->map(fn ($name): string => trim((string) $name))
                        ->filter(fn (string $name): bool => $name !== '')
                        ->map(fn (string $name): array => ['vin_name' => $name, 'member_names' => []])
                        ->values()
                        ->all();

                    $vins = $legacyFinNames;
                }

                return [
                    'role' => trim((string) ($entry['role'] ?? '')),
                    'vins' => $vins,
                ];
            })
            ->values()
            ->all();

        if ($rows === []) {
            return $this->defaultVinindelingRows();
        }

        $headerCandidates = collect($rows)
            ->flatMap(fn (array $row): array => (array) ($row['vins'] ?? []))
            ->filter(fn ($vin): bool => is_array($vin))
            ->map(fn (array $vin): string => trim((string) ($vin['vin_name'] ?? '')))
            ->filter(fn (string $name): bool => $name !== '')
            ->values()
            ->all();

        $headers = [];
        foreach ($headerCandidates as $name) {
            if (! in_array($name, $headers, true)) {
                $headers[] = $name;
            }
            if (count($headers) >= count($defaultHeaders)) {
                break;
            }
        }
        while (count($headers) < count($defaultHeaders)) {
            $headers[] = $defaultHeaders[count($headers)];
        }

        $normalizedRows = collect($rows)
            ->map(function (array $row, int $index) use ($headers, $defaultRoles): array {
                $rowVins = collect((array) ($row['vins'] ?? []))
                    ->filter(fn ($vin): bool => is_array($vin))
                    ->values();

                $vins = collect($headers)->map(function (string $header, int $headerIndex) use ($rowVins): array {
                    $sourceVin = (array) ($rowVins->get($headerIndex, []));
                    $memberNames = collect((array) ($sourceVin['member_names'] ?? []))
                        ->map(fn ($name): string => trim((string) $name))
                        ->filter(fn (string $name): bool => $name !== '')
                        ->unique(fn (string $name): string => mb_strtolower($name))
                        ->values()
                        ->all();

                    return [
                        'vin_name' => $header,
                        'member_names' => $memberNames,
                    ];
                })->all();

                $fallbackRole = $defaultRoles[$index] ?? 'Vinlid';
                $role = trim((string) ($row['role'] ?? ''));

                return [
                    'role' => $role !== '' ? $role : $fallbackRole,
                    'vins' => $vins,
                ];
            })
            ->values()
            ->all();

        return $normalizedRows !== [] ? $normalizedRows : $this->defaultVinindelingRows();
    }

    /**
     * @return array<int,array{
     *   day:string,
     *   date:string,
     *   daywatch:string,
     *   dienstvin:string,
     *   dekhuis:string,
     *   achteronder_en_dekken:string,
     *   wc_en_klusjes:string
     * }>
     */
    private function defaultCorveeRows(): array
    {
        return [[
            'day' => '',
            'date' => '',
            'daywatch' => '',
            'dienstvin' => '',
            'dekhuis' => '',
            'achteronder_en_dekken' => '',
            'wc_en_klusjes' => '',
        ]];
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{
     *   day:string,
     *   date:string,
     *   daywatch:string,
     *   dienstvin:string,
     *   dekhuis:string,
     *   achteronder_en_dekken:string,
     *   wc_en_klusjes:string
     * }>
     */
    private function normalizeCorveeRows(array $raw): array
    {
        $rows = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                return [
                    'day' => trim((string) ($entry['day'] ?? '')),
                    'date' => trim((string) ($entry['date'] ?? '')),
                    'daywatch' => trim((string) ($entry['daywatch'] ?? '')),
                    'dienstvin' => trim((string) ($entry['dienstvin'] ?? '')),
                    'dekhuis' => trim((string) ($entry['dekhuis'] ?? '')),
                    'achteronder_en_dekken' => trim((string) ($entry['achteronder_en_dekken'] ?? '')),
                    'wc_en_klusjes' => trim((string) ($entry['wc_en_klusjes'] ?? '')),
                ];
            })
            ->filter(function (array $row): bool {
                return $row['day'] !== ''
                    || $row['date'] !== ''
                    || $row['daywatch'] !== ''
                    || $row['dienstvin'] !== ''
                    || $row['dekhuis'] !== ''
                    || $row['achteronder_en_dekken'] !== ''
                    || $row['wc_en_klusjes'] !== '';
            })
            ->values()
            ->all();

        return $rows !== [] ? $rows : $this->defaultCorveeRows();
    }

    private function sectionLabel(string $section): string
    {
        return match ($section) {
            UserSectionRole::SECTION_BEVERS => 'Bevers',
            UserSectionRole::SECTION_DOLFIJNEN => 'Dolfijnen',
            UserSectionRole::SECTION_ZEEVERKENNERS => 'Zeeverkenners',
            UserSectionRole::SECTION_WILDE_VAART => 'Wilde Vaart',
            UserSectionRole::SECTION_LOODSEN => 'Loodsen',
            UserSectionRole::SECTION_BESTUUR => 'Bestuur',
            default => ucfirst(str_replace('_', ' ', $section)),
        };
    }

    /**
     * @return array{
     *   crew:array<int,array{first_name:string,last_name:string,functie:string,on_board:string,off_board:string}>,
     *   speltak:array<int,array{first_name:string,last_name:string,functie:string,on_board:string,off_board:string}>
     * }
     */
    private function defaultMonsterrolRows(): array
    {
        $emptyRow = [
            'first_name' => '',
            'last_name' => '',
            'functie' => '',
            'on_board' => '',
            'off_board' => '',
        ];

        return [
            'crew' => [$emptyRow],
            'speltak' => [$emptyRow],
        ];
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array{
     *   crew:array<int,array{first_name:string,last_name:string,functie:string,on_board:string,off_board:string}>,
     *   speltak:array<int,array{first_name:string,last_name:string,functie:string,on_board:string,off_board:string}>
     * }
     */
    private function normalizeMonsterrolRows(array $raw): array
    {
        $defaults = $this->defaultMonsterrolRows();

        $normalizeRows = function (array $rows): array {
            return collect($rows)
                ->filter(fn ($row): bool => is_array($row))
                ->map(function (array $row): array {
                    return [
                        'first_name' => trim((string) ($row['first_name'] ?? '')),
                        'last_name' => trim((string) ($row['last_name'] ?? '')),
                        'functie' => trim((string) ($row['functie'] ?? '')),
                        'on_board' => trim((string) ($row['on_board'] ?? '')),
                        'off_board' => trim((string) ($row['off_board'] ?? '')),
                    ];
                })
                ->filter(fn (array $row): bool => $row['first_name'] !== '' || $row['last_name'] !== '' || $row['functie'] !== '' || $row['on_board'] !== '' || $row['off_board'] !== '')
                ->values()
                ->all();
        };

        // Backward-compatible mapping:
        // old keys were "staff" and "vaarbemanning". These are merged into the new crew table.
        $crewRows = $normalizeRows([
            ...(array) ($raw['crew'] ?? []),
            ...(array) ($raw['staff'] ?? []),
            ...(array) ($raw['vaarbemanning'] ?? []),
        ]);
        $speltakRows = $normalizeRows((array) ($raw['speltak'] ?? []));

        $defaults['crew'] = $crewRows !== [] ? $crewRows : [[
            'first_name' => '',
            'last_name' => '',
            'functie' => '',
            'on_board' => '',
            'off_board' => '',
        ]];
        $defaults['speltak'] = $speltakRows !== [] ? $speltakRows : [[
            'first_name' => '',
            'last_name' => '',
            'functie' => '',
            'on_board' => '',
            'off_board' => '',
        ]];

        return $defaults;
    }

    /**
     * @return array{
     *   huisartsen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>,
     *   ziekenhuizen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>,
     *   tandartsen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>
     * }
     */
    private function defaultEmergencyContacts(): array
    {
        $empty = [
            'name' => '',
            'address' => '',
            'postal_code' => '',
            'city' => '',
            'phone_010' => '',
            'website' => '',
            'extra_info' => '',
        ];

        return [
            'huisartsen' => [$empty],
            'ziekenhuizen' => [$empty],
            'tandartsen' => [$empty],
        ];
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array{
     *   huisartsen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>,
     *   ziekenhuizen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>,
     *   tandartsen:array<int,array{name:string,address:string,postal_code:string,city:string,phone_010:string,website:string,extra_info:string}>
     * }
     */
    private function normalizeEmergencyContacts(array $raw): array
    {
        $defaults = $this->defaultEmergencyContacts();
        $normalizeEntry = function (array $entry): array {
            return [
                'name' => trim((string) ($entry['name'] ?? '')),
                'address' => trim((string) ($entry['address'] ?? '')),
                'postal_code' => trim((string) ($entry['postal_code'] ?? '')),
                'city' => trim((string) ($entry['city'] ?? '')),
                'phone_010' => trim((string) ($entry['phone_010'] ?? '')),
                'website' => trim((string) ($entry['website'] ?? '')),
                'extra_info' => trim((string) ($entry['extra_info'] ?? '')),
            ];
        };

        foreach (array_keys($defaults) as $category) {
            $rawCategory = $raw[$category] ?? [];
            $rows = [];

            if (is_array($rawCategory) && array_is_list($rawCategory)) {
                $rows = collect($rawCategory)
                    ->filter(fn ($entry): bool => is_array($entry))
                    ->map(fn (array $entry): array => $normalizeEntry($entry))
                    ->filter(fn (array $entry): bool => collect($entry)->contains(fn (string $value): bool => $value !== ''))
                    ->values()
                    ->all();
            } elseif (is_array($rawCategory)) {
                // Backward compatibility with old single object shape.
                $single = $normalizeEntry($rawCategory);
                $rows = collect([$single])
                    ->filter(fn (array $entry): bool => collect($entry)->contains(fn (string $value): bool => $value !== ''))
                    ->values()
                    ->all();
            }

            $defaults[$category] = $rows !== [] ? $rows : $this->defaultEmergencyContacts()[$category];
        }

        return $defaults;
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{day_label:string,daywatch_ids:array<int,int>,planning_rows:array<int,array{time:string,program:string,game:string,needs:string}>,game_explanation:string}>
     */
    private function normalizeDayPlans(array $raw): array
    {
        $defaultPlanningRows = $this->defaultPlanningRows();
        $normalized = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                $defaultPlanningRows = $this->defaultPlanningRows();
                $rows = collect((array) ($entry['planning_rows'] ?? []))
                    ->filter(fn ($row): bool => is_array($row))
                    ->map(function (array $row): array {
                        return [
                            'time' => trim((string) ($row['time'] ?? '')),
                            'program' => trim((string) ($row['program'] ?? '')),
                            'game' => trim((string) ($row['game'] ?? '')),
                            'needs' => trim((string) ($row['needs'] ?? '')),
                        ];
                    })
                    ->filter(fn (array $row): bool => $row['time'] !== '' || $row['program'] !== '' || $row['game'] !== '' || $row['needs'] !== '')
                    ->values()
                    ->all();

                $daywatchIds = collect((array) ($entry['daywatch_ids'] ?? []))
                    ->map(fn ($id): int => (int) $id)
                    ->filter(fn (int $id): bool => $id > 0)
                    ->values()
                    ->all();

                return [
                    'day_label' => trim((string) ($entry['day_label'] ?? '')),
                    'daywatch_ids' => $daywatchIds,
                    'planning_rows' => $rows !== [] ? $rows : $defaultPlanningRows,
                    'game_explanation' => trim((string) ($entry['game_explanation'] ?? '')),
                ];
            })
            ->filter(fn (array $day): bool => $day['day_label'] !== '' || $day['game_explanation'] !== '' || $day['planning_rows'] !== $defaultPlanningRows || $day['daywatch_ids'] !== [])
            ->values()
            ->all();

        return $normalized !== [] ? $normalized : $this->defaultDayPlans();
    }

    /**
     * @return array<int,array{day_label:string,daywatch_ids:array<int,int>,planning_rows:array<int,array{time:string,program:string,game:string,needs:string}>,game_explanation:string}>
     */
    private function defaultDayPlans(): array
    {
        return [[
            'day_label' => 'Dag 1',
            'daywatch_ids' => [],
            'planning_rows' => $this->defaultPlanningRows(),
            'game_explanation' => '',
        ]];
    }

    /**
     * @return array<int,array{time:string,program:string,game:string,needs:string}>
     */
    private function defaultPlanningRows(): array
    {
        return [
            ['time' => '7:30', 'program' => 'Opstaan dagwacht en dienstvin', 'game' => '', 'needs' => ''],
            ['time' => '8:00', 'program' => 'Opstaan dolfijnen', 'game' => '', 'needs' => ''],
            ['time' => '8:30', 'program' => 'Ontbijt en corvee', 'game' => '', 'needs' => ''],
            ['time' => '10:00', 'program' => 'Ochtendprogramma', 'game' => '', 'needs' => ''],
            ['time' => '12:00', 'program' => 'Einde ochtendprogramma', 'game' => '', 'needs' => ''],
            ['time' => '12:30', 'program' => 'Lunch en corvee', 'game' => '', 'needs' => ''],
            ['time' => '14:00', 'program' => 'Middagprogramma', 'game' => '', 'needs' => ''],
            ['time' => '16:00', 'program' => 'Einde middagprogramma', 'game' => '', 'needs' => ''],
            ['time' => '17:30', 'program' => 'Avondmaaltijd en corvee', 'game' => '', 'needs' => ''],
            ['time' => '19:00', 'program' => 'Avondprogramma', 'game' => '', 'needs' => ''],
            ['time' => '20:30', 'program' => 'Einde avondprogramma / Dolfijn naar bed', 'game' => '', 'needs' => ''],
            ['time' => '21:00', 'program' => 'Dolfijnen stil', 'game' => '', 'needs' => ''],
            ['time' => '22:00', 'program' => 'Stafoverleg', 'game' => '', 'needs' => ''],
        ];
    }

    /**
     * @return array<int,array{id:int,name:string}>
     */
    private function leaderTeamOptions(): array
    {
        $activeSection = (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN);

        return User::query()
            ->whereNotNull('first_name')
            ->whereHas('sectionRoles', function ($query) use ($activeSection): void {
                $query->where('section', $activeSection)
                    ->whereIn('role', [UserSectionRole::ROLE_TEAMLEIDER, UserSectionRole::ROLE_LEIDING]);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (User $leader): array => [
                'id' => (int) $leader->id,
                'name' => trim(((string) $leader->first_name).' '.((string) ($leader->last_name ?? ''))),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int,array{id:int,name:string}>
     */
    private function sectionMemberOptions(string $section): array
    {
        return Member::query()
            ->withoutGlobalScope('section')
            ->where('section', $section)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->map(fn (Member $member): array => [
                'id' => (int) $member->id,
                'name' => trim(((string) $member->first_name).' '.((string) ($member->last_name ?? ''))),
            ])
            ->filter(fn (array $member): bool => trim((string) ($member['name'] ?? '')) !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int,string>
     */
    private function leaderTeamMapById(): array
    {
        return collect($this->leaderTeamOptions())
            ->mapWithKeys(fn (array $leader): array => [(int) ($leader['id'] ?? 0) => (string) ($leader['name'] ?? 'Onbekend')])
            ->all();
    }

    private function canReviewPlaybooks(User $user, string $activeSection): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        if ($activeSection !== UserSectionRole::SECTION_BESTUUR) {
            return false;
        }

        return $user->isGlobalBoardMember()
            || $user->sectionRoles()
                ->where('section', UserSectionRole::SECTION_BESTUUR)
                ->whereIn('role', UserSectionRole::BESTUUR_ROLES)
                ->exists();
    }

    private function statusFromAction(string $action): string
    {
        return $action === 'submit'
            ? CampPlaybook::STATUS_SUBMITTED
            : CampPlaybook::STATUS_DRAFT;
    }

    /**
     * @param  array<string,mixed>  $meta
     * @return array<string,mixed>
     */
    private function appendReviewNote(array $meta, string $note, User $actor): array
    {
        $history = collect((array) data_get($meta, 'review_notes', []))
            ->filter(fn ($entry): bool => is_array($entry))
            ->values();

        $history->push([
            'note' => $note,
            'user_name' => (string) $actor->name,
            'user_id' => (int) $actor->id,
            'at' => now()->toIso8601String(),
        ]);

        $meta['review_notes'] = $history->take(-100)->values()->all();

        return $meta;
    }

    /**
     * @param  array<int,mixed>  $rawNotes
     * @return array<int,array{note:string,user_name:string,at:string}>
     */
    private function reviewNotesForPayload(array $rawNotes): array
    {
        return collect($rawNotes)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                return [
                    'note' => trim((string) ($entry['note'] ?? '')),
                    'user_name' => trim((string) ($entry['user_name'] ?? 'Onbekend')),
                    'at' => trim((string) ($entry['at'] ?? '')),
                ];
            })
            ->filter(fn (array $entry): bool => $entry['note'] !== '')
            ->sortByDesc('at')
            ->values()
            ->all();
    }

    /**
     * @param  array<int,mixed>  $raw
     * @return array<int,array{date:string,from:string,to:string,depart_at:string,arrive_at:string,tide_margin_minutes:string}>
     */
    private function normalizeVaarschemaRows(array $raw): array
    {
        $rows = collect($raw)
            ->filter(fn ($entry): bool => is_array($entry))
            ->map(function (array $entry): array {
                return [
                    'date' => trim((string) ($entry['date'] ?? '')),
                    'from' => trim((string) ($entry['from'] ?? '')),
                    'to' => trim((string) ($entry['to'] ?? '')),
                    'depart_at' => trim((string) ($entry['depart_at'] ?? '')),
                    'arrive_at' => trim((string) ($entry['arrive_at'] ?? '')),
                    'tide_margin_minutes' => (string) $this->normalizeTideMarginMinutes((string) ($entry['tide_margin_minutes'] ?? '0')),
                ];
            })
            ->filter(fn (array $row): bool => $row['date'] !== '' || $row['from'] !== '' || $row['to'] !== '' || $row['depart_at'] !== '' || $row['arrive_at'] !== '' || $row['tide_margin_minutes'] !== '')
            ->values()
            ->all();

        return $rows !== [] ? $rows : $this->defaultVaarschemaRows();
    }

    /**
     * @return array<int,array{date:string,from:string,to:string,depart_at:string,arrive_at:string,tide_margin_minutes:string}>
     */
    private function defaultVaarschemaRows(): array
    {
        return [
            [
                'date' => '',
                'from' => 'Koedood',
                'to' => '',
                'depart_at' => '',
                'arrive_at' => '',
                'tide_margin_minutes' => '0',
            ],
            [
                'date' => '',
                'from' => '',
                'to' => 'Koedood',
                'depart_at' => '',
                'arrive_at' => '',
                'tide_margin_minutes' => '0',
            ],
        ];
    }

    private function normalizeTideMarginMinutes(string $value): int
    {
        $normalized = preg_replace('/[^\d-]/', '', trim($value));
        if ($normalized === null || $normalized === '') {
            return 0;
        }

        $minutes = (int) $normalized;

        return max(0, $minutes);
    }
}
