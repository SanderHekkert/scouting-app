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
        Schema::create('finance_declarations', function (Blueprint $table): void {
            $table->id();
            $table->string('section');
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pot_id')->nullable()->constrained('finance_pots')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('iban', 64)->nullable();
            $table->string('account_name', 255)->nullable();
            $table->text('description_total')->nullable();
            $table->longText('description_lines')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('receipt_name')->nullable();
            $table->string('receipt_mime', 120)->nullable();
            $table->unsignedBigInteger('receipt_size')->nullable();
            $table->date('declared_at')->nullable();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();

            $table->index(['section', 'status']);
            $table->index(['section', 'created_by_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_declarations');
    }
};
