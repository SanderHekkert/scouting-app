<?php

namespace App\Http\Controllers;

use App\Models\CampBudget;
use App\Models\User;
use App\Models\UserSectionRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                    'id' => (int) $item->id,
                    'section' => (string) $item->section,
                    'camp_year' => (int) $item->camp_year,
                    'title' => (string) $item->title,
                    'content' => (string) ($item->content ?? ''),
                    'pdf_path' => (string) data_get($item->meta, 'pdf_path', ''),
                    'status' => (string) ($item->status ?: CampBudget::STATUS_DRAFT),
                    'review_note' => (string) ($item->review_note ?? ''),
                    'created_by_name' => (string) optional($item->createdBy)->name,
                    'can_review' => $canReview && in_array((string) $item->status, [CampBudget::STATUS_SUBMITTED], true),
                ])
                ->values()
                ->all(),
            'canReview' => $canReview,
        ]);
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
            'budget_sections' => ['nullable', 'array'],
            'standard_values' => ['nullable', 'array'],
            'action' => ['nullable', 'string'],
        ]);
        $sections = $this->normalizeSections((array) ($data['budget_sections'] ?? []));
        $standardValues = $this->normalizeStandardValues((array) ($data['standard_values'] ?? []));

        $status = $this->statusFromAction((string) ($data['action'] ?? 'save'));
        $userId = $request->user()?->id;
        CampBudget::create([
            'camp_year' => (int) $data['camp_year'],
            'title' => (string) $data['title'],
            'content' => (string) ($data['content'] ?? ''),
            'meta' => ['sections' => $sections, 'standard_values' => $standardValues],
            'status' => $status,
            'created_by_user_id' => $userId,
            'updated_by_user_id' => $userId,
        ]);

        return to_route('camp-budgets.index');
    }

    public function update(Request $request, CampBudget $campBudget)
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);

        $data = $request->validate([
            'camp_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'budget_sections' => ['nullable', 'array'],
            'standard_values' => ['nullable', 'array'],
            'action' => ['nullable', 'string'],
        ]);
        $sections = $this->normalizeSections((array) ($data['budget_sections'] ?? []));
        $standardValues = $this->normalizeStandardValues((array) ($data['standard_values'] ?? []));

        $status = $this->statusFromAction((string) ($data['action'] ?? 'save'));
        $meta = (array) ($campBudget->meta ?? []);
        $meta['sections'] = $sections;
        $meta['standard_values'] = $standardValues;
        $campBudget->update([
            'camp_year' => (int) $data['camp_year'],
            'title' => (string) $data['title'],
            'content' => (string) ($data['content'] ?? ''),
            'meta' => $meta,
            'status' => $status,
            'review_note' => null,
            'processed_by_user_id' => null,
            'processed_at' => null,
            'updated_by_user_id' => $request->user()?->id,
        ]);

        return to_route('camp-budgets.index');
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

        $userId = request()->user()?->id;
        CampBudget::create([
            'section' => (string) $campBudget->section,
            'camp_year' => (int) $campBudget->camp_year,
            'title' => (string) $campBudget->title.' (kopie)',
            'content' => (string) ($campBudget->content ?? ''),
            'meta' => (array) ($campBudget->meta ?? []),
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
        $totals = $this->totalsForSections($sections, $standardValues);

        $pdf = Pdf::loadView('pdf.camp-budget', [
            'budget' => $campBudget,
            'sections' => $sections,
            'standardValues' => $standardValues,
            'totals' => $totals,
        ])->setPaper('a4');

        $filename = sprintf('begroting-%d-%s.pdf', (int) $campBudget->id, now()->format('Ymd-His'));
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

        $campBudget->update([
            'status' => CampBudget::STATUS_SUBMITTED,
            'review_note' => null,
            'processed_by_user_id' => null,
            'processed_at' => null,
            'updated_by_user_id' => $request->user()?->id,
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

        $campBudget->update([
            'status' => CampBudget::STATUS_NEEDS_CHANGES,
            'review_note' => trim((string) $data['review_note']),
            'processed_by_user_id' => (int) $user->id,
            'processed_at' => now(),
            'updated_by_user_id' => (int) $user->id,
        ]);

        return back();
    }

    public function downloadPdf(CampBudget $campBudget)
    {
        abort_unless((string) $campBudget->section === (string) session('active_section', 'dolfijnen'), 403);
        $path = (string) data_get($campBudget->meta, 'pdf_path', '');
        abort_unless($path !== '' && str_starts_with($path, 'camp-budgets/'), 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

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
                    ->map(function ($row): array {
                        return [
                            'label' => trim((string) data_get($row, 'label', '')),
                            'quantity' => round((float) data_get($row, 'quantity', 0), 2),
                            'amount' => round((float) data_get($row, 'amount', 0), 2),
                            'note' => trim((string) data_get($row, 'note', '')),
                        ];
                    })
                    ->filter(fn (array $row): bool => $row['label'] !== '' || $row['quantity'] !== 0.0 || $row['amount'] !== 0.0 || $row['note'] !== '')
                    ->values()
                    ->all();

                return ['title' => $title, 'rows' => $rows];
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
            ['title' => 'Uitgaven', 'rows' => [['label' => 'Fram', 'quantity' => 0, 'amount' => 0, 'note' => ''], ['label' => 'Proviand', 'quantity' => 0, 'amount' => 0, 'note' => ''], ['label' => 'Thema en spel', 'quantity' => 0, 'amount' => 0, 'note' => '']]],
            ['title' => 'Overige bijdragen', 'rows' => []],
            ['title' => 'Overige uitgaven', 'rows' => []],
            ['title' => 'Bemanning en deelnemers', 'rows' => []],
        ];
    }

    /**
     * @param  array<int,array{title:string,rows:array<int,array{label:string,quantity:float,amount:float,note:string}>}>  $sections
     * @param  array<string,float>  $standardValues
     * @return array{income:float,expenses:float,difference:float}
     */
    private function totalsForSections(array $sections, array $standardValues): array
    {
        $incomeTitles = ['bijdragen', 'overige bijdragen'];
        $expenseTitles = ['uitgaven', 'overige uitgaven'];
        $income = 0.0;
        $expenses = 0.0;
        foreach ($sections as $section) {
            $sum = collect($section['rows'])->sum(function (array $row) use ($section, $standardValues): float {
                $quantity = (float) ($row['quantity'] ?? 0);
                $price = $this->effectiveAmount($row, (string) ($section['title'] ?? ''), $standardValues);

                return $quantity * $price;
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
            'prijs_per_dag_leiding' => 0.00,
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
    private function effectiveAmount(array $row, string $sectionTitle, array $standardValues): float
    {
        $label = mb_strtolower(trim((string) ($row['label'] ?? '')));
        $section = mb_strtolower(trim($sectionTitle));
        $manualAmount = (float) ($row['amount'] ?? 0);
        if ($manualAmount > 0) {
            return $manualAmount;
        }
        if ($label === '') {
            return $manualAmount;
        }

        if ($section === 'bijdragen' && str_contains($label, 'leiding')) {
            return (float) ($standardValues['prijs_per_dag_leiding'] ?? 0);
        }
        if (str_contains($label, 'vaart')) {
            return (float) ($standardValues['kosten_vaart_pu'] ?? 0);
        }
        if (str_contains($label, 'aggregaat')) {
            return (float) ($standardValues['kosten_aggregaat_pu'] ?? 0);
        }
        if (str_contains($label, 'fram')) {
            return (float) ($standardValues['huur_fram_pppd'] ?? 0);
        }
        if (str_contains($label, 'proviand')) {
            return (float) ($standardValues['proviand_pppd'] ?? 0);
        }
        if (str_contains($label, 'groepsafdracht')) {
            return (float) ($standardValues['groepsafdracht_pjpd'] ?? 0);
        }
        if (str_contains($label, 'nawaka')) {
            return (float) ($standardValues['reservering_nawaka_pjpd'] ?? 0);
        }

        return $manualAmount;
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
                ->whereIn('role', UserSectionRole::BESTUUR_ROLES)
                ->exists();
    }

    private function statusFromAction(string $action): string
    {
        return $action === 'submit'
            ? CampBudget::STATUS_SUBMITTED
            : CampBudget::STATUS_DRAFT;
    }

    private function buildAndStorePdf(CampBudget $campBudget): string
    {
        $sections = $this->normalizeSections(data_get($campBudget->meta, 'sections', []));
        $standardValues = $this->normalizeStandardValues(data_get($campBudget->meta, 'standard_values', []));
        $totals = $this->totalsForSections($sections, $standardValues);

        $pdf = Pdf::loadView('pdf.camp-budget', [
            'budget' => $campBudget,
            'sections' => $sections,
            'standardValues' => $standardValues,
            'totals' => $totals,
        ])->setPaper('a4');

        $filename = sprintf('begroting-%d-%s.pdf', (int) $campBudget->id, now()->format('Ymd-His'));
        $path = 'camp-budgets/'.$filename;
        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
