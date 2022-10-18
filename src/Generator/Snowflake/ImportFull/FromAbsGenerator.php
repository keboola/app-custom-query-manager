<?php

namespace Keboola\CustomQueryManagerApp\Generator\Snowflake\ImportFull;

use Doctrine\DBAL\Connection;
use Keboola\CsvOptions\CsvOptions;
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

class FromAbsGenerator extends TestCase
{
    /**
     * @param string[] $columns
     * @param string[] $primaryKeys
     * @return string[]
     */
    public function generate(array $columns, array $primaryKeys = []): array
    {
        // 'foo'  = identifier
        // '#foo' = value
        // '/foo' = identifier with prefix in value - need to be found first in query

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
                '#sourceFile1' => Utils::getUniqeId('sourceFile1'),
            ],
            '#sourceContainerUrl' => Utils::getUniqeId('sourceContainerUrl'),
            '#sourceSasToken' => Utils::getUniqeId('sourceSasToken'),

            'stageSchemaName' => Utils::getUniqeId('stageSchemaName'),
            'stageTableName' => Utils::getUniqeId('__temp_stageTableName'),
            // dedup table (prefix)
            '/stageDeduplicationTableName' => '__temp_DEDUP_',

            'destSchemaName' => Utils::getUniqeId('destSchemaName'),
            'destTableName' => Utils::getUniqeId('destTableName'),
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
        $source->expects(self::atLeastOnce())->method('getManifestEntries')->willReturn($params['sourceFiles']);
        $source->expects(self::atLeastOnce())->method('getColumnsNames')->willReturn($sourceColumns);
        // ABS specific
        $source->expects(self::atLeastOnce())->method('getContainerUrl')->willReturn($params['#sourceContainerUrl']);
        $source->expects(self::atLeastOnce())->method('getSasToken')->willReturn($params['#sourceSasToken']);

        // fake staging table
        $stagingTable = new SnowflakeTableDefinition(
            $params['stageSchemaName'],
            $params['stageTableName'],
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
            $params['destSchemaName'],
            $params['destTableName'],
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

        // ACTION: create final table
        $conn->executeStatement(
            $qb->getCreateTableCommandFromDefinition($destination)
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
        dump('=== queries');
        foreach ($queries as $query) {
//            dump($query);
//            dump('---');
            $replacedQuery = Utils::replaceParamsInQuery($query, $params, new SnowflakeQuote());
            $replacedQueries[] = $replacedQuery;
            dump($replacedQuery);
            dump('---------');
        }

        return $replacedQueries;
    }
}
