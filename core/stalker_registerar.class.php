<?php

class Stalker_Registerar{
    public static $listOfTables =array();

    private function __construct() {}

    public static function register($name, Stalker_Table $table){
         self::$listOfTables[$name]=$table;
    }
    public static function get_registerd_tables(){
         return self::$listOfTables;
    }
    public static function is_table_registered($name){
        return array_key_exists($name, self::$listOfTables);
   }
}