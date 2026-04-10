<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('section_role_visibilities', function (Blueprint $table): void {
            $table->id();
            $table->string('section');
            $table->string('role');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->unique(['section', 'role']);
            $table->index(['section', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_role_visibilities');
    }
};
