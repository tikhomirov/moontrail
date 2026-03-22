<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use Illuminate\Database\Eloquent\Model;

interface ModelBackedActivityRecordContract extends ActivityRecordContract
{
    public function model(): Model;
}
