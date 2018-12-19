<?php

namespace Rocket\Model;

use FrontBundle\Application;

use Rocket\Traits\SingletonTrait,
	Rocket\Provider\WooCommerceProvider;

use Timber\Timber,
    Timber\Site,
    Timber\Menu as TimberMenu;


class Theme extends Site
{
    use SingletonTrait;

    public $theme_name = 'rocket';
	private $app;

    public function __construct()
    {
        parent::__construct();

        add_filter('timber_context', [$this, 'addToContext']);
        add_filter('get_twig', [$this, 'addToTwig']);
	    add_action('wp_head', [$this, 'headAction']);
	    add_action('wp_footer', [$this, 'footerAction']);

	    /** @var Application $app */
	    $this->app = Application::getInstance();
    }


    public function headAction()
    {
	    if( WP_DEBUG )
		    Timber::render( 'views/header.debug.twig', [
		    	'config'=>$this->app->config->export()
		    ]);
    }


    public function footerAction()
    {
	    if( WP_DEBUG )
		    Timber::render( 'views/footer.debug.twig', [
		    	'config'=>$this->app->config->export(),
			    'environment' => $this->app->config->get('environment', 'production'),
			    'last_update' => strtotime(shell_exec('git log -1 --format=%cd')),
			    'base_url' => get_option('home'),
			    'cookies' => $_COOKIE,
			    'host' => 'http://' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's':'') . $_SERVER['HTTP_HOST']
	    ]);
    }


    public function addToContext($context)
    {
        $language = explode('-', get_bloginfo('language'));

        if( function_exists('wpml_get_active_languages_filter') )
            $languages = wpml_get_active_languages_filter('','skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
        else
            $languages = [];

        if( isset($_REQUEST['q']) )
        	unset($_REQUEST['q']);

        $context = array_merge($context, [

            'project' => [
                'name'        => get_bloginfo('name'),
                'description' => get_bloginfo('description')
            ],
            'debug'            => WP_DEBUG,
            'environment'      => $this->app->config->get('environment', 'production'),
            'locale'           => count($language) ? $language[0] : 'en',
            'languages'        => $languages,
            'is_admin'         => current_user_can('manage_options'),
            'body_class'       => get_bloginfo('language') . ' ' . implode(' ', get_body_class()),
            'is_child_theme'   => is_child_theme(),
            'base_url'         => get_bloginfo('url'),
            'maintenance_mode' => wp_maintenance_mode(),
            'ajax_url'         => admin_url( 'admin-ajax.php' ),
            'noindex'          => !empty($_REQUEST)
        ]);

        $menus = get_registered_nav_menus();
        $context['menus'] = [];

        foreach ( $menus as $location => $description )
            $context['menus'][$location] = new TimberMenu($location);

        if (class_exists('WooCommerce'))
        {
            $wcProvider = WooCommerceProvider::getInstance();
            $wcProvider->globalContext($context);
        }

        // Rocket compatibility
        $context['system']   = [
        	'head' => $context['wp_head'],
	        'footer' => $context['wp_footer']
	    ];

        $context['page_title']  = empty($context['wp_title'])?get_bloginfo('name'):$context['wp_title'];

	    $this->app->initContext();

        return $context;
    }


    public function addToTwig($twig)
    {
        if ( class_exists( '\\FrontBundle\\Helper\\TwigHelper' ) )
            $twig->addExtension( new \FrontBundle\Helper\TwigHelper( get_option('home'), WP_REMOTE ) );

        return $twig;
    }


    public function run() {

        try {

            if (class_exists('Timber')) {

                Timber::$locations = BASE_URI . '/app/views/';
                $context = Timber::get_context();

                if( $this->app ) {

	                $route = false;

	                //clean context
	                unset($context['posts'], $context['request'], $context['theme'], $context['wp_head'], $context['wp_footer'], $context['wp_title']);

	                $this->app->setContext($context);

	                if (!is_404())
		                $route = $this->app->solve();

	                if (!$route)
		                $route = $this->app->getErrorPage(404);

	                $page = $route[0];
	                $context = (count($route) > 1 and is_array($route[1])) ? array_merge($context, $route[1]) : $context;

	                if( WP_DEBUG_TWIG && strpos($page, '.css') == -1 && strpos($page, '.json') == -1 )
		                echo "<!-- page/.$page. -->\n";

	                Timber::render('page/' . $page, $context);
                }
                else {

	                wp_redirect(wp_login_url());
                }
            }

        } catch (Error $exception) {

            echo    "<h1>We are very sorry but this website is currently not available</h1>" .
                "<hr>" . "<p>Message : </p><pre>" . $exception->getMessage() . "</pre>";
        }
    }
}
