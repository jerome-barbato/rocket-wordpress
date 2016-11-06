<?php
/**
 * Plugin Name: Rocket Autoloader
 * Plugin URI: https://git.metabolism.fr/rocket/wordpress
 * Description:
 * Version: 1.0.0
 * Author: Roots
 * Author URI: https://www.metabolism.fr
 * License: MIT License
 */
namespace Rocket;

if (!is_blog_installed()) {
    return;
}