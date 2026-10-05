<?php

namespace Tests\Util;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MigrationSqlModeTest extends TestCase
{
    public function test_migrations_run_in_non_strict_mode_and_restore_previous_mode()
    {
        $originalMode = $this->getSessionSqlMode();
        DB::statement("SET SESSION sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES'");
        $modeBeforeMigrations = $this->getSessionSqlMode();
        $strictKey = 'database.connections.' . DB::connection()->getName() . '.strict';
        config([$strictKey => true]);

        try {
            event(new MigrationsStarted('up'));
            $modeDuringMigrations = $this->getSessionSqlMode();
            $strictConfigDuringMigrations = config($strictKey);
            event(new MigrationsEnded('up'));
            $modeAfterMigrations = $this->getSessionSqlMode();
            $strictConfigAfterMigrations = config($strictKey);
        } finally {
            DB::statement('SET SESSION sql_mode=' . DB::getPdo()->quote($originalMode));
        }

        $this->assertEquals('NO_ENGINE_SUBSTITUTION', $modeDuringMigrations);
        $this->assertEquals($modeBeforeMigrations, $modeAfterMigrations);
        $this->assertFalse($strictConfigDuringMigrations);
        $this->assertTrue($strictConfigAfterMigrations);
    }

    protected function getSessionSqlMode(): string
    {
        return DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
    }
}
