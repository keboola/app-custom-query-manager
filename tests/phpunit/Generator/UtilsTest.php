<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\Tests\Generator;

use Keboola\CustomQueryManagerApp\Generator\Utils;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    public function testGetUniqeId(): void
    {
        $id = Utils::getUniqeId('somePrefix');
        $this->assertStringNotContainsString('.', $id);
        // 23 (lenght of uniqid without prefix) + 10 (length of prefix) - 1 (removed dot)
        $this->assertSame(32, strlen($id));
    }
}
