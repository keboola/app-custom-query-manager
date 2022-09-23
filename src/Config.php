<?php

declare(strict_types=1);

namespace MyComponent;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
    public function getBackendType(): string
    {
        return $this->getStringValue(['parameters', 'backendType']);
    }

    public function getOperation(): string
    {
        return $this->getStringValue(['parameters', 'operation']);
    }
}
