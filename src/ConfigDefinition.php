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
        /** @noinspection NullPointerExceptionInspection */
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
                ->arrayNode('columns')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
                ->arrayNode('primaryKeys')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->scalarPrototype()
                        ->cannotBeEmpty()
                    ->end()
                ->end()
                ->enumNode('source')
                    ->isRequired()
                    ->values(Config::SOURCES)
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
