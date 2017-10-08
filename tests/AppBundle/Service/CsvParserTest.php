<?php

namespace tests\AppBundle\Service;

use AppBundle\Service\CsvParser;
use AppBundle\Service\CsvParserInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CsvParserTest extends WebTestCase
{
    /**
     * @test
     */
    public function itImplementsCsvParserInterface()
    {
        $parser = new CsvParser();
        $this->assertInstanceOf(CsvParserInterface::class, $parser);
    }

    /**
     * @test
     */
    public function itThrowsAnExceptionIfTheProvidedFileIsInvalid()
    {
        $parser = new CsvParser();
        $this->expectExceptionMessage('File does not exist');
        $filename='sdad';
        $parser->parse($filename);

    }

    /**
     * @test
     */
    public function itReturnsAPhpArray()
    {
        $parser = new CsvParser();
        $filename = __DIR__. '/clients.csv';
        $actual = $parser->parse($filename);
        //die(var_dump($actual));
        $this->assertInternalType('array', $actual);
        $this->assertCount(210,$actual);
        $this->assertArrayHasKey('email', $actual[0]);
        $this->assertArrayHasKey('source_brand', $actual[0]);
        $this->assertArrayHasKey('target_brand', $actual[0]);
    }

}