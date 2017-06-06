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
class Post extends \Timber\Post
{

	/**
	 * Post constructor.
	 *
	 * @param null $pid
	 */
	public function __construct($pid = null) {
		parent::__construct( $pid );
		$this->hydrateCustomFields();
	}


	/**
	 * Add ACF custom fields as members of the post
	 */
	protected function hydrateCustomFields() {
		$acfHelper = new ACF( $this->ID);

		$custom_fields = $acfHelper->process();

		foreach ($custom_fields as $name => $value ) {

			$this->$name = $value;
		}
	}
}