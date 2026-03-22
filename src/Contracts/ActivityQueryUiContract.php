<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Use ActivityQueryContract directly.
 *
 * @template TActivity of Model
 *
 * @extends ActivityQueryContract<TActivity>
 */
interface ActivityQueryUiContract extends ActivityQueryContract {}
