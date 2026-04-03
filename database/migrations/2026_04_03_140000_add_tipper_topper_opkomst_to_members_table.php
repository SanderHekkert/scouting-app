<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->boolean('tipper_topper_opkomst')->nullable()->after('active');
            $table->unsignedTinyInteger('tipper_topper_opkomst_order')->nullable()->after('tipper_topper_opkomst');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['tipper_topper_opkomst', 'tipper_topper_opkomst_order']);
        });
    }
};
