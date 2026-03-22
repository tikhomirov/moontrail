<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Traits\HasMoonTrailVersioning;

/**
 * Model with empty $fillable — simulates guarded model for rollback testing.
 */
final class GuardedPost extends Model
{
    use HasMoonTrailVersioning;

    protected $table = 'test_posts';

    protected $guarded = ['*'];

    protected $casts = [
        'id' => 'integer',
    ];
}
