<?php

namespace Rocket;

use Dflydev\DotAccessData\Data as DotAccessData;

use Rocket\Application\ApplicationTrait, Rocket\Application\SingletonTrait;
use Rocket\Helper\Route, Rocket\Helper\ACF;
use Rocket\Model\CustomPostType,  Rocket\Model\Menu, Rocket\Model\Taxonomy;
use Rocket\Model\Theme;
use Timber\Timber;

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
    protected $routes;


    /**
     * Application Constructor
     */
    protected function __construct()
    {

        $this->definePaths();
        $this->loadConfig();
        $this->checkDependencies();

        // *******
        // Actions
        // *******

        // Defines settings for ACF Custom Fields
        add_action('acf/init', array($this, 'acf_settings') );

        add_action( 'init', function(){

            // Load theme : todo: try to find an add_action('timber/init'...
            if ( class_exists( 'Timber' ) )
                Theme::getInstance();

            // Register custom processes on Wordpress common functions
            $this->register_filters();
        });

        // Add specific image sizes for thumbnails
        add_action( 'after_setup_theme', array($this, 'register_image_sizes'));

        // Removes or add pages
        add_action( 'admin_menu', array($this, 'clean_interface'));

        $this->defineSupport();

        $this->add_menus();
        $this->add_post_types();
        $this->add_taxonomies();
        $this->add_option_pages();

        $this->registerRoutes();
    }


    /**
     * Custom theme compatibilities according to created project.
     */
    protected function defineSupport(){

        add_theme_support( 'post-thumbnails' );
        add_post_type_support( 'page', 'excerpt' );
    }


    /**
     * Add or remove image sizes according to wordpress.yml image_sizes option
     * - <name>: <width> <height> <crop>
     */
    public function register_image_sizes(){

        foreach ( $this->config->get('image_sizes', []) as $name=>$size) {

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
    public function add_post_types(){

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


    abstract protected function registerRoutes();

    /**
     * Register wp path
     */
    private function definePaths(){

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
     * Define route manager
     */
    protected function route($pattern, $to = NULL)
    {
        $this->routes[$pattern] = new Route($pattern, $to);
        return $this->routes[$pattern];
    }


    /**
     * Create Menu instances from configs
     * @see Menu
     */
    protected function add_menus() {

        foreach ($this->config->get('menus', []) as $slug => $name)
        {
            new Menu($name, $slug);
        }
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
    public function acf_to_timber( $post_id )
    {
        $ACFHelper = new ACF( $post_id );
        return $ACFHelper->process();
    }


    /**
     * Add settings to acf
     */
    public function acf_settings() {

        acf_update_setting('google_api_key', $this->config->get('options.gmap_api_key', ''));
    }


    public function get_current_url() {

        $current_url = trim(esc_url_raw(add_query_arg([])), '/');
        $home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');

        if ($home_path && strpos($current_url, $home_path) === 0)
            $current_url = trim(substr($current_url, strlen($home_path)), '/');

        return '/'.$current_url;
    }

    /**
     * Define route manager
     */
    public function solve($context)
    {

        $current_url = $this->get_current_url();
        
        $type = '/'.$type;

        if( isset($this->routes[$type] ) ){

            $this->routes[$type]->execute($context);
            return [$this->routes[$type]->page(), $this->routes[$type]->context()];
        }
        else
            return false;
    }


    /**
     * Add wordpress configuration 'options_page' fields as ACF Options pages
     */
    protected function add_option_pages()
    {

        if( function_exists('acf_add_options_page') ) {

            acf_add_options_page();

            foreach ( $this->config->get('options_page') as $name ) {

                acf_add_options_sub_page($name);
            }
        }
    }


    /**
     * Check if ACF and Timber is enabled
     */
    public function checkDependencies()
    {
        $notices = [];

        if ( !class_exists( 'Timber' ) )
            $notices [] = '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';

        if ( !class_exists( 'acf' ) )
            $notices[] = '<div class="error"><p>Advanced Custom Fields not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#acf' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';

        if( !empty($notices) ){

            add_action( 'admin_notices', function() use($notices){

                echo implode('<br/>', $notices );
            });
        }
    }


    public static function run()
    {

        add_action('init', function() {
            new \Customer\Application();
        }, 1);
    }
}