<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Generator\Snowflake\ImportFull;

use Doctrine\DBAL\Connection;
use Keboola\CsvOptions\CsvOptions;
use Keboola\CustomQueryManagerApp\Generator\GeneratorInterface;
use Keboola\CustomQueryManagerApp\Generator\Replace;
use Keboola\CustomQueryManagerApp\Generator\ReplaceToken;
use Keboola\CustomQueryManagerApp\Generator\Utils;
use Keboola\Datatype\Definition\BaseType;
use Keboola\Datatype\Definition\Snowflake;
use Keboola\Db\ImportExport\Backend\Snowflake\SnowflakeImportOptions;
use Keboola\Db\ImportExport\Backend\Snowflake\ToFinalTable\FullImporter;
use Keboola\Db\ImportExport\Backend\Snowflake\ToStage\ToStageImporter;
use Keboola\Db\ImportExport\Storage;
use Keboola\TableBackendUtils\Column\ColumnCollection;
use Keboola\TableBackendUtils\Column\ColumnInterface;
use Keboola\TableBackendUtils\Column\Snowflake\SnowflakeColumn;
use Keboola\TableBackendUtils\Escaping\Snowflake\SnowflakeQuote;
use Keboola\TableBackendUtils\Table\Snowflake\SnowflakeTableDefinition;
use Keboola\TableBackendUtils\Table\Snowflake\SnowflakeTableQueryBuilder;
use PHPUnit\Framework\TestCase;

class FromAbsGenerator extends TestCase implements GeneratorInterface
{
    /**
     * @param string[] $columns
     * @param string[] $primaryKeys
     * @return string[]
     */
    public function generate(array $columns, array $primaryKeys = []): array
    {
        $sourceColumns = $columns;

        $stageColumns = [];
        foreach ($columns as $columnName) {
            $stageColumns[] = new SnowflakeColumn(
                $columnName,
                new Snowflake(Snowflake::getTypeByBasetype(BaseType::STRING))
            );
        }
        $stagePrimaryKeys = $primaryKeys;

        $destColumns = $stageColumns;
        $destColumns[] = new SnowflakeColumn(
            ColumnInterface::TIMESTAMP_COLUMN_NAME,
            new Snowflake(Snowflake::getTypeByBasetype(BaseType::TIMESTAMP))
        );
        $destPrimaryKeys = $primaryKeys;

        $params = [
            'sourceFiles' => [
                new ReplaceToken(
                    Utils::getUniqeId('sourceFile1'),
                    'sourceFile1',
                    Replace::TYPE_MATCH_AS_VALUE,
                ),
            ],
            'sourceContainerUrl' => new ReplaceToken(
                Utils::getUniqeId('sourceContainerUrl'),
                'sourceContainerUrl',
                Replace::TYPE_MATCH_AS_VALUE,
            ),
            'sourceSasToken' => new ReplaceToken(
                Utils::getUniqeId('sourceSasToken'),
                 'sourceSasToken',
                Replace::TYPE_MATCH_AS_VALUE,
            ),

            'stageSchemaName' => new ReplaceToken(
                Utils::getUniqeId('stageSchemaName'),
                'stageSchemaName',
            ),
            'stageTableName' => new ReplaceToken(
                Utils::getUniqeId('__temp_stageTableName'),
                'stageTableName',
            ),
            // dedup table (prefix)
            'stageDeduplicationTableName' => new ReplaceToken(
                '__temp_DEDUP_',
                'stageDeduplicationTableName',
                Replace::TYPE_PREFIX_AS_IDENTIFIER,
            ),

            'destSchemaName' => new ReplaceToken(
                Utils::getUniqeId('destSchemaName'),
                'destSchemaName',
            ),
            'destTableName' => new ReplaceToken(
                Utils::getUniqeId('destTableName'),
                'destTableName',
            ),
        ];

        $queries = [];

        // mock connection
        $conn = $this->createMock(Connection::class);
        $conn->expects(self::atLeastOnce())->method('executeStatement')->willReturnCallback(
            static function (...$values) use (&$queries) {
                $queries[] = $values[0];
                return 0;
            }
        );

        // mock file source
        $source = $this->createMock(Storage\ABS\SourceFile::class);
        $source->expects(self::atLeastOnce())->method('getCsvOptions')->willReturn(new CsvOptions());
        $source->expects(self::atLeastOnce())->method('getManifestEntries')->willReturn(array_map(
            static fn(ReplaceToken $value) => $value->getValue(),
            $params['sourceFiles']
        ));
        $source->expects(self::atLeastOnce())->method('getColumnsNames')->willReturn($sourceColumns);
        // ABS specific
        $source->expects(self::atLeastOnce())->method('getContainerUrl')->willReturn($params['sourceContainerUrl']->getValue());
        $source->expects(self::atLeastOnce())->method('getSasToken')->willReturn($params['sourceSasToken']->getValue());

        // fake staging table
        $stagingTable = new SnowflakeTableDefinition(
            $params['stageSchemaName']->getValue(),
            $params['stageTableName']->getValue(),
            true,
            new ColumnCollection($stageColumns),
            $stagePrimaryKeys
        );
        // fake options
        $options = new SnowflakeImportOptions(
            [],
            false,
            false,
            1
        );
        // fake destination
        $destination = new SnowflakeTableDefinition(
            $params['destSchemaName']->getValue(),
            $params['destTableName']->getValue(),
            false,
            new ColumnCollection($destColumns),
            $destPrimaryKeys
        );

        // mock importer
        $importer = new ToStageImporter($conn);

        // init query builder
        $qb = new SnowflakeTableQueryBuilder();

        // ACTION: create stage table
        $conn->executeStatement(
            $qb->getCreateTableCommandFromDefinition($stagingTable)
        );

        // ACTION: import to stage table
        $importState = $importer->importToStagingTable(
            $source,
            $stagingTable,
            $options
        );

        // ACTION: import to final table
        $toFinalTableImporter = new FullImporter($conn);
        $result = $toFinalTableImporter->importToTable(
            $stagingTable,
            $destination,
            $options,
            $importState
        );

        // result
        $replacedQueries = [];
        foreach ($queries as $query) {
            $replacedQuery = Replace::replaceParamsInQuery($query, $params, new SnowflakeQuote());
            $replacedQueries[] = $replacedQuery;
        }

        return $replacedQueries;
    }
}
