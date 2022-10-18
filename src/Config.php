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

    // supported sources
    public const SOURCE_FILE_ABS = 'fileAbs';
    public const SOURCE_TABLE = 'table';
    public const SOURCES = [
        self::SOURCE_FILE_ABS,
        self::SOURCE_TABLE,
    ];

    public function getBackend(): string
    {
        return $this->getStringValue(['parameters', 'backend']);
    }

    public function getOperation(): string
    {
        return $this->getStringValue(['parameters', 'operation']);
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->getArrayValue(['parameters', 'columns']);
    }

    /**
     * @return string[]
     */
    public function getPrimaryKeys(): array
    {
        return $this->getArrayValue(['parameters', 'primaryKeys']);
    }

    public function getSource(): string
    {
        return $this->getStringValue(['parameters', 'source']);
    }
}
