<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use MoonShine\MoonTrail\Contracts\ActivityQueryContract;
use MoonShine\MoonTrail\Contracts\ActivityRecordContract;
use MoonShine\MoonTrail\Contracts\DiffRendererContract;
use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Support\ActivityAuthorizationResolver;

final readonly class ActivityController
{
    /**
     * @param ActivityQueryContract<Model> $activityQuery
     */
    public function __construct(
        private ActivityQueryContract $activityQuery,
        private DiffRendererContract $diffRenderer,
        private ActivityAuthorizationResolver $authorization,
    ) {}

    public function diff(int|string $activity): JsonResponse
    {
        $activityModel = $this->activityQuery->find($activity);

        if (! $activityModel instanceof ActivityRecordContract) {
            throw (new ModelNotFoundException)->setModel(Model::class, [$activity]);
        }

        $this->authorization->authorize($activityModel);

        $changes = DiffComputer::fromActivity($activityModel);

        return response()->json([
            'html'  => $this->diffRenderer->render($changes),
            'event' => $activityModel->getEvent(),
        ]);
    }
}
