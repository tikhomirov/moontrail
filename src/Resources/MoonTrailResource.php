<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Resources;

use Illuminate\Contracts\Database\Eloquent\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Carbon;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\Laravel\Resources\ModelResource;
use MoonShine\MoonTrail\Components\ActivityTimeline;
use MoonShine\MoonTrail\Components\DiffViewer;
use MoonShine\MoonTrail\Contracts\ActivityFormatterContract;
use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Enums\ActivityEvent;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Pages\MoonTrailDetailPage;
use MoonShine\MoonTrail\Pages\MoonTrailIndexPage;
use MoonShine\MoonTrail\Support\SvgIcons;
use MoonShine\Support\Enums\Ability;
use MoonShine\UI\Fields\Date;
use MoonShine\UI\Fields\ID;
use MoonShine\UI\Fields\Preview;
use MoonShine\UI\Fields\Select;
use MoonShine\UI\Fields\Text;
use Spatie\Activitylog\Models\Activity;
use Throwable;

use function in_array;

/**
 * @extends ModelResource<Activity>
 */
final class MoonTrailResource extends ModelResource
{
    protected string $model = Activity::class;

    protected string $column = 'id';

    public function getTitle(): string
    {
        return (string) __('moontrail::ui.activity_log');
    }

    /**
     * Read-only: disable all write operations on the audit log.
     */
    protected function isCan(Ability $ability): bool
    {
        if (in_array($ability, [
            Ability::CREATE,
            Ability::UPDATE,
            Ability::DELETE,
            Ability::MASS_DELETE,
        ], true)) {
            return false;
        }

        return parent::isCan($ability);
    }

    protected function pages(): array
    {
        return [
            MoonTrailIndexPage::class,
            MoonTrailDetailPage::class,
        ];
    }

    /**
     * @return iterable<int, object>
     */
    protected function indexFields(): iterable
    {
        return [
            ID::make()->sortable(),

            Text::make(
                (string) __('moontrail::ui.field_log'),
                'log_name',
                formatted: static fn (Activity $activity): string => (string) ($activity->log_name ?: '—'),
            ),

            Preview::make(
                (string) __('moontrail::ui.field_event'),
                'event',
                formatted: fn (Activity $activity): string => $this->renderEventBadge($activity),
            )->sortable(),

            Preview::make(
                (string) __('moontrail::ui.field_subject'),
                'subject_type',
                formatted: fn (Activity $activity): string => $this->renderSubjectLink($activity),
            ),

            Preview::make(
                (string) __('moontrail::ui.field_causer'),
                'causer_type',
                formatted: fn (Activity $activity): string => $this->renderCauserLink($activity),
            ),

            Text::make(
                (string) __('moontrail::ui.field_description'),
                'description',
                formatted: fn (Activity $activity): string => $this->formatDescription($activity),
            ),

            Date::make(
                (string) __('moontrail::ui.field_date'),
                'created_at',
            )->sortable(),
        ];
    }

    /**
     * @return iterable<int, object>
     */
    protected function detailFields(): iterable
    {
        return [
            // Section 1: General
            Preview::make(
                label: '',
                column: 'id',
                formatted: fn (Activity $activity): string => $this->renderGeneralSection($activity),
            ),

            // Section 2: Relations
            Preview::make(
                label: '',
                column: 'subject_type',
                formatted: fn (Activity $activity): string => $this->renderRelationsSection($activity),
            ),

            // Section 3: Changes
            Preview::make(
                label: '',
                column: 'properties',
                formatted: fn (Activity $activity): string => $this->renderChangesSection($activity),
            ),

            // Section 4: History
            Preview::make(
                label: '',
                column: 'causer_type',
                formatted: static fn (Activity $activity): string => self::renderHistorySection($activity),
            ),
        ];
    }

