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
     * @param Config::OPERATION_* $operation
     * @param Config::SOURCE_* $source
     * @throws UserException
     */
    public function factory(
        string $backend,
        string $operation,
        string $source
    ): GeneratorInterface {
        if ($backend === Config::BACKEND_SNOWFLAKE) {
            if ($operation === Config::OPERATION_IMPORT_FULL) {
                if ($source === Config::SOURCE_FILE_ABS) {
                    return new Generator\Snowflake\ImportFull\FromAbsGenerator();
                }
            }
        }
        if ($backend === Config::BACKEND_SYNAPSE) {
            if ($operation === Config::OPERATION_IMPORT_FULL) {
                if ($source === Config::SOURCE_FILE_ABS) {
                    return new Generator\Synapse\ImportFull\FromAbsGenerator();
                }
                if ($source === Config::SOURCE_TABLE) {
                    return new Generator\Synapse\ImportFull\FromTableGenerator();
                }
            }
            if ($operation === Config::OPERATION_IMPORT_INCREMENTAL) {
                if ($source === Config::SOURCE_FILE_ABS) {
                    return new Generator\Synapse\ImportIncremental\FromAbsGenerator();
                }
            }
        }
        throw new UserException('Combination of Backend/Operation/Source not implemented yet');
    }
}
