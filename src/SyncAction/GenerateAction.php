<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\SyncAction;

use Keboola\CustomQueryManagerApp\Config;
use Keboola\CustomQueryManagerApp\Generator\GeneratorFactory;

class GenerateAction
{
    public const NAME = 'generate';

    private GeneratorFactory $generatorFactory;
    private Config $config;

    public function __construct(
        GeneratorFactory $generatorFactory,
        Config $config
    ) {
        $this->generatorFactory = $generatorFactory;
        $this->config = $config;
    }

    /**
     * @return array{output: array{queries: array{sql: string, description: string}[]}}
     */
    public function run(): array
    {
        $generator = $this->generatorFactory->factory(
            $this->config->getBackend(),
            $this->config->getOperation(),
            $this->config->getSource(),
        );
        $queries = $generator->generate(
            $this->config->getColumns(),
            $this->config->getPrimaryKeys(),
        );

        return [
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
