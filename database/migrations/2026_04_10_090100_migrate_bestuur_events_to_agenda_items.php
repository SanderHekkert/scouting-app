<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $bestuurEvents = DB::table('events')
            ->where('section', 'bestuur')
            ->get();

        foreach ($bestuurEvents as $event) {
            DB::table('agenda_items')->insert([
                'theme' => (string) ($event->theme ?? ''),
                'event_date' => $event->event_date,
                'location' => $event->location,
                'time_slot' => $event->time_slot,
                'invitees' => $event->invitees,
                'link_url' => $event->link_url,
                'attachments' => $event->attachments,
                'notes' => $event->notes,
                'section' => 'bestuur',
                'created_at' => $event->created_at,
                'updated_at' => $event->updated_at,
            ]);
        }

        DB::table('events')->where('section', 'bestuur')->delete();
    }

    public function down(): void
    {
        $agendaItems = DB::table('agenda_items')
            ->where('section', 'bestuur')
            ->get();

        foreach ($agendaItems as $item) {
            DB::table('events')->insert([
                'theme' => (string) ($item->theme ?? ''),
                'event_date' => $item->event_date,
                'event_type' => '',
                'activity' => '',
                'program_by' => '',
                'location' => $item->location,
                'time_slot' => $item->time_slot,
                'invitees' => $item->invitees,
                'link_url' => $item->link_url,
                'attachments' => $item->attachments,
                'absent' => '',
                'notes' => $item->notes,
                'task_item_ids' => null,
                'shared_sections' => null,
                'section' => 'bestuur',
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ]);
        }

        DB::table('agenda_items')->where('section', 'bestuur')->delete();
    }
};
