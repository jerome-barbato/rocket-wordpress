<?php

/*
 * Route middleware to easily implement multi-langue
 * todo: check to find a better way...
 */
namespace Rocket\Helper;

class Route
{
    private $data;


    public function __construct($pattern, $to)
    {
        $this->data = ['status' => '', 'comment' => ''];

        $this->register($pattern, $to);

        return $this;
    }


    public function getData()
    {

        return $this->data;
    }


    public function register($pattern, $to)
    {

        $this->data['id'] = $pattern == '/' ? 'index' : str_replace('/', '.', $pattern);
        $this->data['path'] = $pattern;
        $this->data['to'] = $to;
    }


    public function page()
    {

        return $this->data['page'];
    }


    public function context()
    {

        return $this->data['context'];
    }


    public function execute( $context )
    {
        $data = $this->data['to']($context['locale'], $context);

        $this->data['page']    = $data[0];
        $this->data['context'] = $data[1];
    }


    public function status($status)
    {

        $this->data['status'] = $status;
        return $this;
    }


    public function comment($comment)
    {

        $this->data['comment'] = $comment;
        return $this;
    }


    public function method($method)
    {
        return $this;
    }


    public function value($id, $value)
    {
        return $this;
    }

    public function assert($id, $pattern)
    {
        return $this;
    }

    public function convert($id, $to)
    {

        return $this;
    }

    public function before($id, $to)
    {
        return $this;
    }

    public function when($id, $to)
    {
        return $this;
    }
}