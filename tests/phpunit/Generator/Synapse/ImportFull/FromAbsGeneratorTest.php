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
            "CREATE TABLE {{ id(destSchemaName) }}.{{ id(stageTableName) }} ([column1] NVARCHAR(4000), [column2] NVARCHAR(4000)) WITH (DISTRIBUTION = ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX)",
            "COPY INTO {{ id(destSchemaName) }}.{{ id(stageTableName) }}
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
