<?php

declare(strict_types=1);

namespace Keboola\CustomQueryManagerApp\FunctionalTests\SyncAction;

use Keboola\CustomQueryManagerApp\Config;
use Keboola\CustomQueryManagerApp\Generator\GeneratorFactory;
use Keboola\CustomQueryManagerApp\Generator\Synapse\ImportFull\FromWorkspaceGenerator;
use Keboola\CustomQueryManagerApp\SyncAction\GenerateAction;
use PHPUnit\Framework\TestCase;

class GenerateActionTest extends TestCase
{
    public function testRunSuccess(): void
    {
        $backend = 'synapse';
        $operation = 'import';
        $operationType = 'full';
        $source = 'workspace';
        $fileStorage = null;
        $columns = ['column1', 'column2'];
        $primaryKeys = ['column1'];

        $generator = $this->createMock(FromWorkspaceGenerator::class);
        $generator->expects($this->atLeastOnce())->method('generate')
            ->with($columns, $primaryKeys)
            ->willReturn([
                'first sql',
                'second sql',
            ]);

        $generatorFactory = $this->createMock(GeneratorFactory::class);
        $generatorFactory->expects($this->atLeastOnce())->method('factory')
            ->with($backend, $operation, $operationType, $source, $fileStorage)
            ->willReturn($generator);

        $config = $this->createMock(Config::class);
        $config->expects($this->atLeastOnce())->method('getBackend')->willReturn($backend);
        $config->expects($this->atLeastOnce())->method('getOperation')->willReturn($operation);
        $config->expects($this->atLeastOnce())->method('getOperationType')->willReturn($operationType);
        $config->expects($this->atLeastOnce())->method('getSource')->willReturn($source);
        $config->expects($this->atLeastOnce())->method('getFileStorage')->willReturn($fileStorage);
        $config->expects($this->atLeastOnce())->method('getColumns')->willReturn($columns);
        $config->expects($this->atLeastOnce())->method('getPrimaryKeys')->willReturn($primaryKeys);

        $action = new GenerateAction($generatorFactory, $config);
        $output = $action->run();

        $this->assertArrayHasKey('output', $output);
        $this->assertArrayHasKey('queries', $output['output']);

        $queries = $output['output']['queries'];
        $this->assertCount(2, $queries);

        $query = $queries[0];
        $this->assertArrayHasKey('sql', $query);
        $this->assertSame('first sql', $query['sql']);
        $this->assertArrayHasKey('description', $query);
        $this->assertSame('', $query['description']);

        $query = $queries[1];
        $this->assertArrayHasKey('sql', $query);
        $this->assertSame('second sql', $query['sql']);
        $this->assertArrayHasKey('description', $query);
        $this->assertSame('', $query['description']);
    }
}
