<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket;


class Menu {
    private $name, $slug;

    public function __construct($name, $slug, $autodeclare = true){
        if ($autodeclare) {
            register_nav_menu(__($slug, Application::$domain_name), __($name, Application::$domain_name));
        }
    }
}