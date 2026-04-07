<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE task_categories DROP INDEX task_categories_name_unique');
        } catch (\Throwable $e) {
            // Index bestond mogelijk al niet.
        }

        try {
            DB::statement('ALTER TABLE task_categories DROP INDEX task_categories_section_name_unique');
        } catch (\Throwable $e) {
            // Index bestond mogelijk al niet.
        }

        DB::statement('ALTER TABLE task_categories ADD UNIQUE INDEX task_categories_section_name_unique (`section`, `name`)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE task_categories DROP INDEX task_categories_section_name_unique');
        } catch (\Throwable $e) {
            // Index bestond mogelijk al niet.
        }

        DB::statement('ALTER TABLE task_categories ADD UNIQUE INDEX task_categories_name_unique (`name`)');
    }
};
