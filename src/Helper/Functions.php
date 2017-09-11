<?php

function wp_maintenance_mode( $strict=false )
{
	if( $strict )
		return get_option( 'maintenance_field', false);
	else
		return !current_user_can('editor') && !current_user_can('administrator') && get_option( 'maintenance_field', false);
}
