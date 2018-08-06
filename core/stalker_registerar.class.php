<?php

class Stalker_Registerar{
    public static $listOfTables =array();

    private function __construct() {}

    public static function register($name, Stalker_Table $table){
         self::$listOfTables[$name]=$table;
    }
    public static function getRegisterdTables(){
         return self::$listOfTables;
    }
    public static function isTableRegistered($name){
        return array_key_exists($name, self::$listOfTables);
   }
}