<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('theme');
            $table->date('event_date');
            $table->string('event_type')->nullable();
            $table->string('activity')->nullable();
            $table->string('program_by')->nullable();
            $table->text('absent')->nullable();
            $table->text('notes')->nullable();
            $table->json('task_item_ids')->nullable();
            $table->string('section', 40)->default('dolfijnen')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
