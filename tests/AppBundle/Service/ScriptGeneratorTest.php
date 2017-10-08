<?php

namespace tests\AppBundle\Service;


use AppBundle\Service\ScriptGeneratorInterface;
use AppBundle\Service\ScriptGenerator;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints\DateTime;

class ScriptGeneratorTest extends WebTestCase
{
    /**
     * @test
     */
    public function itImplementsScriptGeneratorInterface()
    {
        $generator = new ScriptGenerator();
        $this->assertInstanceOf(ScriptGeneratorInterface::class, $generator);
    }

    /**
     * @test
     */
    public function itThrowsExceptionIfNotValidProcessCycleProvided()
    {
        $this->expectExceptionMessage('Not Valid Process Cycle');
        $generator = new ScriptGenerator();
        $generator->generate([], 'sdasdsa');
    }

    /**
     * @test
     */
    public function itCanReturnAnArrayOfFileNames()
    {
        $generator = new ScriptGenerator();
        $file1 = 'update_clients_2017_10_10_1.sql';
        $generator->setFileName($file1);
        $file2 = 'update_clients_2017_10_10_2.sql';
        $generator->setFileName($file2);
        $filenames=$generator->getFileNames();
        $this->assertInternalType('array', $filenames);
        $this->assertEquals($file1, $filenames[0]);
        $this->assertEquals($file2, $filenames[1]);
    }

    /**
     * @test
     */
    public function itThrowsExceptionIfNotValidDataProvided()
    {
        $this->expectExceptionMessage('Empty or Invalid Data');
        $generator = new ScriptGenerator();
        $generator->generate([], 500);
        $generator1 = new ScriptGenerator();
        $generator1->generate('asdasdas', 500);
    }

    /**
     * @test
     */
    public function itCanSplitAnArrayOnChunksDependingOnProcessCycle()
    {
        $processCycle = 2;
        $data = [
            ['email' => 'tino1@pk.ge'],
            ['email' => 'tino2@pk.ge'],
            ['email' => 'tino3@pk.ge'],
            ['email' => 'tino4@pk.ge']
        ];
        $generator = new ScriptGenerator();


        $chunks = $generator->splitData($data, $processCycle);

        $this->assertInternalType('array', $chunks);
        $this->assertCount(2, $chunks[0]);
        $this->assertCount(2, $chunks[1]);
        $this->assertEquals('tino1@pk.ge', $chunks[0][0]['email']);
    }

    /**
     * @test
     */
    public function itCreatesAUniqueFilenameForEachDataChunk()
    {
        $today = new \DateTime("now");
        $generator = new ScriptGenerator();
        $actual = $generator->createFilename(1);

        $this->assertEquals('update_client_brand_'. $today->format('Y_m_d') . '_1.sql', $actual);
    }

    /**
     * @test
     */
    public function itFiltersArrayChunksAccordingToTargetBrand()
    {
        $generator = new ScriptGenerator();
        $data = [
            ['email' => 'costasbastas@gmail.com', 'target_brand' => 'xmbz'],
            ['email' => 'costasbastas@xm.com', 'target_brand' => 'xmtd'],
            ['email' => 'costasbastas@yahoo.com', 'target_brand' => 'xmbz'],
            ['email' => 'costasbastas@trading-point.com', 'target_brand' => 'xmtd'],
        ];

        $actual = $generator->filterData($data, 'xmbz');

        $this->assertInternalType('array', $actual);
        $this->assertEquals('costasbastas@gmail.com', $actual[0]['email']);
        $this->assertEquals('costasbastas@yahoo.com', $actual[2]['email']);

        $actual = $generator->filterData($data, 'xmtd');
        $this->assertEquals($data[1]['email'], $actual[1]['email']);
        $this->assertEquals($data[3]['email'], $actual[3]['email']);
    }

    /**
     * @test
     */
    public function itGeneratesSqlScript()
    {
        $generator = new ScriptGenerator();
        $brand = 'xmbz';
        $data = [
            ['email' => 'costasbastas@gmail.com', 'target_brand' => 'xmbz'],
            ['email' => 'costasbastas@xm.com', 'target_brand' => 'xmbz'],
            ['email' => 'costasbastas@yahoo.com', 'target_brand' => 'xmbz'],
            ['email' => 'costasbastas@trading-point.com', 'target_brand' => 'xmbz'],
        ];
        $actual = $generator->createScript($data, $brand);
        $expected = 'USE csvparser; ';
        $expected .= 'UPDATE clients ';
        $expected .= 'SET brand_id = ';
        $expected .= '(CASE email ';
        $expected .= 'WHEN email THEN '. "'". $brand . "' ";
        $expected .= 'END) ';
        $expected .= "WHERE brand_id = 'xm' ";
        $expected .= 'AND email IN (';
        foreach($data as $client) {
            $expected .= "'{$client['email']}',";
        }
        $expected = rtrim($expected, ',');
        $expected .= ');';
        $expected .= 'SELECT count(*) FROM clients ';
        $expected .= "WHERE brand_id = 'xm' ";
        $expected .= 'AND email IN (';
        foreach($data as $client) {
            $expected .= "'{$client['email']}',";
        }
        $expected = rtrim($expected, ',');
        $expected .= ');';

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function itSavesAScriptToDisk()
    {
        $generator = new ScriptGenerator();
        $data = [
            ['email' => 'costasbastas@gmail.com', 'target_brand' => 'xmbz'],
            ['email' => 'costasbastas@xm.com', 'target_brand' => 'xmtd'],
            ['email' => 'costasbastas@yahoo.com', 'target_brand' => 'xmbz'],
            ['email' => 'costasbastas@trading-point.com', 'target_brand' => 'xmtd'],
        ];
        $filename = 'var/sql_scripts/'. $generator->createFilename(1);
        $xmbz = $generator->filterData($data,'xmbz');
        $xmtd = $generator->filterData($data, 'xmtd');

        $xmbzScript = $generator->createScript($xmbz, 'xmbz');
        $xmtdScript = $generator->createScript($xmtd, 'xmtd');
        $generator->saveFile($filename, $xmbzScript, $xmtdScript);

        $this->assertFileExists($filename);
        $fs = new Filesystem();
        $fs->remove($filename);

    }


}