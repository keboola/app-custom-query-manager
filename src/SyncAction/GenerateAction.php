<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\SyncAction;

use Keboola\Component\UserException;
use Keboola\CustomQueryManagerApp\Config;
use Keboola\CustomQueryManagerApp\Generator;

class GenerateAction
{
    public const NAME = 'generate';

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @codingStandardsIgnoreStart
     * @return array{action: string, backend: string, operation: string, columns: string[], primaryKeys: string[], source: string, output: array{queries: array{sql: string, description: string}[]}}
     * @codingStandardsIgnoreEnd
     */
    public function run(): array
    {
        $queries = [];

        if ($this->config->getBackend() === Config::BACKEND_SNOWFLAKE) {
            if ($this->config->getOperation() === Config::OPERATION_IMPORT_FULL) {
                if ($this->config->getSource() === Config::SOURCE_FILE_ABS) {
                    $generator = new Generator\Snowflake\ImportFull\FromAbsGenerator();
                    $queries = $generator->generate(
                        $this->config->getColumns(),
                        $this->config->getPrimaryKeys(),
                    );
                }
            }
        } elseif ($this->config->getBackend() === Config::BACKEND_SYNAPSE) {
            if ($this->config->getOperation() === Config::OPERATION_IMPORT_FULL) {
                if ($this->config->getSource() === Config::SOURCE_FILE_ABS) {
                    $generator = new Generator\Synapse\ImportFull\FromAbsGenerator();
                    $queries = $generator->generate(
                        $this->config->getColumns(),
                        $this->config->getPrimaryKeys(),
                    );
                }
                if ($this->config->getSource() === Config::SOURCE_TABLE) {
                    $generator = new Generator\Synapse\ImportFull\FromTableGenerator();
                    $queries = $generator->generate(
                        $this->config->getColumns(),
                        $this->config->getPrimaryKeys(),
                    );
                }
            }
        }

        if (empty($queries)) {
            throw new UserException('Combination of Backend/Operation/Source not implemented yet');
        }

        return [
            'action' => self::NAME,
            'backend' => $this->config->getBackend(),
            'operation' => $this->config->getOperation(),
            'source' => $this->config->getSource(),
            'columns' => $this->config->getColumns(),
            'primaryKeys' => $this->config->getPrimaryKeys(),
            'output' => [
                'queries' => $this->formatQueriesForOutput($queries),
            ],
        ];
    }

    /**
     * @param string[] $queries
     * @return array{sql: string, description: string}[]
     */
    private function formatQueriesForOutput(array $queries): array
    {
        $output = [];
        foreach ($queries as $query) {
            $output[] = [
                'sql' => $query,
                'description' => '',
            ];
        }
        return $output;
    }
}
