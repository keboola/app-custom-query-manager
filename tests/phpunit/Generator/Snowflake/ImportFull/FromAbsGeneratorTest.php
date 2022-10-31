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
            "CREATE TEMPORARY TABLE {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp') }}
(
\"column1\" VARCHAR,
\"column2\" VARCHAR
);",
            "COPY INTO {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp') }} 
FROM {{ q(sourceContainerUrl) }}
CREDENTIALS=(AZURE_SAS_TOKEN={{ q(sourceSasToken) }})
FILE_FORMAT = (TYPE=CSV FIELD_DELIMITER = ',' SKIP_HEADER = 1 FIELD_OPTIONALLY_ENCLOSED_BY = '\\\"' ESCAPE_UNENCLOSED_FIELD = NONE)
FILES = ({{ listFiles(sourceFiles) }})",
            "CREATE TEMPORARY TABLE {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp_dedup') }}
(
\"column1\" VARCHAR,
\"column2\" VARCHAR
);",
            "INSERT INTO {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp_dedup') }} (\"column1\", \"column2\") SELECT a.\"column1\",a.\"column2\" FROM (SELECT \"column1\", \"column2\", ROW_NUMBER() OVER (PARTITION BY \"column1\" ORDER BY \"column1\") AS \"_row_number_\" FROM {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp') }}) AS a WHERE a.\"_row_number_\" = 1",
            "BEGIN TRANSACTION",
            "TRUNCATE TABLE {{ id(destSchemaName) }}.{{ id(destTableName) }}",
            "INSERT INTO {{ id(destSchemaName) }}.{{ id(destTableName) }} (\"column1\", \"column2\") (SELECT COALESCE(\"column1\", '') AS \"column1\",COALESCE(\"column2\", '') AS \"column2\" FROM {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp_dedup') }} AS \"src\")",
            "DROP TABLE IF EXISTS {{ id(destSchemaName) }}.{{ id(destTableName ~ rand ~ '_tmp_dedup') }}",
            "COMMIT",
        ];
        /** @codingStandardsIgnoreEnd */

        self::assertSame($expected, $queries);
    }
}
