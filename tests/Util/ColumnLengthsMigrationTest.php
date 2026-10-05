<?php

namespace Tests\Util;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Events\MigrationsStarted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ColumnLengthsMigrationTest extends TestCase
{
    public function test_column_length_migration_keeps_column_collation_when_it_differs_from_table_default()
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            $this->markTestSkipped('Column collations are only relevant for MySQL/MariaDB');
        }

        $table = 'page_revisions';
        $columnCollation = $this->getColumnCollation($table, 'name');
        $tableCollation = DB::selectOne(
            'SELECT TABLE_COLLATION AS collation FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table]
        )->collation;

        // Table default differs from the column, as seen with some older installs.
        $differentCollation = str_starts_with($columnCollation, 'utf8mb4') ? 'utf8mb3_unicode_ci' : 'utf8mb4_unicode_ci';
        $differentCharset = explode('_', $differentCollation)[0];
        $migration = require database_path('migrations/2026_10_05_101500_increase_column_lengths_to_match_validation.php');

        try {
            DB::statement("ALTER TABLE `{$table}` DEFAULT CHARACTER SET {$differentCharset} COLLATE {$differentCollation}");
            // Run as the migrator does (non-strict), starting from the pre-migration (down) state.
            event(new MigrationsStarted('down'));
            $migration->down();
            $widthBeforeUp = $this->getColumnType($table, 'name');
            $migration->up();
            $widthAfterUp = $this->getColumnType($table, 'name');
            $collationAfterUp = $this->getColumnCollation($table, 'name');
            $migration->down();
            $widthAfterDown = $this->getColumnType($table, 'name');
            $collationAfterDown = $this->getColumnCollation($table, 'name');
            $migration->up();
        } finally {
            event(new MigrationsEnded('down'));
            // DDL is not rolled back by the test transaction, so restore the schema as found.
            $originalCharset = explode('_', $columnCollation)[0];
            DB::statement("ALTER TABLE `{$table}` MODIFY `name` varchar(255) CHARACTER SET {$originalCharset} COLLATE {$columnCollation} NOT NULL");
            $tableCharset = explode('_', $tableCollation)[0];
            DB::statement("ALTER TABLE `{$table}` DEFAULT CHARACTER SET {$tableCharset} COLLATE {$tableCollation}");
        }

        $this->assertEquals('varchar(255)', $widthAfterUp);
        $this->assertNotEquals($widthBeforeUp, $widthAfterUp);
        $this->assertEquals($widthBeforeUp, $widthAfterDown);
        $this->assertEquals($columnCollation, $collationAfterUp);
        $this->assertEquals($columnCollation, $collationAfterDown);
    }

    protected function getColumnType(string $table, string $column): ?string
    {
        return collect(Schema::getColumns($table))->firstWhere('name', $column)['type'] ?? null;
    }

    protected function getColumnCollation(string $table, string $column): ?string
    {
        return collect(Schema::getColumns($table))->firstWhere('name', $column)['collation'] ?? null;
    }
}
