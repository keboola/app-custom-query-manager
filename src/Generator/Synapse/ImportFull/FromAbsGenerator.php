<?php

namespace Keboola\CustomQueryManagerApp\Generator\Synapse\ImportFull;

use Doctrine\DBAL\Connection;
use Keboola\CsvOptions\CsvOptions;
use Keboola\CustomQueryManagerApp\Generator\Utils;
use Keboola\Datatype\Definition\BaseType;
use Keboola\Datatype\Definition\Synapse;
use Keboola\Db\ImportExport\Backend\Synapse\SynapseImportOptions;
use Keboola\Db\ImportExport\Backend\Synapse\ToFinalTable\FullImporter;
use Keboola\Db\ImportExport\Backend\Synapse\ToStage\ToStageImporter;
use Keboola\Db\ImportExport\Storage;
use Keboola\TableBackendUtils\Column\ColumnCollection;
use Keboola\TableBackendUtils\Column\ColumnInterface;
use Keboola\TableBackendUtils\Column\SynapseColumn;
use Keboola\TableBackendUtils\Escaping\SynapseQuote;
use Keboola\TableBackendUtils\Table\Synapse\TableDistributionDefinition;
use Keboola\TableBackendUtils\Table\Synapse\TableIndexDefinition;
use Keboola\TableBackendUtils\Table\SynapseTableDefinition;
use Keboola\TableBackendUtils\Table\SynapseTableQueryBuilder;
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

        // TODO replace SECRET ($sourceSasToken):
        //   CREDENTIAL=(IDENTITY='Shared Access Signature', SECRET='?sourceSasToken634ec2b2aaa4f608157197'),
        // TODO replace dedup table (sufix):
        //   CREATE TABLE {{ id(destSchemaName) }}.[destTableName634ec2b2aaa54129985850tmp634ec2b2c31150_96482587_tmp]
        //   RENAME OBJECT {{ id(destSchemaName) }}.{{ id(destTableName) }} TO [destTableName634ec2b2aaa54129985850tmp634ec2b2c31150_96482587_tmp_rename]

        $sourceColumns = $columns;

        $stageColumns = [];
        foreach ($columns as $columnName) {
            $stageColumns[] = new SynapseColumn(
                $columnName,
                new Synapse(Synapse::getTypeByBasetype(BaseType::STRING))
            );
        }
        $stagePrimaryKeys = $primaryKeys;

        $destColumns = $stageColumns;
        $destColumns[] = new SynapseColumn(
            ColumnInterface::TIMESTAMP_COLUMN_NAME,
            new Synapse(Synapse::getTypeByBasetype(BaseType::TIMESTAMP))
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
        $stagingTable = new SynapseTableDefinition(
            $params['stageSchemaName'],
            $params['stageTableName'],
            true,
            new ColumnCollection($stageColumns),
            $stagePrimaryKeys,
            new TableDistributionDefinition(
                TableDistributionDefinition::TABLE_DISTRIBUTION_HASH,
                $stagePrimaryKeys
            ),
            new TableIndexDefinition(
                TableIndexDefinition::TABLE_INDEX_TYPE_HEAP,
                $stagePrimaryKeys
            )
        );
        // fake options
        $options = new SynapseImportOptions(
            [],
            false,
            false,
            1
        );
        // fake destination
        $destination = new SynapseTableDefinition(
            $params['destSchemaName'],
            $params['destTableName'],
            false,
            new ColumnCollection($destColumns),
            $destPrimaryKeys,
            new TableDistributionDefinition(
                TableDistributionDefinition::TABLE_DISTRIBUTION_HASH,
                $destPrimaryKeys
            ),
            new TableIndexDefinition(
                TableIndexDefinition::TABLE_INDEX_TYPE_HEAP,
                $destPrimaryKeys
            )
        );

        // mock importer
        $importer = new ToStageImporter($conn);

        // init query builder
        $qb = new SynapseTableQueryBuilder();

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
            $replacedQuery = Utils::replaceParamsInQuery($query, $params, new SynapseQuote());
            $replacedQueries[] = $replacedQuery;
            dump($replacedQuery);
            dump('---------');
        }

        return $replacedQueries;
    }
}
