<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Psr\Log\LoggerInterface;

class Component extends BaseComponent
{
    public const ACTION_GENERATE = 'generate';

    // supported backends
    public const BACKEND_SNOWFLAKE = 'snowflake';
    public const BACKEND_SYNAPSE = 'synapse';
    public const BACKENDS = [
        self::BACKEND_SNOWFLAKE,
        self::BACKEND_SYNAPSE,
    ];

    // operation for generate action
    public const OPERATION_TABLE_CREATE = 'tableCreate';
    public const OPERATION_TABLE_DROP = 'tableDrop';
    public const OPERATIONS = [
        self::OPERATION_TABLE_CREATE,
        self::OPERATION_TABLE_DROP,
    ];

    public function __construct(LoggerInterface $logger)
    {
        parent::__construct($logger);
    }

    protected function run(): void
    {
        throw new UserException(sprintf(
            'Can be used only for sync actions {%s}.',
            implode(',', [self::ACTION_GENERATE])
        ));
    }

    /**
     * @return array{action: string, backendType: string, operation: string, output: mixed[]}
     */
    public function actionGenerate(): array
    {
        /** @var Config $config */
        $config = $this->getConfig();

        // TODO use import-export-lib

        return [
            'action' => self::ACTION_GENERATE,
            'backendType' => $config->getBackendType(),
            'operation' => $config->getOperation(),
            'output' => [
                'foo' => 'bar',
            ],
        ];
    }

    protected function getConfigClass(): string
    {
        return Config::class;
    }

    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    /**
     * @return string[]
     */
    public function getSyncActions(): array
    {
        return [
            self::ACTION_GENERATE => 'actionGenerate',
        ];
    }
}
