<?php

declare(strict_types=1);

namespace MoonShine\MoonTrail\Contracts;

interface ActivityFilterOptionsContract
{
    /**
     * @return list<string>
     */
    public function logNames(): array;

    /**
     * @return list<string>
     */
    public function events(): array;

    /**
     * @return list<string>
     */
    public function subjectTypes(): array;

    /**
     * @return list<string>
     */
    public function causerTypes(): array;
}