    protected function modifyQueryBuilder(
        BuilderContract $builder,
    ): BuilderContract {
        /** @var EloquentBuilder<Activity> $builder */
        $builder = parent::modifyQueryBuilder($builder)
            ->with(['subject', 'causer'])
            ->latest('id');

        $logName = $this->requestFilter('log_name');

        if ($logName !== null) {
            $builder->where('log_name', $logName);
        }

        $event = $this->requestFilter('event');

        if ($event !== null) {
            $builder->where('event', $event);
        }

        $subjectType = $this->requestFilter('subject_type');

        if ($subjectType !== null) {
            $builder->where('subject_type', $subjectType);
        }

        $subjectId = $this->requestFilter('subject_id');

        if ($subjectId !== null) {
            $builder->where('subject_id', $subjectId);
        }

        $causerType = $this->requestFilter('causer_type');

        if ($causerType !== null) {
            $builder->where('causer_type', $causerType);
        }

        $causerId = $this->requestFilter('causer_id');

        if ($causerId !== null) {
            $builder->where('causer_id', $causerId);
        }

        $dateFrom = $this->parseDateFilter('date_from');

        if ($dateFrom instanceof Carbon) {
            $builder->where('created_at', '>=', $dateFrom->startOfDay());
        }

        $dateUntil = $this->parseDateFilter('date_until');

        if ($dateUntil instanceof Carbon) {
            $builder->where('created_at', '<=', $dateUntil->endOfDay());
        }

        $search = $this->requestSearch();

        if ($search !== null) {
            $like = '%' . $search . '%';

            // Extract numeric IDs from search term (e.g., "product 46" → ["46"])
            preg_match_all('/\d+/', $search, $numericMatches);
            $numericIds = array_unique($numericMatches[0]);

            $builder->where(static function (\Illuminate\Database\Eloquent\Builder $query) use ($like, $numericIds): void {
                $query
                    ->where('description', 'like', $like)
                    ->orWhere('event', 'like', $like)
                    ->orWhere('subject_type', 'like', $like)
                    ->orWhere('log_name', 'like', $like)
                    ->orWhere('properties', 'like', $like);

                // Add exact ID matches for any numbers found in search
                if ($numericIds !== []) {
                    $query->orWhereIn('subject_id', $numericIds)
                        ->orWhereIn('causer_id', $numericIds);
                }
            });
        }

        return $builder;
    }

    protected function filters(): iterable
    {
        $logOptionsRaw = Activity::query()
            ->distinct()
            ->whereNotNull('log_name')
            ->pluck('log_name')
            ->all();

        $eventValuesRaw = Activity::query()
            ->distinct()
            ->whereNotNull('event')
            ->pluck('event')
            ->all();

        $subjectTypesRaw = Activity::query()
            ->distinct()
            ->whereNotNull('subject_type')
            ->pluck('subject_type')
            ->all();

        $causerTypesRaw = Activity::query()
            ->distinct()
            ->whereNotNull('causer_type')
            ->pluck('causer_type')
            ->all();

        /** @var array<string, string> $logOptions */
        $logOptions = [];

        foreach ($logOptionsRaw as $logOption) {
            if (! is_string($logOption)) {
                continue;
            }

            if ($logOption === '') {
                continue;
            }

            $logOptions[$logOption] = $logOption;
        }

        /** @var array<string, string> $eventValues */
        $eventValues = [];

        foreach ($eventValuesRaw as $eventValue) {
            if (! is_string($eventValue)) {
                continue;
            }

            if ($eventValue === '') {
                continue;
            }

            $eventValues[$eventValue] = $eventValue;
        }

        /** @var array<string, string> $subjectTypes */
        $subjectTypes = [];

        foreach ($subjectTypesRaw as $subjectType) {
            if (! is_string($subjectType)) {
                continue;
            }

            if ($subjectType === '') {
                continue;
            }

            $subjectTypes[$subjectType] = class_basename($subjectType);
        }

        /** @var array<string, string> $causerTypes */
        $causerTypes = [];

        foreach ($causerTypesRaw as $causerType) {
            if (! is_string($causerType)) {
                continue;
            }

            if ($causerType === '') {
                continue;
            }

            $causerTypes[$causerType] = class_basename($causerType);
        }

        return [
            Select::make((string) __('moontrail::ui.field_log'), 'log_name')
                ->options($logOptions)
                ->nullable(),
            Select::make((string) __('moontrail::ui.field_event'), 'event')
                ->options($eventValues)
                ->nullable(),
            Select::make((string) __('moontrail::ui.field_subject_type'), 'subject_type')
                ->options($subjectTypes)
                ->nullable(),
            Text::make((string) __('moontrail::ui.field_subject_id'), 'subject_id'),
            Select::make((string) __('moontrail::ui.field_causer_type'), 'causer_type')
                ->options($causerTypes)
                ->nullable(),
            Text::make((string) __('moontrail::ui.field_causer_id'), 'causer_id'),
            Date::make((string) __('moontrail::ui.filter_date_from'), 'date_from'),
            Date::make((string) __('moontrail::ui.filter_date_until'), 'date_until'),
        ];
    }

