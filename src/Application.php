<?php

namespace Rocket;

use Dflydev\DotAccessData\Data;

/**
 * Class Rocket Framework
 */
abstract class Application
{
    private $paths, $config;


    private function definePaths(){
        $this->paths = [
            'config' => BASE_URI . '/config'
        ];
    }



    /**
     * Load App configuration
     */
    private function loadConfig()
    {
        $data = array();

        foreach (array('global', 'wordpress', 'local') as $config) {
            $file = $this->paths['config'] . '/' . $config . '.yml';
            if (file_exists($file))
                $data = array_merge($data, \Spyc::YAMLLoad($file));
        }

        $this->config = new Data($data);
    }


    /**
     * Rocket Constructor
     */
    public function __construct()
    {
        $this->definePaths();
        $this->loadConfig();
    }
}