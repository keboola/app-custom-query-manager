<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Tests\Generator\Synapse\ImportIncremental;

use Keboola\CustomQueryManagerApp\Generator\Synapse\ImportIncremental\FromAbsGenerator;
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
            "CREATE TABLE {{ id(stageSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp') }} ([column1] NVARCHAR(4000), [column2] NVARCHAR(4000)) WITH (DISTRIBUTION = ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX)",
            "BEGIN TRANSACTION",
            "UPDATE {{ id(destSchemaName) }}.{{ id(destTableName) }} SET [column2] = COALESCE([src].[column2], '') FROM {{ id(stageSchemaName) }}.{{ id(stageTableName) }} AS [src] WHERE {{ id(destSchemaName) }}.{{ id(destTableName) }}.[column1] = [src].[column1] AND (COALESCE(CAST({{ id(destSchemaName) }}.{{ id(destTableName) }}.[column2] AS NVARCHAR), '') != COALESCE([src].[column2], '')) ",
            "DELETE {{ id(stageSchemaName) }}.{{ id(stageTableName) }} WHERE EXISTS (SELECT * FROM {{ id(destSchemaName) }}.{{ id(destTableName) }} WHERE {{ id(destSchemaName) }}.{{ id(destTableName) }}.[column1] = {{ id(stageSchemaName) }}.{{ id(stageTableName) }}.[column1])",
            "INSERT INTO {{ id(stageSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp') }} ([column1], [column2]) SELECT a.[column1],a.[column2] FROM (SELECT [column1], [column2], ROW_NUMBER() OVER (PARTITION BY [column1] ORDER BY [column1]) AS \"_row_number_\" FROM {{ id(stageSchemaName) }}.{{ id(stageTableName) }}) AS a WHERE a.\"_row_number_\" = 1",
            "INSERT INTO {{ id(destSchemaName) }}.{{ id(destTableName) }} ([column1], [column2]) (SELECT CAST(COALESCE([column1], '') as NVARCHAR) AS [column1],CAST(COALESCE([column2], '') as NVARCHAR) AS [column2] FROM {{ id(stageSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp') }} AS [src])",
            "COMMIT",
        ];
        /** @codingStandardsIgnoreEnd */

        self::assertSame($expected, $queries);
    }
}
