<?php

declare(strict_types=1);

use MoonShine\MoonTrail\Tests\Fixtures\TestPost;

it('sanitizes sensitive fields and hidden model attributes from version snapshots', function (): void {
    config()->set('moontrail.sensitive_hide', ['secret_token']);

    $post = new class extends TestPost
    {
        protected $hidden = ['password'];
    };

    $post->name = 'Secure Post';
    $post->body = 'Body';
    $post->setAttribute('secret_token', 'super-secret-value');
    $post->setAttribute('password', 'secret-password-hash');
    $post->save();

    $version = $post->versions()->latest('version')->first();

    expect($version)->not->toBeNull()
        ->and($version->snapshot)->toHaveKey('name')
        ->and($version->snapshot)->not->toHaveKey('secret_token')
        ->and($version->snapshot)->not->toHaveKey('password');
});
