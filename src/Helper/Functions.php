<?php

function wp_maintenance_mode( $strict=false )
{
	if( $strict )
		return get_option( 'maintenance_field', false);
	else
		return !current_user_can('editor') && !current_user_can('administrator') && get_option( 'maintenance_field', false);
}

function add_ghost_page( $slug )
{
	global $wp, $wp_rewrite;

	add_rewrite_rule('^'.str_replace('.', '\.', $slug).'/?$', 'index.php?pagename='.$slug, 'top');

	/* Handle template redirect according the template being queried. */
	add_action( 'template_redirect', function () use($slug) {

		global $wp, $wp_query;

		$template = $wp->query_vars;

		if ( array_key_exists( 'pagename', $template ) && $slug == $template['pagename'] )
		{
			$wp_query->is_404 = false;
			$wp_query->set( 'is_404', false );
			header("HTTP/1.1 200 OK");
		}
	});
}
