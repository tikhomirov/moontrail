<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use MoonShine\MoonTrail\Traits\HasMoonTrail;

class TestPost extends Model
{
    use HasMoonTrail;
    use SoftDeletes;

    protected $table = 'test_posts';

    protected $fillable = [
        'name',
        'body',
        'email',
        'password',
    ];

    protected $casts = [
        'id' => 'integer',
    ];
}
