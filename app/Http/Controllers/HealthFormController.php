<?php

namespace App\Http\Controllers;

use App\Models\HealthForm;
use App\Models\Member;
use App\Models\UserSectionRole;
use App\Services\HealthFormAutoExtractor;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HealthFormController extends Controller
{
    public function __construct(
        private readonly HealthFormAutoExtractor $extractor
    ) {}

    public function index(Request $request)
    {
        [, $activeSection, $canManage] = $this->ensureCanView($request);

        $formsQuery = HealthForm::query()
            ->with('uploader:id,name')
            ->when(
                $activeSection !== UserSectionRole::SECTION_BESTUUR,
                fn ($query) => $query->where('section', $activeSection)
            )
            ->latest();

        $forms = $formsQuery->get();
        $membersById = Member::query()
            ->withoutGlobalScope('section')
            ->whereIn('id', $forms->pluck('member_id')->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        return Inertia::render('Admin/HealthForms', [
            'can_manage' => $canManage,
            'active_section' => $activeSection,
            'forms' => $forms
                ->map(function (HealthForm $form) use ($membersById) {
                    $member = $membersById->get($form->member_id);

                    return [
                        'id' => $form->id,
                        'section' => $form->section,
                        'member_name' => trim(($member?->first_name ?? '').' '.($member?->last_name ?? '')),
                        'original_name' => $form->original_name,
                        'mime_type' => $form->mime_type,
                        'size' => (int) $form->size,
                        'uploader_name' => $form->uploader?->name,
                        'created_at' => $form->created_at?->format('d-m-Y H:i')
                            ?? $form->updated_at?->format('d-m-Y H:i')
                            ?? null,
                        'member' => [
                            'first_name' => $member?->first_name,
                            'last_name' => $member?->last_name,
                            'address' => $member?->address,
                            'postal_code' => $member?->postal_code,
                            'city' => $member?->city,
                            'birthday' => $member?->birthday,
                            'phone_mother' => $member?->phone_mother,
                            'phone_father' => $member?->phone_father,
                            'email_parents' => $member?->email_parents,
                            'bijzonderheden' => $member?->bijzonderheden,
                        ],
                    ];
                })
                ->values(),
        ]);
    }

    public function create(Request $request)
    {
        $this->ensureCanManage($request);

        return Inertia::render('Admin/HealthFormsCreate', [
            'preview' => $request->session()->get('health_form_preview'),
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage($request);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimetypes:application/pdf,image/jpeg,image/png'],
        ]);

        $file = $data['file'];
        $normalized = $this->normalizeUploadData($request, $file);

        $token = (string) Str::uuid();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $tmpPath = $file->storeAs('health-forms/tmp', $token.'.'.$extension, 'local');

        $request->session()->put('health_form_uploads.'.$token, [
            'tmp_path' => $tmpPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'size' => (int) $file->getSize(),
        ]);

        $request->session()->put('health_form_preview', [
            'token' => $token,
            ...$normalized,
        ]);

        return to_route('admin.health-forms.create');
    }

    public function confirm(Request $request)
    {
        [, $activeSection] = $this->ensureCanManage($request);

        $data = $request->validate([
            'token' => ['required', 'string'],
            'section' => ['required', 'string', Rule::in([
                UserSectionRole::SECTION_BEVERS,
                UserSectionRole::SECTION_DOLFIJNEN,
                UserSectionRole::SECTION_ZEEVERKENNERS,
                UserSectionRole::SECTION_WILDE_VAART,
                UserSectionRole::SECTION_LOODSEN,
            ])],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'birthday' => ['nullable', 'date'],
            'phone_mother' => ['nullable', 'string', 'max:255'],
            'phone_father' => ['nullable', 'string', 'max:255'],
            'email_parents' => ['nullable', 'string', 'max:255', 'email'],
            'bijzonderheden' => ['nullable', 'string'],
        ]);

        $uploadMeta = $request->session()->get('health_form_uploads.'.$data['token']);
        if (! is_array($uploadMeta) || empty($uploadMeta['tmp_path'])) {
            throw ValidationException::withMessages([
                'token' => 'Uploadsessie verlopen. Upload het formulier opnieuw.',
            ]);
        }
        $tmpPath = (string) $uploadMeta['tmp_path'];
        if (! Storage::disk('local')->exists($tmpPath)) {
            throw ValidationException::withMessages([
                'token' => 'Tijdelijk uploadbestand niet gevonden. Upload opnieuw.',
            ]);
        }

        if ($activeSection !== UserSectionRole::SECTION_BESTUUR && $data['section'] !== $activeSection) {
            throw ValidationException::withMessages([
                'section' => 'Je kunt alleen gezondheidsformulieren voor je eigen speltak opslaan.',
            ]);
        }

        $member = Member::query()->create([
            'section' => $data['section'],
            'first_name' => $data['first_name'],
            'last_name' => trim((string) ($data['last_name'] ?? '')),
            'address' => $this->sanitizeExtractedValue($data['address'] ?? null),
            'postal_code' => $this->sanitizeExtractedValue($data['postal_code'] ?? null),
            'city' => $this->sanitizeExtractedValue($data['city'] ?? null),
            'birthday' => $data['birthday'] ?: null,
            'age' => Member::calculateAgeFromBirthday($data['birthday'] ?? null),
            'phone_mother' => $this->sanitizePhoneValue($data['phone_mother'] ?? null),
            'phone_father' => $this->sanitizePhoneValue($data['phone_father'] ?? null),
            'email_parents' => $this->sanitizeEmailValue($data['email_parents'] ?? null),
            'bijzonderheden' => $this->sanitizeExtractedValue($data['bijzonderheden'] ?? null),
            'installed' => false,
        ]);

        $finalPath = 'health-forms/'.now()->format('Y/m').'/'.Str::uuid().'.'.pathinfo($tmpPath, PATHINFO_EXTENSION);
        Storage::disk('local')->move($tmpPath, $finalPath);

        $healthForm = HealthForm::query()->create([
            'section' => $data['section'],
            'member_id' => $member->id,
            'original_name' => (string) ($uploadMeta['original_name'] ?? 'gezondheidsformulier.pdf'),
            'storage_path' => $finalPath,
            'mime_type' => (string) ($uploadMeta['mime_type'] ?? 'application/pdf'),
            'size' => (int) ($uploadMeta['size'] ?? 0),
            'uploaded_by_user_id' => $request->user()?->id,
        ]);

        $request->session()->forget('health_form_uploads.'.$data['token']);
        $request->session()->forget('health_form_preview');
        $request->session()->put('active_section', $data['section']);

        return to_route('admin.health-forms.show', $healthForm->id);
    }

    public function show(Request $request, HealthForm $health_form)
    {
        [, $activeSection] = $this->ensureCanView($request);
        abort_unless(
            $activeSection === UserSectionRole::SECTION_BESTUUR || $health_form->section === $activeSection,
            403
        );

        $health_form->load(['uploader:id,name']);
        $member = Member::query()
            ->withoutGlobalScope('section')
            ->find($health_form->member_id);

        return Inertia::render('Admin/HealthFormsShow', [
            'form' => [
                'id' => $health_form->id,
                'section' => $health_form->section,
                'original_name' => $health_form->original_name,
                'mime_type' => $health_form->mime_type,
                'size' => (int) $health_form->size,
                'uploader_name' => $health_form->uploader?->name,
                'created_at' => optional($health_form->created_at)?->toDateTimeString(),
                'member' => $member ? $this->memberPayload($member) : null,
            ],
        ]);
    }

    public function download(Request $request, HealthForm $health_form): BinaryFileResponse
    {
        [, $activeSection] = $this->ensureCanView($request);
        abort_unless(
            $activeSection === UserSectionRole::SECTION_BESTUUR || $health_form->section === $activeSection,
            403
        );

        abort_unless(Storage::disk('local')->exists($health_form->storage_path), 404);

        return response()->download(
            Storage::disk('local')->path($health_form->storage_path),
            $health_form->original_name
        );
    }

    public function destroy(Request $request, HealthForm $health_form)
    {
        $this->ensureCanManage($request);

        if (Storage::disk('local')->exists($health_form->storage_path)) {
            Storage::disk('local')->delete($health_form->storage_path);
        }

        $health_form->delete();

        return back();
    }

    private function ensureCanView(Request $request): array
    {
        $user = $request->user();
        abort_unless($user, 403);

        $activeSection = session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
        $hasAllowedRoleInSection = $user->sectionRoles()
            ->where('section', $activeSection)
            ->whereIn('role', [
                UserSectionRole::ROLE_TEAMLEIDER,
                UserSectionRole::ROLE_OUDERCONTACT,
            ])
            ->exists();
        $hasBoardAccessRole = $user->sectionRoles()
            ->where('section', UserSectionRole::SECTION_BESTUUR)
            ->whereIn('role', [
                UserSectionRole::ROLE_ADMIN,
                UserSectionRole::ROLE_BESTUURSLID,
            ])
            ->exists();
        $isGlobalAdmin = $user->isGlobalAdmin();
        $isGlobalBoardMember = $user->isGlobalBoardMember();
        $isGlobal = $isGlobalAdmin || $isGlobalBoardMember;

        $canViewAllSections = $activeSection === UserSectionRole::SECTION_BESTUUR && ($isGlobal || $hasBoardAccessRole);
        $canViewOwnSection = $activeSection !== UserSectionRole::SECTION_BESTUUR && ($hasAllowedRoleInSection || $isGlobalAdmin);

        abort_unless($canViewAllSections || $canViewOwnSection, 403);

        $canManage = $canViewAllSections || $canViewOwnSection;

        return [$user, $activeSection, $canManage];
    }

    private function ensureCanManage(Request $request): array
    {
        [$user, $activeSection, $canManage] = $this->ensureCanView($request);
        abort_unless($canManage, 403);

        return [$user, $activeSection, $canManage];
    }

    private function memberPayload(Member $member): array
    {
        return [
            'id' => $member->id,
            'section' => $member->section,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'full_name' => trim(($member->first_name ?? '').' '.($member->last_name ?? '')),
            'address' => $member->address,
            'postal_code' => $member->postal_code,
            'city' => $member->city,
            'birthday' => $member->birthday,
            'phone_mother' => $member->phone_mother,
            'phone_father' => $member->phone_father,
            'email_parents' => $member->email_parents,
            'bijzonderheden' => $member->bijzonderheden,
        ];
    }

    private function detectSectionFromFilename(string $name): ?string
    {
        $low = mb_strtolower($name);

        return match (true) {
            str_contains($low, 'bever') => UserSectionRole::SECTION_BEVERS,
            str_contains($low, 'dolf') => UserSectionRole::SECTION_DOLFIJNEN,
            str_contains($low, 'zeeverkenner') => UserSectionRole::SECTION_ZEEVERKENNERS,
            str_contains($low, 'wilde') && str_contains($low, 'vaart') => UserSectionRole::SECTION_WILDE_VAART,
            str_contains($low, 'loods') => UserSectionRole::SECTION_LOODSEN,
            default => null,
        };
    }

    private function extractNameFromFilename(string $name): array
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $clean = preg_replace('/[^\\p{L}\\p{N}\\s\\-]+/u', ' ', $base) ?? '';
        $clean = trim(preg_replace('/\\s+/u', ' ', $clean) ?? '');

        if ($clean === '') {
            return ['first_name' => null, 'last_name' => null];
        }

        $parts = preg_split('/\\s+/u', $clean) ?: [];
        $first = array_shift($parts);
        $last = count($parts) ? implode(' ', $parts) : null;

        return [
            'first_name' => $first ?: null,
            'last_name' => $last ?: null,
        ];
    }

    private function isLikelyPersonName(?string $value): bool
    {
        $v = trim((string) $value);
        if ($v === '') {
            return false;
        }
        if (strlen($v) > 40) {
            return false;
        }

        return (bool) preg_match('/^[\p{L}\-\s\']+$/u', $v);
    }

    private function sanitizeExtractedValue(?string $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }

        $lettersOnly = strtolower(preg_replace('/[^a-z]/i', '', $v) ?? '');
        $suspectWords = ['adres', 'dres', 'postcode', 'woonplaats', 'naam', 'land', 'telefoonnummer', 'mobiel', 'relatietotscout'];
        if (in_array($lettersOnly, $suspectWords, true)) {
            return null;
        }
        if (str_starts_with($v, '(')) {
            return null;
        }

        return $v;
    }

    private function sanitizePhoneValue(?string $value): ?string
    {
        $v = $this->sanitizeExtractedValue($value);
        if ($v === null) {
            return null;
        }

        // Keep only realistic phone-ish values.
        if (! preg_match('/\d{6,}/', $v)) {
            return null;
        }

        return $v;
    }

    private function normalizeUploadData(Request $request, UploadedFile $file): array
    {
        [, $activeSection] = $this->ensureCanView($request);
        $extracted = $this->extractor->extract($file);
        $filenameHints = $this->extractNameFromFilename((string) $file->getClientOriginalName());

        $section = $extracted['section']
            ?? $this->detectSectionFromFilename((string) $file->getClientOriginalName());
        $section = $section ?: $activeSection;

        if (! in_array($section, UserSectionRole::ALL_SECTIONS, true) || $section === UserSectionRole::SECTION_BESTUUR) {
            $section = $activeSection;
        }
        if ($activeSection !== UserSectionRole::SECTION_BESTUUR) {
            $section = $activeSection;
        }

        $firstName = $this->isLikelyPersonName($extracted['first_name'] ?? null)
            ? $extracted['first_name']
            : $filenameHints['first_name'];
        $lastName = $this->isLikelyPersonName($extracted['last_name'] ?? null)
            ? $extracted['last_name']
            : $filenameHints['last_name'];

        if (empty($firstName)) {
            throw ValidationException::withMessages([
                'file' => 'Kon geen naam uitlezen uit het formulier. Gebruik een bestandsnaam zoals "Voornaam Achternaam.pdf" of vul het formulier digitaal in.',
            ]);
        }

        return [
            'section' => $section,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'address' => $this->sanitizeExtractedValue($extracted['address'] ?? null),
            'postal_code' => $this->sanitizeExtractedValue($extracted['postal_code'] ?? null),
            'city' => $this->sanitizeExtractedValue($extracted['city'] ?? null),
            'birthday' => $extracted['birthday'] ?? null,
            'phone_mother' => $this->sanitizePhoneValue($extracted['phone_mother'] ?? null),
            'phone_father' => $this->sanitizePhoneValue($extracted['phone_father'] ?? null),
            'email_parents' => $this->sanitizeEmailValue($extracted['email_parents'] ?? null),
            'bijzonderheden' => $this->sanitizeExtractedValue($extracted['bijzonderheden'] ?? null),
        ];
    }

    private function sanitizeEmailValue(?string $value): ?string
    {
        $v = trim((string) $value);
        if ($v === '') {
            return null;
        }
        if (! filter_var($v, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return strtolower($v);
    }
}
