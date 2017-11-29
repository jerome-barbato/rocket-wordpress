<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Model;

/**
 * Class Post
 * @see \Timber\Term
 *
 * @package Rocket\Model
 */
class Image extends \Timber\Image
{
	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		parent::__construct( $id );

		$this->clean();
	}


	/**
	 * Remove useless data
	 */
	protected function clean()
	{
		unset(
			$this->_wp_attachment_metadata, $this->sizes, $this->ImageClass, $this->PostClass, $this->TermClass,
			$this->post_content, $this->post_excerpt, $this->post_title, $this->ping_status, $this->to_ping, $this->post_name,
			$this->pinged
		);
	}
}