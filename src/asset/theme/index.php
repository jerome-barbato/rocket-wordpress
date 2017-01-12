<?php

use Customer\Application;
use Timber\Timber;
use Timber\Post;

die('toto');

try {

    if (class_exists('Timber')) {

        $context = Timber::get_context();

        $app = Application::getInstance();

        if( $app ){

            $post = new Post();
            $context['post'] = $post;
            $context['post_objects'] = $app->acf_to_timber( $post->ID );

            if( $route = $app->solve($context) )
                Timber::render( 'page/'.$route[0], $route[1] );
        }
        else{

            wp_redirect( wp_login_url() );
        }
    }

} catch (Error $exception) {

    echo    "<h1>We are very sorry but this website is currently not available</h1>" .
            "<hr>" . "<p>Message : </p><br><pre>" . $exception->getMessage() . "</pre>";
}