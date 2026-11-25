<?php

/**
 * Installs the plugin schema for the database the suite runs on (DB_DRIVER), the way
 * Kanboard picks Schema/<Driver>.php at install time, so real-DB runs test the real schema.
 */
function scheduler_test_schema(\PDO $pdo): void
{
    $file = ['sqlite' => 'Sqlite', 'mysql' => 'Mysql', 'postgres' => 'Postgres'][DB_DRIVER];
    require_once __DIR__.'/../Schema/'.$file.'.php';
    \Kanboard\Plugin\SchedulerPlugin\Schema\version_1($pdo);
}
