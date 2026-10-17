<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Adapters\Livewire\Components;

use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class ActivityTimelineModal extends Component
{
    public bool $isOpen = false;

    #[Locked]
    public ?string $modelClass = null;

    #[Locked]
    public int|string|null $modelId = null;

    public ?string $title = null;

    #[On('open-moontrail-timeline')]
    public function open(string $modelClass, int|string $modelId, ?string $title = null): void
    {
        $this->modelClass = $modelClass;
        $this->modelId = $modelId;
        $this->title = $title;
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->modelClass = null;
        $this->modelId = null;
        $this->title = null;
    }

    public function render(): View
    {
        return view('moontrail::livewire.activity-timeline-modal');
    }
}
