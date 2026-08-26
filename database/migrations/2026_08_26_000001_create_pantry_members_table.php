<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pantry_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('pantry_id')->constrained('pantries')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['pantry_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pantry_members');
    }
};
