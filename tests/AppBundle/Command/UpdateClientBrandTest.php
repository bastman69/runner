<?php

namespace tests\AppBundle\Command;

use AppBundle\Command\UpdateClientBrandCommand;
use Symfony\Component\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class UpdateClientBrandTest extends KernelTestCase
{
    /**
     * TDD Test
     * @test
     */
    public function itCanExecute()
    {
        $filename = __DIR__. '/../Service/clients.csv';
        $processCycle = 5000;
        self::bootKernel();
        $application = new Application(self::$kernel);
        $application->add(new UpdateClientBrandCommand);
        $command = $application->find('app.migrate.client:brand');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
           'command' => $command->getName(),
            'filename' => $filename,
            'processCycle' => $processCycle
        ]);
        $expectedDescription = 'It Updates Client Brand from XM to XMBZ/XMTD';
        $this->assertEquals($expectedDescription, $command->getDescription());
        $expectedHelp = 'It updates the brand_id for all clients that exist in the csv';
        $this->assertEquals($expectedHelp, $command->getHelp());
        $this->assertEquals($filename, $commandTester->getInput('parameters')->getArgument('filename'));
        $this->assertEquals($processCycle, $commandTester->getInput('parameters')->getArgument('processCycle'));
        $output = $commandTester->getDisplay();
        $this->assertContains('Client Brand Update', $output);
        $this->assertContains('Records count: 210', $output);
    }


}