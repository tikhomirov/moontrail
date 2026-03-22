<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('moontrail_activity_log')) {
            return;
        }

        Schema::create('moontrail_activity_log', static function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('event');
            $table->json('properties')->nullable();
            $table->nullableMorphs('causer');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
            $table->index(['model_type', 'model_id']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moontrail_activity_log');
    }
};
