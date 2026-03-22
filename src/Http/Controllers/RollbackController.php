<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use MoonShine\MoonTrail\Contracts\RollbackStrategyContract;
use MoonShine\MoonTrail\Exceptions\ModelVersionNotFoundException;
use MoonShine\MoonTrail\Exceptions\NoChangesToRollbackException;
use MoonShine\MoonTrail\Exceptions\RollbackCancelledException;
use MoonShine\MoonTrail\Exceptions\RollbackConflictException;
use MoonShine\MoonTrail\Exceptions\RollbackDeniedException;
use MoonShine\MoonTrail\Models\ModelVersion;
use MoonShine\MoonTrail\Versioning\RollbackAuthorizationResolver;
use MoonShine\Support\Enums\ToastType;
use Throwable;

use function in_array;

final readonly class RollbackController
{
    public function __construct(
        private RollbackStrategyContract $rollbackService,
        private RollbackAuthorizationResolver $authorization,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        try {
            $modelVersionId = (int) $request->integer('modelVersion');

            $modelVersion = ModelVersion::query()->find($modelVersionId);

            if (! $modelVersion instanceof ModelVersion) {
                throw new ModelVersionNotFoundException('Version not found.');
            }

            $relation = $modelVersion->versionable();

            if ($this->usesSoftDeletes((string) $modelVersion->versionable_type)) {
                $relation = $relation->withTrashed();
            }

            $model = $relation->first();

            if (! $model instanceof Model) {
                throw new ModelVersionNotFoundException('Version target model not found.');
            }

            $this->authorization->authorize($model);

            $this->rollbackService->rollback(
                model: $model,
                targetVersion: (int) $modelVersion->version,
            );

            toast(
                message: (string) __('moontrail::ui.rollback_success'),
                type: ToastType::SUCCESS,
            );

            return redirect()->back();
        } catch (AuthorizationException|RollbackDeniedException) {
            toast(
                message: (string) __('moontrail::ui.rollback_error_forbidden'),
                type: ToastType::ERROR,
            );

            abort(403, (string) __('moontrail::ui.rollback_error_forbidden'));
        } catch (ValidationException|ModelVersionNotFoundException|RollbackCancelledException|NoChangesToRollbackException $e) {
            $message = $e instanceof RollbackCancelledException
                ? (string) __('moontrail::ui.rollback_error_cancelled')
                : (string) __('moontrail::ui.rollback_error_validation');

            toast(message: $message, type: ToastType::ERROR);

            abort(422, $message);
        } catch (RollbackConflictException) {
            toast(
                message: (string) __('moontrail::ui.rollback_error_conflict'),
                type: ToastType::ERROR,
            );

            abort(409, (string) __('moontrail::ui.rollback_error_conflict'));
        } catch (Throwable $exception) {
            report($exception);

            toast(
                message: (string) __('moontrail::ui.rollback_error_unexpected'),
                type: ToastType::ERROR,
            );

            abort(500, (string) __('moontrail::ui.rollback_error_unexpected'));
        }
    }

    private function usesSoftDeletes(string $className): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($className), true);
    }
}
