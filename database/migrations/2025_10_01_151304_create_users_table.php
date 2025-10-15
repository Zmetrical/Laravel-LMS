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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string(column: 'last_name');
            $table->string(column: 'user_name');
            $table->string(column: 'email')->unique();
            $table->string(column: 'password');
            $table->integer('role_id')->default(1);

            $table->timestamp(column: 'email_verified_at')->nullable();

            $table->integer('status')->default(value: 1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
