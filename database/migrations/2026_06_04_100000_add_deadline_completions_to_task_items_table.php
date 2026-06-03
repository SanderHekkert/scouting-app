<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_items', function (Blueprint $table) {
            $table->json('deadline_completions')->nullable()->after('completed_at');
        });

        DB::table('task_items')
            ->whereNotNull('completed_at')
            ->orderBy('id')
            ->each(function (object $row): void {
                $deadlines = json_decode((string) ($row->deadlines ?? '[]'), true);
                if (! is_array($deadlines) || $deadlines === []) {
                    return;
                }

                $completedAt = (string) $row->completed_at;
                $completions = [];
                foreach ($deadlines as $deadline) {
                    $date = trim((string) $deadline);
                    if ($date === '') {
                        continue;
                    }
                    $completions[$date] = $completedAt;
                }

                if ($completions === []) {
                    return;
                }

                DB::table('task_items')
                    ->where('id', $row->id)
                    ->update(['deadline_completions' => json_encode($completions)]);
            });
    }

    public function down(): void
    {
        Schema::table('task_items', function (Blueprint $table) {
            $table->dropColumn('deadline_completions');
        });
    }
};
