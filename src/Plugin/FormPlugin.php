<?php

namespace Rocket\Plugin;


/**
 * Class Rocket Framework
 */
class FormPlugin {

	/**
	 * Get request parameter
	 */
	public static function get( $key, $limit=500 ) {

		if ( !isset( $_REQUEST[ $key ] ) )
			return false;
		else
			return substr( trim(sanitize_text_field( $_REQUEST[ $key ] )), 0, $limit );
	}


	/**
	 * Quickly send form
	 */
	public static function send($fields=[], $files=[], $subject='New message from website', $email_id='email', $delete_attachements=true){

		$email = self::get( $email_id );

		if ( $email && is_email( $email ) )
		{
			$body = $subject." :\n\n";

			foreach ( $fields as $key )
			{
				$value = self::get( $key );
				$body  .= ( $value ? ' - ' . $key . ' : ' . $value . "\n" : '' );
			}

			$attachments = [];

			foreach ( $files as $file )
			{
				$file = self::get( $file );

				if ( file_exists( WP_CONTENT_DIR.$file ) )
					$attachments[] = WP_CONTENT_DIR.$file;
			}

			if ( wp_mail( get_option( 'admin_email' ), $subject, $body, $attachments ) )
			{
				if( $delete_attachements )
				{
					foreach ( $attachments as $file )
						@unlink($file);
				}

				return true;
			}
			else
				return ['error' => 2, 'message' => "Sorry, the server wasn't able to complete this request"];

		}
		else
		{
			return ['error' => 1, 'message' => "Invalid email address. Please type a valid email address."];
		}
	}
}
