<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp;

use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use Keboola\CustomQueryManagerApp\Generator\GeneratorFactory;
use Keboola\CustomQueryManagerApp\SyncAction\GenerateAction;

class Component extends BaseComponent
{
    protected function run(): void
    {
        throw new UserException(sprintf(
            'Can be used only for sync actions {%s}.',
            implode(',', [GenerateAction::NAME])
        ));
    }

    /**
     * @return array{output: array{queries: array{sql: string, description: string}[]}}
     */
    public function actionGenerate(): array
    {
        /** @var Config $config */
        $config = $this->getConfig();

        return (new GenerateAction(
            new GeneratorFactory(),
            $config,
        ))->run();
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
            GenerateAction::NAME => 'actionGenerate',
        ];
    }
}
