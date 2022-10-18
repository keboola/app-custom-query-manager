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
                ->enumNode('backendType')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->values(Config::BACKENDS)
                ->end()
                ->enumNode('operation')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->values(Config::OPERATIONS)
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
