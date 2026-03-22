<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Support;

use Illuminate\Http\Request;
use InvalidArgumentException;
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
        'log_name'     => 'logName',
        'event'        => 'event',
        'subject_type' => 'subjectType',
        'subject_id'   => 'subjectId',
        'causer_type'  => 'causerType',
        'causer_id'    => 'causerId',
        'date_from'    => 'dateFrom',
        'date_until'   => 'dateUntil',
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

    public static function fromRequestStrict(Request $request): self
    {
        return new self(
            logName: self::readParam('log_name', $request),
            event: self::readParam('event', $request),
            subjectType: self::readParam('subject_type', $request),
            subjectId: self::readParam('subject_id', $request),
            causerType: self::readParam('causer_type', $request),
            causerId: self::readParam('causer_id', $request),
            dateFrom: self::readParam('date_from', $request),
            dateUntil: self::readParam('date_until', $request),
            search: self::readSearch($request),
        );
    }

    public static function fromRequest(?Request $request = null): self
    {
        if (! $request instanceof Request) {
            @trigger_error(
                'ActivityLogFilterData::fromRequest() without explicit Request is deprecated and no longer supported. Use fromRequestStrict($request).',
                E_USER_DEPRECATED,
            );

            throw new InvalidArgumentException(
                'ActivityLogFilterData::fromRequest() requires explicit Request argument.',
            );
        }

        return self::fromRequestStrict($request);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public static function fromArray(array $filters): self
    {
        $value = static fn (string $key): ?string => (
            isset($filters[$key]) && is_scalar($filters[$key]) && (string) $filters[$key] !== ''
                ? (string) $filters[$key]
                : null
        );

        return new self(
            logName: $value('log_name'),
            event: $value('event'),
            subjectType: $value('subject_type'),
            subjectId: $value('subject_id'),
            causerType: $value('causer_type'),
            causerId: $value('causer_id'),
            dateFrom: $value('date_from'),
            dateUntil: $value('date_until'),
            search: $value('search') ?? $value('query'),
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
     * @return array<string, string>
     */
    public function toArray(): array
    {
        $result = [];

        foreach (self::FIELD_MAP as $requestKey => $propertyName) {
            $value = $this->{$propertyName};

            if ($value !== null && $value !== '') {
                $result[$requestKey] = $value;
            }
        }

        if ($this->search !== null && $this->search !== '') {
            $result['search'] = $this->search;
        }

        return $result;
    }

    /**
     * @return array<int, array{requestKey: string, label: string, value: string}>
     */
    public function activeFilterChips(): array
    {
        $chips = [];

        foreach (self::FIELD_MAP as $requestKey => $propertyName) {
            $rawValue = $this->{$propertyName};

            if ($rawValue === null) {
                continue;
            }

            if ($rawValue === '') {
                continue;
            }

            $displayValue = $requestKey === 'event'
                ? (ActivityEvent::tryFrom($rawValue)?->label() ?? $rawValue)
                : $rawValue;

            $chips[] = [
                'requestKey' => $requestKey,
                'label'      => $this->filterLabel($requestKey),
                'value'      => $displayValue,
            ];
        }

        return $chips;
    }

    private static function readParam(string $key, Request $request): ?string
    {
        $direct = $request->input($key);

        if (is_scalar($direct) && (string) $direct !== '') {
            return (string) $direct;
        }

        $nested = $request->input('filters.' . $key);

        if (is_scalar($nested) && (string) $nested !== '') {
            return (string) $nested;
        }

        return null;
    }

    private static function readSearch(Request $request): ?string
    {
        $candidates = [
            $request->input('search'),
            $request->input('query'),
            $request->input('filters.search'),
            $request->input('filters.query'),
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
            'log_name'     => (string) __('moontrail::ui.field_log'),
            'event'        => (string) __('moontrail::ui.field_event'),
            'subject_type' => (string) __('moontrail::ui.field_subject'),
            'subject_id'   => (string) __('moontrail::ui.field_subject_id'),
            'causer_type'  => (string) __('moontrail::ui.field_causer_type'),
            'causer_id'    => (string) __('moontrail::ui.field_causer_id'),
            'date_from'    => (string) __('moontrail::ui.date_from'),
            'date_until'   => (string) __('moontrail::ui.date_until'),
            default        => $requestKey,
        };
    }
}
