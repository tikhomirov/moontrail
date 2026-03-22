<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Use ActivityQueryContract directly and return
 * ModelBackedActivityRecordContract from find() when detail model access is needed.
 *
 * @template TActivity of Model
 *
 * @extends ActivityQueryContract<TActivity>
 */
interface ModelBackedActivityQueryContract extends ActivityQueryContract
{
    public function find(int|string $id): ?ModelBackedActivityRecordContract;
}
