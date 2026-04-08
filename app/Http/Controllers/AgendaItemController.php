<?php

namespace App\Http\Controllers;

use App\Models\AgendaItem;
use App\Models\Event;
use App\Models\UserSectionRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class AgendaItemController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $section = $this->activeSection();

        $items = AgendaItem::query()
            ->whereDate('event_date', '>=', $today)
            ->orderBy('event_date')
            ->orderBy('theme')
            ->get();

        $opkomsten = collect();
        if ($section !== UserSectionRole::SECTION_BESTUUR) {
            $opkomsten = Event::withoutGlobalScope('section')
                ->where(function (Builder $query) use ($section): void {
                    $query->where('section', $section);
                    if ($this->supportsSharedEventsForSection($section)) {
                        $query->orWhereJsonContains('shared_sections', $section);
                    }
                })
                ->whereDate('event_date', '>=', $today)
                ->orderBy('event_date')
                ->orderBy('theme')
                ->get();
        }

        return Inertia::render('Agenda/Index', [
            'items' => $items->map(fn (AgendaItem $item): array => [
                ...$item->toArray(),
                'attachment_name' => $this->attachmentName($item->attachments),
                'has_attachment' => $this->attachmentName($item->attachments) !== null,
                'google_calendar_url' => $this->googleCalendarUrl($item),
            ])->values(),
            'opkomsten' => $opkomsten->map(fn (Event $event): array => [
                'id' => (int) $event->id,
                'theme' => (string) ($event->theme ?? ''),
                'event_date' => (string) ($event->event_date ?? ''),
                'event_type' => (string) ($event->event_type ?? ''),
                'activity' => (string) ($event->activity ?? ''),
                'section' => (string) ($event->section ?? ''),
                'is_shared' => in_array($section, collect($event->shared_sections ?? [])->map(fn ($s) => (string) $s)->all(), true),
                'shared_sections' => collect($event->shared_sections ?? [])->map(fn ($s) => (string) $s)->values()->all(),
            ])->values(),
        ]);
    }

    public function archived()
    {
        $today = Carbon::today();
        $items = AgendaItem::query()
            ->whereDate('event_date', '<', $today)
            ->orderByDesc('event_date')
            ->orderBy('theme')
            ->get();

        return Inertia::render('Agenda/Archived', [
            'archivedItems' => $items->map(fn (AgendaItem $item): array => [
                ...$item->toArray(),
                'attachment_name' => $this->attachmentName($item->attachments),
                'has_attachment' => $this->attachmentName($item->attachments) !== null,
                'google_calendar_url' => $this->googleCalendarUrl($item),
            ])->values(),
        ]);
    }

    public function show(AgendaItem $agendaItem)
    {
        return Inertia::render('Agenda/Show', [
            'item' => [
                ...$agendaItem->toArray(),
                'attachment_name' => $this->attachmentName($agendaItem->attachments),
                'google_calendar_url' => $this->googleCalendarUrl($agendaItem),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'theme' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'time_slot' => ['nullable', 'string', 'max:255'],
            'invitees' => ['nullable', 'string'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'attachment_file' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(10 * 1024)],
            'notes' => ['nullable', 'string'],
        ]);

        $data['theme'] = (string) ($data['theme'] ?? '');

        if ($request->hasFile('attachment_file')) {
            $data['attachments'] = $this->encodeAttachmentMeta($request->file('attachment_file'));
        }

        AgendaItem::create($data);

        return to_route('agenda.index');
    }

    public function update(Request $request, AgendaItem $agendaItem)
    {
        $data = $request->validate([
            'theme' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'time_slot' => ['nullable', 'string', 'max:255'],
            'invitees' => ['nullable', 'string'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'attachment_file' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png'])->max(10 * 1024)],
            'notes' => ['nullable', 'string'],
        ]);
        $data['theme'] = (string) ($data['theme'] ?? '');

        if ($request->hasFile('attachment_file')) {
            $this->deleteAttachmentFile($agendaItem->attachments);
            $data['attachments'] = $this->encodeAttachmentMeta($request->file('attachment_file'));
        }

        $agendaItem->update($data);

        return to_route('agenda.index');
    }

    public function destroy(AgendaItem $agendaItem)
    {
        $this->deleteAttachmentFile($agendaItem->attachments);
        $agendaItem->delete();

        return back();
    }

    public function downloadAttachment(AgendaItem $agendaItem): BinaryFileResponse
    {
        $meta = $this->attachmentMeta($agendaItem->attachments);
        abort_unless($meta !== null, 404);
        abort_unless(Storage::disk('local')->exists($meta['path']), 404);

        return response()->download(Storage::disk('local')->path($meta['path']), $meta['name']);
    }

    public function ics(AgendaItem $agendaItem): Response
    {
        $start = Carbon::parse($agendaItem->event_date)->startOfDay();
        $end = $start->copy()->addDay();
        $uid = 'agenda-item-'.$agendaItem->id.'@scouting-app';
        $summary = $this->icsEscape((string) ($agendaItem->theme ?? 'Agenda-item'));
        $description = $this->icsEscape(trim((string) ($agendaItem->notes ?? '')));
        $location = $this->icsEscape(trim((string) ($agendaItem->location ?? '')));
        $url = trim((string) ($agendaItem->link_url ?? ''));

        $content = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Scouting App//Agenda//NL',
            'CALSCALE:GREGORIAN',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART;VALUE=DATE:'.$start->format('Ymd'),
            'DTEND;VALUE=DATE:'.$end->format('Ymd'),
            'SUMMARY:'.$summary,
            'DESCRIPTION:'.$description,
            'LOCATION:'.$location,
            ...($url !== '' ? ['URL:'.$this->icsEscape($url)] : []),
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);

        $filename = Str::slug((string) ($agendaItem->theme ?: 'agenda-item')).'-'.$start->format('Ymd').'.ics';

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function googleCalendarUrl(AgendaItem $agendaItem): string
    {
        $start = Carbon::parse($agendaItem->event_date)->startOfDay();
        $end = $start->copy()->addDay();

        return 'https://calendar.google.com/calendar/render?action=TEMPLATE'
            .'&text='.rawurlencode((string) ($agendaItem->theme ?? 'Agenda-item'))
            .'&dates='.$start->format('Ymd').'/'.$end->format('Ymd')
            .'&details='.rawurlencode((string) ($agendaItem->notes ?? ''))
            .'&location='.rawurlencode((string) ($agendaItem->location ?? ''))
            .'&sprop='.rawurlencode((string) ($agendaItem->link_url ?? ''));
    }

    private function encodeAttachmentMeta(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin');
        $path = $file->storeAs(
            'agenda-attachments/'.now()->format('Y/m'),
            Str::uuid().'.'.$ext,
            'local'
        );

        return json_encode([
            'path' => $path,
            'name' => (string) ($file->getClientOriginalName() ?: basename($path)),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function attachmentName(?string $raw): ?string
    {
        return $this->attachmentMeta($raw)['name'] ?? null;
    }

    private function deleteAttachmentFile(?string $raw): void
    {
        $meta = $this->attachmentMeta($raw);
        if ($meta && Storage::disk('local')->exists($meta['path'])) {
            Storage::disk('local')->delete($meta['path']);
        }
    }

    /**
     * @return array{path:string,name:string}|null
     */
    private function attachmentMeta(?string $raw): ?array
    {
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return null;
        }
        $path = trim((string) ($decoded['path'] ?? ''));
        if ($path === '' || ! str_starts_with($path, 'agenda-attachments/')) {
            return null;
        }

        return [
            'path' => $path,
            'name' => trim((string) ($decoded['name'] ?? '')) ?: basename($path),
        ];
    }

    private function icsEscape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $value
        );
    }

    private function activeSection(): string
    {
        return session('active_section', UserSectionRole::SECTION_DOLFIJNEN);
    }

    private function supportsSharedEventsForSection(string $section): bool
    {
        return in_array($section, [
            UserSectionRole::SECTION_BEVERS,
            UserSectionRole::SECTION_DOLFIJNEN,
            UserSectionRole::SECTION_ZEEVERKENNERS,
            UserSectionRole::SECTION_WILDE_VAART,
        ], true);
    }
}
