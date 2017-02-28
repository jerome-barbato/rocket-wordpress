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
use Timber\Image;
use Timber\ImageHelper;

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
    protected $router, $context;
    public $remote_url;


    /**
     * Set context
     * @param $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }


    /**
     * Get context
     */
    public function getContext()
    {
        return $this->context;
    }


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
        $this->register_filters();


        // Global init action
        add_action( 'init', [$this, 'add_menus']);


        // When viewing admin
        if( is_admin() )
        {
            // Set default theme
            add_action( 'init', function()
            {
                if( WP_REMOTE )
                    wp_redirect(WP_REMOTE.'/edition/wp-admin/');

                $this->set_theme();
                $this->set_permalink();
                $this->add_option_pages();
            });

            // Setup ACF Settings
            add_action( 'acf/init', [$this, 'acf_settings'] );

            // Remove image sizes for thumbnails
            add_filter('intermediate_image_sizes_advanced', [$this, 'remove_image_sizes'] );

            // Removes or add pages
            add_action( 'admin_menu', [$this, 'clean_interface']);

            //check loaded plugin
            add_action( 'plugins_loaded', [$this, 'plugin_loaded']);

            $this->defineSupport();
        }
        else
        {
            add_action('after_setup_theme', [$this, 'clean_header']);
            add_action('wp_footer', [$this, 'clean_footer']);

            $this->router = new Router();
            $this->router->setLocale(get_locale());

            $this->registerRoutes();
        }
    }


    /**
     * Unset thumbnail image
     */
    public function remove_image_sizes($sizes)
    {
        unset($sizes['medium'], $sizes['medium_large'], $sizes['large']);
        return $sizes;
    }


    /**
     * Define rocket theme as default theme.
     */
    public function set_theme()
    {
        $current_theme = wp_get_theme();

        if ($current_theme->get_stylesheet() != 'rocket')
            switch_theme('rocket');
    }


    /**
     * Define rocket theme as default theme.
     */
    public function define_cache()
    {
        $cache = get_option('cache');
        $cache_options = $this->config->get('cache');

        // Cache_Enabler options
        // expires, new_post, new_comment, compress, webp, excl_ids, minify_html

        if( defined('WP_CACHE') and WP_CACHE and $cache_options and class_exists('Cache_Enabler') )
        {
            if( isset($cache_options['http']) and $cache_options['http'] != $cache['expires'] )
            {
                $cache_options['expires'] = $cache_options['http']/3600;
                $cache_options['new_post'] = 1;
                $cache = array_merge($cache, $cache_options);
                update_option('cache', $cache);
            }
        }
    }


    /**
     * Clean WP Head
     */
    public function clean_header()
    {
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'print_emoji_detection_script', 7 );
        remove_action('wp_print_styles', 'print_emoji_styles' );
        remove_action('wp_head', 'rest_output_link_wp_head');
        remove_action('wp_head', 'wp_resource_hints', 2 );
        remove_action('wp_head', 'wp_oembed_add_discovery_links');
    }


    /**
     * Clean WP Footer
     */
    public function clean_footer()
    {
        wp_deregister_script( 'wp-embed' );
    }


    /**
     * Set permalink stucture
     */
    public function set_permalink()
    {
        global $wp_rewrite;

        $wp_rewrite->set_permalink_structure('/%postname%');

        update_option( "rewrite_rules", FALSE );

        $wp_rewrite->flush_rules( true );
    }


    /**
     * Custom theme compatibilities according to created project.
     */
    protected function defineSupport()
    {
        if( $this->config->get('post-thumbnails') )
            add_theme_support( 'post-thumbnails' );

        add_post_type_support( 'page', 'excerpt' );
    }



    /**
     * Adds or remove pages from menu admin.
     */
    public function clean_interface()
    {
        foreach ( $this->config->get('remove_menu_page', []) as $page)
        {
            remove_menu_page($page);
        }
    }


    /**
     * Adds specific post types here
     * @see CustomPostType
     */
    public function add_post_types()
    {
        foreach ( $this->config->get('post_types', []) as $slug => $data_post_type )
        {
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
        foreach ( $this->config->get('taxonomies', []) as $slug => $data_taxonomy )
        {
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
        $this->paths['wp'] = CMS_URI;

        if( !defined('WP_REMOTE') and is_blog_installed())
        {
            global $wpdb;
            $remote = preg_replace('/\/edition$/', '', $wpdb->get_var( "SELECT `option_value` FROM $wpdb->options WHERE `option_name` = 'siteurl'" ));

            define('WP_REMOTE', $remote!=WP_HOME?$remote:false);
        }
    }


    /**
     * Detect active plugin
     * @param $plugin
     * @return bool
     */
    private function is_active($plugin )
    {
        $network_active = false;

        if ( is_multisite() )
        {
            $plugins = get_site_option( 'active_sitewide_plugins' );
            if ( isset( $plugins[$plugin] ) )
                $network_active = true;
        }

        return in_array( $plugin, get_option( 'active_plugins' ) ) || $network_active;
    }


    /**
     * @param $value
     * @return mixed
     */
    public function get_upload_url($value, $replace=false)
    {
        if( $replace and WP_REMOTE )
            $value = str_replace(WP_HOME, WP_REMOTE, $value);

        $value = str_replace('/app/cms/uploads', '/upload', $value);
        $value = str_replace('/edition/wp-content/uploads', '/upload', $value);

        return $value;
    }


    /**
     * @param $path
     * @return mixed
     */
    public function check_image($path)
    {
        if( WP_REMOTE )
        {
            $base   = str_replace(WP_HOME, '', $path);
            $file   = BASE_URI.$base;
            $remote = WP_REMOTE.$base;

            if( !file_exists($file) )
            {
                $dir = dirname($file) ;

                if( !is_dir($dir) )
                    mkdir($dir, 0777, true);

                file_put_contents($file, file_get_contents($remote));
            }
        }

        return $path;
    }


    /**
     * Allows user to add specific process on Wordpress functions
     */
    public function register_filters()
    {
        add_filter('rewrite_upload_url', function($value){ return $this->get_upload_url($value, true); });
        add_filter('timber/image/new_url', [$this, 'get_upload_url']);
        add_filter('timber/image/src', [$this, 'check_image']);

        add_filter('acf/settings/save_json', function(){ return BASE_URI.'/app/resources/acf'; });
        add_filter('acf/settings/load_json', function(){ return [BASE_URI.'/app/resources/acf']; });

        add_filter('wp_calculate_image_srcset_meta', '__return_null');

        if( $jpeg_quality = $this->config->get('jpeg_quality') )
            add_filter( 'jpeg_quality', function() use ($jpeg_quality){ return $jpeg_quality; });

        //implement in src/application
        //ex : add_filter( 'page_link', [$this, 'rewrite_common'), 10, 3);
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
    public function add_menus()
    {
        foreach ($this->config->get('menus', []) as $slug => $name)
        {
            new Menu($name, $slug);
        }
    }


    /**
     * Define route manager
     * @param $template
     * @param bool $context
     * @return array
     */
    protected function page($template, $context=false)
    {
        return [$template, $context];
    }


    /**
     * Get ACF Fields
     * @param $post_id
     * @return array
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
     * @return bool|mixed
     */
    public function solve()
    {
        return $this->router->solve();
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
    public function plugin_loaded()
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

        $this->define_cache();
    }


    public function __construct()
    {
        $this->context = [];

        if( !defined('WPINC') )
            include CMS_URI.'/wp-blog-header.php';
        else
           $this->setup();
    }
}
