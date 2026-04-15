<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CampPlaybook;
use App\Models\User;
use App\Models\UserSectionRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait CampPlaybookWorkflowMedia
{
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
