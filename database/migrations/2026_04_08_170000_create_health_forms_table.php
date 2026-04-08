<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_forms', function (Blueprint $table): void {
            $table->id();
            $table->string('section', 40)->index();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('original_name');
            $table->string('storage_path')->unique();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_forms');
    }
};
