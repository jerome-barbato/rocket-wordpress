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

		$this->clean();
		$this->hydrateCustomFields();
	}


	/**
	 * Add ACF custom fields as members of the post
	 */
	protected function hydrateCustomFields()
	{
		$custom_fields = new ACF( $this->ID);

		foreach ($custom_fields->get() as $name => $value )
		{
			$this->$name = $value;
		}
	}


	/**
	 * Add ACF custom fields as members of the post
	 */
	protected function clean()
	{
		foreach ($this as $key=>$value){

			if( substr($key,0,1) == '_' and $key != '_content')
			{
				unset($this->$key);
				$key = substr($key,1);
				unset($this->$key);
			}
		}

		unset($this->custom, $this->guid, $this->post_content_filtered, $this->to_ping, $this->pinged);
	}
}