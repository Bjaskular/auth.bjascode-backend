<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class _ClearDBTest extends TestCase
{
    #[Test]
    public function clear(): void
    {
        $dbTestName = 'db_test';

        DB::statement("SET foreign_key_checks=0");

        $schema = DB::select("SELECT table_name as `name` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '{$dbTestName}'");
        collect($schema)
            ->reject(fn (\stdClass $table) => in_array($table->name, ['migrations']))
            ->each(fn (\stdClass $table) => DB::table($table->name)->truncate());

        DB::statement("SET foreign_key_checks=1");

        $this->assertTrue(true);
    }
}
