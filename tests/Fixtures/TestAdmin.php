<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

final class TestAdmin extends Authenticatable
{
    protected $table = 'test_admins';

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $casts = [
        'id' => 'integer',
    ];
}
