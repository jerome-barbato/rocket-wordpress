<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Rocket;

/**
 * Autoloader for Wordpress
 * @param $class
 * @return bool
 */
function rocket_autoload($class) {

    $class = str_replace(__NAMESPACE__ . '\\', '', $class);

    $ds = DIRECTORY_SEPARATOR;
    $model = __DIR__ . $ds . 'Model' . $ds;
    $helper = __DIR__ . $ds . 'Helper' . $ds;

    if (file_exists($model.$class.'.php')) {

        require_once $model.$class.'.php';
    } else if (file_exists($helper.$class.'.php')) {

        require_once $helper.$class.'.php';
    } else {
        return false;
    }
    return true;
}

require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'rocket-kernel' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'autoload.php';
spl_autoload_register(__NAMESPACE__ . '\rocket_autoload');