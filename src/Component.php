<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\BaseComponent;
use Keboola\Component\UserException;
use MyComponent\SyncAction\GenerateAction;

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
     * @return array{action: string, backendType: string, operation: string, output: mixed[]}
     */
    public function actionGenerate(): array
    {
        /** @var Config $config */
        $config = $this->getConfig();

        return (new GenerateAction(
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
