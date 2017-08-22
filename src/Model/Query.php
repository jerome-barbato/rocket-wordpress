<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Model;

use Rocket\Helper\ACF;

/**
 * Class Post
 * @see \Timber\Post
 *
 * @package Rocket\Model
 */
class Query
{
	public static function get_fields($id)
	{
		$post = new ACF($id);
		return $post->get();
	}


	public static function get_post($args=[], $fields=[])
	{
		if( empty($args) )
			return new Post();

		if( !is_array($args) )
			return new Post($args);

		$args['posts_per_page'] = 1;

		$posts = self::get_posts($args, $fields);

		if( count($posts) )
			return $posts[0];

		return $posts;
	}


	public static function get_terms($args=[])
	{
		$args['fields'] = 'ids';

		$terms = get_terms( $args );

		foreach ($terms as &$term)
		{
			$term = new Term( $term );
		}

		return $terms;
	}


	public static function get_term_by($field, $value, $taxonomy)
	{
		$term = get_term_by( $field, $value, $taxonomy );

		if( $term )
			return new Term( $term->term_id );
		else
			return false;
	}


	public static function get_posts($args=[], $fields=[])
	{
		global $wp_query;

		$args = array_merge($wp_query->query, $args);

		if( !isset($args['posts_per_page']) and !isset($args['numberposts']))
			$args['posts_per_page'] = get_option( 'posts_per_page' );

		if( isset($args['post_type'], $args['pagename']) )
			unset($args['pagename']);

		$args['fields'] = 'ids';

		$posts = get_posts( $args );

		foreach ($posts as &$post)
		{
			if( !empty($fields) )
			{
				$id   = $post;
				$post = [];

				foreach ($fields as $key)
				{
					$post[$key] = get_field($key, $id);
				}
			}
			else
				$post = new Post( $post );
		}

		return $posts;
	}
}
