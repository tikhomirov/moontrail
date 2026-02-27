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

            // Полиморфная связь с моделью
            $table->morphs('versionable');

            // Номер версии (автоинкремент в рамках конкретной записи)
            $table->unsignedInteger('version');

            // Полный снапшот всех атрибутов модели на момент версии
            $table->json('snapshot');

            // Связь с записью в activity_log (nullable для начальных версий)
            $table->foreignId('activity_id')
                ->nullable()
                ->constrained('activity_log')
                ->nullOnDelete();

            // Кто создал версию
            $table->nullableMorphs('author');

            // Событие (created, updated, rolled_back)
            $table->string('event', 50);

            // Метка: является ли это rollback-версией
            $table->boolean('is_rollback')->default(false);

            // Номер версии, к которой откатились (если is_rollback = true)
            $table->unsignedInteger('rollback_to_version')->nullable();

            $table->timestamps();

            // Уникальность версии в рамках конкретной записи
            $table->unique(['versionable_type', 'versionable_id', 'version']);

            // Индекс для быстрого поиска по модели
            $table->index(['versionable_type', 'versionable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_versions');
    }
};
