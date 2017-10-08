<?php

namespace AppBundle\Service;


/**
 * Class CsvParser
 * @package AppBundle\Service
 */
class CsvParser implements CsvParserInterface
{

    /**
     * @param $filename
     * @return array|null
     * @throws \Exception
     */
    public function parse($filename)
    {
        if(!file_exists($filename)) throw new \Exception('File does not exist');
        $header = null;
        $data = null;
        if (($handle = fopen($filename, 'r')) !== FALSE) {
            while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                if(!$header) {
                    $header = $row;
                } else {
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }
}