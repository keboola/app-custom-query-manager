<?php

namespace Keboola\CustomQueryManagerApp\Generator;

use Keboola\TableBackendUtils\Column\ColumnInterface;
use Keboola\TableBackendUtils\Escaping\QuoteInterface;

class Utils
{
    /**
     * Keys in `$params` can specify their purpose:
     *   - 'foo'  = identifier
     *   - '#foo' = value
     *   - '^foo' = identifier with prefix in value
     *
     * @param mixed[] $params
     */
    public static function replaceParamsInQuery(
        string $query,
        array $params,
        QuoteInterface $quoter,
        string $outputPrefix = '{{ ',
        string $outputSuffix = ' }}'
    ): string {
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $keyInArray => $valueInArray) {
                    if ($valueInArray instanceof ColumnInterface) {
                        $query = self::replaceParamInQuery($query, $valueInArray->getColumnName(), $keyInArray, $quoter, $outputPrefix, $outputSuffix);
                    } else {
                        $query = self::replaceParamInQuery($query, $valueInArray, $keyInArray, $quoter, $outputPrefix, $outputSuffix);
                    }
                }
            } else {
                $query = self::replaceParamInQuery($query, $value, $key, $quoter, $outputPrefix, $outputSuffix);
            }
        }
        return $query;
    }

    public static function replaceParamInQuery(
        string $query,
        string $valueInQuery,
        string $keyInOutput,
        QuoteInterface $quoter,
        string $outputPrefix = '{{ ',
        string $outputSuffix = ' }}'
    ): string {
        if (strpos($keyInOutput, '#') === 0) {
            // replace values
            $valueInQuery = $quoter::quote($valueInQuery);
            $keyInOutput = substr($keyInOutput, 1);
        } elseif (strpos($keyInOutput, '^') === 0) {
            // replace generated identifiers at the beginning
            $matches = [];
            if (preg_match('/\b(' . $valueInQuery . '\w+)\b/', $query, $matches) === 1) {
                $valueInQuery = $quoter::quoteSingleIdentifier($matches[1]);
                $keyInOutput = substr($keyInOutput, 1);
                $keyInOutput = sprintf('id(%s)', $keyInOutput);
            } else {
                // not found, return original query
                return $query;
            }
            }
        } else {
            // replace identifiers
            $valueInQuery = $quoter::quoteSingleIdentifier($valueInQuery);
            $keyInOutput = sprintf('id(%s)', $keyInOutput);
        }
        return str_replace(
            $valueInQuery,
            sprintf(
                '%s%s%s',
                $outputPrefix,
                $keyInOutput,
                $outputSuffix
            ),
            $query
        );
    }

    public static function getUniqeId(string $prefix): string
    {
        return str_replace(
            '.',
            '',
            uniqid($prefix, true),
        );
    }
}