    protected function search(): array
    {
        // Search is handled in modifyQueryBuilder() to support mixed queries
        // like "product 46" (text + numeric IDs) without framework-level conflicts.
        return [];
    }

    // -------------------------------------------------------------------------
    // Static helpers (usable from both static and instance contexts)
    // -------------------------------------------------------------------------

    private static function sectionIcon(string $type): string
    {
        return match ($type) {
            'general'   => SvgIcons::info(),
            'relations' => SvgIcons::link(),
            'changes'   => SvgIcons::diff(),
            'history'   => SvgIcons::clock(),
            default     => '',
        };
    }

    private static function sectionHeader(string $title, string $icon): string
    {
        return <<<HTML
<div class="ms-al-section-header flex items-center gap-2.5 px-4 py-3 bg-gray-50/80 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-700/70">
    <div class="ms-al-section-icon flex-shrink-0 w-7 h-7 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-400 shadow-sm">
        {$icon}
    </div>
    <h3 class="ms-al-section-title text-sm font-bold text-gray-700 dark:text-gray-200 tracking-wide">{$title}</h3>
</div>
HTML;
    }

    // -------------------------------------------------------------------------
    // History section (static — called from static closure)
    // -------------------------------------------------------------------------

    private static function renderHistorySection(Activity $activity): string
    {
        if ($activity->subject_type === null || $activity->subject_id === null) {
            return '';
        }

        $perPage = is_numeric(config('moontrail.ui.per_page'))
            ? (int) config('moontrail.ui.per_page')
            : 20;

        $versions = ModelVersion::query()
            ->where('versionable_type', $activity->subject_type)
            ->where('versionable_id', $activity->subject_id)
            ->with('author')
            ->latest('version')
            ->limit($perPage)
            ->get()
            ->all();

        if ($versions === []) {
            return '';
        }

        $sectionTitle = e((string) __('moontrail::ui.section_history'));
        $header = self::sectionHeader($sectionTitle, self::sectionIcon('history'));

        $timeline = ActivityTimeline::make(
            label: (string) __('moontrail::ui.history'),
        )->setVersions(array_values($versions));

        $rendered = $timeline->render();
        $timelineHtml = is_string($rendered) ? $rendered : (method_exists($rendered, 'toHtml') ? $rendered->toHtml() : '');

        return <<<HTML
<div class="ms-al-card">
    {$header}
    <div class="p-4">
        {$timelineHtml}
    </div>
</div>
HTML;
    }

