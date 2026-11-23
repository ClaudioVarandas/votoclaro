<?php

namespace Tests\Concerns;

use Illuminate\Foundation\Testing\DatabaseTruncation;

/**
 * Runs a test class on the mysql_testing connection instead of SQLite, for behaviour only MySQL
 * has. Use it in classes of the "mysql" group; they are skipped when no such database is set.
 *
 * It truncates the tables instead of rolling back a transaction, because InnoDB only adds rows to
 * a full-text index when they are committed: inside a transaction a search finds nothing.
 */
trait RefreshesMysqlTestDatabase
{
    use DatabaseTruncation;

    protected function beforeTruncatingDatabase(): void
    {
        if (! config('database.connections.mysql_testing.database')) {
            $this->markTestSkipped('Set DB_TEST_MYSQL_DATABASE (and host, port, user, password) to run the MySQL tests.');
        }

        config(['database.default' => 'mysql_testing']);
    }
}
