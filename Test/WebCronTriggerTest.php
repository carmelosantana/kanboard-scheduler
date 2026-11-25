<?php

require_once 'tests/units/Base.php';

use Kanboard\Plugin\SchedulerPlugin\Trigger\WebCronTrigger;
use Kanboard\Plugin\SchedulerPlugin\Model\SchedulerConfigModel;
use KanboardTests\units\Base;

class WebCronTriggerTest extends Base
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->container['schedulerConfigModel'] = function ($c) {
            return new \Kanboard\Plugin\SchedulerPlugin\Model\SchedulerConfigModel($c);
        };
        $this->container['schedulerRunner'] = function ($c) {
            return new \Kanboard\Plugin\SchedulerPlugin\Model\SchedulerRunner($c);
        };
    }

    public function testDoesNotFireWhenMasterOff()
    {
        $trigger = new WebCronTrigger($this->container);
        $this->assertFalse($trigger->maybeRun());
    }

    public function testDoesNotFireWhenAlreadyRanToday()
    {
        $cfg = new SchedulerConfigModel($this->container);
        $this->container['configModel']->save([
            SchedulerConfigModel::MASTER => '1',
            SchedulerConfigModel::TARGET_HOUR => '0', // any hour qualifies
        ]);
        $cfg->setLastRun(date('Y-m-d')); // already ran today
        $trigger = new WebCronTrigger($this->container);
        $this->assertFalse($trigger->maybeRun());
    }

    public function testFiresAndStampsLastRunWhenDue()
    {
        $cfg = new SchedulerConfigModel($this->container);
        $this->container['configModel']->save([
            SchedulerConfigModel::MASTER => '1',
            SchedulerConfigModel::TARGET_HOUR => '0',
        ]);
        $cfg->setLastRun(date('Y-m-d', time() - 86400)); // last ran yesterday

        $trigger = new WebCronTrigger($this->container);
        $this->assertTrue($trigger->maybeRun());
        $this->assertSame(date('Y-m-d'), $cfg->getLastRun()); // stamped today

        // Second call same day is a no-op.
        $this->assertFalse($trigger->maybeRun());
    }

    public function testOnlyOneClaimForTheSameDayWins()
    {
        $cfg = new SchedulerConfigModel($this->container);
        $yesterday = date('Y-m-d', time() - 86400);
        $today = date('Y-m-d');
        $cfg->setLastRun($yesterday);

        // Two requests both read "yesterday", then both try to claim today.
        $this->assertTrue($cfg->claimLastRun($yesterday, $today));
        $this->assertFalse($cfg->claimLastRun($yesterday, $today));
        $this->assertSame($today, $cfg->getLastRun());
    }

    public function testFirstEverClaimWinsOnceWhenNoRowExists()
    {
        $cfg = new SchedulerConfigModel($this->container);
        $today = date('Y-m-d');

        $this->assertTrue($cfg->claimLastRun('', $today));
        $this->assertFalse($cfg->claimLastRun('', $today));
        $this->assertSame($today, $cfg->getLastRun());
    }

    public function testLosingTheClaimSkipsTheSweep()
    {
        $this->container['configModel']->save([
            SchedulerConfigModel::MASTER => '1',
            SchedulerConfigModel::TARGET_HOUR => '0',
        ]);
        $cfg = new SchedulerConfigModel($this->container);
        $cfg->setLastRun(date('Y-m-d', time() - 86400));

        $runs = 0;
        $this->container['schedulerRunner'] = function () use (&$runs) {
            return new class($runs) {
                private $runs;
                public function __construct(&$runs) { $this->runs = &$runs; }
                public function run(array $options) { $this->runs++; }
            };
        };

        // Another request claims today between this request's read and its claim.
        $this->container['schedulerConfigModel'] = function ($c) {
            return new class($c) extends SchedulerConfigModel {
                public function claimLastRun($expected, $ymd)
                {
                    parent::claimLastRun($expected, $ymd);   // the rival wins
                    return parent::claimLastRun($expected, $ymd);
                }
            };
        };

        $trigger = new WebCronTrigger($this->container);
        $this->assertFalse($trigger->maybeRun());
        $this->assertSame(0, $runs);
    }
}
