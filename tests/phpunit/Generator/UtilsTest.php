<?php

namespace Keboola\CustomQueryManagerApp\Tests\Generator;

use Keboola\CustomQueryManagerApp\Generator\Utils;
use Keboola\TableBackendUtils\Escaping\Snowflake\SnowflakeQuote;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{

    public function testReplaceParamsInQuery()
    {
        $input = /** @lang Snowflake */ <<<SQL
            COPY INTO "stageSchemaName6336e8dda7606"."stageTableName6336e8dda7607"
            FROM 'sourceContainerUrl6336ebdee0b80'
            CREDENTIALS=(AZURE_SAS_TOKEN='sourceSasToken6336ebdee0b81')
            FILE_FORMAT = (TYPE=CSV FIELD_DELIMITER = ',' SKIP_HEADER = 1 FIELD_OPTIONALLY_ENCLOSED_BY = '\"' ESCAPE_UNENCLOSED_FIELD = NONE)
            FILES = ('sourceFile16336ebdee0b7f')
        SQL;
        $params = [
            'stageSchemaName' => 'stageSchemaName6336e8dda7606',
            '#sourceContainerUrl' => 'sourceContainerUrl6336ebdee0b80',
            '#sourceSasToken' => 'sourceSasToken6336ebdee0b81',
            'testIdInArray' => [
                'stageTableName' => 'stageTableName6336e8dda7607',
            ],
            'testValueInArray' => [
                '#sourceFile1' => 'sourceFile16336ebdee0b7f',
            ],
        ];

        $output = Utils::replaceParamsInQuery($input, $params, new SnowflakeQuote());

        $expected = /** @lang Snowflake */ <<<SQL
            COPY INTO {{ id(stageSchemaName) }}.{{ id(stageTableName) }}
            FROM {{ sourceContainerUrl }}
            CREDENTIALS=(AZURE_SAS_TOKEN={{ sourceSasToken }})
            FILE_FORMAT = (TYPE=CSV FIELD_DELIMITER = ',' SKIP_HEADER = 1 FIELD_OPTIONALLY_ENCLOSED_BY = '\"' ESCAPE_UNENCLOSED_FIELD = NONE)
            FILES = ({{ sourceFile1 }})
        SQL;
        $this->assertSame($expected, $output);
    }

    /**
     * @dataProvider replaceParamInQueryProvider
     */
    public function testReplaceParamInQuery(string $input, string $key, string $value, ?string $prefix, ?string $suffix, string $expectedOutput): void
    {
        $output = Utils::replaceParamInQuery(
            $input,
            $value,
            $key,
            new SnowflakeQuote(),
            $prefix,
            $suffix,
        );
        $this->assertSame($expectedOutput, $output);
    }

    public function replaceParamInQueryProvider(): array
    {
        $defaultQuery = <<<SQL
            COPY INTO "stageSchemaName6336e8dda7606"."stageTableName6336e8dda7607"
            FROM 'sourceContainerUrl6336ebdee0b80'
        SQL;

        $dedupQuerySnowflake = <<<SQL
            COPY INTO "stageSchemaName6336e8dda7606"."__temp_DEDUP_csvimport6336e8dda7607"
            FROM 'sourceContainerUrl6336ebdee0b80'
        SQL;

        return [
            'test id' => [
                $defaultQuery,
                'keyInOutput' => 'stageSchemaName',
                'valueInQuery' => 'stageSchemaName6336e8dda7606',
                '{{ ',
                ' }}',
                'output' => <<<SQL
                    COPY INTO {{ id(stageSchemaName) }}."stageTableName6336e8dda7607"
                    FROM 'sourceContainerUrl6336ebdee0b80'
                SQL,
            ],
            'test value' => [
                $defaultQuery,
                '#sourceContainerUrl',
                'sourceContainerUrl6336ebdee0b80',
                '{{ ',
                ' }}',
                <<<SQL
                    COPY INTO "stageSchemaName6336e8dda7606"."stageTableName6336e8dda7607"
                    FROM {{ sourceContainerUrl }}
                SQL,
            ],
            'test id with other prefix+suffix' => [
                $defaultQuery,
                'stageSchemaName',
                'stageSchemaName6336e8dda7606',
                '[',
                ']',
                <<<SQL
                    COPY INTO [id(stageSchemaName)]."stageTableName6336e8dda7607"
                    FROM 'sourceContainerUrl6336ebdee0b80'
                SQL
            ],
            'test value with other prefix+suffix' => [
                $defaultQuery,
                '#sourceContainerUrl',
                'sourceContainerUrl6336ebdee0b80',
                '[',
                ']',
                <<<SQL
                    COPY INTO "stageSchemaName6336e8dda7606"."stageTableName6336e8dda7607"
                    FROM [sourceContainerUrl]
                SQL
            ],
            'test generated id at the beginning' => [
                $dedupQuerySnowflake,
                '^stageDeduplicationTableName',
                '__temp_DEDUP_',
                '{{ ',
                ' }}',
                <<<SQL
                    COPY INTO "stageSchemaName6336e8dda7606".{{ id(stageDeduplicationTableName) }}
                    FROM 'sourceContainerUrl6336ebdee0b80'
                SQL,
            ],
        ];
    }

    public function testGetUniqeId()
    {
        $id = Utils::getUniqeId('somePrefix');
        $this->assertStringNotContainsString('.', $id);
        // 23 (lenght of uniqid without prefix) + 10 (length of prefix) - 1 (removed dot)
        $this->assertSame(32, strlen($id));
    }
}
