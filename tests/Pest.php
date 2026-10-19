<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Tests\LivewireAdapterTestCase;
use MoonShine\MoonTrail\Tests\TestCase;

uses(TestCase::class)->in('Unit', 'Feature');
uses(LivewireAdapterTestCase::class)->in('NoMoonShine');
