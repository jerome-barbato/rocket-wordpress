<?php

namespace Rocket;

use Rocket\Application\ApplicationTrait;
use Rocket\Application\SingletonTrait;
use Rocket\Helper\Route, Rocket\Helper\ACF;
use Dflydev\DotAccessData\Data;

/**
 * Class Rocket Framework
 */
abstract class Application {

    // Use of cross-framework functions by extending traits
    use ApplicationTrait {
        loadConfig      as private k_LoadConfig;
        addTwigGlobal   as private k_AddTwigGlobal;
        definePaths     as protected k_definePaths;
        asset_url       as public;
        upload_url      as public;
    }
    use SingletonTrait;

    /**
     * @var string plugin domain name for translations
     */
    public static $domain_name = 'rocket_customer';
    private $ft_images_sizes;
    protected static $_instance;

    public static function run()
    {
        add_action('init', function() {
            new \Customer\Application();
        }, 1);
    }

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


        //$this->enable_plugins();

        // Automatically set Rocket theme
        $this->set_theme();

        // Register custom processes on Wordpress common functions
        add_action( 'init', array($this, 'register_filters') );

        // Add specific image sizes for thumbnails
        add_action('after_setup_theme', array($this, 'image_sizes'));

        // Removes or add pages
        add_action( 'admin_menu', array($this, 'clean_interface'));

        add_theme_support( 'post-thumbnails' );

        /*
        $this->add_menus();
        $this->add_post_types();
        //this->add_taxonomies();
        $this->add_option_pages();

        $this->registerRoutes();
        */

    }

    /**
     * Add or remove image sizes
     */
    public function image_sizes(){

        add_image_size('full-hd', 1920, 1080, true);
        $this->ft_images_sizes = get_intermediate_image_sizes();
    }


    /**
     * Adds or remove pages from menu admin.
     */
    public function clean_interface()
    {
        remove_menu_page('edit-comments.php' ); // Posts
        //remove_menu_page('edit.php' ); // Posts


        // Add excerpt support to pages
        add_post_type_support( 'page', 'excerpt' );
    }


    /**
     * Adds specific post types here
     */
    public function add_post_types(){

        foreach ( $this->config->get('post_types', []) as $slug => $data_post_type ){

            $data_post_type = new Data($data_post_type);

            $singular = $slug;
            $plurial = $slug.'s';
            $post_type = new CustomPostType(__(ucfirst($plurial), Application::$domain_name), $singular);

            $post_type->label_name(__($data_post_type->get('labels.name', ucfirst($plurial)), Application::$domain_name));
            $post_type->label_all_items(__($data_post_type->get('labels.all_items','All '.$plurial), Application::$domain_name));
            $post_type->label_singular_name(__($data_post_type->get('labels.singular_name',ucfirst($singular)), Application::$domain_name));
            $post_type->label_add_new_item(__($data_post_type->get('labels.add_new_item','Add a '.$singular), Application::$domain_name));
            $post_type->label_edit_item(__($data_post_type->get('labels.edit_item','Edit '.$singular), Application::$domain_name));
            $post_type->label_not_found(__($data_post_type->get('labels.not_found',ucfirst($singular).' not found'), Application::$domain_name));
            $post_type->label_search_items(__($data_post_type->get('labels.search_items','Search in '.$plurial), Application::$domain_name));
            $post_type->menu_icon($data_post_type->get('menu_icon','dashicons-media-default'));
            $post_type->setPublic($data_post_type->get('public', true));
            $post_type->has_archive($data_post_type->get('has_archive', false));
            $post_type->capability_type($data_post_type->get('capability_type', 'post'));
            $post_type->supports( $data_post_type->get('supports', ['title', 'editor', 'thumbnail']));
            $post_type->rewrite($data_post_type->get('rewrite', true));
            $post_type->exclude_from_search($data_post_type->get('exclude_from_search', true));
            $post_type->query_var($data_post_type->get('query_var', true));
        };

    }

    abstract protected function registerRoutes();

    private function definePaths($custom_paths = null){

        $this->k_definePaths($custom_paths);
        $this->paths['views'] = [ BASE_URI . '/web/views', __DIR__.'/../web/views' ];
        $this->paths['config']= BASE_URI . '/config';
        $this->paths['wp']= BASE_URI . '/web/wp';
    }


    public function register_filters()
    {

    }
    /**
     * Load App configuration
     */
    private function loadConfig()
    {

        $this->k_LoadConfig('wordpress');
    }


    /**
     * Define route manager
     */
    protected function route($pattern, $to = NULL)
    {
        $this->routes[$pattern] = new Route($pattern, $to);
        return $this->routes[$pattern];
    }

    protected function add_menus() {

        $menus = $this->config->get('menus');
        if (!empty($menus) && is_array($menus)) {
            foreach ($menus as $slug => $name) {
                new Menu($name, $slug);
            }
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

    private function add_option_pages()
    {

        if( function_exists('acf_add_options_page') ) {

            acf_add_options_page();

            foreach ( $this->config->get('options_page') as $name ) {

                acf_add_options_sub_page($name);
            }
        }
    }


    /**
     * Define meta theme as theme.
     */
    public function set_theme()
    {
        $current_theme = wp_get_theme();
        $meta_theme = 'rocket';

        if ($current_theme->get_stylesheet() != $meta_theme) {
            switch_theme($meta_theme);
        }
    }

    public function enable_plugins() {


        if ( ! class_exists( 'Timber' ) ) {

            add_action( 'admin_notices', function() {

                echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
            } );

            return;
        }
        // Require parent plugin
        /*if ( class_exists(ACF)! is_plugin_active( $this->paths['wp'] . '/advanced-custom-fields-pro/acf.php' ) and current_user_can( 'activate_plugins' ) ) {
            // Stop activation redirect and show error
            wp_die('Sorry, but this plugin requires the Parent Plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
        }*/
    }

    public function checkDependencies()
    {

    }
}