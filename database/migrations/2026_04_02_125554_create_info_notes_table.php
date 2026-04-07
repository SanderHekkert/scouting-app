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
        Schema::create('info_notes', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->text('content');
            $table->string('link', 2048)->nullable();
            $table->string('section', 40)->default('dolfijnen')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('info_notes');
    }
};
