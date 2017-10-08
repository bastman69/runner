<?php

namespace AppBundle\Service;


/**
 * Interface ScriptGeneratorInterface
 * @package AppBundle\Service
 */
interface ScriptGeneratorInterface
{
    /**
     * @param array $data
     * @param int $processCycle
     * @return mixed
     */
    public function generate(array $data, $processCycle=0);

    /**
     * @param $filename string
     * @return void
     */
    public function setFilename($filename);

    /**
     * @return array
     */
    public function getFileNames();
}