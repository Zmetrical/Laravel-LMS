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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_number')->unique();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('strand_id');
            $table->unsignedBigInteger('section_id')->nullable();

            $table->date('enrollment_date')->nullable();
            $table->string('year_level')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
