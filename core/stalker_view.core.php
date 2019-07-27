<?php

class Stalker_View
{
    public $view_name;
    protected $db;

    public function __construct()
    {
        //register the table
        $this->view_name = strtolower(get_class($this));
        if(!Stalker_Registerar::is_view_registered($this->view_name)){
            Stalker_Registerar::register_view($this->view_name, $this);
        }

        $this -> db = Stalker_Database::instance();
    }

    public function data() { /* Not implemented */ }

    public function serialize() { /* Not implemented */ }

    public static function fetch_all()
    {
        return self::fetch();
    }

    public static function get($id)
    {
        return self::where("id", $id)->first();
    }

    // queries
    public static function query() {
        $self = new static();
        return new Stalker_Query($self->view_name);
    }

    public static function __callStatic($name, $arguments) {
        if(method_exists('Stalker_Query', $name)) {
            return self::query()->{$name}(...$arguments);
        } else {
			$trace = debug_backtrace();
			$caller = $trace[1];
			trigger_error($caller['class']. "::" .$caller['function']. " -> Call to undefined method '$name'", E_USER_ERROR);
        }
    }

    // relations
    public function __get($key)
    {
        // if $key is a function in the child class
        if(method_exists($this, $key) && !method_exists("Stalker_View", $key)) {
            return $this->{$key}();
        } else {
			$class = get_class($this);
			trigger_error($class. "::$" .$key. " -> Undefined property '$$key'", E_USER_NOTICE);
        }
    }

    protected function has_one($table_name, $column_name) {
        return $table_name::where($column_name, $this->id)->first();
    }

    protected function has_many($table_name, $column_name) {
        return $table_name::where($column_name, $this->id)->fetch();
    }

    protected function belongs_to($table_name, $column_name) {
        return $table_name::where('id', $this->{$column_name})->first();
    }

    protected function belongs_to_many($table_name, $column_name) {
        return $table_name::where('id', $this->{$column_name})->fetch();
    }

    protected function has_one_through($target_table_name, $intermediate_table_name, $intermediate_target_column_name, $intermediate_self_column_name) {
        $stmt = $this->db->execute("SELECT *
                                    FROM `{$target_table_name}`
                                    INNER JOIN `{$intermediate_table_name}`
                                        ON `{$target_table_name}`.`id` = `{$intermediate_table_name}`.`{$intermediate_target_column_name}`
                                    INNER JOIN `{$this->view_name}`
                                        ON `{$this->view_name}`.`id` = `{$intermediate_table_name}`.`{$intermediate_self_column_name}`
                                    WHERE `{$this->view_name}`.`id` = :id
                                    LIMIT 1",
                                array(":id"=>$this->id));
        $results = $stmt ->fetchAll();
        if($results) {
            return $results[0];
        }
        return null;
    }

    protected function has_many_through($target_table_name, $intermediate_table_name, $intermediate_target_column_name, $intermediate_self_column_name) {
        $stmt = $this->db->execute("SELECT `{$target_table_name}`.*
                                    FROM `{$target_table_name}`
                                    INNER JOIN `{$intermediate_table_name}`
                                        ON `{$target_table_name}`.`id` = `{$intermediate_table_name}`.`{$intermediate_target_column_name}`
                                    INNER JOIN `{$this->view_name}`
                                        ON `{$this->view_name}`.`id` = `{$intermediate_table_name}`.`{$intermediate_self_column_name}`
                                    WHERE `{$this->view_name}`.`id` = :id",
                                array(":id"=>$this->id));
        $results = $stmt ->fetchAll();
        return $results;
    }

}
?>
