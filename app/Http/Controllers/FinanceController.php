<?php

namespace App\Http\Controllers;

use App\Models\FinanceDeclaration;
use App\Models\FinanceLedgerEntry;
use App\Models\FinancePot;
use App\Models\User;
use App\Models\UserSectionRole;
use App\Services\ReceiptOcrService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;

class FinanceController extends Controller
{
    public function __construct(
        private readonly ReceiptOcrService $ocrService
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $section = $this->activeSection();

        $canManage = $this->canManageFinance($user, $section);
        $canCreatePots = $this->canCreatePots($user, $section);
        $pots = FinancePot::withoutGlobalScope('section')
            ->where('section', $section)
            ->orderByDesc('active')
            ->orderBy('name')
            ->get()
            ->map(fn (FinancePot $pot): array => [
                'id' => (int) $pot->id,
                'name' => (string) $pot->name,
                'starting_amount' => (float) $pot->starting_amount,
                'current_amount' => (float) $pot->current_amount,
                'active' => (bool) $pot->active,
            ])
            ->values()
            ->all();

        $declarationsQuery = FinanceDeclaration::withoutGlobalScope('section')
            ->where('section', $section);
        if (! $canManage) {
            $declarationsQuery->where('created_by_user_id', (int) $user->id);
        }

        $declarations = $declarationsQuery
            ->latest('id')
            ->take(100)
            ->get()
            ->map(fn (FinanceDeclaration $row): array => [
                'id' => (int) $row->id,
                'status' => (string) $row->status,
                'amount' => (float) $row->amount,
                'iban' => (string) ($row->iban ?? ''),
                'account_name' => (string) ($row->account_name ?? ''),
                'description_total' => (string) ($row->description_total ?? ''),
                'description_lines' => (string) ($row->description_lines ?? ''),
                'declared_at' => optional($row->declared_at)?->toDateString(),
                'pot_id' => $row->pot_id ? (int) $row->pot_id : null,
                'pot_name' => (string) optional($row->pot)->name,
                'receipt_name' => (string) ($row->receipt_name ?? ''),
                'created_by_name' => (string) optional($row->createdBy)->name,
                'review_note' => (string) ($row->review_note ?? ''),
                'can_review' => $canManage && in_array((string) $row->status, [FinanceDeclaration::STATUS_SUBMITTED], true),
            ])
            ->values()
            ->all();

        return Inertia::render('Finance/Index', [
            'pots' => $pots,
            'declarations' => $declarations,
            'canManage' => $canManage,
            'canCreatePots' => $canCreatePots,
            'activeSection' => $section,
        ]);
    }

    public function createPot(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $section = $this->activeSection();
        abort_unless($this->canCreatePots($user, $section), 403);

        return Inertia::render('Finance/CreatePot', [
            'activeSection' => $section,
        ]);
    }

    public function createDeclaration(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $section = $this->activeSection();

        $pots = FinancePot::withoutGlobalScope('section')
            ->where('section', $section)
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (FinancePot $pot): array => [
                'id' => (int) $pot->id,
                'name' => (string) $pot->name,
                'current_amount' => (float) $pot->current_amount,
            ])
            ->values()
            ->all();

