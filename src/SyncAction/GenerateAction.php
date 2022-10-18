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
     * @return array<string, string|array>
     */
    public function run(): array
    {
        if ($this->config->getBackend() === Config::BACKEND_SNOWFLAKE) {
            if ($this->config->getOperation() === Config::OPERATION_IMPORT_FULL_FROM_FILE) {
                if ($this->config->getSource() === Config::SOURCE_FILE_ABS) {
                    $generator = new Generator\Snowflake\ImportFull\FromAbsGenerator();
                    $queries = $generator->generate(
                        $this->config->getColumns(),
                        $this->config->getPrimaryKeys(),
                    );
                } else {
                    throw new UserException('Source not implemented yet');
                }
            } else {
                throw new UserException('Operation not implemented yet');
            }
        } else {
            throw new UserException('Backend not implemented yet');
        }

        return [
            'action' => self::NAME,
            'backendType' => $this->config->getBackend(),
            'operation' => $this->config->getOperation(),
            'columns' => $this->config->getColumns(),
            'primaryKeys' => $this->config->getPrimaryKeys(),
            'source' => $this->config->getSource(),
            'output' => [
                'queries' => $queries,
            ],
        ];
    }
}
