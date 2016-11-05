<?php

/*
 * Route middleware to easily implement multi-langue
 * todo: check to find a better way...
 */
namespace Rocket\Helper;

use Timber\Post, Timber\Image, Timber\User, Timber\Term;

class ACF
{
    private $raw_objects;


    public function __construct($post_id)
    {
        if( function_exists('get_field_objects') )
        $this->raw_objects = get_field_objects($post_id);
    }


    public function process()
    {
        return $this->clean( $this->raw_objects );
    }


    public function clean($raw_objects)
    {
        $objects = [];

        if( !$raw_objects or !is_array($raw_objects) )
            return [];

        foreach ($raw_objects as $object) {

            switch ($object['type']) {

                case 'image';

                    if ($object['return_format'] == 'id')
                        $objects[$object['name']] = new Image($object['value']);
                    elseif ($object['return_format'] == 'object')
                        $objects[$object['name']] = new Image($object['value']['id']);
                    else
                        $objects[$object['name']] = $object['value'];

                    break;

                case 'relationship';

                    $objects[$object['name']] = [];

                    foreach ($object['value'] as $value) {

                        if ($object['return_format'] == 'id')
                            $objects[$object['name']][] = new Post($value);
                        elseif ($object['return_format'] == 'object')
                            $objects[$object['name']][] = new Post($value->ID);
                        else
                            $objects[$object['name']][] = $object['value'];
                    }
                    break;

                case 'post_object';

                    $objects[$object['name']] = new Post($object['value']->ID);
                    break;

                case 'user';

                    $objects[$object['name']] = new User($object['value']['ID']);
                    break;

                case 'flexible_content';

                    $objects[$object['name']] = [];

                    foreach ($object['layouts'] as $value) {

                        $objects[$object['name']][] = $this->clean($value['sub_fields']);
                    }

                    break;

                case 'repeater';

                    $objects[$object['name']] = [];

                    foreach ($object['value'] as &$value) {
                        $i = 0;
                        foreach ($value as $id=>&$_value) {

                            $_tmp_value = $_value;
                            $_value = $object['sub_fields'][$i];
                            $_value['value'] = $_tmp_value;
                            $i++;
                        }
                    }

                    foreach ($object['value'] as $value) {

                        $objects[$object['name']][] = $this->clean($value);
                    }

                    break;

                case 'taxonomy';

                    $objects[$object['name']] = [];

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
                    $objects[$object['name']] = $object['value'];
                    break;
            }
        }

        return $objects;
    }
}