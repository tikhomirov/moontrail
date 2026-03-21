<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use MoonShine\MoonTrail\Enums\ActivityEvent;

/**
 * Normalised filter values read from the current HTTP request.
 *
 * Supports both direct query params (e.g. ?event=created) and
 * nested MoonShine params (e.g. ?filters[event]=created).
 */
final readonly class ActivityLogFilterData
{
    /**
     * @var array<string, string>
     */
    private const FIELD_MAP = [
        'log_name' => 'logName',
        'event' => 'event',
        'subject_type' => 'subjectType',
        'subject_id' => 'subjectId',
        'causer_type' => 'causerType',
        'causer_id' => 'causerId',
        'date_from' => 'dateFrom',
        'date_until' => 'dateUntil',
    ];

    public function __construct(
        public ?string $logName = null,
        public ?string $event = null,
        public ?string $subjectType = null,
        public ?string $subjectId = null,
        public ?string $causerType = null,
        public ?string $causerId = null,
        public ?string $dateFrom = null,
        public ?string $dateUntil = null,
        public ?string $search = null,
    ) {}

    public static function fromRequest(): self
    {
        return new self(
            logName: self::readParam('log_name'),
            event: self::readParam('event'),
            subjectType: self::readParam('subject_type'),
            subjectId: self::readParam('subject_id'),
            causerType: self::readParam('causer_type'),
            causerId: self::readParam('causer_id'),
            dateFrom: self::readParam('date_from'),
            dateUntil: self::readParam('date_until'),
            search: self::readSearch(),
        );
    }

    /**
     * @return list<string>
     */
    public static function filterRequestKeys(): array
    {
        return array_keys(self::FIELD_MAP);
    }

    /**
     * @return array<int, array{requestKey: string, label: string, value: string}>
     */
    public function activeFilterChips(): array
    {
        $chips = [];

        foreach (self::FIELD_MAP as $requestKey => $propertyName) {
            $rawValue = $this->{$propertyName};

            if ($rawValue === null || $rawValue === '') {
                continue;
            }

            $displayValue = $requestKey === 'event'
                ? (ActivityEvent::tryFrom($rawValue)?->label() ?? $rawValue)
                : $rawValue;

            $chips[] = [
                'requestKey' => $requestKey,
                'label' => $this->filterLabel($requestKey),
                'value' => $displayValue,
            ];
        }

        return $chips;
    }

    private static function readParam(string $key): ?string
    {
        $direct = request()->input($key);

        if (is_scalar($direct) && (string) $direct !== '') {
            return (string) $direct;
        }

        $nested = request()->input('filters.' . $key);

        if (is_scalar($nested) && (string) $nested !== '') {
            return (string) $nested;
        }

        return null;
    }

    private static function readSearch(): ?string
    {
        $candidates = [
            request()->input('search'),
            request()->input('query'),
            request()->input('filters.search'),
            request()->input('filters.query'),
        ];

        foreach ($candidates as $value) {
            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    private function filterLabel(string $requestKey): string
    {
        return match ($requestKey) {
            'log_name' => (string) __('moontrail::ui.field_log'),
            'event' => (string) __('moontrail::ui.field_event'),
            'subject_type' => (string) __('moontrail::ui.field_subject'),
            'subject_id' => (string) __('moontrail::ui.field_subject_id'),
            'causer_type' => (string) __('moontrail::ui.field_causer_type'),
            'causer_id' => (string) __('moontrail::ui.field_causer_id'),
            'date_from' => (string) __('moontrail::ui.date_from'),
            'date_until' => (string) __('moontrail::ui.date_until'),
            default => $requestKey,
        };
    }
}
