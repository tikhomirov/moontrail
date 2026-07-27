<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_versions', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation to the model
            $table->morphs('versionable');

            // Auto-incrementing version number scoped per record
            $table->unsignedInteger('version');

            // Full attribute snapshot at this version
            $table->json('snapshot');

            // Link to activity_log or moontrail_activity_log (nullable for initial versions)
            $table->foreignId('activity_id')
                ->nullable()
                ->nullOnDelete();

            // Who created the version
            $table->nullableMorphs('author');

            // Event (created, updated, rolled_back)
            $table->string('event', 50);

            // Whether this version was created by a rollback
            $table->boolean('is_rollback')->default(false);

            // Version number that was rolled back to (when is_rollback = true)
            $table->unsignedInteger('rollback_to_version')->nullable();

            $table->timestamps();

            // Unique version number per model record
            $table->unique(['versionable_type', 'versionable_id', 'version']);

            // Index for fast model-based lookups
            $table->index(['versionable_type', 'versionable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_versions');
    }
};
