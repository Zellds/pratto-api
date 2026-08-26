<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pantry_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('pantry_id')->constrained('pantries')->cascadeOnDelete();
            $table->foreignUlid('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('quantity', 8, 2)->default(1.0);
            $table->string('unit')->default('unidade');
            $table->boolean('needs_to_buy')->default(true);
            $table->boolean('is_fixed')->default(false);
            $table->timestamps();
            $table->unique(['pantry_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pantry_items');
    }
};
