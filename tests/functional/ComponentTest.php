<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\FunctionalTests;

use Generator;
use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecification;
use Keboola\DatadirTests\Exception\DatadirTestsException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use function GuzzleHttp\json_encode;

class ComponentTest extends AbstractDatadirTestCase
{
    /**
     * @dataProvider generateActionProvider
     */
    public function testGenerateAction(
        string $backend,
        string $operation,
        string $operationType,
        string $source,
        ?string $fileStorage
    ): void {
        $specification = new DatadirTestSpecification(
            __DIR__,
            0,
            null,
            null,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);
        $data = [
            'action' => 'generate',
            'parameters' => [
                'backend' => $backend,
                'operation' => $operation,
                'operationType' => $operationType,
                'source' => $source,
                'fileStorage' => $fileStorage,
                'columns' => [
                    'column1',
                    'column2',
                ],
                'primaryKeys' => [
                    'column1',
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', json_encode($data));

        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
        /**
         * @codingStandardsIgnoreStart
         * @var array{action: string, backend: string, operation: string, columns: string[], primaryKeys: string[], source: string, output: array{queries: array{sql: string, description: string}[]}} $output
         * @codingStandardsIgnoreEnd
         */
        $output = json_decode($process->getOutput(), true);

        self::assertIsArray($output);

        self::assertArrayHasKey('output', $output);
        self::assertArrayHasKey('queries', $output['output']);
        $queries = $output['output']['queries'];
        self::assertGreaterThan(0, count($queries));
        self::assertArrayHasKey('sql', $queries[0]);
        self::assertIsString($queries[0]['sql']);
        self::assertArrayHasKey('description', $queries[0]);
        self::assertIsString($queries[0]['description']);
    }

    public function generateActionProvider(): Generator
    {
        yield 'synapse-import-full-workspace' => [
            'synapse',
            'import',
            'full',
            'workspace',
            null,
        ];
        yield 'synapse-import-incremental-workspace' => [
            'synapse',
            'import',
            'incremental',
            'workspace',
            null,
        ];
        yield 'synapse-import-full-file-abs' => [
            'synapse',
            'import',
            'full',
            'file',
            'abs',
        ];
        yield 'synapse-import-incremental-file-abs' => [
            'synapse',
            'import',
            'incremental',
            'file',
            'abs',
        ];
        yield 'snowflake-import-full-file-abs' => [
            'snowflake',
            'import',
            'full',
            'file',
            'abs',
        ];
    }

    /**
     * @dataProvider generateActionFailedProvider
     */
    public function testGenerateActionFailed(
        string $backend,
        string $operation,
        string $operationType,
        string $source,
        ?string $fileStorage,
        string $expectedStderr
    ): void {
        $specification = new DatadirTestSpecification(
            __DIR__,
            1,
            null,
            $expectedStderr,
            null
        );
        $tempDatadir = $this->getTempDatadir($specification);
        $data = [
            'action' => 'generate',
            'parameters' => [
                'backend' => $backend,
                'operation' => $operation,
                'operationType' => $operationType,
                'source' => $source,
                'fileStorage' => $fileStorage,
                'columns' => [
                    'column1',
                    'column2',
                ],
                'primaryKeys' => [
                    'column1',
                ],
            ],
        ];
        file_put_contents($tempDatadir->getTmpFolder() . '/config.json', json_encode($data));

        $process = $this->runScript($tempDatadir->getTmpFolder());
        $this->assertMatchesSpecification($specification, $process, $tempDatadir->getTmpFolder());
        $output = json_decode($process->getOutput(), true);
    }

    public function generateActionFailedProvider(): Generator
    {
        yield 'combination not implemented yet' => [
            'snowflake',
            'import',
            'incremental',
            'workspace',
            null,
            'Combination of options is not implemented yet',
        ];
        yield 'invalid backend value' => [
            'redshift',
            'import',
            'full',
            'workspace',
            null,
            'The value "redshift" is not allowed for path "root.parameters.backend".' .
                ' Permissible values: "snowflake", "synapse"',
        ];
    }

    protected function runScript(string $datadirPath, ?string $runId = null): Process
    {
        $fs = new Filesystem();

        $script = $this->getScript();
        if (!$fs->exists($script)) {
            throw new DatadirTestsException(sprintf(
                'Cannot open script file "%s"',
                $script
            ));
        }

        $runCommand = [
            'php',
            $script,
        ];
        $runProcess = new Process($runCommand);
        $runProcess->setEnv([
            'KBC_DATADIR' => $datadirPath,
        ]);
        $runProcess->setTimeout(0);
        $runProcess->run();
        return $runProcess;
    }
}
