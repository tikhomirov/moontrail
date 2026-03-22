<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\DiffRendererContract;
use MoonShine\MoonTrail\Diff\DiffComputer;

final readonly class ActivityController
{
    /**
     * @param ActivityQueryContract<Model> $activityQuery
     */
    public function __construct(
        private ActivityQueryContract $activityQuery,
        private DiffRendererContract $diffRenderer,
    ) {}

    public function diff(int|string $activity): JsonResponse
    {
        $activityModel = $this->activityQuery->find($activity);

        if (! $activityModel instanceof \MoonShine\MoonTrail\Contracts\ActivityRecordContract) {
            throw (new ModelNotFoundException)->setModel(\Illuminate\Database\Eloquent\Model::class, [$activity]);
        }

        $changes = DiffComputer::fromActivity($activityModel);

        return response()->json([
            'html'  => $this->diffRenderer->render($changes),
            'event' => $activityModel->getEvent(),
        ]);
    }
}
