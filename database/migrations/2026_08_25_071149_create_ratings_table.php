<?php

// database/migrations/2026_08_25_071149_create_ratings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 2, 1);
            $table->timestamps();
            $table->unique(['recipe_id', 'user_id']);
        });

        DB::statement('ALTER TABLE ratings ADD CONSTRAINT ratings_score_check CHECK (score IN (1.0,1.5,2.0,2.5,3.0,3.5,4.0,4.5,5.0))');
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
