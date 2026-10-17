<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Diff;

use Illuminate\Support\Collection;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Enums\ChangeType;
use MoonShine\MoonTrail\Support\MoonTrailConfig;

final class DiffComputer
{
    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @param array<int, string> $hiddenFields
     * @param array<int, string> $maskedFields
     * @return array<string, FieldChange>
     */
    public static function compute(array $before, array $after, array $hiddenFields = [], array $maskedFields = []): array
    {
        $keys = array_values(array_unique(array_merge(array_keys($before), array_keys($after))));

        $changes = collect($keys)
            ->reject(static fn (string $key): bool => in_array($key, $hiddenFields, true))
            ->mapWithKeys(static function (string $key) use ($before, $after, $maskedFields): array {
                $oldExists = array_key_exists($key, $before);
                $newExists = array_key_exists($key, $after);
                $oldValue = $before[$key] ?? null;
                $newValue = $after[$key] ?? null;
                $isMasked = in_array($key, $maskedFields, true);

                if (! $oldExists && $newExists) {
                    $type = ChangeType::Added;
                } elseif ($oldExists && ! $newExists) {
                    $type = ChangeType::Removed;
                } elseif ($oldValue !== $newValue) {
                    $type = ChangeType::Modified;
                } else {
                    $type = ChangeType::Unchanged;
                }

                return [
                    $key => new FieldChange(
                        field: $key,
                        oldValue: $isMasked ? '********' : $oldValue,
                        newValue: $isMasked ? '********' : $newValue,
                        type: $type,
                        masked: $isMasked,
                    ),
                ];
            })
            ->sortBy(static fn (FieldChange $change): int => $change->type === ChangeType::Unchanged ? 1 : 0);

        /** @var array<string, FieldChange> $result */
        $result = $changes->all();

        return $result;
    }

    /**
     * @param Collection<string, mixed>|array<string, mixed>|null $properties
     * @return array<string, FieldChange>
     */
    public static function fromActivityProperties(Collection|array|null $properties, ?string $event = null): array
    {
        $attributes = data_get($properties, 'attributes', []);
        $old = data_get($properties, 'old', []);
        $changed = data_get($properties, 'changed', []);

        if (! is_array($attributes)) {
            $attributes = [];
        }

        if (! is_array($old)) {
            $old = [];
        }

        if (! is_array($changed)) {
            $changed = [];
        }

        if (empty($attributes) && ! empty($changed)) {
            $attributes = $changed;
        }

        $isUpdateEvent = $event === 'updated' || $event === 'reverted' || (! empty($old) && ! empty($attributes));

        if ($isUpdateEvent && ! empty($old)) {
            foreach ($attributes as $key => $value) {
                if (! array_key_exists($key, $old) && ! array_key_exists($key, $changed)) {
                    $old[$key] = $value;
                }
            }
        }

        /** @var array<int, string> $hiddenFields */
        $hiddenFields = MoonTrailConfig::sensitiveHide();
        /** @var array<int, string> $maskedFields */
        $maskedFields = MoonTrailConfig::sensitiveMask();

        return self::compute(
            before: $old,
            after: $attributes,
            hiddenFields: $hiddenFields,
            maskedFields: $maskedFields,
        );
    }

    /**
     * @return array<string, FieldChange>
     */
    public static function fromActivity(ActivityRecordContract $activity): array
    {
        return self::fromActivityProperties($activity->getProperties(), $activity->getEvent());
    }
}
