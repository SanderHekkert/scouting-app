<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('year_theme_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->string('label');
            $table->text('value')->nullable();
            $table->string('section', 40)->default('dolfijnen')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('year_theme_entries');
    }
};
