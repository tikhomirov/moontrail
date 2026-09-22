<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

it('sanitizes sensitive fields and hidden model attributes from version snapshots', function (): void {
    config()->set('moontrail.ui.hidden_fields', ['email']);
    config()->set('moontrail.sensitive_hide', ['email']);

    $post = new class extends TestPost
    {
        protected $hidden = ['password'];
    };

    $post->name = 'Secure Post';
    $post->body = 'Body';
    $post->email = 'super-secret@example.com';
    $post->password = 'secret-password-hash';
    $post->save();

    $version = $post->versions()->latest('version')->first();

    expect($version)->not->toBeNull()
        ->and($version->snapshot)->toHaveKey('name')
        ->and($version->snapshot)->not->toHaveKey('email')
        ->and($version->snapshot)->not->toHaveKey('password');
});
