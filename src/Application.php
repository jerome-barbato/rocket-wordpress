<?php

namespace Rocket;

use Dflydev\DotAccessData\Data as DotAccessData;

use Rocket\Application\ApplicationTrait,
    Rocket\Application\SingletonTrait;

use Rocket\Helper\ACF;

use Rocket\Model\CustomPostType,
    Rocket\Model\Menu,
    Rocket\Model\Taxonomy,
    Rocket\Model\Router;

use Symfony\Component\Routing\Route as Route;

/**
 * Class Rocket Framework
 */
abstract class Application {


    // Use of cross-framework functions by extending traits
    use ApplicationTrait, SingletonTrait;


    /**
     * @var string plugin domain name for translations
     */
    public static $domain_name = 'default';
    protected $router;


    /**
     * Application Constructor
     */
    public function setup()
    {
        $this->definePaths();
        $this->loadConfig();


        // ******
        // Register post and taxonomies
        // ******

        $this->add_post_types();
        $this->add_taxonomies();


        // *******
        // Actions
        // *******

        add_action( 'init', array($this, 'register_filters'));

        // When viewing admin
        if( is_admin() ){

            // Set default theme
            add_action( 'init', function()
            {
                $this->set_theme();
                $this->set_permalink();
            });

            // Setup ACF Settings
            add_action( 'acf/init', array($this, 'acf_settings') );

            // Add specific image sizes for thumbnails
            add_action( 'after_setup_theme', array($this, 'register_image_sizes'));

            // Removes or add pages
            add_action( 'admin_menu', array($this, 'clean_interface'));

            //check loaded plugin
            add_action( 'plugins_loaded', array($this, 'checkDependencies'));

            $this->defineSupport();
            $this->add_menus();
            $this->add_option_pages();
        }
        else{

            $this->router = new Router();
            $this->registerRoutes();
        }
    }


    /**
     * Define rocket theme as default theme.
     */
    public function set_theme()
    {
        $current_theme = wp_get_theme();

        if ($current_theme->get_stylesheet() != 'rocket') {
            switch_theme('rocket');
        }
    }


    /**
     * Set permalink stucture
     */
    public function set_permalink()
    {
        global $wp_rewrite;

        $wp_rewrite->set_permalink_structure('/%postname%/');

        update_option( "rewrite_rules", FALSE );

        $wp_rewrite->flush_rules( true );
    }


    /**
     * Custom theme compatibilities according to created project.
     */
    protected function defineSupport()
    {
        add_theme_support( 'post-thumbnails' );
        add_post_type_support( 'page', 'excerpt' );
    }


    /**
     * Add or remove image sizes according to wordpress.yml image_sizes option
     * - <name>: <width> <height> <crop>
     */
    public function register_image_sizes()
    {
        foreach ( $this->config->get('image_sizes', []) as $name=>$size)
        {
            $size = explode(' ', $size);

            if( count($size) == 3 )
                add_image_size($name, intval($size[0]), intval($size[1]), intval($size[2]));
        };
    }


    /**
     * Adds or remove pages from menu admin.
     */
    public function clean_interface()
    {
        foreach ( $this->config->get('remove_menu_page', []) as $page) {

            remove_menu_page($page);
        }
    }


    /**
     * Adds specific post types here
     * @see CustomPostType
     */
    public function add_post_types()
    {
        foreach ( $this->config->get('post_types', []) as $slug => $data_post_type ){

            $data_post_type = new DotAccessData($data_post_type);

            $label = __(ucfirst($slug.'s'), Application::$domain_name);

            $post_type = new CustomPostType($label, $slug);
            $post_type->hydrate($data_post_type);
        };
    }


    /**
     * Adds Custom taxonomies
     * @see Taxonomy
     */
    public function add_taxonomies()
    {
        foreach ( $this->config->get('taxonomies', []) as $slug => $data_taxonomy ) {

            $data_taxonomy = new DotAccessData($data_taxonomy);
            $label = __(ucfirst($slug.'s'), Application::$domain_name);

            $taxonomy = new Taxonomy($label, $slug);
            $taxonomy->hydrate($data_taxonomy);
        }
    }


    protected function registerRoutes(){}


    /**
     * Register wp path
     */
    private function definePaths()
    {
        $this->paths = $this->getPaths();
        $this->paths['wp'] = BASE_URI . '/web/wp';
    }


    /**
     * Allows user to add specific process on Wordpress functions
     */
    public function register_filters()
    {
        add_filter('wp_get_attachment_url', function($rewrite){

            $rewrite = str_replace('/wp/wp-content/uploads', '/public/upload', $rewrite);
            return $rewrite;
        });

        add_filter('wp_calculate_image_srcset_meta', '__return_null');

        //implement in src/application
        //ex : add_filter( 'page_link', array($this, 'rewrite_common'), 10, 3);
    }


    /**
     * Load App configuration
     */
    private function loadConfig()
    {
        $this->config = $this->getConfig('wordpress');

        self::$domain_name = $this->config->get('domain_name');
    }


    /**
     * Create Menu instances from configs
     * @see Menu
     */
    protected function add_menus()
    {
        foreach ($this->config->get('menus', []) as $slug => $name)
        {
            new Menu($name, $slug);
        }
    }


    /**
     * Define route manager
     */
    protected function page($template, $context=false)
    {
        return [$template, $context];
    }


    /**
     * Get ACF Fields
     */
    public function acf_to_timber( $post_id )
    {
        $ACFHelper = new ACF( $post_id );
        return $ACFHelper->process();
    }


    /**
     * Add settings to acf
     */
    public function acf_settings()
    {
        acf_update_setting('google_api_key', $this->config->get('options.gmap_api_key', ''));
    }


    /**
     * Register route
     * @param $pattern
     * @param $controller
     * @return Route
     */
    protected function route($pattern, $controller)
    {
        return $this->router->add($pattern, $controller);
    }


    /**
     * Define route manager
     * @param $context
     * @return bool|mixed
     */
    public function solve($context)
    {
        return $this->router->solve($context);
    }


    /**
     * Add wordpress configuration 'options_page' fields as ACF Options pages
     */
    protected function add_option_pages()
    {
        if( function_exists('acf_add_options_page') )
        {
            acf_add_options_page();

            foreach ( $this->config->get('options_page', []) as $name )
            {
                acf_add_options_sub_page($name);
            }
        }
    }


    /**
     * Check if ACF and Timber are enabled
     */
    public function checkDependencies()
    {
        $notices = [];

        if ( !class_exists( 'Timber' ) )
            $notices [] = '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';

        if ( !class_exists( 'acf' ) )
            $notices[] = '<div class="error"><p>Advanced Custom Fields not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#acf' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';

        if( !empty($notices) )
        {
            add_action( 'admin_notices', function() use($notices)
            {
                echo implode('<br/>', $notices );
            });
        }
    }


    public function __construct()
    {
        if( !defined('WPINC') )
            include 'wp/wp-blog-header.php';
        else
           $this->setup();
    }
}

//Application::run();