<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Diff\DiffComputer;
use MoonShine\MoonTrail\Enums\ChangeType;

it('detects modified fields', function (): void {
    $old = ['name' => 'John', 'email' => 'john@example.com'];
    $new = ['name' => 'John Doe', 'email' => 'john@example.com'];

    $changes = DiffComputer::compute($old, $new);

    expect($changes)->toHaveKey('name')
        ->and($changes['name']->type)->toBe(ChangeType::Modified)
        ->and($changes['name']->oldValue)->toBe('John')
        ->and($changes['name']->newValue)->toBe('John Doe');
});

it('detects added and removed fields', function (): void {
    $old = ['name' => 'John', 'role' => 'admin'];
    $new = ['name' => 'John', 'department' => 'it'];

    $changes = DiffComputer::compute($old, $new);

    expect($changes['department']->type)->toBe(ChangeType::Added)
        ->and($changes['role']->type)->toBe(ChangeType::Removed);
});

it('returns unchanged for identical data', function (): void {
    $data = ['name' => 'John', 'email' => 'john@example.com'];

    $changes = DiffComputer::compute($data, $data);

    expect($changes['name']->type)->toBe(ChangeType::Unchanged)
        ->and($changes['email']->type)->toBe(ChangeType::Unchanged);
});

it('handles null values correctly', function (): void {
    $old = ['bio' => null, 'name' => 'John'];
    $new = ['bio' => 'Some bio', 'name' => null];

    $changes = DiffComputer::compute($old, $new);

    expect($changes['bio']->type)->toBe(ChangeType::Modified)
        ->and($changes['bio']->oldValue)->toBeNull()
        ->and($changes['bio']->newValue)->toBe('Some bio')
        ->and($changes['name']->type)->toBe(ChangeType::Modified)
        ->and($changes['name']->oldValue)->toBe('John')
        ->and($changes['name']->newValue)->toBeNull();
});

it('handles nested array values as modified', function (): void {
    $old = ['meta' => ['color' => 'red', 'size' => 'S']];
    $new = ['meta' => ['color' => 'blue', 'size' => 'S']];

    $changes = DiffComputer::compute($old, $new);

    expect($changes['meta']->type)->toBe(ChangeType::Modified)
        ->and($changes['meta']->oldValue)->toBe(['color' => 'red', 'size' => 'S'])
        ->and($changes['meta']->newValue)->toBe(['color' => 'blue', 'size' => 'S']);
});

it('returns empty array for two empty arrays', function (): void {
    $changes = DiffComputer::compute([], []);

    expect($changes)->toBeEmpty();
});

it('hides fields listed in hiddenFields', function (): void {
    $old = ['name' => 'John', 'password' => 'secret123'];
    $new = ['name' => 'John Doe', 'password' => 'newsecret'];

    $changes = DiffComputer::compute($old, $new, hiddenFields: ['password']);

    expect($changes)->not->toHaveKey('password')
        ->and($changes)->toHaveKey('name');
});

it('masks fields listed in maskedFields', function (): void {
    $old = ['token' => 'old-secret', 'name' => 'John'];
    $new = ['token' => 'new-secret', 'name' => 'John'];

    $changes = DiffComputer::compute($old, $new, maskedFields: ['token']);

    expect($changes)->toHaveKey('token')
        ->and($changes['token']->oldValue)->toBe('********')
        ->and($changes['token']->newValue)->toBe('********')
        ->and($changes['token']->masked)->toBeTrue();
});

it('gives hiddenFields priority over maskedFields', function (): void {
    $old = ['password' => 'old-secret'];
    $new = ['password' => 'new-secret'];

    $changes = DiffComputer::compute(
        $old,
        $new,
        hiddenFields: ['password'],
        maskedFields: ['password'],
    );

    expect($changes)->not->toHaveKey('password');
});

it('isChanged returns true only for non-unchanged types', function (): void {
    $old = ['a' => 1, 'b' => 2];
    $new = ['a' => 1, 'b' => 3];

    $changes = DiffComputer::compute($old, $new);

    expect($changes['a']->isChanged())->toBeFalse()
        ->and($changes['b']->isChanged())->toBeTrue();
});

it('detects all keys from both sides', function (): void {
    $old = ['a' => 1, 'b' => 2];
    $new = ['b' => 2, 'c' => 3];

    $changes = DiffComputer::compute($old, $new);

    expect($changes)->toHaveKeys(['a', 'b', 'c']);
});

it('computes diff from activity properties with old and attributes', function (): void {
    config()->set('moontrail.ui.hidden_fields', []);

    $properties = collect([
        'attributes' => ['name' => 'New Name', 'status' => 'active'],
        'old'        => ['name' => 'Old Name', 'status' => 'active'],
    ]);

    $changes = DiffComputer::fromActivityProperties($properties);

    expect($changes['name']->type)->toBe(ChangeType::Modified)
        ->and($changes['status']->type)->toBe(ChangeType::Unchanged);
});

it('handles non-array properties gracefully', function (): void {
    $changes = DiffComputer::fromActivityProperties(null);

    expect($changes)->toBeEmpty();
});

it('uses strict comparison: 0 vs empty string are different', function (): void {
    $changes = DiffComputer::compute(['val' => 0], ['val' => '']);

    expect($changes['val']->type)->toBe(ChangeType::Modified);
});

it('uses strict comparison: null vs empty string are different', function (): void {
    $changes = DiffComputer::compute(['val' => null], ['val' => '']);

    expect($changes['val']->type)->toBe(ChangeType::Modified);
});

it('uses strict comparison: string "1" vs boolean true are different', function (): void {
    $changes = DiffComputer::compute(['val' => '1'], ['val' => true]);

    expect($changes['val']->type)->toBe(ChangeType::Modified);
});

it('uses strict comparison: integer 0 vs null are different', function (): void {
    $changes = DiffComputer::compute(['val' => 0], ['val' => null]);

    expect($changes['val']->type)->toBe(ChangeType::Modified);
});

it('uses strict comparison: integer 1 vs string "1" are different', function (): void {
    $changes = DiffComputer::compute(['val' => 1], ['val' => '1']);

    expect($changes['val']->type)->toBe(ChangeType::Modified);
});

it('uses strict comparison: false vs null are different', function (): void {
    $changes = DiffComputer::compute(['val' => false], ['val' => null]);

    expect($changes['val']->type)->toBe(ChangeType::Modified);
});
