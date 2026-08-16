<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('kind');
            $table->string('storage_key')->unique();
            $table->decimal('focal_x', 3, 2)->default(0.50);
            $table->decimal('focal_y', 3, 2)->default(0.50);
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('status')->default('pending_review');
            $table->text('rejection_reason')->nullable();
            $table->foreignUlid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
