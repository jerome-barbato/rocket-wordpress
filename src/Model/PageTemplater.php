<?php

namespace Rocket\Model;

class PageTemplater {


	/**
	 * The array of templates that this plugin tracks.
	 */
	protected $page_templates;
	protected $post_templates;

	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	public function __construct($page_templates, $post_templates) {

		$this->page_templates = $page_templates;
		$this->post_templates = $post_templates;


		// Add a filter to the wp 4.7 version attributes metabox
		add_filter( 'theme_page_templates', array( $this, 'add_new_page_template' ));
		add_filter( 'theme_post_templates', array( $this, 'add_new_post_template' ));
	}

	/**
	 * Adds our template to the page dropdown for v4.7+
	 *
	 */
	public function add_new_page_template( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this->page_templates );
		return $posts_templates;
	}

	/**
	 * Adds our template to the page dropdown for v4.7+
	 *
	 */
	public function add_new_post_template( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this->post_templates );
		return $posts_templates;
	}
}
