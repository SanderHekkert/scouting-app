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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->boolean('installed')->default(false);
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birthday')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('address')->nullable();
            $table->string('phone_mother')->nullable();
            $table->string('phone_father')->nullable();
            $table->text('bijzonderheden')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('tipper_topper_opkomst')->nullable();
            $table->unsignedTinyInteger('tipper_topper_opkomst_order')->nullable();
            $table->string('section', 40)->default('dolfijnen')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
