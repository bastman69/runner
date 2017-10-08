<?php

namespace AppBundle\Command;

use AppBundle\Service\CsvParser;
use AppBundle\Service\ScriptGenerator;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateClientBrandCommand extends ContainerAwareCommand
{
    public $filename;

    public function configure()
    {
        $this->setName('app.migrate.client:brand')
        ->setDescription('It Updates Client Brand from XM to XMBZ/XMTD')
        ->setHelp('It updates the brand_id for all clients that exist in the csv')
        ->addArgument('filename', InputArgument::REQUIRED, 'csv filename')
        ->addArgument('processCycle', InputArgument::OPTIONAL, 'process cycle', 5000);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<info>Client Brand Update</info>');
        $output->writeln('===================');
        //1. Parse File
        $output->writeln('Processing File '. $input->getArgument('filename'));
        $phpArray = $this->parseCsv($input, $output);
        //2. Create SQL Scripts
        $sqlScripts = $this->generateScripts(
            $input,
            $output,
            $phpArray
        );
        foreach($sqlScripts as $sqlScript) {
            $output->writeln('Created File '. $sqlScript);
        }
        //3. Execute Scripts
        $this->executeScripts($output, $sqlScripts);
        $output->writeln('<info>Done.</info>');
    }

    public function generateScripts(InputInterface $input, OutputInterface $output, $phpArray = array())
    {
        $output->writeln('<info>Generating SQL scripts</info>');
        $generator = new ScriptGenerator();
        $generator->generate($phpArray, $input->getArgument('processCycle'));
        return $generator->getFileNames();

    }

    public function parseCsv(InputInterface $input, OutputInterface $output)
    {
        //$parser = $this->getContainer()->get('csv_parser');
        $parser = new CsvParser();
        $data = $parser->parse($input->getArgument('filename'));
        $output->writeln('Records count: '. count($data));
        return $data;
    }

    /**
     * @param OutputInterface $output
     * @param $sqlScripts
     */
    protected function executeScripts(OutputInterface $output, $sqlScripts)
    {
        $output->writeln('<info>Connecting to database</info>');
        foreach ($sqlScripts as $sqlScript) {
            $output->writeln('Running script '.$sqlScript);
            $output->writeln(
                'mysql call'
            //shell_exec('mysql -u root -p  < ' . $sqlScript)
            );
        }
    }

}