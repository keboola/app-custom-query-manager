<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Generator;

use Keboola\Component\UserException;
use Keboola\CustomQueryManagerApp\Config;
use Keboola\CustomQueryManagerApp\Generator;

class GeneratorFactory
{
    /**
     * @param Config::BACKEND_* $backend
     * @param Config::OPERATION_ACTION_* $operation
     * @param Config::OPERATION_TYPE_* $operationType
     * @param Config::SOURCE_* $source
     * @param Config::FILE_STORAGE_*|null $fileStorage
     * @throws UserException
     */
    public function factory(
        string $backend,
        string $operation,
        string $operationType,
        string $source,
        ?string $fileStorage
    ): GeneratorInterface {
        if ($backend === Config::BACKEND_SNOWFLAKE) {
            if ($operation === Config::OPERATION_ACTION_IMPORT) {
                if ($operationType === Config::OPERATION_TYPE_FULL) {
                    if ($source === Config::SOURCE_FILE) {
                        if ($fileStorage === Config::FILE_STORAGE_ABS) {
                            return new Generator\Snowflake\ImportFull\FromAbsGenerator();
                        }
                    }
                }
            }
        }
        if ($backend === Config::BACKEND_SYNAPSE) {
            if ($operation === Config::OPERATION_ACTION_IMPORT) {
                if ($operationType === Config::OPERATION_TYPE_FULL) {
                    if ($source === Config::SOURCE_FILE) {
                        if ($fileStorage === Config::FILE_STORAGE_ABS) {
                            return new Generator\Synapse\ImportFull\FromAbsGenerator();
                        }
                    }
                    if ($source === Config::SOURCE_WORKSPACE) {
                        return new Generator\Synapse\ImportFull\FromWorkspaceGenerator();
                    }
                }
                if ($operationType === Config::OPERATION_TYPE_INCREMENTAL) {
                    if ($source === Config::SOURCE_FILE) {
                        if ($fileStorage === Config::FILE_STORAGE_ABS) {
                            return new Generator\Synapse\ImportIncremental\FromAbsGenerator();
                        }
                    }
                    if ($source === Config::SOURCE_WORKSPACE) {
                        return new Generator\Synapse\ImportIncremental\FromWorkspaceGenerator();
                    }
                }
            }
        }
        throw new UserException('Combination of options is not implemented yet');
    }
}
