<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('audience_scope', 20)->default('self');
            $table->json('target_user_ids')->nullable();
            $table->string('theme')->default('');
            $table->date('event_date');
            $table->date('end_date')->nullable();
            $table->string('start_time', 5)->nullable();
            $table->string('end_time', 5)->nullable();
            $table->string('location')->nullable();
            $table->string('time_slot')->nullable();
            $table->text('invitees')->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->text('attachments')->nullable();
            $table->text('notes')->nullable();
            $table->string('section', 40)->default('dolfijnen')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_items');
    }
};
