<?php

namespace Rocket\Model;
use Customer\Application;
use Rocket\Application\SingletonTrait;
use Timber\Post,
    Timber\Timber,
    Timber\Site,
    Timber\Menu as TimberMenu;


class Theme extends Site
{
    use SingletonTrait;

    public $theme_name = 'rocket';


    public function __construct()
    {
        Timber::$dirname = '../../../../web/views';

        parent::__construct();

        add_filter('timber_context', array($this, 'add_to_context'));
        add_filter('get_twig', array($this, 'add_to_twig'));
    }


    public function add_to_context($context)
    {
        $language = explode('-', get_bloginfo('language'));

        if( function_exists('wpml_get_active_languages_filter') )
            $languages = wpml_get_active_languages_filter('','skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str');
        else
            $languages = [];

        $context = array_merge($context, array(

            'project' => array(
                'name'        => get_bloginfo('name'),
                'description' => get_bloginfo('description')
            ),
            'debug'          => WP_DEBUG,
            'environment'    => WP_DEBUG ? 'development' : 'production',
            'locale'         => count($language) ? $language[0] : 'en',
            'languages'      => $languages,
            'is_admin'       => current_user_can('manage_options'),
            'body_class'     => get_bloginfo('language') . ' ' . implode(' ', get_body_class()),
            'is_child_theme' => is_child_theme(),
            'base_url'       => get_bloginfo('template_url')
        ));

        $menus = get_registered_nav_menus();
        $context['menus'] = [];

        foreach ( $menus as $location => $description ) {

            $context['menus'][$location] = new TimberMenu($location);
        }

        if( function_exists('get_fields') )
            $context['options'] = get_fields('options');


        // Rocket compatibility
        $context['head']   = $context['wp_head'];
        $context['footer'] = $context['wp_footer'];
        $context['page_title']  = empty($context['wp_title'])?get_bloginfo('name'):$context['wp_title'];

        return $context;
    }


    public function add_to_twig($twig)
    {
        include BASE_URI . '/src/Helper/Twig.php';

        $twig->addExtension(new \Customer\Helper\Twig(get_bloginfo('url')));
        return $twig;
    }


    public function run() {

        try {

            if (class_exists('Timber')) {

                Timber::$locations = BASE_URI . '/app/views/';
                $context = Timber::get_context();

                /** @var Application $app */
                $app = Application::getInstance();

                if( $app ){

                    $post = new Post();

                    $context['post'] = $post;
                    $context['post_objects'] = $app->acf_to_timber( $post->ID );

                    if( !is_404() and $route = $app->solve($context) ){

                        $page = $route[0];
                        $context = (count($route)>1 and is_array($route[1])) ? $route[1]: $context;

                        Timber::render( 'page/'.$page, $context );
                    }
                    else{

                        $context['code'] = 404;
                        Timber::render( 'page/error.html.twig', $context );
                    }
                }
                else{

                    wp_redirect( wp_login_url() );
                }
            }

        } catch (Error $exception) {

            echo    "<h1>We are very sorry but this website is currently not available</h1>" .
                "<hr>" . "<p>Message : </p><pre>" . $exception->getMessage() . "</pre>";
        }
    }
}