<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\PushDispatchLog;
use App\Models\PushSubscription;
use App\Models\TaskItem;
use App\Models\User;
use App\Models\UserSectionRole;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

#[Description('Verstuur geplande pushmeldingen voor taken en opkomsten')]
class SendScheduledPushNotifications extends Command
{
    protected $signature = 'app:send-scheduled-push-notifications';

    public function __construct(private readonly WebPushService $webPushService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $today = now()->startOfDay();
        $weekAhead = (clone $today)->addWeek();

        $this->sendTaskDeadlineNotificationsForDate($weekAhead, 'week_before');
        $this->sendTaskDeadlineNotificationsForDate($today, 'day_of');
        $this->sendSaturdayEventNotifications($today);

        return self::SUCCESS;
    }

    private function sendTaskDeadlineNotificationsForDate(Carbon $targetDate, string $timing): void
    {
        $targetIso = $targetDate->toDateString();

        $tasks = TaskItem::withoutGlobalScope('section')
            ->whereNotNull('deadlines')
            ->get(['id', 'title', 'deadlines', 'owner_user_id', 'owner_user_ids', 'section']);

        foreach ($tasks as $task) {
            $deadlines = collect($task->deadlines ?? [])
                ->map(fn ($d) => is_string($d) ? trim($d) : '')
                ->filter(fn ($d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) === 1)
                ->values();

            if (! $deadlines->contains($targetIso)) {
                continue;
            }

            $ownerIds = collect($task->owner_user_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0);

            if ((int) ($task->owner_user_id ?? 0) > 0) {
                $ownerIds->push((int) $task->owner_user_id);
            }
            $ownerIds = $ownerIds->unique()->values();
            if ($ownerIds->isEmpty()) {
                continue;
            }

            $dispatchKey = "task:{$task->id}:{$targetIso}:{$timing}";
            if ($this->isAlreadySent($dispatchKey)) {
                continue;
            }

            $title = $timing === 'week_before' ? 'Taakdeadline over 1 week' : 'Taakdeadline vandaag';
            $body = $timing === 'week_before'
                ? "Taak \"{$task->title}\" heeft over 1 week een deadline ({$targetIso})."
                : "Taak \"{$task->title}\" heeft vandaag een deadline ({$targetIso}).";

            $subscriptions = PushSubscription::query()
                ->whereIn('user_id', $ownerIds->all())
                ->get();

            if ($subscriptions->isEmpty()) {
                continue;
            }

            $result = $this->webPushService->sendToSubscriptions($subscriptions, $title, $body, '/task-items');
            $this->markAsSent($dispatchKey, 'task_deadline', $targetDate, [
                'task_id' => (int) $task->id,
                'timing' => $timing,
                'result' => $result,
            ]);
        }
    }

    private function sendSaturdayEventNotifications(Carbon $today): void
    {
        if ((int) $today->dayOfWeek !== Carbon::SATURDAY) {
            return;
        }

        $events = Event::withoutGlobalScope('section')
            ->whereDate('event_date', $today->toDateString())
            ->get(['id', 'theme', 'event_date', 'section', 'shared_sections', 'absent', 'present_names']);

        foreach ($events as $event) {
            $dispatchKey = "event:saturday:{$event->id}:{$today->toDateString()}";
            if ($this->isAlreadySent($dispatchKey)) {
                continue;
            }

            $sections = collect([(string) $event->section])
                ->merge(collect($event->shared_sections ?? [])->map(fn ($s) => (string) $s))
                ->filter()
                ->unique()
                ->values();

            $baseUserIds = UserSectionRole::query()
                ->where(function (Builder $query) use ($sections): void {
                    $query->whereIn('section', $sections->all())
                        ->orWhere('section', UserSectionRole::SECTION_ALL);
                })
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            if ($baseUserIds->isEmpty()) {
                continue;
            }

            $userIds = $this->filterRecipientsForEventByAttendance($event, $sections->all(), $baseUserIds->all());
            if ($userIds === []) {
                continue;
            }

            $subscriptions = PushSubscription::query()
                ->whereIn('user_id', $userIds)
                ->get();

            if ($subscriptions->isEmpty()) {
                continue;
            }

            $title = 'Vandaag opkomst';
            $body = "Vandaag is er opkomst: \"{$event->theme}\".";
            $result = $this->webPushService->sendToSubscriptions($subscriptions, $title, $body, '/events');

            $this->markAsSent($dispatchKey, 'event_saturday', $today, [
                'event_id' => (int) $event->id,
                'sections' => $sections->all(),
                'result' => $result,
            ]);
        }
    }

