<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Generator\Synapse\ImportIncremental;

use Doctrine\DBAL\Connection;
use Keboola\CustomQueryManagerApp\Generator\GeneratorInterface;
use Keboola\CustomQueryManagerApp\Generator\Replace;
use Keboola\CustomQueryManagerApp\Generator\ReplaceToken;
use Keboola\CustomQueryManagerApp\Generator\Utils;
use Keboola\Datatype\Definition\BaseType;
use Keboola\Datatype\Definition\Synapse;
use Keboola\Db\ImportExport\Backend\Synapse\SynapseImportOptions;
use Keboola\Db\ImportExport\Backend\Synapse\ToFinalTable\IncrementalImporter;
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

class FromWorkspaceGenerator extends TestCase implements GeneratorInterface
{
    /**
     * @param string[] $columns
     * @param string[] $primaryKeys
     * @return string[]
     */
    public function generate(array $columns, array $primaryKeys = []): array
    {
        $sourceColumns = $columns;
        $sourcePrimaryKeys = $primaryKeys;

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
            'sourceSchemaName' => new ReplaceToken(
                Utils::getUniqeId('sourceSchemaName'),
                'sourceSchemaName',
            ),
            'sourceTableName' => new ReplaceToken(
                Utils::getUniqeId('sourceTableName'),
                'sourceTableName',
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
            'dedup_stageTableName' => new ReplaceToken(
                '#__temp_csvimport',
                "tableName ~ rand ~ '_tmp'",
                Replace::TYPE_PREFIX_AS_IDENTIFIER,
            ),

            'destSchemaName' => new ReplaceToken(
                Utils::getUniqeId('destSchemaName'),
                'schemaName',
            ),
            'destTableName' => new ReplaceToken(
                Utils::getUniqeId('destTableName'),
                'tableName',
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

        // fake source table
        $source = new Storage\Synapse\Table(
            $params['sourceSchemaName']->getValue(),
            $params['sourceTableName']->getValue(),
            $sourceColumns,
            $sourcePrimaryKeys,
        );

        // fake staging table
        $stagingTable = new SynapseTableDefinition(
            $params['stageSchemaName']->getValue(),
            $params['stageTableName']->getValue(),
            true,
            new ColumnCollection($stageColumns),
            $stagePrimaryKeys,
            new TableDistributionDefinition(
                TableDistributionDefinition::TABLE_DISTRIBUTION_ROUND_ROBIN,
                $stagePrimaryKeys
            ),
            new TableIndexDefinition(
                TableIndexDefinition::TABLE_INDEX_TYPE_CLUSTERED_COLUMNSTORE_INDEX,
                $stagePrimaryKeys
            )
        );
        // fake options
        $options = new SynapseImportOptions(
            [],
            true,
            true,
            1
        );
        // fake destination
        $destination = new SynapseTableDefinition(
            $params['destSchemaName']->getValue(),
            $params['destTableName']->getValue(),
            false,
            new ColumnCollection($destColumns),
            $destPrimaryKeys,
            new TableDistributionDefinition(
                TableDistributionDefinition::TABLE_DISTRIBUTION_ROUND_ROBIN,
                $destPrimaryKeys
            ),
            new TableIndexDefinition(
                TableIndexDefinition::TABLE_INDEX_TYPE_CLUSTERED_COLUMNSTORE_INDEX,
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

        // ACTION: import to stage table
        $importState = $importer->importToStagingTable(
            $source,
            $stagingTable,
            $options
        );

        // ACTION: import to final table
        $toFinalTableImporter = new IncrementalImporter($conn);
        $result = $toFinalTableImporter->importToTable(
            $stagingTable,
            $destination,
            $options,
            $importState
        );

        // result
        $replacedQueries = [];
        foreach ($queries as $query) {
            $replacedQuery = Replace::replaceParamsInQuery($query, $params, new SynapseQuote());
            $replacedQueries[] = $replacedQuery;
        }

        return $replacedQueries;
    }
}
