<?php

class Stalker_Registerar{
    protected static $list_of_tables =array();
    protected static $list_of_seeds =array();

    private function __construct() {}

    public static function register(string $table_name, Stalker_Table $table){
        $table_name = strtolower($table_name);
        if(!self::is_table_registered($table_name)) {
            self::$list_of_tables[$table_name]=$table;
            self::register_seed($table_name);
        }
    }

    public static function get_registerd_tables(){
         return self::$list_of_tables;
    }

    public static function is_table_registered(string $table_name){
        return array_key_exists(strtolower($table_name), self::$list_of_tables);
    }

    public static function table_has_seed(string $table_name){
        return array_key_exists(strtolower($table_name), self::$list_of_seeds);
    }

    public static function get_table_seed(string $table_name){
        $table_name = strtolower($table_name);
        if(self::table_has_seed($table_name)) {
            return self::$list_of_seeds[$table_name];
        }
        return FALSE;
    }

    public static function get_seeds(){
         return self::$list_of_seeds;
    }

    public static function auto_register() {
        foreach(get_declared_classes() as $class){
            if(is_subclass_of($class, 'Stalker_Table')){
                self::register(strtolower($class), new $class);
            }
        }
    }

    protected static function register_seed(string $table_name){
        $table_name = strtolower($table_name);
        if(self::is_table_registered($table_name) && !self::table_has_seed($table_name)) {
            $seed_class_name = $table_name."_seed";
            if(class_exists($seed_class_name)) {
                self::$list_of_seeds[$table_name]= new $seed_class_name;
            }
        }
    }

}
