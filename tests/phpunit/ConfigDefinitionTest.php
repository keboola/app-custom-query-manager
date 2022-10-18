<?php

namespace Keboola\CustomQueryManagerApp\Tests;

use Keboola\CustomQueryManagerApp\ConfigDefinition;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

class ConfigDefinitionTest extends TestCase
{
    /**
     * @dataProvider provideInvalidConfigs
     */
    public function testInvalidConfigDefinition(
        string $inputConfig,
        string $expectedExceptionClass,
        string $expectedExceptionMessage
    ): void {
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($inputConfig, JsonEncoder::FORMAT);
        $this->expectException($expectedExceptionClass);
        $this->expectExceptionMessage($expectedExceptionMessage);
        (new Processor())->processConfiguration(new ConfigDefinition(), [$config]);
    }

    /**
     * @return mixed[][]
     */
    public function provideInvalidConfigs(): array
    {
        return [
            'missing operation' => [
                /** @lang JSON */ <<<JSON
                {
                    "parameters": {
                        "backend": "snowflake",
                        "source": "table",
                        "columns": [],
                        "primaryKeys": []
                    }
                }
                JSON,
                InvalidConfigurationException::class,
                'The child config "operation" under "root.parameters" must be configured.',
            ],
            'empty operation' => [
                /** @lang JSON */ <<<JSON
                {
                    "parameters": {
                        "operation": "",
                        "backend": "snowflake",
                        "source": "table",
                        "columns": [],
                        "primaryKeys": []
                    }
                }
                JSON,
                InvalidConfigurationException::class,
                'The value "" is not allowed for path "root.parameters.operation". Permissible values: "importFullFromFile", "importFullFromTable"',
            ],
            // TODO backend
            // TODO source
            // TODO columns
            // TODO primaryKeys
        ];
    }

    public function testValidGetParametersDefinition(): void
    {
        $inputs = <<<JSON
        {
            "parameters": {
                "operation": "importFullFromFile",
                "backend": "snowflake",
                "source": "fileAbs",
                "columns": [
                  "col1",
                  "col2"
                ],
                "primaryKeys": [
                  "col1"
                ]
            }
        }
        JSON;
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($inputs, JsonEncoder::FORMAT);
        $processedConfig = (new Processor())->processConfiguration(new ConfigDefinition(), [$config]);
        self::assertSame([
            'parameters' => [
                'operation' => 'importFullFromFile',
                'backend' => 'snowflake',
                'source' => 'fileAbs',
                'columns' => [
                    'aaa',
                    'bbb'
                ],
                'primaryKeys' => [
                    'aaa'
                ],
            ],
        ], $processedConfig);
    }
}
