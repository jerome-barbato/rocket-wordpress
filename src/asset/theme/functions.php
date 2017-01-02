<?php


if ( ! class_exists( 'Timber' ) ) {

    add_action( 'admin_notices', function() {

        echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
    } );

    return;
}


use Timber\Timber,
    Timber\Site,
    Timber\Menu;


if (!defined('BASE_URI'))
    define('BASE_URI', str_replace('/vendor/metabolism/rocket-wordpress', '', dirname(__DIR__)));


//path is relative from theme
Timber::$dirname = '../../../../web/views';


class Theme extends Site
{

    function __construct()
    {

        parent::__construct();

        add_filter('timber_context', array($this, 'add_to_context'));
        add_filter('get_twig', array($this, 'add_to_twig'));
        add_filter('wp_calculate_image_srcset_meta', '__return_null');
        add_filter('wp_get_attachment_url',  array($this, 'rewrite_url'));

        add_theme_support('post-thumbnails');
    }


    function rewrite_url($rewrite)
    {
        $rewrite = str_replace('/wp/wp-content/uploads', '/public/upload', $rewrite);
        return $rewrite;
    }


    function add_to_context($context)
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

            $context['menus'][$location] = new Menu($location);
        }

        if( function_exists('get_fields') )
            $context['options'] = get_fields('options');


        // Rocket compatibility
        $context['head']   = $context['wp_head'];
        $context['footer'] = $context['wp_footer'];
        $context['title']  = $context['wp_title'];

        return $context;
    }


    function add_to_twig($twig)
    {
        include BASE_URI . '/src/Helper/Twig.php';

        $twig->addExtension(new \Customer\Helper\Twig(get_bloginfo('url')));
        return $twig;
    }
}

new Theme();