    private function isAlreadySent(string $dispatchKey): bool
    {
        return PushDispatchLog::query()->where('dispatch_key', $dispatchKey)->exists();
    }

    private function markAsSent(string $dispatchKey, string $kind, Carbon $scheduledFor, array $meta = []): void
    {
        PushDispatchLog::query()->create([
            'dispatch_key' => $dispatchKey,
            'kind' => $kind,
            'scheduled_for' => $scheduledFor->toDateString(),
            'meta' => $meta,
        ]);
    }

    /**
     * @param  list<string>  $eventSections
     * @param  list<int>  $candidateUserIds
     * @return list<int>
     */
    private function filterRecipientsForEventByAttendance(Event $event, array $eventSections, array $candidateUserIds): array
    {
        $candidateUsers = User::query()
            ->with('sectionRoles:id,user_id,section,role')
            ->whereIn('id', $candidateUserIds)
            ->get();

        $absentNames = collect(explode(',', (string) ($event->absent ?? '')))
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->values()
            ->all();
        $presentNames = collect($event->present_names ?? [])
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->values()
            ->all();

        $classicSections = [
            UserSectionRole::SECTION_DOLFIJNEN,
            UserSectionRole::SECTION_BEVERS,
            UserSectionRole::SECTION_ZEEVERKENNERS,
        ];
        $optInSections = [
            UserSectionRole::SECTION_LOODSEN,
            UserSectionRole::SECTION_WILDE_VAART,
        ];

        $usesClassic = count(array_intersect($eventSections, $classicSections)) > 0;
        $usesOptIn = count(array_intersect($eventSections, $optInSections)) > 0;

        $recipientIds = [];
        foreach ($candidateUsers as $user) {
            $displayName = $this->displayNameForUser($user);
            $isAbsent = $this->nameMatches($displayName, $absentNames);
            $isPresent = $this->nameMatches($displayName, $presentNames);
            $hasGlobalRole = $user->sectionRoles->contains(fn (UserSectionRole $r) => $r->section === UserSectionRole::SECTION_ALL);

            if ($usesClassic && $isAbsent) {
                continue;
            }
            if ($usesOptIn && ! $hasGlobalRole && ! $isPresent) {
                continue;
            }

            $recipientIds[] = (int) $user->id;
        }

        return array_values(array_unique($recipientIds));
    }

    private function displayNameForUser(User $user): string
    {
        $full = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
        if ($full !== '') {
            return $full;
        }

        return trim((string) ($user->name ?? ''));
    }

    /**
     * @param  list<string>  $list
     */
    private function nameMatches(string $name, array $list): bool
    {
        $normalized = mb_strtolower(trim($name));
        if ($normalized === '') {
            return false;
        }
        $first = mb_strtolower(strtok($name, ' ') ?: $name);

        foreach ($list as $item) {
            $candidate = mb_strtolower(trim($item));
            if ($candidate === '') {
                continue;
            }
            if ($candidate === $normalized) {
                return true;
            }
            $candidateFirst = mb_strtolower(strtok($item, ' ') ?: $item);
            if ($candidateFirst === $first) {
                return true;
            }
        }

        return false;
    }
}
