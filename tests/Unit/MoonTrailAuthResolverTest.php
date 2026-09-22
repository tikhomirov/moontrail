<?php

declare(strict_types=1);

use MoonShine\Laravel\MoonShineAuth;
use MoonShine\MoonTrail\Support\MoonTrailAuthResolver;
use MoonShine\MoonTrail\Tests\Fixtures\TestAdmin;

it('resolves authenticated moonshine user and returns id and morph type', function (): void {
    $admin = TestAdmin::query()->create([
        'name'  => 'Resolver Admin',
        'email' => 'resolver-admin@example.test',
    ]);

    $resolver = new MoonTrailAuthResolver;

    expect($resolver->resolveUser())->toBeNull()
        ->and($resolver->resolveId())->toBeNull()
        ->and($resolver->resolveType())->toBeNull();

    $this->actingAs($admin, MoonShineAuth::getGuardName());

    expect($resolver->resolveUser()?->getKey())->toBe($admin->getKey())
        ->and($resolver->resolveId())->toBe($admin->getKey())
        ->and($resolver->resolveType())->toBe($admin->getMorphClass());
});
