<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Generator;

interface GeneratorInterface
{
    /**
     * @param string[] $columns
     * @param string[] $primaryKeys
     * @return string[]
     */
    public function generate(array $columns, array $primaryKeys = []): array;
}
