<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        Schema::create('ingredients', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('normalized_name')->unique();
            $table->string('status')->default('provisional');
            $table->timestamps();
        });

        DB::statement('CREATE INDEX ingredients_normalized_name_trgm_idx ON ingredients USING GIN (normalized_name gin_trgm_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