    private function eventSvgIcon(?ActivityEvent $event): string
    {
        if (! $event instanceof ActivityEvent) {
            return '';
        }

        return match ($event) {
            ActivityEvent::Created    => SvgIcons::created('w-3 h-3', 'flex-shrink-0'),
            ActivityEvent::Updated    => SvgIcons::updated('w-3 h-3', 'flex-shrink-0'),
            ActivityEvent::Deleted    => SvgIcons::deleted('w-3 h-3', 'flex-shrink-0'),
            ActivityEvent::Restored   => SvgIcons::restored('w-3 h-3', 'flex-shrink-0'),
            ActivityEvent::RolledBack => SvgIcons::rolledBack('w-3 h-3', 'flex-shrink-0'),
        };
    }

    // -------------------------------------------------------------------------
    // Detail section renderers (instance — closures bind $this)
    // -------------------------------------------------------------------------

    private function renderGeneralSection(Activity $activity): string
    {
        $dateFormat = is_string(config('moontrail.ui.date_format'))
            ? config('moontrail.ui.date_format')
            : 'd.m.Y H:i:s';

        $rawKey = $activity->getKey();
        $id = is_scalar($rawKey) ? (string) $rawKey : '0';
        $logName = $activity->log_name ? e((string) $activity->log_name) : null;
        $eventBadge = $this->renderEventBadge($activity);
        $description = e($this->formatDescription($activity));
        $date = $activity->created_at?->format($dateFormat) ?? '—';

        $labelId = e((string) __('moontrail::ui.field_id'));
        $labelLog = e((string) __('moontrail::ui.field_log'));
        $labelEvent = e((string) __('moontrail::ui.field_event'));
        $labelDescription = e((string) __('moontrail::ui.field_description'));
        $labelDate = e((string) __('moontrail::ui.field_date'));
        $sectionTitle = e((string) __('moontrail::ui.section_general'));

        $header = self::sectionHeader($sectionTitle, self::sectionIcon('general'));

        $row = static fn (string $label, string $value, bool $raw = false): string => <<<HTML
<div class="ms-al-row flex items-start gap-4 py-2.5 border-b border-gray-50 dark:border-gray-700/40 last:border-0">
    <span class="ms-al-row-label w-28 shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide pt-0.5">{$label}</span>
    <span class="ms-al-row-value text-sm text-gray-800 dark:text-gray-100 leading-relaxed">{$value}</span>
</div>
HTML;

        $logRow = $logName !== null ? $row($labelLog, "<span class=\"inline-flex items-center px-2 py-0.5 rounded-md text-xs font-mono bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300\">{$logName}</span>", true) : '';

        return <<<HTML
<div class="ms-al-card">
    {$header}
    <div class="px-4 py-2">
        {$row($labelId, "<span class=\"font-mono font-bold text-gray-600 dark:text-gray-300\">#{$id}</span>", true)}
        {$logRow}
        <div class="flex items-center gap-4 py-2.5 border-b border-gray-50 dark:border-gray-700/40">
            <span class="ms-al-row-label w-28 shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">{$labelEvent}</span>
            <span class="text-sm">{$eventBadge}</span>
        </div>
        {$row($labelDescription, $description)}
        {$row($labelDate, "<span class=\"font-mono text-xs text-gray-700 dark:text-gray-300\">{$date}</span>", true)}
    </div>
</div>
HTML;
    }

    private function renderRelationsSection(Activity $activity): string
    {
        $sectionTitle = e((string) __('moontrail::ui.section_relations'));
        $labelCauser = e((string) __('moontrail::ui.field_causer'));
        $labelSubject = e((string) __('moontrail::ui.field_subject'));
        $openLabel = e((string) __('moontrail::ui.open'));

        $header = self::sectionHeader($sectionTitle, self::sectionIcon('relations'));

        $causerHtml = $this->renderRelationEntry($activity->causer_type, $activity->causer_id, $activity->causer, $openLabel);
        $subjectHtml = $this->renderRelationEntry($activity->subject_type, $activity->subject_id, $activity->subject, $openLabel);

        return <<<HTML
<div class="ms-al-card">
    {$header}
    <div class="px-4 py-2">
        <div class="ms-al-row border-b">
            <span class="ms-al-row-label w-28 shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">{$labelCauser}</span>
            <span class="flex items-center gap-2 text-sm flex-wrap">{$causerHtml}</span>
        </div>
        <div class="flex items-center gap-4 py-2.5">
            <span class="ms-al-row-label w-28 shrink-0 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wide">{$labelSubject}</span>
            <span class="flex items-center gap-2 text-sm flex-wrap">{$subjectHtml}</span>
        </div>
    </div>
</div>
HTML;
    }

