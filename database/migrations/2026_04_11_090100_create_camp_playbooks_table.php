<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camp_playbooks', function (Blueprint $table): void {
            $table->id();
            $table->string('section');
            $table->unsignedSmallInteger('camp_year');
            $table->string('title', 255);
            $table->longText('content')->nullable();
            $table->json('meta')->nullable();
            $table->string('status', 32)->default('draft');
            $table->text('review_note')->nullable();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['section', 'camp_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camp_playbooks');
    }
};
