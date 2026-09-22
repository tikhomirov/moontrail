<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('moontrail_activity_log')) {
            return;
        }

        $existingIndexes = collect(Schema::getIndexes('moontrail_activity_log'))->pluck('name')->all();

        Schema::table('moontrail_activity_log', static function (Blueprint $table) use ($existingIndexes): void {
            if (Schema::hasColumn('moontrail_activity_log', 'log_name') && ! in_array('moontrail_activity_log_log_name_index', $existingIndexes, true)) {
                $table->index('log_name');
            }

            if (Schema::hasColumn('moontrail_activity_log', 'created_at') && ! in_array('moontrail_activity_log_created_at_index', $existingIndexes, true)) {
                $table->index('created_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('moontrail_activity_log')) {
            return;
        }

        $existingIndexes = collect(Schema::getIndexes('moontrail_activity_log'))->pluck('name')->all();

        Schema::table('moontrail_activity_log', static function (Blueprint $table) use ($existingIndexes): void {
            if (in_array('moontrail_activity_log_created_at_index', $existingIndexes, true)) {
                $table->dropIndex(['created_at']);
            }

            if (in_array('moontrail_activity_log_log_name_index', $existingIndexes, true)) {
                $table->dropIndex(['log_name']);
            }
        });
    }
};
