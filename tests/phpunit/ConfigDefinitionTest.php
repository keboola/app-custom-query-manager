<?php

declare(strict_types=1);

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
     * @param class-string<\Throwable> $expectedExceptionClass
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
                        "operationType": "full",
                        "source": "workspace",
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
                        "operationType": "full",
                        "backend": "snowflake",
                        "source": "workspace",
                        "columns": [],
                        "primaryKeys": []
                    }
                }
                JSON,
                InvalidConfigurationException::class,
                'The value "" is not allowed for path "root.parameters.operation". ' .
                'Permissible values: "import"',
            ],
            'source file - missing fileStorage' => [
                /** @lang JSON */ <<<JSON
                {
                    "parameters": {
                        "operation": "import",
                        "operationType": "full",
                        "backend": "snowflake",
                        "source": "file",
                        "columns": [
                          "column1"
                        ],
                        "primaryKeys": []
                    }
                }
                JSON,
                InvalidConfigurationException::class,
                'A value is required for option "root.parameters.fileStorage" ' .
                'if "root.parameters.source" contains "file" value.',
            ],
            'source file - nullable fileStorage' => [
                /** @lang JSON */ <<<JSON
                {
                    "parameters": {
                        "operation": "import",
                        "operationType": "full",
                        "backend": "snowflake",
                        "source": "file",
                        "fileStorage": null,
                        "columns": [
                          "column1"
                        ],
                        "primaryKeys": []
                    }
                }
                JSON,
                InvalidConfigurationException::class,
                'A value is required for option "root.parameters.fileStorage" ' .
                'if "root.parameters.source" contains "file" value.',
            ],
            'source file - empty fileStorage' => [
                /** @lang JSON */ <<<JSON
                {
                    "parameters": {
                        "operation": "import",
                        "operationType": "full",
                        "backend": "snowflake",
                        "source": "file",
                        "fileStorage": "",
                        "columns": [
                          "column1"
                        ],
                        "primaryKeys": []
                    }
                }
                JSON,
                InvalidConfigurationException::class,
                'The value "" is not allowed for path "root.parameters.fileStorage". ' .
                'Permissible values: "abs", null',
            ],
            // TODO backend
            // TODO source
            // TODO fileStorage
            // TODO columns
            // TODO primaryKeys
        ];
    }

    /**
     * @dataProvider provideValidConfig
     * @param mixed[][] $expected
     */
    public function testValidGetParametersDefinition(
        string $inputConfig,
        array $expected
    ): void {
        $config = (new JsonDecode([JsonDecode::ASSOCIATIVE => true]))->decode($inputConfig, JsonEncoder::FORMAT);
        $processedConfig = (new Processor())->processConfiguration(new ConfigDefinition(), [$config]);
        self::assertSame($expected, $processedConfig);
    }

    /**
     * @return mixed[][]
     */
    public function provideValidConfig(): array
    {
        return [
            'valid with source file' => [
                'input' => <<<JSON
                    {
                        "parameters": {
                            "operation": "import",
                            "operationType": "full",
                            "backend": "snowflake",
                            "source": "file",
                            "fileStorage": "abs",
                            "columns": [
                              "col1",
                              "col2"
                            ],
                            "primaryKeys": [
                              "col1"
                            ]
                        }
                    }
                    JSON,
                'expected' => [
                    'parameters' => [
                        'operation' => 'import',
                        'operationType' => 'full',
                        'backend' => 'snowflake',
                        'source' => 'file',
                        'fileStorage' => 'abs',
                        'columns' => [
                            'col1',
                            'col2',
                        ],
                        'primaryKeys' => [
                            'col1',
                        ],
                    ],
                ],
            ],
            'valid with source workspace' => [
                'input' => <<<JSON
                    {
                        "parameters": {
                            "operation": "import",
                            "operationType": "full",
                            "backend": "snowflake",
                            "source": "workspace",
                            "columns": [
                              "col1",
                              "col2"
                            ],
                            "primaryKeys": [
                              "col1"
                            ]
                        }
                    }
                    JSON,
                'expected' => [
                    'parameters' => [
                        'operation' => 'import',
                        'operationType' => 'full',
                        'backend' => 'snowflake',
                        'source' => 'workspace',
                        'columns' => [
                            'col1',
                            'col2',
                        ],
                        'primaryKeys' => [
                            'col1',
                        ],
                    ],
                ],
            ],
        ];
    }
}
