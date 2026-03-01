<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Http\Controllers;

use Illuminate\Http\JsonResponse;
use MoonShine\MoonTrail\Contracts\DiffRendererContract;
use MoonShine\MoonTrail\Diff\DiffComputer;
use Spatie\Activitylog\Models\Activity;

final readonly class ActivityController
{
    public function __construct(
        private DiffRendererContract $diffRenderer,
    ) {}

    public function diff(int $activity): JsonResponse
    {
        /** @var Activity $activityModel */
        $activityModel = Activity::findOrFail($activity);

        $changes = DiffComputer::fromActivity($activityModel);

        return response()->json([
            'html'  => $this->diffRenderer->render($changes),
            'event' => $activityModel->event,
        ]);
    }
}
