<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        $parametersNode
            ->children()
                ->enumNode('backend')
                    ->isRequired()
                    ->values(Config::BACKENDS)
                ->end()
                ->enumNode('operation')
                    ->isRequired()
                    ->values(Config::OPERATIONS)
                ->end()
                ->enumNode('operationType')
                    ->isRequired()
                    ->values(Config::OPERATION_TYPES)
                ->end()
                ->enumNode('source')
                    ->isRequired()
                    ->values(Config::SOURCES)
                ->end()
                ->enumNode('fileStorage')
                    // allow `null` because it's optional
                    ->values(array_merge(Config::FILE_STORAGES, [null]))
                ->end()
                ->arrayNode('columns')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
                ->arrayNode('primaryKeys')
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->defaultValue([])
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->ifTrue(function ($v) {
                    if (!is_array($v)) {
                        return true;
                    }
                    $source = $v['source'];
                    $fileStorage = $v['fileStorage'] ?? null;
                    if ($source === Config::SOURCE_FILE && $fileStorage === null) {
                        return true;
                    }
                })
                ->thenInvalid('A value is required for option "root.parameters.fileStorage" ' .
                    'if "root.parameters.source" contains "file" value.')
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
