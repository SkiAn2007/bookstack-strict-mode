<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Increases column lengths to match the lengths allowed by validation, so these values
     * are stored in full instead of being truncated (or rejected when using strict mode).
     * None of these columns are indexed.
     * The charset/collation of each column is kept as it is, since it may differ from the table default.
     */
    public function up(): void
    {
        $this->changeColumnLengths([
            ['entities', 'name', 255],
            ['page_revisions', 'name', 255],
            ['attachments', 'name', 255],
            ['imports', 'name', 255],
            ['sort_rules', 'name', 200],
            ['api_tokens', 'name', 250],
            ['images', 'url', 500],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->changeColumnLengths([
            ['entities', 'name', null],
            ['page_revisions', 'name', null],
            ['attachments', 'name', null],
            ['imports', 'name', null],
            ['sort_rules', 'name', null],
            ['api_tokens', 'name', null],
            ['images', 'url', null],
        ]);
    }

    /**
     * Change the length of the given [table, column, length] string columns, where a null length
     * is the default string length. Keeps each column's current collation (and so its charset)
     * where the driver reports one.
     * A change without it would reset the column to the table default, which can differ.
     */
    private function changeColumnLengths(array $columns): void
    {
        foreach ($columns as [$tableName, $columnName, $length]) {
            $collation = collect(Schema::getColumns($tableName))->firstWhere('name', $columnName)['collation'] ?? null;

            Schema::table($tableName, function (Blueprint $table) use ($columnName, $length, $collation) {
                $column = $table->string($columnName, $length);
                if ($collation) {
                    $column->collation($collation);
                }
                $column->change();
            });
        }
    }
};
