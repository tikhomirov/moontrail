<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

interface ActivityFormatterContract
{
    /**
     * @return array{description: string, icon: string, color: string}
     */
    public function format(ActivityRecordContract $activity): array;
}
