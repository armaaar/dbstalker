<?php

class Stalker_Registerar{
    public static $listOfTables =array();

    private function __construct() {}

    public static function register(string $name, Stalker_Table $table){
        if(!self::is_table_registered($name)) {
            self::$listOfTables[$name]=$table;
        }
    }
    public static function get_registerd_tables(){
         return self::$listOfTables;
    }
    public static function is_table_registered(string $name){
        return array_key_exists($name, self::$listOfTables);
    }

    public static function auto_register() {
        foreach(get_declared_classes() as $class){
            if(is_subclass_of( $class, 'Stalker_Table' )){
                self::register(strtolower($class), new $class);
            }
        }
    }
}