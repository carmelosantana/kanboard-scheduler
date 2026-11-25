<?php

require_once 'tests/units/Base.php';

use Kanboard\Plugin\SchedulerPlugin\Plugin;
use Kanboard\Plugin\SchedulerPlugin\Model\SchedulerConfigModel;
use KanboardTests\units\Base;

class WebCronHookTestLogger extends \Kanboard\Core\Log\Base
{
    public array $records = [];

    public function log($level, $message, array $context = array()): void
    {
        $this->records[] = [$level, (string) $message];
    }
}

class WebCronHookTest extends Base
{
    private function hookCallable(): callable
    {
        foreach ($this->container['hook']->getListeners('template:layout:top') as $listener) {
            if (is_array($listener) && ($listener['template'] ?? '') === 'SchedulerPlugin:layout/webcron') {
                return $listener['callable'];
            }
        }
        $this->fail('SchedulerPlugin web-cron hook is not registered on template:layout:top');
    }

    public function testThrowingSweepDoesNotBreakThePageAndIsLogged()
    {
        (new Plugin($this->container))->initialize();
        $this->container['schedulerRunner'] = function () {
            return new class {
                public function run(array $options)
                {
                    throw new \RuntimeException('sweep exploded');
                }
            };
        };
        $this->container['configModel']->save([
            SchedulerConfigModel::MASTER => '1',
            SchedulerConfigModel::TARGET_HOUR => '0',
        ]);
        $logger = new WebCronHookTestLogger();
        $logger->setLevel(\Psr\Log\LogLevel::DEBUG);
        $this->container['logger']->setLogger($logger);

        $this->assertSame([], ($this->hookCallable())());

        $errors = array_filter($logger->records, fn ($r) => $r[0] === \Psr\Log\LogLevel::ERROR);
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('sweep exploded', array_values($errors)[0][1]);
    }
}
