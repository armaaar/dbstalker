<?php

class Stalker_Registerar{
    protected static $list_of_tables = array();
    protected static $list_of_views = array();
    protected static $list_of_seeds = array();

    private function __construct() {}

    // Tables
    public static function register_table($table_name){
        if (
            is_subclass_of($table_name, 'Stalker_Table')
            && !self::is_table_registered($table_name)
        ) {
            self::$list_of_tables[strtolower($table_name)] = new $table_name;
            self::register_seed($table_name);
        }
    }

    public static function get_registerd_tables(){
         return self::$list_of_tables;
    }

    public static function is_table_registered($table_name){
        return array_key_exists(strtolower($table_name), self::$list_of_tables);
    }

    // Seeds
    public static function table_has_seed($table_name){
        return array_key_exists(strtolower($table_name), self::$list_of_seeds);
    }

    public static function get_table_seed($table_name){
        if(self::table_has_seed($table_name)) {
            return self::$list_of_seeds[strtolower($table_name)];
        }
        return FALSE;
    }

    public static function get_seeds(){
         return self::$list_of_seeds;
    }

    protected static function register_seed($table_name){
        if(self::is_table_registered($table_name) && !self::table_has_seed($table_name)) {
            $seed_class_name = $table_name."_Seed";
            if(class_exists($seed_class_name)) {
                self::$list_of_seeds[strtolower($table_name)]= new $seed_class_name;
            }
        }
    }

    // Views
    public static function register_view($view_name, Stalker_View $view){
        if (
            is_subclass_of($view_name, 'Stalker_View')
            && !self::is_view_registered($view_name)
        ) {
            self::$list_of_views[strtolower($view_name)] = new $view_name;
        }
    }

    public static function get_registerd_views(){
         return self::$list_of_views;
    }

    public static function is_view_registered($view_name){
        return array_key_exists(strtolower($view_name), self::$list_of_views);
    }

    public static function auto_register() {
        foreach(get_declared_classes() as $class){
            if(is_subclass_of($class, 'Stalker_Table')){
                self::register_table($class);
            } elseif(is_subclass_of($class, 'Stalker_View')){
                self::register_view($class);
            }
        }
    }

    public static function clear_registerar() {
        self::$list_of_tables = array();
        self::$list_of_views = array();
        self::$list_of_seeds = array();
    }
}
