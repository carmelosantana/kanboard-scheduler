<?php

namespace Kanboard\Plugin\SchedulerPlugin\Trigger;

use Kanboard\Core\Base;

/**
 * Lazy web-cron: fires the daily sweep at most once per day, on the first
 * rendered page request past the configured target hour. Claims the day with an
 * atomic conditional update of last_run BEFORE running, so of two concurrent
 * requests that both read "not run today", only one sweeps.
 */
class WebCronTrigger extends Base
{
    public function maybeRun()
    {
        $config = $this->schedulerConfigModel;

        if (! $config->isMasterEnabled()) {
            return false;
        }

        $today = date('Y-m-d');
        $lastRun = $config->getLastRun();
        if ($lastRun === $today) {
            return false;
        }

        if ((int) date('G') < $config->getTargetHour()) {
            return false;
        }

        // Claim the day first, then run. Losing the claim means another request is sweeping.
        if (! $config->claimLastRun($lastRun, $today)) {
            return false;
        }
        $this->schedulerRunner->run(array('trigger' => 'web'));

        return true;
    }
}
