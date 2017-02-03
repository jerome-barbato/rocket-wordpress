<?php

if (class_exists('Timber')) {

    $theme = \Rocket\Model\Theme::getInstance();
    $theme->run();
}
else{

    wp_redirect( wp_login_url() );
}