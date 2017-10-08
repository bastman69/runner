<?php

namespace AppBundle\Service;

use Symfony\Component\Filesystem\Filesystem;


/**
 * Class ScriptGenerator
 * @package AppBundle\Service
 */
class ScriptGenerator implements ScriptGeneratorInterface
{
    /**
     * @var array
     */
    protected $filenames = [];

    /**
     * It generates sql update scripts for sms_notifications table
     * @param array $data
     * @param int $processCycle
     * @throws \Exception
     * @return mixed
     */
    public function generate(array $data, $processCycle = 0)
    {
        $this->validate($data, $processCycle);
        $chunks = $this->splitData($data, $processCycle);
        $counter = 1;
        foreach ($chunks as $chunk) {
            $xmbz = $this->filterData($chunk, 'xmbz');
            $xmtd = $this->filterData($chunk, 'xmtd');
            //basic check if the filtered arrays have entries
            //and only then create sql script
            if (count($xmbz) > 0) {
                $xmbzScript = $this->createScript($xmbz, 'xmbz');
            }
            if (count($xmtd) > 0) {
                $xmtdScript = $this->createScript($xmtd, 'xmtd');
            }

            if (count($xmbz) > 0 || count($xmtd) > 0) {
                $filename = 'var/sql_scripts/'.$this->createFilename($counter);

                $this->saveFile($filename, $xmbzScript, $xmtdScript);

                $this->setFileName($filename);

                $counter++;
            }

        }

    }

    /**
     * It creates the file on var/sql_scripts folder
     * It uses the symfony filesystem class
     * @param string $filename
     * @param string $xmbzScript
     * @param string $xmtdScript
     */
    public function saveFile($filename, $xmbzScript = '', $xmtdScript = '')
    {
        $fs = new Filesystem();

        if (!$fs->touch($filename)) {
            $fs->mkdir('var/sql_scripts');
            $fs->touch($filename);
        };
        $fs->appendToFile($filename, $xmbzScript);
        $fs->appendToFile($filename, $xmtdScript);

    }

    /**
     * It filters the provided array by brand_id and returns the
     * filtered array back
     * @param array $clients
     * @param string $needle
     * @return array
     */
    public function filterData($clients, $needle)
    {
        return array_filter(
            $clients,
            function ($client) use ($needle) {
                return $client['target_brand'] == $needle;
            }
        );
    }

    /**
     * It creates a unique filename to used in the file creation
     * @param $counter
     * @return string
     */
    public function createFilename($counter)
    {
        $today = new \DateTime("now");

        return "update_client_brand_".$today->format('Y_m_d')."_".$counter.".sql";
    }

    /**
     * It returns an array of chunk arrays where the length
     * of each chunk equals the process cycle provided
     * @param $data
     * @param $processCycle
     * @return array
     */
    public function splitData($data, $processCycle)
    {
        return array_chunk($data, $processCycle);
    }

    /**
     * @param $filename
     * @return void
     */
    public function setFileName($filename)
    {
        $this->filenames[] = $filename;
    }

    /**
     * @return array
     */
    public function getFileNames()
    {
        return $this->filenames;
    }

    /**
     * It performs basic validations against the provided
     * data array and process cycle integer
     * @param $data
     * @param $processCycle
     * @throws \Exception
     */
    public function validate($data, $processCycle)
    {
        if (!is_numeric($processCycle)) {
            throw new \Exception('Not Valid Process Cycle');
        }
        if (empty($data) || !is_array($data)) {
            throw new \Exception('Empty or Invalid Data');
        }
    }

    /**
     * It Creates the sql queries that will be saved in the file later.
     * One update query and one select count(*)
     * @param $data
     * @param $brand
     * @return string
     */
    public function createScript($data, $brand)
    {
        $lines = $this->prepareLines($data);

        $script = 'USE csvparser; ';
        $script .= 'UPDATE clients ';
        $script .= 'SET brand_id = ';
        $script .= '(CASE email ';
        $script .= 'WHEN email THEN '."'".$brand."' ";
        $script .= 'END) ';
        $script .= "WHERE brand_id = 'xm' ";
        $script .= 'AND email IN (';
        $script .= $lines;
        $script .= ');';
        $script .= 'SELECT count(*) FROM clients ';
        $script .= "WHERE brand_id = 'xm' ";
        $script .= 'AND email IN (';
        $script .= $lines;
        $script .= ');';

        return $script;
    }

    /**
     * @param $data
     * @return string
     */
    public function prepareLines($data)
    {
        $lines = null;
        foreach ($data as $client) {
            $lines .= "'{$client['email']}',";
        }
        //removes the comma from last iteration
        $lines = rtrim($lines, ',');

        return $lines;
    }
}