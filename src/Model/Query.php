<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Model;

/**
 * Class Post
 * @see \Timber\Post
 *
 * @package Rocket\Model
 */
class Query
{
	public static function get_post($args)
	{
		$args['posts_per_page'] = 1;

		$posts = self::get_posts($args);

		if( count($posts) )
			return $posts[0];

		return $posts;
	}


	public static function get_posts($args=[])
	{
		global $wp_query;

		if( !isset($args['post_type']) )
			$args = array_merge($wp_query->query, $args);

		if( !isset($args['posts_per_page']))
			$args['posts_per_page'] = $wp_query->post_count;

		$args['fields'] = 'ids';

		$posts = get_posts( $args );

		foreach ($posts as &$post)
			$post = new Post( $post );

		return $posts;
	}
}