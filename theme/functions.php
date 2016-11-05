<?php


if ( ! class_exists( 'Timber' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="error"><p>Timber not activated. Make sure you activate the plugin in <a href="' . esc_url( admin_url( 'plugins.php#timber' ) ) . '">' . esc_url( admin_url( 'plugins.php' ) ) . '</a></p></div>';
    } );
    return;
}


use Timber\Timber;

if (!defined('BASE_URI'))
    define('BASE_URI', str_replace('/vendor/metabolism/rocket-wordpress', '', dirname(__DIR__)));


//path is relative from theme
Timber::$dirname = '../../../../web/views';

class Site extends TimberSite
{

    function __construct()
    {

        parent::__construct();

        add_filter('timber_context', array($this, 'add_to_context'));
        add_filter('get_twig', array($this, 'add_to_twig'));
        add_filter('the_permalink', array($this, 'edit_the_permalink'));

        add_theme_support('post-thumbnails');

        $this->register_option_pages();
    }


    function edit_the_permalink($url){
        return str_replace('/wp/', '', $url);
    }

    function register_post_types()
    {
    }


    function register_menu()
    {
    }

    function register_taxonomies()
    {
    }

    function add_to_context($context)
    {

        $language = explode('-', get_bloginfo('language'));
        $languages = [];//todo:$this->parse_langs(icl_get_languages('skip_missing=N&orderby=KEY&order=DIR&link_empty_to=str'));

        $context = array_merge($context, array(

            'project' => array(
                'name' => get_bloginfo('name'),
                'description' => get_bloginfo('description')
            ),
            'debug' => WP_DEBUG,
            'environment' => WP_DEBUG ? 'development' : 'production',
            'locale' => count($language) ? $language[0] : 'en',
            'languages' => $languages,
            'is_admin' => current_user_can('manage_options'),
            'body_class' => get_bloginfo('language') . ' ' . implode(' ', get_body_class()),
            'is_child_theme' => is_child_theme(),
            'base_url' => get_bloginfo('template_url')
        ));

        // Rocket compatibility
        $context['head'] = $context['wp_head'];
        $context['footer'] = $context['wp_footer'];
        $context['title'] = $context['wp_title'];

        return $context;
    }

    function register_option_pages()
    {

    }

    function add_to_twig($twig)
    {
        include BASE_URI . '/src/Helper/Twig.php';

        $twig->addExtension(new \Customer\Helper\Twig(get_bloginfo('url')));
        return $twig;
    }
}

new Site();