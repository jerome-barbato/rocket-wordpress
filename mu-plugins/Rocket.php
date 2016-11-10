<?php
/**
 * Plugin Name: Rocket Autoloader
 * Plugin URI: https://git.metabolism.fr/rocket/wordpress
 * Description: Register post type and taxonomy from wordpress.yml
 * Version: 1.0.0
 * Author: Metabolism
 * Author URI: https://www.metabolism.fr
 */
namespace Rocket;

use Dflydev\DotAccessData\Data;

if (!is_blog_installed()) {
    return;
}

class Autoloader
{

    private $config;

    /**
     * Load App configuration
     */
    private function loadConfig()
    {
        $data = array();

        foreach (array('global', 'wordpress', 'local') as $config) {
            $file = BASE_URI . '/config/' . $config . '.yml';
            if (file_exists($file))
                $data = array_merge($data, \Spyc::YAMLLoad($file));
        }

        $this->config = new Data($data);
    }


    public function __construct()
    {
        $this->loadConfig();

        add_action( 'init', array( $this, 'init' ) );
    }


    function init(){

        $this->register_post_types();
        $this->register_taxonomies();
        $this->register_menus();
        $this->register_option_pages();
    }


    function register_option_pages(){

        if( function_exists('acf_add_options_page') ) {

            acf_add_options_page();

            foreach ( $this->config->get('options_page', []) as $name ) {

                acf_add_options_sub_page($name);
            }
        }
    }


    function register_post_types()
    {
        foreach ( $this->config->get('post_types', []) as $id => $post_type ){

            $post_type = new Data($post_type);
            $singular = $id;
            $plurial = $id.'s';

            $labels = [
                'name'          => __($post_type->get('labels.name', ucfirst($plurial))),
                'all_items'     => __($post_type->get('labels.all_items','All '.$plurial)),
                'singular_name' => __($post_type->get('labels.singular_name',ucfirst($singular))),
                'add_new_item'  => __($post_type->get('labels.add_new_item','Add a '.$singular)),
                'edit_item'     => __($post_type->get('labels.edit_item','Edit '.$singular)),
                'not_found'     => __($post_type->get('labels.not_found',ucfirst($singular).' not found')),
                'search_items'  => __($post_type->get('labels.search_items','Search in '.$plurial))
            ];

            $args = [
                'labels'              => $labels,
                'menu_icon'           => $post_type->get('menu_icon','dashicons-media-default'),
                'public'              => $post_type->get('public', true),
                'has_archive'         => $post_type->get('has_archive', false),
                'capability_type'     => $post_type->get('capability_type', 'post'),
                'supports'            =>  $post_type->get('supports', ['title', 'editor', 'thumbnail']),
                'rewrite'             => $post_type->get('rewrite', true),
                'exclude_from_search' => $post_type->get('exclude_from_search', true),
                'query_var'           => $post_type->get('query_var', true)
            ];

            register_post_type($id, $args);
        }
    }


    function register_taxonomies()
    {

        foreach ( $this->config->get('taxonomies', []) as $id => $taxonomy ){

            $taxonomy = new Data($taxonomy);
            $singular = $id;
            $plurial = $id.'s';

            $labels = array(
                'name'              => __($taxonomy->get('labels.name', ucfirst($plurial))),
                'singular_name'     => __($taxonomy->get('labels.singular_name', ucfirst($singular))),
                'search_items'      => __($taxonomy->get('labels.search_items', 'Search in '.$plurial)),
                'all_items'         => __($taxonomy->get('labels.all_items', 'All '.$plurial)),
                'parent_item'       => __($taxonomy->get('labels.parent_item', 'Parent '.$singular)),
                'parent_item_colon' => __($taxonomy->get('labels.parent_item_colon', 'Parent Category:')),
                'edit_item'         => __($taxonomy->get('labels.edit_item', 'Edit '.$singular)),
                'update_item'       => __($taxonomy->get('labels.update_item', 'Update '.$singular)),
                'add_new_item'      => __($taxonomy->get('labels.add_new_item', 'Add an '.$singular)),
                'new_item_name'     => __($taxonomy->get('labels.new_item_name', 'Name'))
            );

            $args = array(
                'hierarchical'      => $taxonomy->get('hierarchical', true),
                'labels'            => $labels,
                'show_ui'           => $taxonomy->get('show_ui', true),
                'show_admin_column' => $taxonomy->get('show_admin_column', false),
                'query_var'         => $taxonomy->get('query_var', false)
            );

            register_taxonomy($id, $taxonomy->get('object_type', ['page']), $args);
        }
    }


    function register_menus() {

        foreach ( $this->config->get('menus', []) as $id => $menu ) {

            register_nav_menu($id, __($menu));
        }
    }

}

new Autoloader();