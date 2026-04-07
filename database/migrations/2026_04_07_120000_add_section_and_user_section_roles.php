<?php

use App\Models\UserSectionRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'events',
            'members',
            'leaders',
            'pods',
            'pod_memberships',
            'info_notes',
            'task_items',
            'task_categories',
            'year_theme_entries',
        ];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->string('section', 40)->default(UserSectionRole::SECTION_DOLFIJNEN)->index();
            });
        }

        Schema::table('task_categories', function (Blueprint $table): void {
            $table->dropUnique('task_categories_name_unique');
            $table->unique(['section', 'name'], 'task_categories_section_name_unique');
        });

        Schema::create('user_section_roles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('section', 40)->index();
            $table->string('role', 40)->index();
            $table->timestamps();
            $table->unique(['user_id', 'section', 'role'], 'user_section_roles_unique');
        });

        $users = DB::table('users')->get(['id', 'name']);
        foreach ($users as $user) {
            $isSander = mb_strtolower(trim((string) $user->name)) === 'sander hekkert';
            $defaultRole = $isSander
                ? UserSectionRole::ROLE_TEAMLEIDER
                : UserSectionRole::ROLE_LEIDING;

            DB::table('user_section_roles')->insert([
                [
                    'user_id' => $user->id,
                    'section' => UserSectionRole::SECTION_DOLFIJNEN,
                    'role' => $defaultRole,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'user_id' => $user->id,
                    'section' => UserSectionRole::SECTION_ZEEVERKENNERS,
                    'role' => $defaultRole,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
            if ($isSander) {
                DB::table('user_section_roles')->insert([
                    'user_id' => $user->id,
                    'section' => UserSectionRole::SECTION_ALL,
                    'role' => UserSectionRole::ROLE_ADMIN,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_section_roles');

        $tables = [
            'events',
            'members',
            'leaders',
            'pods',
            'pod_memberships',
            'info_notes',
            'task_items',
            'task_categories',
            'year_theme_entries',
        ];

        Schema::table('task_categories', function (Blueprint $table): void {
            $table->dropUnique('task_categories_section_name_unique');
            $table->unique('name', 'task_categories_name_unique');
        });

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn('section');
            });
        }
    }
};
