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
    public const OPERATION_ACTION_IMPORT = 'import';
    public const OPERATIONS = [
        self::OPERATION_ACTION_IMPORT,
    ];

    public const OPERATION_TYPE_FULL = 'full';
    public const OPERATION_TYPE_INCREMENTAL = 'incremental';
    public const OPERATION_TYPES = [
        self::OPERATION_TYPE_FULL,
        self::OPERATION_TYPE_INCREMENTAL,
    ];

    // supported sources
    public const SOURCE_FILE = 'file';
    public const SOURCE_TABLE = 'table';
    public const SOURCES = [
        self::SOURCE_FILE,
        self::SOURCE_TABLE,
    ];

    public const FILE_STORAGE_ABS = 'abs';
    public const FILE_STORAGES = [
        self::FILE_STORAGE_ABS,
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
     * @return self::OPERATION_ACTION_*
     */
    public function getOperation(): string
    {
        $value = $this->getStringValue(['parameters', 'operation']);
        /** @var self::OPERATION_ACTION_* $value */
        return $value;
    }

    /**
     * @return self::OPERATION_TYPE_*
     */
    public function getOperationType(): string
    {
        $value = $this->getStringValue(['parameters', 'operationType']);
        /** @var self::OPERATION_TYPE_* $value */
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

    /**
     * @return self::FILE_STORAGE_*|null
     */
    public function getFileStorage(): ?string
    {
        $value = $this->getValue(['parameters', 'fileStorage']);
        if ($value === null) {
            return null;
        }

        assert(is_string($value));
        /** @var self::FILE_STORAGE_* $value */
        return $value;
    }
}
