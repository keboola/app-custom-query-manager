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
    public const OPERATION_IMPORT_FULL = 'importFull';
    public const OPERATION_IMPORT_INCREMENTAL = 'importIncremental';
    public const OPERATIONS = [
        self::OPERATION_IMPORT_FULL,
        self::OPERATION_IMPORT_INCREMENTAL,
    ];

    // supported sources
    public const SOURCE_FILE_ABS = 'fileAbs';
    public const SOURCE_TABLE = 'table';
    public const SOURCES = [
        self::SOURCE_FILE_ABS,
        self::SOURCE_TABLE,
    ];

    /**
     * @return self::BACKEND_*
     */
    public function getBackend(): string
    {
        $value = $this->getStringValue(['parameters', 'backend']);
        /** @var self::BACKEND_* $value */
        return $value;
    }

    /**
     * @return self::OPERATION_*
     */
    public function getOperation(): string
    {
        $value = $this->getStringValue(['parameters', 'operation']);
        /** @var self::OPERATION_* $value */
        return $value;
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

    /**
     * @return self::SOURCE_*
     */
    public function getSource(): string
    {
        $value = $this->getStringValue(['parameters', 'source']);
        /** @var self::SOURCE_* $value */
        return $value;
    }
}
