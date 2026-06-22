<?php

namespace App\Http\Controllers;

use App\Models\CampBudget;
use App\Models\User;
use App\Models\UserSectionRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CampBudgetController extends Controller
{
    public function index(): Response
    {
        $user = request()->user();
        abort_unless($user instanceof User, 403);
        $activeSection = (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
        $canReview = $this->canReviewBudgets($user, $activeSection);

        $query = CampBudget::query();
        if ($canReview && $activeSection === UserSectionRole::SECTION_BESTUUR) {
            $query = CampBudget::withoutGlobalScope('section')
                ->where('section', '!=', UserSectionRole::SECTION_BESTUUR);
        }

        return Inertia::render('CampBudgets/Index', [
            'items' => $query
                ->latest('camp_year')
                ->latest('id')
                ->get()
                ->map(fn (CampBudget $item): array => [
                    ...$this->indexItemPayload($item, $canReview),
                ])
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
     *   pdf_path:string,
     *   status:string,
     *   review_note:string,
     *   review_notes:array<int,array{note:string,user_name:string,at:string}>,
     *   created_by_name:string,
     *   updated_at:?string,
     *   can_review:bool,
     *   totals:array{income:float,expenses:float,difference:float}
     * }
     */
    private function indexItemPayload(CampBudget $item, bool $canReview): array
    {
        $sections = $this->normalizeSections(data_get($item->meta, 'sections', []));
        $standardValues = $this->normalizeStandardValues(data_get($item->meta, 'standard_values', []));
        $campDays = $this->normalizeCampDays((int) data_get($item->meta, 'camp_days', 1));
        $campLocation = $this->normalizeCampLocation((string) data_get($item->meta, 'camp_location', 'fram'));
        $totals = $this->totalsForSections($sections, $standardValues, $campDays, $campLocation);

        return [
            'id' => (int) $item->id,
            'section' => (string) $item->section,
            'camp_year' => (int) $item->camp_year,
            'title' => (string) $item->title,
            'pdf_path' => (string) data_get($item->meta, 'pdf_path', ''),
            'status' => (string) ($item->status ?: CampBudget::STATUS_DRAFT),
            'review_note' => (string) ($item->review_note ?? ''),
            'review_notes' => $this->reviewNotesForPayload((array) data_get($item->meta, 'review_notes', [])),
            'created_by_name' => (string) optional($item->createdBy)->name,
            'updated_by_name' => (string) optional($item->updatedBy)->name,
            'updated_at' => optional($item->updated_at)?->toIso8601String(),
            'can_review' => $canReview && in_array((string) $item->status, [CampBudget::STATUS_SUBMITTED], true),
            'totals' => $totals,
        ];
    }

    public function create(Request $request): Response
    {
        $copyId = (int) $request->query('copy', 0);
        $copyItem = null;
        if ($copyId > 0) {
            $source = CampBudget::query()->find($copyId);
            if ($source && (string) $source->section === (string) session('active_section', 'dolfijnen')) {
                $copyItem = [
                    'camp_year' => (int) $source->camp_year,
                    'title' => (string) $source->title,
                    'content' => (string) ($source->content ?? ''),
                    'camp_days' => $this->normalizeCampDays((int) data_get($source->meta, 'camp_days', 1)),
                    'camp_location' => $this->normalizeCampLocation((string) data_get($source->meta, 'camp_location', 'fram')),
                    'budget_sections' => $this->normalizeSections(data_get($source->meta, 'sections', [])),
                    'standard_values' => $this->normalizeStandardValues(data_get($source->meta, 'standard_values', [])),
                ];
            }
        }

        return Inertia::render('CampBudgets/Show', [
            'mode' => 'create',
            'item' => null,
            'copyItem' => $copyItem,
            'defaultSections' => $this->defaultSections(),
            'defaultStandardValues' => $this->defaultStandardValues(),
        ]);
    }

    public function show(CampBudget $campBudget): Response
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);

        return Inertia::render('CampBudgets/Show', [
            'mode' => 'edit',
            'item' => [
                'id' => (int) $campBudget->id,
                'camp_year' => (int) $campBudget->camp_year,
                'title' => (string) $campBudget->title,
                'content' => (string) ($campBudget->content ?? ''),
                'camp_days' => $this->normalizeCampDays((int) data_get($campBudget->meta, 'camp_days', 1)),
                'camp_location' => $this->normalizeCampLocation((string) data_get($campBudget->meta, 'camp_location', 'fram')),
                'budget_sections' => $this->normalizeSections(data_get($campBudget->meta, 'sections', [])),
                'pdf_path' => (string) data_get($campBudget->meta, 'pdf_path', ''),
                'status' => (string) ($campBudget->status ?: CampBudget::STATUS_DRAFT),
                'review_note' => (string) ($campBudget->review_note ?? ''),
                'standard_values' => $this->normalizeStandardValues(data_get($campBudget->meta, 'standard_values', [])),
            ],
            'copyItem' => null,
            'defaultSections' => $this->defaultSections(),
            'defaultStandardValues' => $this->defaultStandardValues(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'camp_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'camp_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'camp_location' => ['nullable', 'string', 'in:clubhuis,fram'],
            'budget_sections' => ['nullable', 'array'],
            'standard_values' => ['nullable', 'array'],
            'action' => ['nullable', 'string'],
        ]);
        $sections = $this->normalizeSections((array) ($data['budget_sections'] ?? []));
        $standardValues = $this->normalizeStandardValues((array) ($data['standard_values'] ?? []));
        $campDays = $this->normalizeCampDays((int) ($data['camp_days'] ?? 1));
        $campLocation = $this->normalizeCampLocation((string) ($data['camp_location'] ?? 'fram'));

        $status = $this->statusFromAction((string) ($data['action'] ?? 'save'));
        $actor = $request->user();
        $userId = $actor?->id;
        $meta = [
            'sections' => $sections,
            'standard_values' => $standardValues,
            'camp_days' => $campDays,
            'camp_location' => $campLocation,
        ];
        $meta = $this->appendChangeLog($meta, $status === CampBudget::STATUS_SUBMITTED ? 'submitted' : 'saved_draft', $actor);
        CampBudget::create([
            'camp_year' => (int) $data['camp_year'],
            'title' => (string) $data['title'],
            'content' => (string) ($data['content'] ?? ''),
            'meta' => $meta,
            'status' => $status,
            'submitted_by_user_id' => $status === CampBudget::STATUS_SUBMITTED ? $userId : null,
            'submitted_at' => $status === CampBudget::STATUS_SUBMITTED ? now() : null,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return $this->redirectAfterSave($request, config('save-redirects.camp_budgets'));
    }

    public function update(Request $request, CampBudget $campBudget)
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);

        $data = $request->validate([
            'camp_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'camp_days' => ['nullable', 'integer', 'min:1', 'max:60'],
            'camp_location' => ['nullable', 'string', 'in:clubhuis,fram'],
            'budget_sections' => ['nullable', 'array'],
            'standard_values' => ['nullable', 'array'],
            'action' => ['nullable', 'string'],
        ]);
        $sections = $this->normalizeSections((array) ($data['budget_sections'] ?? []));
        $standardValues = $this->normalizeStandardValues((array) ($data['standard_values'] ?? []));
        $campDays = $this->normalizeCampDays((int) ($data['camp_days'] ?? 1));
        $campLocation = $this->normalizeCampLocation((string) ($data['camp_location'] ?? 'fram'));

        $status = $this->statusFromAction((string) ($data['action'] ?? 'save'));
        $actor = $request->user();
        $meta = (array) ($campBudget->meta ?? []);
        $meta['sections'] = $sections;
        $meta['standard_values'] = $standardValues;
        $meta['camp_days'] = $campDays;
        $meta['camp_location'] = $campLocation;
        $meta = $this->appendChangeLog($meta, $status === CampBudget::STATUS_SUBMITTED ? 'submitted' : 'saved_draft', $actor);
        $campBudget->update([
            'camp_year' => (int) $data['camp_year'],
            'title' => (string) $data['title'],
            'content' => (string) ($data['content'] ?? ''),
            'meta' => $meta,
            'status' => $status,
            'review_note' => null,
            'submitted_by_user_id' => $status === CampBudget::STATUS_SUBMITTED ? $actor?->id : null,
            'submitted_at' => $status === CampBudget::STATUS_SUBMITTED ? now() : null,
            'processed_by_user_id' => null,
            'processed_at' => null,
            'updated_by_user_id' => $actor?->id,
        ]);

        return $this->redirectAfterSave($request, config('save-redirects.camp_budgets'));
    }

    public function destroy(CampBudget $campBudget)
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);
        $campBudget->delete();

        return to_route('camp-budgets.index');
    }

    public function copy(CampBudget $campBudget)
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);

        $actor = request()->user();
        $userId = $actor?->id;
        $meta = (array) ($campBudget->meta ?? []);
        unset($meta['review_notes']);
        $meta = $this->appendChangeLog($meta, 'copied', $actor);
        CampBudget::create([
            'section' => (string) $campBudget->section,
            'camp_year' => (int) $campBudget->camp_year,
            'title' => (string) $campBudget->title.' (kopie)',
            'content' => (string) ($campBudget->content ?? ''),
            'meta' => $meta,
            'review_note' => null,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return to_route('camp-budgets.index');
    }

    public function generatePdf(CampBudget $campBudget)
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);
        $sections = $this->normalizeSections(data_get($campBudget->meta, 'sections', []));
        $standardValues = $this->normalizeStandardValues(data_get($campBudget->meta, 'standard_values', []));
        $campDays = $this->normalizeCampDays((int) data_get($campBudget->meta, 'camp_days', 1));
        $campLocation = $this->normalizeCampLocation((string) data_get($campBudget->meta, 'camp_location', 'fram'));
        $totals = $this->totalsForSections($sections, $standardValues, $campDays, $campLocation);
        $sectionsForPdf = $this->sectionsForPdf($sections, $standardValues, $campDays, $campLocation);

        $pdf = Pdf::loadView('pdf.camp-budget', [
            'budget' => $campBudget,
            'sections' => $sectionsForPdf,
            'standardValues' => $standardValues,
            'totals' => $totals,
            'campDays' => $campDays,
            'campLocation' => $campLocation,
            'logoDataUri' => $this->logoDataUri(),
        ])->setPaper('a4');

        $filename = $this->budgetPdfFilename($campBudget);
        $path = 'camp-budgets/'.$filename;
        Storage::disk('local')->put($path, $pdf->output());

        $meta = (array) ($campBudget->meta ?? []);
        $meta['sections'] = $sections;
        $meta['pdf_path'] = $path;
        $meta['pdf_generated_at'] = now()->toIso8601String();
        $campBudget->update([
            'meta' => $meta,
            'updated_by_user_id' => request()->user()?->id,
        ]);

        return to_route('camp-budgets.show', $campBudget->id);
    }

    public function approve(Request $request, int $campBudget)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canReviewBudgets($user, (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)), 403);
        $campBudget = CampBudget::withoutGlobalScope('section')->findOrFail($campBudget);
        abort_unless((string) $campBudget->section !== UserSectionRole::SECTION_BESTUUR, 403);
        abort_unless((string) $campBudget->status === CampBudget::STATUS_SUBMITTED, 422);

        $path = $this->buildAndStorePdf($campBudget);
        $meta = (array) ($campBudget->meta ?? []);
        $meta['pdf_path'] = $path;
        $meta['pdf_generated_at'] = now()->toIso8601String();
        $meta = $this->appendChangeLog($meta, 'approved', $user);

        $campBudget->update([
            'meta' => $meta,
            'status' => CampBudget::STATUS_APPROVED,
            'review_note' => null,
            'processed_by_user_id' => (int) $user->id,
            'processed_at' => now(),
            'updated_by_user_id' => (int) $user->id,
        ]);

        return back();
    }

    public function submit(Request $request, CampBudget $campBudget)
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN), 403);
        $actor = $request->user();
        $meta = (array) ($campBudget->meta ?? []);
        $meta = $this->appendChangeLog($meta, 'submitted', $actor);

        $campBudget->update([
            'meta' => $meta,
            'status' => CampBudget::STATUS_SUBMITTED,
            'review_note' => null,
            'submitted_by_user_id' => $actor?->id,
            'submitted_at' => now(),
            'processed_by_user_id' => null,
            'processed_at' => null,
            'updated_by_user_id' => $actor?->id,
        ]);

        return to_route('camp-budgets.index');
    }

    public function reject(Request $request, int $campBudget)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canReviewBudgets($user, (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN)), 403);
        $campBudget = CampBudget::withoutGlobalScope('section')->findOrFail($campBudget);
        abort_unless((string) $campBudget->section !== UserSectionRole::SECTION_BESTUUR, 403);
        abort_unless((string) $campBudget->status === CampBudget::STATUS_SUBMITTED, 422);

        $data = $request->validate([
            'review_note' => ['required', 'string', 'max:1000'],
        ]);

        $meta = (array) ($campBudget->meta ?? []);
        $meta = $this->appendChangeLog($meta, 'needs_changes', $user);
        $reviewNote = trim((string) $data['review_note']);
        $meta = $this->appendReviewNote($meta, $reviewNote, $user);

        $campBudget->update([
            'meta' => $meta,
            'status' => CampBudget::STATUS_NEEDS_CHANGES,
            'review_note' => $reviewNote,
            'processed_by_user_id' => (int) $user->id,
            'processed_at' => now(),
            'updated_by_user_id' => (int) $user->id,
        ]);

        return back();
    }

    public function downloadPdf(CampBudget $campBudget)
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);
        $path = $this->buildAndStorePdf($campBudget);
        $meta = (array) ($campBudget->meta ?? []);
        $meta['pdf_path'] = $path;
        $meta['pdf_generated_at'] = now()->toIso8601String();
        $campBudget->update([
            'meta' => $meta,
            'updated_by_user_id' => request()->user()?->id,
        ]);

        return response()->download(
            Storage::disk('local')->path($path),
            basename($path),
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * @param  array<int,mixed>  $rawSections
     * @return array<int,array{title:string,rows:array<int,array{label:string,quantity:float,amount:float,note:string}>}>
     */
    private function normalizeSections(array $rawSections): array
    {
        $normalized = collect($rawSections)
            ->map(function ($section): array {
                $title = trim((string) data_get($section, 'title', ''));
                $rows = collect((array) data_get($section, 'rows', []))
                    ->map(function ($row) use ($title): array {
                        $label = trim((string) data_get($row, 'label', ''));
                        $quantity = round((float) data_get($row, 'quantity', 0), 2);
                        if ($this->hasFixedQuantityOne($title, $label)) {
                            $quantity = 1.0;
                        }

                        return [
                            'label' => $label,
                            'quantity' => $quantity,
                            'amount' => round((float) data_get($row, 'amount', 0), 2),
                            'note' => trim((string) data_get($row, 'note', '')),
                        ];
                    })
                    ->filter(fn (array $row): bool => $row['label'] !== '' || $row['quantity'] !== 0.0 || $row['amount'] !== 0.0 || $row['note'] !== '')
                    ->values()
                    ->all();

                return ['title' => $title, 'rows' => $rows];
            })
            ->reject(function (array $section): bool {
                $title = mb_strtolower(trim((string) ($section['title'] ?? '')));

                return $title === 'bemanning en deelnemers';
            })
            ->filter(fn (array $section): bool => $section['title'] !== '' || $section['rows'] !== [])
            ->values()
            ->all();

        return $normalized !== [] ? $normalized : $this->defaultSections();
    }

    /**
     * @return array<int,array{title:string,rows:array<int,array{label:string,quantity:float,amount:float,note:string}>}>
     */
    private function defaultSections(): array
    {
        return [
            ['title' => 'Bijdragen', 'rows' => [['label' => 'Leiding', 'quantity' => 0, 'amount' => 0, 'note' => ''], ['label' => 'Jeugdleden', 'quantity' => 0, 'amount' => 0, 'note' => ''], ['label' => 'Vaarbemanning', 'quantity' => 0, 'amount' => 0, 'note' => '']]],
            ['title' => 'Uitgaven', 'rows' => [['label' => 'Geschatte vaaruren', 'quantity' => 0, 'amount' => 0, 'note' => ''], ['label' => 'Geschatte aggregaaturen', 'quantity' => 0, 'amount' => 0, 'note' => ''], ['label' => 'Proviand', 'quantity' => 1, 'amount' => 0, 'note' => ''], ['label' => 'Groepsafdracht', 'quantity' => 1, 'amount' => 0, 'note' => ''], ['label' => 'Reservering NaWaKa', 'quantity' => 1, 'amount' => 0, 'note' => ''], ['label' => 'Thema en spel', 'quantity' => 0, 'amount' => 0, 'note' => '']]],
            ['title' => 'Overige bijdragen', 'rows' => []],
            ['title' => 'Overige uitgaven', 'rows' => []],
        ];
    }

    /**
     * @param  array<int,array{title:string,rows:array<int,array{label:string,quantity:float,amount:float,note:string}>}>  $sections
     * @param  array<string,float>  $standardValues
     * @return array{income:float,expenses:float,difference:float}
     */
    private function totalsForSections(array $sections, array $standardValues, int $campDays, string $campLocation): array
    {
        $campDays = $this->normalizeCampDays($campDays);
        $campLocation = $this->normalizeCampLocation($campLocation);
        $incomeTitles = ['bijdragen', 'overige bijdragen'];
        $expenseTitles = ['uitgaven', 'overige uitgaven'];
        $income = 0.0;
        $expenses = 0.0;
        foreach ($sections as $section) {
            $sum = collect($section['rows'])->sum(function (array $row) use ($section, $sections, $standardValues, $campDays, $campLocation): float {
                if ($this->isRefundableDepositRow((string) ($section['title'] ?? ''), (string) ($row['label'] ?? ''))) {
                    return 0.0;
                }

                return $this->rowTotalForCalculation($row, (string) ($section['title'] ?? ''), $sections, $standardValues, $campDays, $campLocation);
            });
            $title = mb_strtolower(trim($section['title']));
            if (in_array($title, $incomeTitles, true)) {
                $income += $sum;
            } elseif (in_array($title, $expenseTitles, true)) {
                $expenses += $sum;
            }
        }

        return [
            'income' => round($income, 2),
            'expenses' => round($expenses, 2),
            'difference' => round($income - $expenses, 2),
        ];
    }

    /**
     * @param  array<string,mixed>  $raw
     * @return array<string,float>
     */
    private function normalizeStandardValues(array $raw): array
    {
        $defaults = $this->defaultStandardValues();
        if (! array_key_exists('clubhuis_bedrag', $raw) && array_key_exists('prijs_per_dag_clubhuis', $raw)) {
            $raw['clubhuis_bedrag'] = $raw['prijs_per_dag_clubhuis'];
        }
        foreach ($defaults as $key => $value) {
            $defaults[$key] = round((float) ($raw[$key] ?? $value), 2);
        }

        return $defaults;
    }

    /**
     * @return array<string,float>
     */
    private function defaultStandardValues(): array
    {
        return [
            'clubhuis_bedrag' => 0.00,
            'borg_bedrag' => 0.00,
            'prijs_per_dag_clubhuis' => 0.00,
            'prijs_per_dag_leiding' => 0.00,
            'prijs_per_dag_jeugdlid' => 80.00,
            'kosten_vaart_pu' => 0.00,
            'kosten_aggregaat_pu' => 0.00,
            'huur_fram_pppd' => 0.00,
            'proviand_pppd' => 0.00,
            'groepsafdracht_pjpd' => 0.00,
            'reservering_nawaka_pjpd' => 0.00,
        ];
    }

    /**
     * @param  array{label:string,quantity:float,amount:float,note:string}  $row
     * @param  array<string,float>  $standardValues
     */
    private function effectiveAmount(array $row, string $sectionTitle, array $standardValues, int $campDays, string $campLocation): float
    {
        $campDays = $this->normalizeCampDays($campDays);
        $label = mb_strtolower(trim((string) ($row['label'] ?? '')));
        $section = mb_strtolower(trim($sectionTitle));
        $manualAmount = (float) ($row['amount'] ?? 0);
        if ($label === '') {
            return $manualAmount;
        }

        if ($section === 'bijdragen' && str_contains($label, 'leiding')) {
            return (float) ($standardValues['prijs_per_dag_leiding'] ?? 0) * $campDays;
        }
        if ($section === 'bijdragen' && (str_contains($label, 'jeugdleden') || str_contains($label, 'jeugdlid'))) {
            return (float) ($standardValues['prijs_per_dag_jeugdlid'] ?? 0);
        }
        if ($section === 'uitgaven' && str_contains($label, 'vaar')) {
            return (float) ($standardValues['kosten_vaart_pu'] ?? 0);
        }
        if ($section === 'uitgaven' && str_contains($label, 'aggreg')) {
            return (float) ($standardValues['kosten_aggregaat_pu'] ?? 0);
        }
        if ($section === 'uitgaven' && str_contains($label, 'huur fram')) {
            return (float) ($standardValues['huur_fram_pppd'] ?? 0);
        }
        if ($section === 'uitgaven' && str_contains($label, 'borg')) {
            return (float) ($standardValues['borg_bedrag'] ?? 0);
        }
        if (str_contains($label, 'clubhuis')) {
            return (float) ($standardValues['clubhuis_bedrag'] ?? $standardValues['prijs_per_dag_clubhuis'] ?? 0);
        }
        if (str_contains($label, 'fram')) {
            if ($campLocation === 'clubhuis') {
                return (float) ($standardValues['clubhuis_bedrag'] ?? $standardValues['prijs_per_dag_clubhuis'] ?? 0);
            }

            return (float) ($standardValues['huur_fram_pppd'] ?? 0) * $campDays;
        }
        if (str_contains($label, 'proviand')) {
            if ($manualAmount > 0) {
                return $manualAmount;
            }

            return (float) ($standardValues['proviand_pppd'] ?? 0) * $campDays;
        }
        if (str_contains($label, 'groepsafdracht')) {
            return (float) ($standardValues['groepsafdracht_pjpd'] ?? 0) * $campDays;
        }
        if (str_contains($label, 'nawaka')) {
            return (float) ($standardValues['reservering_nawaka_pjpd'] ?? 0) * $campDays;
        }

        if ($manualAmount > 0) {
            return $manualAmount;
        }

        return $manualAmount;
    }

    private function hasFixedQuantityOne(string $sectionTitle, string $label): bool
    {
        $section = mb_strtolower(trim($sectionTitle));
        if ($section !== 'uitgaven') {
            return false;
        }

        $normalizedLabel = mb_strtolower(trim($label));

        return str_contains($normalizedLabel, 'proviand')
            || str_contains($normalizedLabel, 'clubhuis')
            || str_contains($normalizedLabel, 'huur fram')
            || str_contains($normalizedLabel, 'groepsafdracht')
            || str_contains($normalizedLabel, 'nawaka')
            || str_contains($normalizedLabel, 'borg');
    }

    private function isRefundableDepositRow(string $sectionTitle, string $label): bool
    {
        $section = mb_strtolower(trim($sectionTitle));
        if ($section !== 'uitgaven') {
            return false;
        }

        return str_contains(mb_strtolower(trim($label)), 'borg');
    }

    private function normalizeCampLocation(string $campLocation): string
    {
        return in_array($campLocation, ['clubhuis', 'fram'], true) ? $campLocation : 'fram';
    }

    private function normalizeCampDays(int $campDays): int
    {
        if ($campDays < 1) {
            return 1;
        }

        return min($campDays, 60);
    }

    /**
     * @param  array{label:string,quantity:float,amount:float,note:string}  $row
     * @param  array<int,array{title:string,rows:array<int,array{label:string,quantity:float,amount:float,note:string}>}>  $sections
     * @param  array<string,float>  $standardValues
     */
    private function rowTotalForCalculation(array $row, string $sectionTitle, array $sections, array $standardValues, int $campDays, string $campLocation): float
    {
        $section = mb_strtolower(trim($sectionTitle));
        $label = mb_strtolower(trim((string) ($row['label'] ?? '')));
        if ($section === 'uitgaven' && str_contains($label, 'proviand')) {
            $manualAmount = (float) ($row['amount'] ?? 0);
            if ($manualAmount > 0) {
                $quantity = (float) ($row['quantity'] ?? 0);

                return max(0.0, $quantity) * $manualAmount;
            }
            $participants = $this->participantCountFromSections($sections);
            $proviandPerDay = (float) ($standardValues['proviand_pppd'] ?? 0);

            return $participants * $proviandPerDay * $campDays;
        }
        if ($section === 'uitgaven' && str_contains($label, 'groepsafdracht')) {
            $jeugdleden = $this->jeugdledenCountFromSections($sections);
            $groepsafdrachtPjpd = (float) ($standardValues['groepsafdracht_pjpd'] ?? 0);

            return $jeugdleden * $groepsafdrachtPjpd * $campDays;
        }
        if ($section === 'uitgaven' && str_contains($label, 'nawaka')) {
            $jeugdleden = $this->jeugdledenCountFromSections($sections);
            $reserveringNawakaPjpd = (float) ($standardValues['reservering_nawaka_pjpd'] ?? 0);

            return $jeugdleden * $reserveringNawakaPjpd * $campDays;
        }
        if ($section === 'uitgaven' && str_contains($label, 'huur fram')) {
            $participants = $this->participantCountFromSections($sections);
            $framPppd = (float) ($standardValues['huur_fram_pppd'] ?? 0);

            return $participants * $framPppd * $campDays;
        }

        $quantity = (float) ($row['quantity'] ?? 0);
        $price = $this->effectiveAmount($row, $sectionTitle, $standardValues, $campDays, $campLocation);

        return $quantity * $price;
    }

    /**
     * @param  array<int,array{title:string,rows:array<int,array{label:string,quantity:float,amount:float,note:string}>}>  $sections
     */
    private function participantCountFromSections(array $sections): float
    {
        $contributions = collect($sections)->first(function (array $section): bool {
            return mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'bijdragen';
        });
        if (! is_array($contributions)) {
            return 0.0;
        }

        return (float) collect((array) ($contributions['rows'] ?? []))
            ->filter(function (array $row): bool {
                $label = mb_strtolower(trim((string) ($row['label'] ?? '')));

                return str_contains($label, 'leiding')
                    || str_contains($label, 'jeugdleden')
                    || str_contains($label, 'jeugdlid');
            })
            ->sum(fn (array $row): float => max(0, (float) ($row['quantity'] ?? 0)));
    }

    /**
     * @param  array<int,array{title:string,rows:array<int,array{label:string,quantity:float,amount:float,note:string}>}>  $sections
     */
    private function jeugdledenCountFromSections(array $sections): float
    {
        $contributions = collect($sections)->first(function (array $section): bool {
            return mb_strtolower(trim((string) ($section['title'] ?? ''))) === 'bijdragen';
        });
        if (! is_array($contributions)) {
            return 0.0;
        }

        return (float) collect((array) ($contributions['rows'] ?? []))
            ->filter(function (array $row): bool {
                $label = mb_strtolower(trim((string) ($row['label'] ?? '')));

                return str_contains($label, 'jeugdleden')
                    || str_contains($label, 'jeugdlid');
            })
            ->sum(fn (array $row): float => max(0, (float) ($row['quantity'] ?? 0)));
    }

    private function canReviewBudgets(User $user, string $activeSection): bool
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
                ->whereIn('role', UserSectionRole::BESTUUR_ROLES, 'and', false)
                ->exists();
    }

    private function statusFromAction(string $action): string
    {
        return $action === 'submit'
            ? CampBudget::STATUS_SUBMITTED
            : CampBudget::STATUS_DRAFT;
    }

    /**
     * @param  array<string,mixed>  $meta
     * @return array<string,mixed>
     */
    private function appendChangeLog(array $meta, string $action, ?User $actor): array
    {
        $history = collect((array) data_get($meta, 'change_log', []))
            ->filter(fn ($entry): bool => is_array($entry))
            ->values();

        $history->push([
            'action' => $action,
            'user_id' => $actor?->id,
            'user_name' => $actor?->name,
            'at' => now()->toIso8601String(),
        ]);

        $meta['change_log'] = $history->take(-200)->values()->all();

        return $meta;
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
     * @param  array<int,array{title:string,rows:array<int,array{label:string,quantity:float,amount:float,note:string}>}>  $sections
     * @param  array<string,float>  $standardValues
     * @return array<int,array{title:string,rows:array<int,array{label:string,quantity:float,amount:float,effective_amount:float,computed_total:float,note:string}>}>
     */
    private function sectionsForPdf(array $sections, array $standardValues, int $campDays, string $campLocation): array
    {
        return collect($sections)
            ->map(function (array $section) use ($sections, $standardValues, $campDays, $campLocation): array {
                $sectionTitle = (string) ($section['title'] ?? '');
                $rows = collect((array) ($section['rows'] ?? []))
                    ->map(function (array $row) use ($sectionTitle, $sections, $standardValues, $campDays, $campLocation): array {
                        $quantity = max(0, (float) ($row['quantity'] ?? 0));
                        $effectiveAmount = $this->effectiveAmount($row, $sectionTitle, $standardValues, $campDays, $campLocation);
                        $computedTotal = $this->rowTotalForCalculation($row, $sectionTitle, $sections, $standardValues, $campDays, $campLocation);

                        return [
                            'label' => (string) ($row['label'] ?? ''),
                            'quantity' => $quantity,
                            'amount' => (float) ($row['amount'] ?? 0),
                            'effective_amount' => round($effectiveAmount, 2),
                            'computed_total' => round($computedTotal, 2),
                            'note' => (string) ($row['note'] ?? ''),
                        ];
                    })
                    ->filter(fn (array $row): bool => (float) ($row['quantity'] ?? 0) > 0)
                    ->values()
                    ->all();

                return [
                    'title' => $sectionTitle,
                    'rows' => $rows,
                ];
            })
            ->values()
            ->all();
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

    private function buildAndStorePdf(CampBudget $campBudget): string
    {
        $sections = $this->normalizeSections(data_get($campBudget->meta, 'sections', []));
        $standardValues = $this->normalizeStandardValues(data_get($campBudget->meta, 'standard_values', []));
        $campDays = $this->normalizeCampDays((int) data_get($campBudget->meta, 'camp_days', 1));
        $campLocation = $this->normalizeCampLocation((string) data_get($campBudget->meta, 'camp_location', 'fram'));
        $totals = $this->totalsForSections($sections, $standardValues, $campDays, $campLocation);
        $sectionsForPdf = $this->sectionsForPdf($sections, $standardValues, $campDays, $campLocation);

        $pdf = Pdf::loadView('pdf.camp-budget', [
            'budget' => $campBudget,
            'sections' => $sectionsForPdf,
            'standardValues' => $standardValues,
            'totals' => $totals,
            'campDays' => $campDays,
            'campLocation' => $campLocation,
            'logoDataUri' => $this->logoDataUri(),
        ])->setPaper('a4');

        $filename = $this->budgetPdfFilename($campBudget);
        $path = 'camp-budgets/'.$filename;
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    private function budgetPdfFilename(CampBudget $campBudget): string
    {
        $sectionSlug = Str::slug(str_replace('_', ' ', (string) $campBudget->section), '-');
        $titleSlug = Str::slug((string) $campBudget->title, '-');
        if ($titleSlug === '') {
            $titleSlug = 'zonder-titel';
        }

        return sprintf(
            'begroting-%s-%d-%s-%s.pdf',
            $sectionSlug !== '' ? $sectionSlug : 'speltak',
            (int) $campBudget->camp_year,
            $titleSlug,
            now()->format('Ymd-His')
        );
    }
}
