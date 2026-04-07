<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('task_items', 'owner_user_ids')) {
            Schema::table('task_items', function (Blueprint $table): void {
                $table->json('owner_user_ids')->nullable()->after('owner_user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('task_items', 'owner_user_ids')) {
            Schema::table('task_items', function (Blueprint $table): void {
                $table->dropColumn('owner_user_ids');
            });
        }
    }
};
