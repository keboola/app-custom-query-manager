<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Generator;

class ReplaceToken
{
    private string $value;
    private string $replacement;
    private int $type;

    /**
     * @param Replace::TYPE_* $type
     */
    public function __construct(string $value, string $replacement, int $type = Replace::TYPE_MATCH_AS_IDENTIFIER)
    {
        $this->value = $value;
        $this->replacement = $replacement;
        $this->type = $type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getReplacement(): string
    {
        return $this->replacement;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
