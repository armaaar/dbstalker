<?php

spl_autoload_register(function ($class_name) {
    var_dump($class_name);
    if(preg_match('/^stalker_.+/i', $class_name)) {
        include_once './core/'. $class_name . '.class.php';
    } else {
        include_once './tables/'. $class_name . '.table.php';
    }
});
