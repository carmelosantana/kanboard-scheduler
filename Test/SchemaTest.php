<?php

require_once 'tests/units/Base.php';

use KanboardTests\units\Base;

class SchemaTest extends Base
{
    public function testVersion1CreatesBothTables()
    {
        require_once __DIR__.'/schema.php';
        scheduler_test_schema($this->container['db']->getConnection());
        $db = $this->container['db'];

        // Insert + read back a row from each table to prove they exist with the expected columns.
        // Through PicoDb so the reserved column name `trigger` is quoted per driver.
        $this->assertTrue($db->table('scheduler_runs')->insert([
            'started_at' => 100, 'trigger' => 'cli', 'moved_count' => 3, 'is_dry_run' => 0,
        ]));
        $runId = (int) $db->getLastId();
        $this->assertGreaterThan(0, $runId);

        $this->assertTrue($db->table('scheduler_moves')->insert([
            'run_id' => $runId, 'project_id' => 1, 'task_id' => 2, 'old_date' => 10, 'new_date' => 20, 'reason' => 'roll-forward',
        ]));
        $this->assertSame(1, $db->table('scheduler_moves')->eq('run_id', $runId)->count());
    }

    public function testVersionConstantIsOne()
    {
        require_once __DIR__.'/schema.php';
        scheduler_test_schema($this->container['db']->getConnection());
        $this->assertSame(1, \Kanboard\Plugin\SchedulerPlugin\Schema\VERSION);
    }
}