        return Inertia::render('Finance/CreateDeclaration', [
            'activeSection' => $section,
            'pots' => $pots,
        ]);
    }

    public function storePot(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $section = $this->activeSection();
        abort_unless($this->canCreatePots($user, $section), 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'starting_amount' => ['required', 'numeric', 'min:0'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $start = round((float) $data['starting_amount'], 2);
        FinancePot::create([
            'section' => $section,
            'name' => trim((string) $data['name']),
            'starting_amount' => $start,
            'current_amount' => $start,
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return back();
    }

    public function updatePot(Request $request, FinancePot $pot)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $section = $this->activeSection();
        abort_unless($this->canManageFinance($user, $section), 403);
        abort_unless((string) $pot->section === $section, 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'current_amount' => ['required', 'numeric'],
            'active' => ['required', 'boolean'],
        ]);

        $pot->update([
            'name' => trim((string) $data['name']),
            'current_amount' => round((float) $data['current_amount'], 2),
            'active' => (bool) $data['active'],
        ]);

        return back();
    }

    public function storeDeclaration(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $section = $this->activeSection();

        $data = $request->validate([
            'pot_id' => [
                'required',
                'integer',
                Rule::exists('finance_pots', 'id')->where(fn ($q) => $q->where('section', $section)->where('active', true)),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'iban' => ['required', 'string', 'max:64'],
            'account_name' => ['required', 'string', 'max:255'],
            'description_total' => ['required', 'string', 'max:1000'],
            'description_lines' => ['required', 'string', 'max:6000'],
            'declared_at' => ['required', 'date'],
            'receipt_file' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'])->max(12 * 1024)],
        ]);

        $file = $request->file('receipt_file');
        $path = $file?->store('finance-receipts/'.now()->format('Y/m'), 'local');

        FinanceDeclaration::create([
            'section' => $section,
            'created_by_user_id' => (int) $user->id,
            'pot_id' => (int) $data['pot_id'],
            'status' => FinanceDeclaration::STATUS_SUBMITTED,
            'amount' => round((float) $data['amount'], 2),
            'iban' => trim((string) $data['iban']),
            'account_name' => trim((string) $data['account_name']),
            'description_total' => trim((string) $data['description_total']),
            'description_lines' => trim((string) $data['description_lines']),
            'receipt_path' => $path,
            'receipt_name' => (string) ($file?->getClientOriginalName() ?: ''),
            'receipt_mime' => (string) ($file?->getMimeType() ?: ''),
            'receipt_size' => $file?->getSize(),
            'declared_at' => $data['declared_at'],
        ]);

        return back();
    }

    public function approveDeclaration(Request $request, FinanceDeclaration $declaration)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $section = $this->activeSection();
        abort_unless($this->canManageFinance($user, $section), 403);
        abort_unless((string) $declaration->section === $section, 403);
        abort_unless((string) $declaration->status === FinanceDeclaration::STATUS_SUBMITTED, 422);

        DB::transaction(function () use ($declaration, $user, $section): void {
            $pot = FinancePot::withoutGlobalScope('section')
                ->where('section', $section)
                ->whereKey($declaration->pot_id)
                ->lockForUpdate()
                ->firstOrFail();

            $amount = round((float) $declaration->amount, 2);
            $nextBalance = round((float) $pot->current_amount - $amount, 2);

            $pot->update([
                'current_amount' => $nextBalance,
            ]);

            FinanceLedgerEntry::create([
                'section' => $section,
                'pot_id' => (int) $pot->id,
                'declaration_id' => (int) $declaration->id,
                'type' => FinanceLedgerEntry::TYPE_DEBIT,
                'amount' => $amount,
                'balance_after' => $nextBalance,
                'note' => 'Declaratie goedgekeurd',
                'created_by_user_id' => (int) $user->id,
            ]);

            $declaration->update([
                'status' => FinanceDeclaration::STATUS_APPROVED,
                'processed_by_user_id' => (int) $user->id,
                'processed_at' => now(),
                'review_note' => null,
            ]);
        });

        return back();
    }

    public function rejectDeclaration(Request $request, FinanceDeclaration $declaration)
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $section = $this->activeSection();
        abort_unless($this->canManageFinance($user, $section), 403);
        abort_unless((string) $declaration->section === $section, 403);
        abort_unless((string) $declaration->status === FinanceDeclaration::STATUS_SUBMITTED, 422);

        $data = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $declaration->update([
            'status' => FinanceDeclaration::STATUS_REJECTED,
            'processed_by_user_id' => (int) $user->id,
            'processed_at' => now(),
            'review_note' => trim((string) ($data['review_note'] ?? '')),
        ]);

        return back();
    }

    public function ocr(Request $request)
    {
        $request->validate([
            'receipt_file' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png', 'webp', 'heic', 'heif'])->max(12 * 1024)],
        ]);
        $file = $request->file('receipt_file');
        abort_unless($file instanceof UploadedFile, 422);
        $suggestions = $this->ocrService->extractSuggestions($file);

        return response()->json([
            'suggestions' => $suggestions,
        ]);
    }

    private function activeSection(): string
    {
        return (string) session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
    }

    private function canManageFinance(User $user, string $section): bool
    {
        if ($user->isGlobalAdmin() || $user->isGlobalBoardMember()) {
            return true;
        }

        return $section === UserSectionRole::SECTION_BESTUUR;
    }

    private function canCreatePots(User $user, string $section): bool
    {
        if ($user->isGlobalAdmin()) {
            return true;
        }

        return $section === UserSectionRole::SECTION_BESTUUR && $this->canManageFinance($user, $section);
    }
}
