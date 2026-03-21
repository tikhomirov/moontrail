<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Pages;

use Illuminate\Contracts\Support\Renderable;
use MoonShine\Contracts\Core\ResourceContract;
use MoonShine\Contracts\UI\ComponentContract;
use MoonShine\Laravel\Pages\Crud\DetailPage;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\Layout\Box;
use Throwable;

/**
 * Detail page that renders the 4 activity-log sections (General / Relations /
 * Changes / History) as full-width blocks, bypassing the default 2-column
 * (label + value) table layout used by DefaultDetailComponent.
 */
final class MoonTrailDetailPage extends DetailPage
{
    /**
     * @return list<ComponentContract>
     *
     * @throws Throwable
     */
    protected function mainLayer(): array
    {
        $item = $this->getItem();
        $resource = $this->getResource();

        if (! $resource instanceof ResourceContract) {
            return [Box::make([])];
        }

        $caster = $resource->getCaster();
        $fields = $resource->getDetailFields();
        $assetUrl = e($this->resolveStylesheetAssetUrl());
        $sections = [
            FlexibleRender::make("<link rel=\"stylesheet\" href=\"{$assetUrl}\">"),
        ];

        foreach ($fields as $field) {
            $field->fillCast($item, $caster);
            $rendered = $field->preview();
            $html = $rendered instanceof Renderable ? $rendered->render() : $rendered;

            if ($html !== '') {
                $sections[] = FlexibleRender::make($html);
            }
        }

        return [
            Box::make($sections),
        ];
    }

    private function resolveStylesheetAssetUrl(): string
    {
        return asset('vendor/moontrail/moontrail.css');
    }
}
