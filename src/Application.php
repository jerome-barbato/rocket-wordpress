<?php

namespace Rocket;

use Rocket\Helper\Route;
use Dflydev\DotAccessData\Data;

/**
 * Class Rocket Framework
 */
abstract class Application
{
    private $paths, $config, $routes;
    public static $instance;

    abstract protected function registerRoutes();

    private function definePaths(){
        $this->paths = [
            'config' => BASE_URI . '/config',
            'wp'     => BASE_URI . '/web/wp'
        ];
    }

    public static function getInstance()
    {
        if (self::$instance === null)
            return false;

        return self::$instance;
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
     * Define route manager
     */
    protected function route($pattern, $to = NULL)
    {
        $this->routes[$pattern] = new Route($pattern, $to);
        return $this->routes[$pattern];
    }


    /**
     * Define route manager
     */
    protected function page($template, $context)
    {
        return [$template, $context];
    }


    /**
     * Define route manager
     */
    public function solve($context)
    {
        if     ( is_embed() ) $type = 'embed';
        elseif ( is_404() ) $type = '404';
        elseif ( is_search() ) $type = 'search';
        elseif ( is_front_page() ) $type = '';
        elseif ( is_home() ) $type = '';
        elseif ( is_post_type_archive() ) $type = 'post_type_archive';
        elseif ( is_tax() ) $type = 'tax';
        elseif ( is_attachment() ) $type = 'attachment';
        elseif ( is_single() ) $type = 'single';
        elseif ( is_page() ) $type = 'page';
        elseif ( is_singular() ) $type = 'singular';
        elseif ( is_category() ) $type = 'category';
        elseif ( is_tag() ) $type = 'tag';
        elseif ( is_author() ) $type = 'author';
        elseif ( is_date() ) $type = 'date';
        elseif ( is_archive() ) $type = 'archive';
        elseif ( is_paged() ) $type = 'paged';
        else $type = '';

        $type = '/'.$type;

        if( isset($this->routes[$type] ) ){

            $this->routes[$type]->execute($context);
            return [$this->routes[$type]->page(), $this->routes[$type]->context()];
        }
        else
            return false;
    }


    /**
     * Rocket Constructor
     */
    public function __construct()
    {
        self::$instance = $this;

        $this->definePaths();
        $this->loadConfig();

        $this->registerRoutes();

        include $this->paths['wp'].'/wp-blog-header.php';
    }
}