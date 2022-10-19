<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Tests\Generator;

use Keboola\CustomQueryManagerApp\Generator\Utils;
use Keboola\TableBackendUtils\Escaping\QuoteInterface;
use Keboola\TableBackendUtils\Escaping\Snowflake\SnowflakeQuote;
use Keboola\TableBackendUtils\Escaping\SynapseQuote;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{

    public function testReplaceParamsInQuery(): void
    {
        $input = /** @lang Snowflake */ <<<SQL
            COPY INTO "stageSchemaName6336e8dda7606"."stageTableName6336e8dda7607"
            FROM 'sourceContainerUrl6336ebdee0b80'
            CREDENTIALS=(AZURE_SAS_TOKEN='sourceSasToken6336ebdee0b81')
            FILE_FORMAT = (TYPE=CSV FIELD_DELIMITER = ','
                SKIP_HEADER = 1
                FIELD_OPTIONALLY_ENCLOSED_BY = '\"'
                ESCAPE_UNENCLOSED_FIELD = NONE)
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
            FILE_FORMAT = (TYPE=CSV FIELD_DELIMITER = ','
                SKIP_HEADER = 1
                FIELD_OPTIONALLY_ENCLOSED_BY = '\"'
                ESCAPE_UNENCLOSED_FIELD = NONE)
            FILES = ({{ sourceFile1 }})
        SQL;
        $this->assertSame($expected, $output);
    }

    /**
     * @dataProvider replaceParamInQueryProvider
     */
    public function testReplaceParamInQuery(
        string $input,
        string $key,
        string $value,
        QuoteInterface $quoter,
        string $prefix,
        string $suffix,
        string $expectedOutput
    ): void {
        $output = Utils::replaceParamInQuery(
            $input,
            $value,
            $key,
            $quoter,
            $prefix,
            $suffix,
        );
        $this->assertSame($expectedOutput, $output);
    }

    public function replaceParamInQueryProvider(): \Generator
    {
        $defaultQuery = <<<SQL
            COPY INTO "stageSchemaName6336e8dda7606"."stageTableName6336e8dda7607"
            FROM 'sourceContainerUrl6336ebdee0b80'
        SQL;

        $dedupQuerySnowflake = <<<SQL
            COPY INTO "stageSchemaName6336e8dda7606"."__temp_DEDUP_csvimport6336e8dda7607"
            FROM 'sourceContainerUrl6336ebdee0b80'
        SQL;

        $dedupQuerySynapse = <<<SQL
            CREATE TABLE
                [destSchemaName6336e8dda7606].[destTableName634fca7a22355200942535tmp634fca7a3eb402_17122540_tmp]
            WITH (DISTRIBUTION=ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX)
        SQL;

        $dedupQueryWithRenameSynapse = <<<SQL
            CREATE TABLE
                [destSchemaName6336e8dda7606].[destTableName634fca7a22355200942535tmp634fca7a3eb402_tmp_rename]
            WITH (DISTRIBUTION=ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX)
        SQL;

        $defaultQueryWithSecretSynapse = <<<SQL
            COPY INTO [stageSchemaName634ff46baec71046847136].[__temp_stageTableName634ff46baec72821993597]
            FROM 'sourceFile1634ff46baec6c521446965'
            WITH ( CREDENTIAL=(IDENTITY='Shared Access Signature', SECRET='?sourceSasToken634ff46baec58062943542') )
        SQL;

        yield 'test id' => [
            $defaultQuery,
            'stageSchemaName',
            'stageSchemaName6336e8dda7606',
            new SnowflakeQuote(),
            '{{ ',
            ' }}',
            'output' => <<<SQL
                COPY INTO {{ id(stageSchemaName) }}."stageTableName6336e8dda7607"
                FROM 'sourceContainerUrl6336ebdee0b80'
            SQL,
        ];
        yield 'test value' => [
            $defaultQuery,
            '#sourceContainerUrl',
            'sourceContainerUrl6336ebdee0b80',
            new SnowflakeQuote(),
            '{{ ',
            ' }}',
            <<<SQL
                COPY INTO "stageSchemaName6336e8dda7606"."stageTableName6336e8dda7607"
                FROM {{ sourceContainerUrl }}
            SQL,
        ];
        yield 'test id with other prefix+suffix' => [
            $defaultQuery,
            'stageSchemaName',
            'stageSchemaName6336e8dda7606',
            new SnowflakeQuote(),
            '[',
            ']',
            <<<SQL
                COPY INTO [id(stageSchemaName)]."stageTableName6336e8dda7607"
                FROM 'sourceContainerUrl6336ebdee0b80'
            SQL,
        ];
        yield 'test value with other prefix+suffix' => [
            $defaultQuery,
            '#sourceContainerUrl',
            'sourceContainerUrl6336ebdee0b80',
            new SnowflakeQuote(),
            '[',
            ']',
            <<<SQL
                COPY INTO "stageSchemaName6336e8dda7606"."stageTableName6336e8dda7607"
                FROM [sourceContainerUrl]
            SQL,
        ];
        yield 'test generated id at the beginning' => [
            $dedupQuerySnowflake,
            '^stageDeduplicationTableName',
            '__temp_DEDUP_',
            new SnowflakeQuote(),
            '{{ ',
            ' }}',
            <<<SQL
                COPY INTO "stageSchemaName6336e8dda7606".{{ id(stageDeduplicationTableName) }}
                FROM 'sourceContainerUrl6336ebdee0b80'
            SQL,
        ];
        yield 'test generated id at the end - synapse' => [
            $dedupQuerySynapse,
            '$destDeduplicationTableName',
            '_tmp',
            new SynapseQuote(),
            '{{ ',
            ' }}',
            <<<SQL
                CREATE TABLE
                    [destSchemaName6336e8dda7606].{{ id(destDeduplicationTableName) }}
                WITH (DISTRIBUTION=ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX)
            SQL,
        ];
        yield 'test generated id at the end - not found - synapse' => [
            $dedupQueryWithRenameSynapse,
            '$destDeduplicationTableName',
            '_tmp',
            new SynapseQuote(),
            '{{ ',
            ' }}',
            <<<SQL
                CREATE TABLE
                    [destSchemaName6336e8dda7606].[destTableName634fca7a22355200942535tmp634fca7a3eb402_tmp_rename]
                WITH (DISTRIBUTION=ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX)
            SQL,
        ];
        yield 'test generated id at the end with rename - synapse' => [
            $dedupQueryWithRenameSynapse,
            '$destDeduplicationTableName',
            '_tmp_rename',
            new SynapseQuote(),
            '{{ ',
            ' }}',
            <<<SQL
                CREATE TABLE
                    [destSchemaName6336e8dda7606].{{ id(destDeduplicationTableName) }}
                WITH (DISTRIBUTION=ROUND_ROBIN,CLUSTERED COLUMNSTORE INDEX)
            SQL,
        ];
        yield 'test prefixed value produces Twig syntax - synapse' => [
            $defaultQueryWithSecretSynapse,
            // '#' means value + Twig syntax
            '#\'?\' ~ sourceSasSecret',
            // prefixed by '?'
            '?sourceSasToken634ff46baec58062943542',
            new SynapseQuote(),
            '{{ ',
            ' }}',
            <<<SQL
                COPY INTO [stageSchemaName634ff46baec71046847136].[__temp_stageTableName634ff46baec72821993597]
                FROM 'sourceFile1634ff46baec6c521446965'
                WITH ( CREDENTIAL=(IDENTITY='Shared Access Signature', SECRET={{ '?' ~ sourceSasSecret }}) )
            SQL,
        ];
    }

    public function testGetUniqeId(): void
    {
        $id = Utils::getUniqeId('somePrefix');
        $this->assertStringNotContainsString('.', $id);
        // 23 (lenght of uniqid without prefix) + 10 (length of prefix) - 1 (removed dot)
        $this->assertSame(32, strlen($id));
    }
}
