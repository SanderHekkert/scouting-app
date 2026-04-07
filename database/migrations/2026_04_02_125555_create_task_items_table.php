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
        Schema::create('task_items', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default('Algemeen');
            $table->string('title');
            $table->string('owner')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->date('deadline')->nullable();
            $table->string('section', 40)->default('dolfijnen')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_items');
    }
};
