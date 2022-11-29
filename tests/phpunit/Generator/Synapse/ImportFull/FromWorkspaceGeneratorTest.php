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
            "CREATE TABLE {{ id(stageSchemaName) }}.{{ id(stageTableName) }} ([column1] NVARCHAR(4000), [column2] NVARCHAR(4000)) WITH (DISTRIBUTION = ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX)",
            "INSERT INTO {{ id(stageSchemaName) }}.{{ id(stageTableName) }} ([column1], [column2]) SELECT [column1], [column2] FROM {{ id(sourceSchemaName) }}.{{ id(sourceTableName) }}",
            "CREATE TABLE {{ id(schemaName) }}.{{ id(tableName ~ rand ~ '_tmp') }} WITH (DISTRIBUTION=ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX) AS SELECT a.[column1],a.[column2],a.[_timestamp] FROM (SELECT COALESCE([column1], '') AS [column1],COALESCE([column2], '') AS [column2],CAST({{ _timestamp }} as DATETIME2) AS [_timestamp], ROW_NUMBER() OVER (PARTITION BY [column1] ORDER BY [column1]) AS \"_row_number_\" FROM {{ id(stageSchemaName) }}.{{ id(stageTableName) }}) AS a WHERE a.\"_row_number_\" = 1",
            "RENAME OBJECT {{ id(schemaName) }}.{{ id(tableName) }} TO {{ id(tableName ~ rand ~ '_tmp_rename') }}",
            "RENAME OBJECT {{ id(schemaName) }}.{{ id(tableName ~ rand ~ '_tmp') }} TO {{ id(tableName) }}",
            "DROP TABLE {{ id(schemaName) }}.{{ id(tableName ~ rand ~ '_tmp_rename') }}",
            "IF OBJECT_ID (N'{{ id(schemaName) }}.{{ id(tableName ~ rand ~ '_tmp') }}', N'U') IS NOT NULL DROP TABLE {{ id(schemaName) }}.{{ id(tableName ~ rand ~ '_tmp') }}",
            "IF OBJECT_ID (N'{{ id(schemaName) }}.{{ id(tableName ~ rand ~ '_tmp_rename') }}', N'U') IS NOT NULL DROP TABLE {{ id(schemaName) }}.{{ id(tableName ~ rand ~ '_tmp_rename') }}",
        ];
        /** @codingStandardsIgnoreEnd */

        self::assertSame($expected, $queries);
    }
}
