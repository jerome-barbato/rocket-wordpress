<?php

use Customer\Application;
use Timber\Timber;
use Timber\Post;
use Timber\Image;
use Timber\User;
use Timber\Term;

$context = Timber::get_context();
$app = Application::getInstance();

$post = new Post();

if( function_exists('get_field_objects') )
{
    $objects = [];
    $raw_objects = get_field_objects($post->ID);
    print_r($raw_objects);

    foreach ($raw_objects as $object){

        switch( $object['type'] ){

            case 'image';

                if( $object['save_format'] == 'id')
                    $objects[ $object['name'] ] = new Image($object['value']);
                elseif( $object['save_format'] == 'object')
                    $objects[ $object['name'] ] = new Image($object['value']['id']);
                else
                    $objects[ $object['name'] ] = $object['value'];

                    break;

            case 'relationship';

                $objects[ $object['name'] ] = [];

                foreach ($object['value'] as $value){

                    if( $object['return_format'] == 'id')
                        $objects[ $object['name'] ][] = new Post($value);
                    elseif( $object['return_format'] == 'object')
                        $objects[ $object['name'] ][] = new Post($value->ID);
                    else
                        $objects[ $object['name'] ][] = $object['value'];
                }
                break;

            case 'post_object';

                $objects[ $object['name'] ] = new Post($object['value']->ID);
                break;

            case 'user';

                $objects[ $object['name'] ] = new User($object['value']['ID']);
                break;

            case 'taxonomy';

                $objects[ $object['name'] ] = [];

                foreach ($object['value'] as $value) {

                    if ($object['return_format'] == 'id')
                        $objects[$object['name']][] = new Term($value);
                    elseif ($object['return_format'] == 'object')
                        $objects[$object['name']][] = new Term($value->term_id);
                    else
                        $objects[$object['name']][] = $object['value'];
                }
                break;

            default:
                $objects[ $object['name'] ] = $object['value'];
                break;
        }
    }
}

print_r($objects);

$context["post"] = $post;

if( $route = $app->solve($context) )
    Timber::render( 'page/'.$route[0], $route[1] );