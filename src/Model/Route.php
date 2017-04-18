<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket\Model;


class Route extends \Symfony\Component\Routing\Route
{

    public function bind($string)
    {
        return $this;
    }

    public function value($string)
    {
        return $this;
    }
}
