<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Generator;

use Keboola\TableBackendUtils\Escaping\QuoteInterface;
use LogicException;

class Replace
{
    public const TYPE_MATCH_AS_IDENTIFIER = 1;
    public const TYPE_MATCH_AS_VALUE = 2;
    /** value with custom method - don't add quote method */
    public const TYPE_MATCH_AS_VALUE_CUSTOM = 5;
    /** identifier with prefix in value */
    public const TYPE_PREFIX_AS_IDENTIFIER = 3;
    /** identifier with suffix in value */
    public const TYPE_SUFFIX_AS_IDENTIFIER = 4;
    /** regex in value */
    public const TYPE_MATCH_AS_VALUE_REGEX = 6;

    /**
     * ReplaceToken object in `$params` can specify value purpose:
     *   - Replace::TYPE_MATCH_AS_IDENTIFIER:
     *          `randomName`
     *          + replacement `foo`
     *          + `create table "randomName"`
     *          = `create table {{ id(foo) }}`
     *   - Replace::TYPE_MATCH_AS_VALUE:
     *          `randomValue`
     *          + replacement `foo`
     *          + `select 'randomValue' from "table"`
     *          = `select {{ q(foo) }} from table`
     *   - Replace::TYPE_MATCH_AS_VALUE_CUSTOM:
     *          `randomValue`
     *          + replacement `fn(foo)`
     *          + `select 'randomValue' from "table"`
     *          = `select {{ fn(foo) }} from table`
     *   - Replace::TYPE_PREFIX_AS_IDENTIFIER:
     *          `__temp_import_`
     *          + replacement `foo`
     *          + `create table "__temp_import_randomName"`
     *          = `create table {{ id(foo) }}`
     *   - Replace::TYPE_SUFFIX_AS_IDENTIFIER:
     *          `_temp_import`
     *          + replacement `foo`
     *          + `create table "randomName_temp_import"`
     *          = `create table {{ id(foo) }}`
     *
     * @param array<string|int, ReplaceToken|array<string|int, ReplaceToken>> $params
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
                    assert($valueInArray instanceof ReplaceToken);
                    $query = self::replaceParamInQuery(
                        $query,
                        $valueInArray,
                        $quoter,
                        $outputPrefix,
                        $outputSuffix
                    );
                }
            } else {
                assert($value instanceof ReplaceToken);
                $query = self::replaceParamInQuery(
                    $query,
                    $value,
                    $quoter,
                    $outputPrefix,
                    $outputSuffix
                );
            }
        }
        return $query;
    }

    public static function replaceParamInQuery(
        string $query,
        ReplaceToken $token,
        QuoteInterface $quoter,
        string $outputPrefix = '{{ ',
        string $outputSuffix = ' }}'
    ): string {

        if ($token->getType() === self::TYPE_MATCH_AS_VALUE) {
            $valueInQuery = $quoter::quote($token->getValue());
            $keyInOutput = sprintf('q(%s)', $token->getReplacement());
        } elseif ($token->getType() === self::TYPE_MATCH_AS_VALUE_CUSTOM) {
            $valueInQuery = $quoter::quote($token->getValue());
            $keyInOutput = $token->getReplacement();
        } elseif ($token->getType() === self::TYPE_MATCH_AS_IDENTIFIER) {
            $valueInQuery = $quoter::quoteSingleIdentifier($token->getValue());
            $keyInOutput = sprintf('id(%s)', $token->getReplacement());
        } elseif ($token->getType() === self::TYPE_PREFIX_AS_IDENTIFIER) {
            // replace generated identifiers at the beginning
            $matches = [];
            if (preg_match('/^\w/', $token->getValue()) === 1) {
                // the first character is any word char
                $pregMatch = preg_match('/\b(' . $token->getValue() . '\w+)\b/', $query, $matches);
            } else {
                // the first character is non-word char
                $quotingChars = preg_quote($quoter::quoteSingleIdentifier(''), '/');
                $pregMatch = preg_match('/[' . $quotingChars . '](' . $token->getValue() . '\w+)\b/', $query, $matches);
            }
            if ($pregMatch === 1) {
                $valueInQuery = $quoter::quoteSingleIdentifier($matches[1]);
                $keyInOutput = sprintf('id(%s)', $token->getReplacement());
            } else {
                // not found, return original query
                return $query;
            }
        } elseif ($token->getType() === self::TYPE_SUFFIX_AS_IDENTIFIER) {
            // replace generated identifiers at the end
            $matches = [];
            if (preg_match('/\b(\w+' . $token->getValue() . ')\b/', $query, $matches) === 1) {
                $valueInQuery = $quoter::quoteSingleIdentifier($matches[1]);
                $keyInOutput = sprintf('id(%s)', $token->getReplacement());
            } else {
                // not found, return original query
                return $query;
            }
        } elseif ($token->getType() === self::TYPE_MATCH_AS_VALUE_REGEX) {
            if (preg_match('/\b(' . $token->getValue() . ')\b/', $query, $matches) === 1) {
                $valueInQuery = $quoter::quote($matches[1]);
                $keyInOutput = $token->getReplacement();
            } else {
                // not found, return original query
                return $query;
            }
        } else {
            throw new LogicException('Unknown type');
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
}
