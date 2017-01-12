<?php

namespace Rocket\Model;
use Rocket\Application\SingletonTrait;
use Timber\Timber, Timber\Site, Timber\Menu as TimberMenu;


class Theme extends Site
{
    use SingletonTrait;

    protected static $_instance;
    public $theme_name = 'rocket';

    public function __construct($autodeclare = true)
    {
        if ( !class_exists( 'Timber' ) )
            return;

        Timber::$dirname = '../../../../web/views';

        parent::__construct();

        if ($autodeclare)
            $this->set_theme();

        //add_filter('timber_context', array($this, 'add_to_context'));
        //add_filter('get_twig', array($this, 'add_to_twig'));
    }



    /**
     * Define meta theme as theme.
     */
    public function set_theme()
    {
        $current_theme = wp_get_theme();

        if ($current_theme->get_stylesheet() != $this->theme_name) {
            switch_theme($this->theme_name);
        }
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

            $context['menus'][$location] = new TimberMenu($location);
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