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
     * @param array $data
     * @param int $processCycle
     * @throws \Exception
     * @return mixed
     */
    public function generate(array $data,  $processCycle = 0)
    {
        $this->validate($data, $processCycle);
        $chunks =$this->splitData($data, $processCycle);
        $counter = 1;
        foreach ($chunks as $chunk) {
            $xmbz = $this->filterData($chunk,'xmbz');
            $xmtd = $this->filterData($chunk, 'xmtd');

            if(count($xmbz)>0) $xmbzScript = $this->createScript($xmbz, 'xmbz');
            if(count($xmtd)>0) $xmtdScript = $this->createScript($xmtd, 'xmtd');

            if(count($xmbz)>0 || count($xmtd)>0) {
                $filename =  'var/sql_scripts/'. $this->createFilename($counter);

                $this->saveFile($filename, $xmbzScript, $xmtdScript);

                $this->setFileName($filename);

                $counter++;
            }

        }

    }

    public function saveFile($filename, $xmbzScript='', $xmtdScript='')
    {
        $fs = new Filesystem();

        if(!$fs->touch($filename)) {
            $fs->mkdir('var/sql_scripts');
            $fs->touch($filename);
        };
        $fs->appendToFile($filename, $xmbzScript);
        $fs->appendToFile($filename, $xmtdScript);

    }
    public function filterData($clients, $needle)
    {
        return array_filter($clients, function($client) use($needle){
            return $client['target_brand'] == $needle;
        });
    }

    public function createFilename($counter)
    {
        $today = new \DateTime("now");
        return "update_client_brand_". $today->format('Y_m_d')."_".$counter. ".sql";
    }

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

    public function createScript($data, $brand)
    {
        $lines = $this->prepareLines($data);

        $script = 'USE csvparser; ';
        $script .= 'UPDATE clients ';
        $script .= 'SET brand_id = ';
        $script .= '(CASE email ';
        $script .= 'WHEN email THEN '. "'". $brand . "' ";
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
        $lines = rtrim($lines, ',');

        return $lines;
    }
}