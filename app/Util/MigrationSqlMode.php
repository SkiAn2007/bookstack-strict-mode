<?php

namespace BookStack\Util;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Support\Facades\DB;

/**
 * Runs database migrations using a non-strict MySQL sql_mode, even when strict mode
 * has been enabled for the application via the DB_STRICT option.
 * Our historic migrations, and the data they transform on long-running instances,
 * were written and tested for non-strict mode, so this keeps upgrades working as before.
 */
class MigrationSqlMode
{
    protected ?string $previousMode = null;
    protected ?string $configKey = null;
    protected mixed $previousStrictConfig = null;

    public function onMigrationsStarted(MigrationsStarted $event): void
    {
        $connection = DB::connection();
        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        // Also disable strict in the connection config so that any reconnection
        // made during the migrations will remain non-strict.
        $this->configKey = 'database.connections.' . $connection->getName() . '.strict';
        $this->previousStrictConfig = config($this->configKey);
        config([$this->configKey => false]);

        $this->previousMode = $connection->selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        $connection->statement("SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
    }

    public function onMigrationsEnded(MigrationsEnded $event): void
    {
        if (is_null($this->previousMode)) {
            return;
        }

        $connection = DB::connection();
        $connection->statement('SET SESSION sql_mode=' . $connection->getPdo()->quote($this->previousMode));
        $this->previousMode = null;

        config([$this->configKey => $this->previousStrictConfig]);
        $this->configKey = null;
        $this->previousStrictConfig = null;
    }
}
