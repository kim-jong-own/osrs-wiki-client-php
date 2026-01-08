<?php

declare(strict_types=1);

namespace KimJongOwn\OsrsWiki\Helpers\Traits;

trait CalculatesTickTime
{
    protected function getTickTimeInSeconds(int $ticks): float
    {
        return (float) $ticks * 0.6;
    }

    protected function getTickTimeInMinutes(int $ticks): float
    {
        return $this->getTickTimeInSeconds($ticks) / 60;
    }

    protected function getTickTimeInHours(int $ticks): float
    {
        return $this->getTickTimeInMinutes($ticks) / 60;
    }
}
