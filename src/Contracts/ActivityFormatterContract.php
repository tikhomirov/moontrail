<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use Spatie\Activitylog\Models\Activity;

interface ActivityFormatterContract
{
    /**
     * @return array{description: string, icon: string, color: string}
     */
    public function format(Activity $activity): array;
}