    private function renderChangesSection(Activity $activity): string
    {
        $sectionTitle = e((string) __('moontrail::ui.section_changes'));
        $header = self::sectionHeader($sectionTitle, self::sectionIcon('changes'));
        $changes = DiffComputer::fromActivity($activity);

        $viewer = DiffViewer::make(changes: $changes);
        $rendered = $viewer->render();
        $diffHtml = is_string($rendered) ? $rendered : (method_exists($rendered, 'toHtml') ? $rendered->toHtml() : '');

        return <<<HTML
<div class="ms-al-card">
    {$header}
    <div class="p-4">
        {$diffHtml}
    </div>
</div>
HTML;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function renderEventBadge(Activity $activity): string
    {
        $event = ActivityEvent::tryFrom((string) $activity->event);

        if ($event instanceof ActivityEvent) {
            $label = e($event->label());
            $color = $event->color();
            $icon = $this->eventSvgIcon($event);
            $msAlEventClass = 'ms-al-event-' . str_replace('_', '-', $event->value);
        } else {
            $label = $activity->event !== null && $activity->event !== ''
                ? e(ucfirst((string) $activity->event))
                : '—';
            $color = 'gray';
            $icon = '';
            $msAlEventClass = 'ms-al-event-unknown';
        }

        $colorClasses = match ($color) {
            'green'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 ring-1 ring-emerald-200 dark:ring-emerald-800',
            'blue'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 ring-1 ring-blue-200 dark:ring-blue-800',
            'red'    => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 ring-1 ring-red-200 dark:ring-red-800',
            'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 ring-1 ring-purple-200 dark:ring-purple-800',
            'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300 ring-1 ring-orange-200 dark:ring-orange-800',
            default  => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 ring-1 ring-gray-200 dark:ring-gray-600',
        };

        $iconHtml = $icon !== '' ? "<span class=\"opacity-80\">{$icon}</span>" : '';

        return "<span class=\"ms-al-event-badge {$msAlEventClass} inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {$colorClasses}\">{$iconHtml}{$label}</span>";
    }

    private function renderRelationEntry(
        ?string $morphType,
        int|string|null $morphId,
        mixed $model,
        string $openLabel,
    ): string {
        if ($morphType === null) {
            $systemLabel = e((string) __('moontrail::ui.system'));

            return '<span class="ms-al-relation-system ms-al-row-value inline-flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300">'
                . SvgIcons::computer('w-4 h-4', 'flex-shrink-0 text-gray-500 dark:text-gray-400')
                . $systemLabel
                . '</span>';
        }

        $className = class_basename($morphType);
        $identifier = $morphId !== null ? '#' . $morphId : '';
        $displayName = $this->extractDisplayName($model);
        $text = e(trim("{$className} {$identifier} {$displayName}"));

        $openBtn = '';

        if ($morphId !== null) {
            $detailUrl = $this->findDetailUrl($morphType, $morphId);

            if ($detailUrl !== null) {
                $escapedUrl = e($detailUrl);
                $openBtn = ' <a href="' . $escapedUrl . '" target="_blank"'
                    . ' class="ms-al-btn-open inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-md border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-800/40 hover:border-blue-300 dark:hover:border-blue-600 transition-all duration-150 shadow-sm">'
                    . SvgIcons::externalLink('w-3 h-3', 'flex-shrink-0')
                    . $openLabel
                    . '</a>';
            } else {
                $noResourceLabel = e((string) __('moontrail::ui.no_resource'));
                $openBtn = ' <span class="ms-al-relation-no-resource inline-flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500 italic">'
                    . SvgIcons::info('w-3 h-3', 'flex-shrink-0')
                    . $noResourceLabel
                    . '</span>';
            }
        }

        return '<span class="ms-al-row-value font-medium text-gray-800 dark:text-gray-100">' . $text . '</span>' . $openBtn;
    }

    private function formatDescription(Activity $activity): string
    {
        /** @var ActivityFormatterContract $formatter */
        $formatter = app(ActivityFormatterContract::class);
        $formatted = $formatter->format($activity);
        $description = $formatted['description'];

        if ($description !== '') {
            return $description;
        }

        return (string) ($activity->description ?: '—');
    }

    private function renderSubjectLink(Activity $activity): string
    {
        return $this->renderEntityLink(
            $activity->subject_type,
            $activity->subject_id,
            $activity->subject,
        );
    }

    private function renderCauserLink(Activity $activity): string
    {
        return $this->renderEntityLink(
            $activity->causer_type,
            $activity->causer_id,
            $activity->causer,
        );
    }

    private function renderEntityLink(
        ?string $morphType,
        int|string|null $morphId,
        mixed $model,
    ): string {
        if ($morphType === null) {
            $systemLabel = e((string) __('moontrail::ui.system'));

            return "<span class=\"text-gray-400 dark:text-gray-500 text-xs italic\">{$systemLabel}</span>";
        }

        $className = class_basename($morphType);
        $identifier = $morphId !== null ? '#' . $morphId : '';
        $name = $this->extractDisplayName($model);
        $text = e(trim("{$className} {$identifier} {$name}"));

        if ($morphId !== null) {
            $url = $this->findDetailUrl($morphType, $morphId);

            if ($url !== null) {
                $escapedUrl = e($url);
                $icon = SvgIcons::externalLink('w-3 h-3', 'flex-shrink-0 opacity-60');

                return "<a href=\"{$escapedUrl}\" class=\"ms-al-index-link inline-flex items-center gap-1 text-blue-600 dark:text-blue-400 hover:underline font-medium text-sm\">{$icon}{$text}</a>";
            }
        }

        return '<span class="text-sm text-gray-700 dark:text-gray-300">' . $text . '</span>';
    }

    private function extractDisplayName(mixed $model): ?string
    {
        if (! is_object($model)) {
            return null;
        }

        $raw = data_get($model, 'name')
            ?? data_get($model, 'title')
            ?? data_get($model, 'email');

        if (! is_scalar($raw) || $raw === '') {
            return null;
        }

        return '(' . $raw . ')';
    }

    /**
     * Attempt to resolve a MoonShine detail URL for the given morph type + id.
     * Returns null if no matching resource is registered.
     */
    private function findDetailUrl(string $morphType, int|string $morphId): ?string
    {
        try {
            $resources = moonshine()->getResources();

            /** @var ResourceContract $resource */
            foreach ($resources as $resource) {
                if (
                    method_exists($resource, 'getModel')
                    && $resource->getModel()::class === $morphType
                ) {
                    $url = toPage(
                        page: DetailPage::class,
                        resource: $resource,
                        params: ['resourceItem' => $morphId],
                    );

                    return is_string($url) ? $url : null;
                }
            }
        } catch (Throwable) {
            // Resource lookup failed — return null silently
        }

        return null;
    }

    private function requestFilter(string $key): ?string
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

    private function requestSearch(): ?string
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

    private function parseDateFilter(string $key): ?Carbon
    {
        $raw = $this->requestFilter($key);

        if ($raw === null) {
            return null;
        }

        try {
            $parsed = Carbon::createFromFormat('Y-m-d', $raw);

            if (! $parsed instanceof Carbon || $parsed->format('Y-m-d') !== $raw) {
                return null;
            }

            return $parsed;
        } catch (Throwable) {
            return null;
        }
    }
}
