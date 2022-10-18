<?php

namespace Keboola\CustomQueryManagerApp\Generator\Snowflake\ImportFull;

use Doctrine\DBAL\Connection;
use Keboola\CsvOptions\CsvOptions;
use Keboola\CustomQueryManagerApp\Generator\Utils;
use Keboola\Datatype\Definition\Snowflake;
use Keboola\Db\ImportExport\Backend\Snowflake\SnowflakeImportOptions;
use Keboola\Db\ImportExport\Backend\Snowflake\ToFinalTable\FullImporter;
use Keboola\Db\ImportExport\Backend\Snowflake\ToStage\ToStageImporter;
use Keboola\Db\ImportExport\Storage;
use Keboola\TableBackendUtils\Column\ColumnCollection;
use Keboola\TableBackendUtils\Column\ColumnInterface;
use Keboola\TableBackendUtils\Column\Snowflake\SnowflakeColumn;
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
        // TODO use given columns and primaryKeys

        // 'foo'  = identifier
        // '#foo' = value
        // '/foo' = identifier with prefix in value - need to be found first in query
        $sourceCol1Name = Utils::getUniqeId('sourceColumn1');
        $sourceCol2Name = Utils::getUniqeId('sourceColumn2');
        $destCol1Name = Utils::getUniqeId('destColumn1');
        $destCol2Name = Utils::getUniqeId('destColumn2');
        $params = [
            'sourceFiles' => [
                '#sourceFile1' => Utils::getUniqeId('sourceFile1'),
            ],
            '#sourceContainerUrl' => Utils::getUniqeId('sourceContainerUrl'),
            '#sourceSasToken' => Utils::getUniqeId('sourceSasToken'),

            'stageSchemaName' => Utils::getUniqeId('stageSchemaName'),
            'stageTableName' => Utils::getUniqeId('__temp_stageTableName'),
            'stageColumns' => [
                'sourceColumn1' => new SnowflakeColumn(
                    $sourceCol1Name,
                    new Snowflake(Snowflake::TYPE_INT)
                ),
                'sourceColumn2' => new SnowflakeColumn(
                    $sourceCol2Name,
                    new Snowflake(Snowflake::TYPE_VARCHAR)
                ),
            ],
            'stagePrimaryKeys' => [],
//            'stagePrimaryKeys' => [
//                'sourceCol1' => $sourceCol1Name,
//            ],
            // dedup table (prefix)
            '/stageDeduplicationTableName' => '__temp_DEDUP_',

            'destSchemaName' => Utils::getUniqeId('destSchemaName'),
            'destTableName' => Utils::getUniqeId('destTableName'),
            'destColumns' => [
                'destCol1' => new SnowflakeColumn(
                    $destCol1Name,
                    new Snowflake(Snowflake::TYPE_INT)
                ),
                'destCol2' => new SnowflakeColumn(
                    $destCol2Name,
                    new Snowflake(Snowflake::TYPE_VARCHAR)
                ),
                'destColTimestamp' => new SnowflakeColumn(
                    Utils::getUniqeId('_timestamp'),
                    new Snowflake(Snowflake::TYPE_TIMESTAMP)
                ),
            ],
            'destPrimaryKeys' => [],
//            'destPrimaryKeys' => [
//                'destPrimaryKey1' => $destCol1Name,
//            ],
        ];

        $sourceColumnsNames = [];
        /** @var ColumnInterface $column */
        foreach ($params['stageColumns'] as $column) {
            $sourceColumnsNames[] = $column->getColumnName();
        }

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
        $source->expects(self::atLeastOnce())->method('getColumnsNames')->willReturn($sourceColumnsNames);
        // ABS specific
        $source->expects(self::atLeastOnce())->method('getContainerUrl')->willReturn($params['#sourceContainerUrl']);
        $source->expects(self::atLeastOnce())->method('getSasToken')->willReturn($params['#sourceSasToken']);

        // fake staging table
        $stagingTable = new SnowflakeTableDefinition(
            $params['stageSchemaName'],
            $params['stageTableName'],
            true,
            new ColumnCollection($params['stageColumns']),
            $params['stagePrimaryKeys']
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
            new ColumnCollection($params['destColumns']),
            $params['destPrimaryKeys']
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
            $replacedQuery = Utils::replaceParamsInQuery($query, $params);
            $replacedQueries[] = $replacedQuery;
            dump($replacedQuery);
            dump('---------');
        }

        return $replacedQueries;
    }
}
