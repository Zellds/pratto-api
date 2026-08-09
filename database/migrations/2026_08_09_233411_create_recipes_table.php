<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description');
            $table->unsignedSmallInteger('portions');
            $table->unsignedSmallInteger('prep_time_minutes');
            $table->string('status')->default('draft');
            // Sem FK ainda: a tabela `media` só existe no Plano 3, que adiciona
            // a constraint numa migration própria.
            $table->ulid('cover_media_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        DB::statement('ALTER TABLE recipes ADD COLUMN search_vector tsvector');
        DB::statement('CREATE INDEX recipes_search_vector_idx ON recipes USING GIN (search_vector)');
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
