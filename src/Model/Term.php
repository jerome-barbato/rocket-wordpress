<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Model;


use Rocket\Helper\ACF;

/**
 * Class Post
 * @see \Timber\Term
 *
 * @package Rocket\Model
 */
class Term extends \Timber\Term
{
	public $ID, $description;

	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		parent::__construct( $id );

		$this->ID = 'term_' . $id;
		$this->content = term_description($id);

		$this->clean();
		$this->hydrateCustomFields();
	}


	/**
	 * Add ACF custom fields as members of the post
	 */
	protected function hydrateCustomFields()
	{
		$custom_fields = new ACF( $this->ID );

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

			if( substr($key,0,1) == '_' and $key != '_content' and $key != '_prev' and $key != '_next')
			{
				unset($this->$key);
				$key = substr($key,1);
				unset($this->$key);
			}
		}

		unset($this->custom, $this->guid, $this->post_content_filtered, $this->to_ping, $this->pinged);
	}
}