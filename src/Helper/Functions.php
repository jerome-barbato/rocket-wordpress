<?php

function add_ghost_page( $slug )
{
	global $wp, $wp_rewrite;

	$wp->add_query_var( $slug );
	add_rewrite_endpoint( $slug, EP_ROOT );
	$wp_rewrite->add_rule( '^/'.$slug.'?$', 'index.php?template='.$slug, 'bottom' );
	$wp_rewrite->flush_rules();


	/* Handle template redirect according the template being queried. */
	add_action( 'template_redirect', function () use($slug) {

		global $wp, $wp_query;

		$template = $wp->query_vars;

		if ( array_key_exists( 'template', $template ) && $slug == $template['template'] )
			$wp_query->set( 'is_404', false );
	});
}
