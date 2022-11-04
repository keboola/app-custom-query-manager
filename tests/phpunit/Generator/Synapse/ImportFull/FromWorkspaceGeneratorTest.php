<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Tests\Generator\Synapse\ImportFull;

use Keboola\CustomQueryManagerApp\Generator\Synapse\ImportFull\FromWorkspaceGenerator;
use PHPUnit\Framework\TestCase;

class FromWorkspaceGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $generator = new FromWorkspaceGenerator();

        $columns = [
            'column1',
            'column2',
        ];
        $primaryKeys = [
            'column1',
        ];
        $queries = $generator->generate($columns, $primaryKeys);

        /** @codingStandardsIgnoreStart */
        $expected = [
            "CREATE TABLE {{ id(destSchemaName) }}.{{ id(stageTableName) }} ([column1] NVARCHAR(4000), [column2] NVARCHAR(4000)) WITH (DISTRIBUTION = ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX)",
            "INSERT INTO {{ id(destSchemaName) }}.{{ id(stageTableName) }} ([column1], [column2]) SELECT [column1], [column2] FROM {{ id(schemaName) }}.{{ id(tableName) }}",
            "CREATE TABLE {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp') }} WITH (DISTRIBUTION=ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX) AS SELECT a.[column1],a.[column2] FROM (SELECT COALESCE([column1], '') AS [column1],COALESCE([column2], '') AS [column2], ROW_NUMBER() OVER (PARTITION BY [column1] ORDER BY [column1]) AS \"_row_number_\" FROM {{ id(destSchemaName) }}.{{ id(stageTableName) }}) AS a WHERE a.\"_row_number_\" = 1",
            "RENAME OBJECT {{ id(destSchemaName) }}.{{ id(destTableName) }} TO {{ id(destTableName ~ rand ~ '_tmp_rename') }}",
            "RENAME OBJECT {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp') }} TO {{ id(destTableName) }}",
            "DROP TABLE {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp_rename') }}",
            "IF OBJECT_ID (N'{{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp') }}', N'U') IS NOT NULL DROP TABLE {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp') }}",
            "IF OBJECT_ID (N'{{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp_rename') }}', N'U') IS NOT NULL DROP TABLE {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp_rename') }}",
        ];
        /** @codingStandardsIgnoreEnd */

        self::assertSame($expected, $queries);
    }
}
