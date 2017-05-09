<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Provider;


use Rocket\Application\SingletonTrait;
use Timber\Timber;

/**
 * Class WooCommerceProvider
 *
 * @package Rocket\Provider
 */
class WooCommerceProvider
{
    use SingletonTrait;

    /** @var $_instance WooCommerceProvider Singleton Instance */
    protected static $_instance;


    /**
     * Add current product to TemplateEngine context
     *
     * @param $context
     * @return bool whether or not context is modified.
     */
    public function singleProductContext(&$context)
    {
        if (is_singular('product')) {

            $context['post']    = Timber::get_post();
            $product            = wc_get_product( $context['post']->ID );
            $context['product'] = $product;

            return true;
        }
        return false;
    }


    /**
     * Add categories to TemplateEngine context
     *
     * @param $context
     * @return bool whether or not context is modified.
     */
    public function productCategoryContext(&$context)
    {

        if ( is_product_category() ) {
            $queried_object = get_queried_object();
            $term_id = $queried_object->term_id;
            $context['category'] = get_term( $term_id, 'product_cat' );
            $context['title'] = single_term_title('', false);

            return true;
        }
        return false;
    }


    /**
     * Add products to TemplateEngine context
     * @param $context
     * @return bool whether or not context is modified.
     */
    public function productsContext(&$context)
    {
        $posts = Timber::get_posts();
        if ($posts === null || $posts === false) {

            return false;
        }

        $context['products'] = $posts;

        return true;
    }

}
