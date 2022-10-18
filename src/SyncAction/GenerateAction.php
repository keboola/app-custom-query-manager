<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\SyncAction;

use Keboola\CustomQueryManagerApp\Config;

class GenerateAction
{
    public const NAME = 'generate';

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @return array{action: string, backendType: string, operation: string, output: mixed[]}
     */
    public function run(): array
    {
        // TODO use import-export-lib

        return [
            'action' => self::NAME,
            'backendType' => $this->config->getBackend(),
            'operation' => $this->config->getOperation(),
            'columns' => $this->config->getColumns(),
            'primaryKeys' => $this->config->getPrimaryKeys(),
            'source' => $this->config->getSource(),
            'output' => [
                'foo' => 'bar',
            ],
        ];
    }
}
