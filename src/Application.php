<?php

namespace Rocket;

use Dflydev\DotAccessData\Data as DotAccessData;

use Rocket\Traits\ApplicationTrait,
    Rocket\Traits\SingletonTrait;

use Rocket\Model\CustomPostType,
    Rocket\Model\Menu,
    Rocket\Model\Taxonomy,
    Rocket\Model\Router,
	Rocket\Model\PageTemplater,
	Rocket\Model\Terms;

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
    protected $router, $global_context, $class_loader;
    public $remote_url;


	/**
	 * Set context
	 * @param $context
	 */
	public function setContext($context)
	{
		$this->global_context = $context;
	}


    /**
     * Get context
     */
	protected function getContext($context){ return []; }


    /**
     * Get context
     */
	protected function getArchivePage($post_type){

		return $this->config->get('post_types.'.$post_type.'.has_archive');
	}


    /**
     * Application Constructor
     */
    public function setup()
    {
    	if( defined('WP_INSTALLING') and WP_INSTALLING )
		    return;

        $this->definePaths();
        $this->loadConfig();

        $this->registerFilters();

        // Global init action
        add_action( 'init', function(){

	        $this->addPostTypes();
	        $this->addTaxonomies();
	        $this->addMenus();

	        $this->init();
        });


        // When viewing admin
        if( is_admin() )
        {
            // Set default theme
            add_action( 'init', function()
            {
                if( WP_REMOTE )
                    wp_redirect(WP_REMOTE.'/edition/wp-admin/');

                $this->setTheme();
                $this->setPermalink();
                $this->addOptionPages();
            });

            // Setup ACF Settings
            add_action( 'acf/init', [$this, 'ACFInit'] );

            // Remove image sizes for thumbnails
            add_filter( 'intermediate_image_sizes_advanced', [$this, 'intermediateImageSizesAdvanced'] );

	        add_filter( 'wp_terms_checklist_args', [Terms::getInstance(), 'wp_terms_checklist_args'] );

            // Removes or add pages
            add_action( 'admin_menu', [$this, 'adminMenu']);
	        add_action( 'admin_footer', [$this, 'adminFooter'] );
	        add_action( 'admin_init', [$this, 'adminInit'] );

            //check loaded plugin
            add_action( 'plugins_loaded', [$this, 'pluginsLoaded']);

            $this->defineSupport();
        }
        else
        {
            add_action( 'after_setup_theme', [$this, 'afterSetupTheme']);
            add_action( 'wp_footer', [$this, 'wpFooter']);

            $this->router = new Router();
            $this->router->setLocale(get_locale());

            $this->registerRoutes();
        }

        $this->registerActions();
    }


    /**
     * Unset thumbnail image
     */
    public function intermediateImageSizesAdvanced($sizes)
    {
        unset($sizes['medium'], $sizes['medium_large'], $sizes['large']);
        return $sizes;
    }


    /**
     * Define rocket theme as default theme.
     */
    public function setTheme()
    {
        $current_theme = wp_get_theme();

        if ($current_theme->get_stylesheet() != 'rocket')
            switch_theme('rocket');
    }


    /**
     * Define rocket theme as default theme.
     */
    public function defineCache()
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
    public function afterSetupTheme()
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
    public function wpFooter()
    {
        wp_deregister_script( 'wp-embed' );
    }


    /**
     * Set permalink stucture
     */
    public function setPermalink()
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
        if( $this->config->get('theme-support.post-thumbnails') )
            add_theme_support( 'post-thumbnails' );

	    if( $this->config->get('theme-support.woocommerce') )
		    add_theme_support( 'woocommerce' );

        add_post_type_support( 'page', 'excerpt' );
    }



    /**
     * Adds or remove pages from menu admin.
     */
    public function adminMenu()
    {
    	//clean interface
        foreach ( $this->config->get('remove_menu_page', []) as $menu)
        {
            remove_menu_page($menu);
        }

	    remove_submenu_page('themes.php', 'themes.php');

    	//clean interface
        foreach ( $this->config->get('remove_submenu_page', []) as $menu=>$submenu)
        {
	        remove_submenu_page($menu, $submenu);
        }
    }


    /**
     * Adds specific post types here
     * @see CustomPostType
     */
    public function addPostTypes()
    {
        foreach ( $this->config->get('post_types', []) as $slug => $data )
        {
            $data = new DotAccessData($data);

            $label = __(ucfirst($this->config->get('taxonomies.'.$slug.'.name', $slug.'s')), Application::$domain_name);

            $post_type = new CustomPostType($label, $slug);
            $post_type->hydrate($data);
            $post_type->register();
        };
    }


    /**
     * Adds Custom taxonomies
     * @see Taxonomy
     */
    public function addTaxonomies()
    {
        foreach ( $this->config->get('taxonomies', []) as $slug => $data )
        {
            $data = new DotAccessData($data);
            $label = __(ucfirst( $this->config->get('taxonomies.'.$slug.'.name', $slug.'s')), Application::$domain_name);

            $taxonomy = new Taxonomy($label, $slug);
            $taxonomy->hydrate($data);
            $taxonomy->register();
        }
    }


    protected function registerRoutes() {}
	protected function registerActions() {}
	public function adminFooter() {}
	public function initContext() {}


	/**
	 * change editor capabilities
	 */
	public function adminInit()
	{
		$role_object = get_role( 'editor' );

		if( !$role_object->has_cap('edit_theme_options') )
			$role_object->add_cap( 'edit_theme_options' );
	}


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
     * @param $value
     * @return mixed
     */
    public function rewriteUploadURL($value, $replace=false)
    {
        if( $replace and WP_REMOTE )
            $value = str_replace(WP_HOME, WP_REMOTE, $value);

        $value = str_replace('/edition/wp-content/uploads', '/app/uploads', $value);

        return $value;
    }


    /**
     * @param $path
     * @return mixed
     */
    public function checkImage($path)
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
    public function registerFilters()
    {
	    add_filter('posts_request', [$this, 'postsRequest'] );

	    add_filter('woocommerce_template_path', function(){ return '../../../../../src/Woocommerce/'; });
	    add_filter('woocommerce_enqueue_styles', '__return_empty_array' );

	    add_filter('rewrite_upload_url', function($value){ return $this->rewriteUploadURL($value, true); });
        add_filter('timber/image/new_url', [$this, 'rewriteUploadURL']);
        add_filter('timber/image/src', [$this, 'checkImage']);

        add_filter('acf/settings/save_json', function(){ return BASE_URI.'/app/config/acf'; });
        add_filter('acf/settings/load_json', function(){ return [BASE_URI.'/app/config/acf']; });

	    add_filter('timber/post/get_preview/read_more_link', '__return_null' );
        add_filter('wp_calculate_image_srcset_meta', '__return_null');

        // Handle /edition in url
	    add_filter('option_siteurl', [$this, 'optionSiteURL'] );
	    add_filter('network_site_url', [$this, 'networkSiteURL'] );

        if( $jpeg_quality = $this->config->get('jpeg_quality') )
            add_filter( 'jpeg_quality', function() use ($jpeg_quality){ return $jpeg_quality; });

        //implement in src/application
        //ex : add_filter( 'page_link', [$this, 'rewrite_common'), 10, 3);
    }


	/**
	 * Create Menu instances from configs
	 * @see Menu
	 */
	public function postsRequest($input)
	{
		if( $this->config->get('debug.show_query'))
			var_dump($input);

		return $input;
	}


    /**
     * Add edition folder to option url
     */
    public function networkSiteURL($url)
    {
	    if( strpos($url,'/edition') === false )
		    return str_replace('/wp-admin', '/edition/wp-admin', $url);
	    else
		    return $url;
    }


    /**
     * Add edition folder to option url
     */
    public function optionSiteURL($url)
    {
        return strpos($url, 'edition') === false ? $url.'/edition' : $url;
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
    public function addMenus()
    {
        foreach ($this->config->get('menus', []) as $slug => $name)
        {
            new Menu($name, $slug);
        }
    }


    /**
     * Init handler
     * @see Menu
     */
    public function init(){}


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
	 * Return json data / Silex compatibility
	 * @param $data
	 * @return bool
	 */
    protected function json($data)
    {
        wp_send_json($data);

        return true;
    }


    /**
     * Add settings to acf
     */
    public function ACFInit()
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
	 * Register route
	 * @param $id
	 * @param $controller
	 * @param bool $no_private
	 */
    protected function action($id, $controller, $no_private=true)
    {
	    add_action( 'wp_ajax_'.$id, $controller );

	    if( $no_private )
		    add_action( 'wp_ajax_nopriv_'.$id, $controller );
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
    protected function addOptionPages()
    {
        if( function_exists('acf_add_options_page') )
        {
            acf_add_options_page();

            foreach ( $this->config->get('options_page', []) as $name )
            {
            	if( isset($name['menu_slug']) )
		            $name['menu_slug'] = 'acf-options-'.$name['menu_slug'];

                acf_add_options_sub_page($name);
            }
        }
    }


    /**
     * Check if ACF and Timber are enabled
     */
    public function pluginsLoaded()
    {
	    new PageTemplater($this->config->get('page_templates', []));

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

        $this->defineCache();
    }


    public function __construct($autoloader=false)
    {
        $this->class_loader = $autoloader;
        $this->global_context = [];

        if( !defined('WPINC') )
            include CMS_URI.'/wp-blog-header.php';
        else
            $this->setup();
    }
}
