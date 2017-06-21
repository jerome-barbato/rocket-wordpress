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
	public static function get_post($args){

		$args['posts_per_page'] = 1;
		$args['fields'] = 'ids';

		$posts = get_posts( $args );

		if( count($posts) )
			return new Post( $posts[0] );

		return $posts;
	}

	public static function get_posts($args)
	{
		$posts = get_posts( $args );
		$args['fields'] = 'ids';

		foreach ($posts as &$post)
			$post = new Post( $post );

		return $posts;
	}
}