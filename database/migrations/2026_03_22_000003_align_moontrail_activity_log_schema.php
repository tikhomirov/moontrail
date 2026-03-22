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

        Schema::table('moontrail_activity_log', static function (Blueprint $table): void {
            if (! Schema::hasColumn('moontrail_activity_log', 'log_name')) {
                $table->string('log_name')->nullable()->after('id');
            }

            if (! Schema::hasColumn('moontrail_activity_log', 'subject_type')) {
                $table->string('subject_type')->nullable()->after('log_name');
            }

            if (! Schema::hasColumn('moontrail_activity_log', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
            }
        });

    }

    public function down(): void
    {
        if (! Schema::hasTable('moontrail_activity_log')) {
            return;
        }

        Schema::table('moontrail_activity_log', static function (Blueprint $table): void {
            if (Schema::hasColumn('moontrail_activity_log', 'subject_id')) {
                $table->dropColumn('subject_id');
            }

            if (Schema::hasColumn('moontrail_activity_log', 'subject_type')) {
                $table->dropColumn('subject_type');
            }

            if (Schema::hasColumn('moontrail_activity_log', 'log_name')) {
                $table->dropColumn('log_name');
            }
        });
    }
};
