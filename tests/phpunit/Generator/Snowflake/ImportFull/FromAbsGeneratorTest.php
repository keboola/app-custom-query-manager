<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Tests\Generator\Snowflake\ImportFull;

use Keboola\CustomQueryManagerApp\Generator\Snowflake\ImportFull\FromAbsGenerator;
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
            "CREATE TEMPORARY TABLE {{ id(stageSchemaName) }}.{{ id(stageTableName) }}
(
\"column1\" VARCHAR,
\"column2\" VARCHAR
);",
            "COPY INTO {{ id(stageSchemaName) }}.{{ id(stageTableName) }} 
FROM {{ q(sourceContainerUrl) }}
CREDENTIALS=(AZURE_SAS_TOKEN={{ q(sourceSasToken) }})
FILE_FORMAT = (TYPE=CSV FIELD_DELIMITER = ',' SKIP_HEADER = 1 FIELD_OPTIONALLY_ENCLOSED_BY = '\\\"' ESCAPE_UNENCLOSED_FIELD = NONE)
FILES = ({{ listFiles(sourceFiles) }})",
            "CREATE TEMPORARY TABLE {{ id(stageSchemaName) }}.{{ id(tableName ~ rand ~ '_tmp') }}
(
\"column1\" VARCHAR,
\"column2\" VARCHAR
);",
            "INSERT INTO {{ id(stageSchemaName) }}.{{ id(tableName ~ rand ~ '_tmp') }} (\"column1\", \"column2\") SELECT a.\"column1\",a.\"column2\" FROM (SELECT \"column1\", \"column2\", ROW_NUMBER() OVER (PARTITION BY \"column1\" ORDER BY \"column1\") AS \"_row_number_\" FROM {{ id(stageSchemaName) }}.{{ id(stageTableName) }}) AS a WHERE a.\"_row_number_\" = 1",
            "BEGIN TRANSACTION",
            "TRUNCATE TABLE {{ id(schemaName) }}.{{ id(tableName) }}",
            "INSERT INTO {{ id(schemaName) }}.{{ id(tableName) }} (\"column1\", \"column2\", \"_timestamp\") (SELECT COALESCE(\"column1\", '') AS \"column1\",COALESCE(\"column2\", '') AS \"column2\",{{ q(timestamp) }} FROM {{ id(stageSchemaName) }}.{{ id(tableName ~ rand ~ '_tmp') }} AS \"src\")",
            "DROP TABLE IF EXISTS {{ id(stageSchemaName) }}.{{ id(tableName ~ rand ~ '_tmp') }}",
            "COMMIT",
        ];
        /** @codingStandardsIgnoreEnd */

        self::assertSame($expected, $queries);
    }
}
