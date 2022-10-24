<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Tests\Generator\Synapse\ImportFull;

use Keboola\CustomQueryManagerApp\Generator\Synapse\ImportFull\FromTableGenerator;
use PHPUnit\Framework\TestCase;

class FromTableGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $generator = new FromTableGenerator();

        $columns = [
            'column1',
            'column2',
        ];
        $primaryKeys = [
            'column1',
        ];
        $queries = $generator->generate($columns, $primaryKeys);

        self::assertCount(8, $queries);

        self::assertStringStartsWith(
            'CREATE TABLE {{ id(stageSchemaName) }}.{{ id(stageTableName) }} ' .
            '([column1] NVARCHAR(4000), [column2] NVARCHAR(4000)) ' .
            'WITH (DISTRIBUTION = ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX)',
            $queries[0]
        );
        self::assertStringStartsWith(
            'INSERT INTO {{ id(stageSchemaName) }}.{{ id(stageTableName) }} ([column1], [column2]) ' .
            'SELECT',
            $queries[1]
        );
        self::assertStringStartsWith(
            "CREATE TABLE {{ id(destSchemaName) }}.{{ id(stageTableName ~ '_tmp') }} " .
            'WITH (DISTRIBUTION=ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX) AS SELECT',
            $queries[2]
        );
        self::assertStringStartsWith(
            'RENAME OBJECT {{ id(destSchemaName) }}.{{ id(destTableName) }} TO',
            $queries[3]
        );
        self::assertStringStartsWith(
            "RENAME OBJECT {{ id(destSchemaName) }}.{{ id(stageTableName ~ '_tmp') }} TO",
            $queries[4]
        );
        self::assertStringStartsWith(
            "DROP TABLE {{ id(destSchemaName) }}.{{ id(stageTableName ~ '_tmp_rename') }}",
            $queries[5]
        );
        self::assertStringStartsWith(
            "IF OBJECT_ID (N'{{ id(destSchemaName) }}.{{ id(stageTableName ~ '_tmp') }}', N'U') " .
            "IS NOT NULL DROP TABLE {{ id(destSchemaName) }}.{{ id(stageTableName ~ '_tmp') }}",
            $queries[6]
        );
        self::assertStringStartsWith(
            "IF OBJECT_ID (N'{{ id(destSchemaName) }}.{{ id(stageTableName ~ '_tmp_rename') }}', N'U') IS NOT NULL " .
            "DROP TABLE {{ id(destSchemaName) }}.{{ id(stageTableName ~ '_tmp_rename') }}",
            $queries[7]
        );
    }
}
