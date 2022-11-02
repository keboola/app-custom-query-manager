<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Generator;

class Utils
{
    public static function getUniqeId(string $prefix): string
    {
        return str_replace(
            '.',
            '',
            uniqid($prefix, true),
        );
    }
}
