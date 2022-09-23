<?php

declare(strict_types=1);

namespace MyComponent;

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
                    ->values(Component::BACKENDS)
                ->end()
                ->enumNode('operation')
                    ->isRequired()
                    ->cannotBeEmpty()
                    ->values(Component::OPERATIONS)
                ->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
