<?php

spl_autoload_register(function ($class_name) {
    if(preg_match('/^stalker_.+/i', $class_name)) {
        include_once './core/'. $class_name . '.class.php';
    } else {
        include_once './tables/'. $class_name . '.table.php';
    }
});

$foo = new Courses_Class();

//var_dump(Stalker_Registerar::get_registerd_tables());
//var_dump($foo->schema());
$fa = Stalker_Migrator::table_need_migration($foo);
var_dump($fa);
