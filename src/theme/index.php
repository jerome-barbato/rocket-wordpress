<?php

if ( class_exists('Timber') and !WP_DIRECT_LOADING )
{
    $theme = \Rocket\Model\Theme::getInstance();
    $theme->run();
}
else
{
    wp_redirect( wp_login_url() );
}