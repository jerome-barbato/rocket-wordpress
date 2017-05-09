<?php

// file is required

function rocket_set_product( $this_post ) {
    global $product;
    global $post;
    if ( is_woocommerce() ) {
        $product = wc_get_product($this_post->ID);
        $post = get_post($this_post->ID);
    }
}
