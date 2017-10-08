<?php

namespace AppBundle\Service;


/**
 * Interface CsvParserInterface
 * @package AppBundle\Service
 */
interface CsvParserInterface
{
    /**
     * @param $filename
     * @return array
     */
    public function parse($filename);
}