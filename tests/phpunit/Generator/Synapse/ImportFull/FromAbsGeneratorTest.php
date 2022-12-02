<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Tests\Generator\Synapse\ImportFull;

use Keboola\CustomQueryManagerApp\Generator\Synapse\ImportFull\FromAbsGenerator;
use PHPUnit\Framework\TestCase;

class FromAbsGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $generator = new FromAbsGenerator();

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
            "COPY INTO {{ id(stageSchemaName) }}.{{ id(stageTableName) }}
FROM {{ listFiles(sourceFiles) }}
WITH (
    FILE_TYPE='CSV',
    CREDENTIAL=(IDENTITY='Managed Identity'),
    FIELDQUOTE='\"',
    FIELDTERMINATOR=',',
    ENCODING = 'UTF8',
    
    IDENTITY_INSERT = 'OFF'
    ,FIRSTROW=2
)",
        "CREATE TABLE {{ id(schemaName) }}.{{ id(tableName ~ rand ~ '_tmp') }} WITH (DISTRIBUTION=ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX) AS SELECT a.[column1],a.[column2],a.[_timestamp] FROM (SELECT COALESCE([column1], '') AS [column1],COALESCE([column2], '') AS [column2],CAST({{ timestamp }} as DATETIME2) AS [_timestamp], ROW_NUMBER() OVER (PARTITION BY [column1] ORDER BY [column1]) AS \"_row_number_\" FROM {{ id(stageSchemaName) }}.{{ id(stageTableName) }}) AS a WHERE a.\"_row_number_\" = 1",
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
