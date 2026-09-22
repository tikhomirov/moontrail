<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use MoonShine\MoonTrail\Traits\HasMoonTrailActivity;
use Spatie\Activitylog\Traits\LogsActivity;

final class TestPostWithActivityTrait extends Model
{
    use HasMoonTrailActivity;
    use LogsActivity;

    protected $table = 'test_posts';

    protected $fillable = [
        'name',
        'body',
    ];
}
