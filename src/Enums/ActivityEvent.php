<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Enums;

enum ActivityEvent: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case RolledBack = 'rolled_back';

    public function label(): string
    {
        return match ($this) {
            self::Created    => (string) __('moontrail::ui.event_created'),
            self::Updated    => (string) __('moontrail::ui.event_updated'),
            self::Deleted    => (string) __('moontrail::ui.event_deleted'),
            self::Restored   => (string) __('moontrail::ui.event_restored'),
            self::RolledBack => (string) __('moontrail::ui.event_rollback'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Created    => 'plus',
            self::Updated    => 'pencil',
            self::Deleted    => 'trash',
            self::Restored   => 'arrow-uturn-left',
            self::RolledBack => 'refresh',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created    => 'green',
            self::Updated    => 'blue',
            self::Deleted    => 'red',
            self::Restored   => 'purple',
            self::RolledBack => 'orange',
        };
    }
}
