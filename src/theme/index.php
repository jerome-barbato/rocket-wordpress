<?php

if (class_exists('Timber')) {

    $theme = \Rocket\Model\Theme::getInstance();
    $theme->run();
}
else{

    echo "<h1>We are very sorry but this page is currently not available</h1>" .
        "<hr>" . "<p>Message : </p><pre>Timber plugin is not active</pre>";
}