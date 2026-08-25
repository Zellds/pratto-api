<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('follower_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('followee_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['follower_id', 'followee_id']);
        });

        DB::statement('ALTER TABLE follows ADD CONSTRAINT follows_not_self_check CHECK (follower_id <> followee_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
