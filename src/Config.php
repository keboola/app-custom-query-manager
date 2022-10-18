<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp;

use Keboola\Component\Config\BaseConfig;

class Config extends BaseConfig
{
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

    public function getBackendType(): string
    {
        return $this->getStringValue(['parameters', 'backendType']);
    }

    public function getOperation(): string
    {
        return $this->getStringValue(['parameters', 'operation']);
    }
}
