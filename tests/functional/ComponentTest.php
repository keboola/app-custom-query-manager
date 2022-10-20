<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\FunctionalTests;

use Keboola\DatadirTests\AbstractDatadirTestCase;
use Keboola\DatadirTests\DatadirTestSpecification;
use Keboola\DatadirTests\Exception\DatadirTestsException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use function GuzzleHttp\json_encode;

class ComponentTest extends AbstractDatadirTestCase
{
    public function testGenerateSynapseFullImportTable(): void
    {
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
                'backend' => 'synapse',
                'operation' => 'importFull',
                'source' => 'table',
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

        self::assertArrayHasKey('action', $output);
        self::assertSame('generate', $output['action']);
        self::assertArrayHasKey('backend', $output);
        self::assertSame('synapse', $output['backend']);
        self::assertArrayHasKey('operation', $output);
        self::assertSame('importFull', $output['operation']);
        self::assertArrayHasKey('source', $output);
        self::assertSame('table', $output['source']);
        self::assertArrayHasKey('columns', $output);
        self::assertSame(['column1', 'column2'], $output['columns']);
        self::assertArrayHasKey('primaryKeys', $output);
        self::assertSame(['column1'], $output['primaryKeys']);

        self::assertArrayHasKey('output', $output);
        self::assertArrayHasKey('queries', $output['output']);
        $queries = $output['output']['queries'];
        self::assertGreaterThan(0, count($queries));
        self::assertArrayHasKey('sql', $queries[0]);
        self::assertIsString($queries[0]['sql']);
        self::assertArrayHasKey('description', $queries[0]);
        self::assertIsString($queries[0]['description']);

        );
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
