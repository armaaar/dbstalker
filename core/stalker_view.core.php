<?php

class Stalker_View
{
    public $view_name;
    protected $db;

    public function __construct($data=null) {
        $this->view_name = strtolower(get_class($this));

        // get db instance
        $this -> db = Stalker_Database::instance();
        $this -> update_object($data);
    }

    public function update_object($data) {
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
            return true;
        } else {
            return false;
        }
    }

    public function serialize() {
        return json_encode(get_object_vars($this));
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
        if(property_exists($this, 'id') && Stalker_Validator::is_id($this->id)) {
            return $table_name::where($column_name, $this->id)->first();
        } else {
            return null;
        }
    }

    protected function has_many($table_name, $column_name) {
        if(property_exists($this, 'id') && Stalker_Validator::is_id($this->id)) {
            return $table_name::where($column_name, $this->id)->fetch();
        } else {
            return null;
        }
    }

    protected function belongs_to($table_name, $column_name) {
        // if property doesn't exist in instance
        if(!(property_exists($this, $column_name) && Stalker_Validator::is_id($this->{$column_name}))) {
            // fetch it from database if you can
            if(property_exists($this, 'id') && Stalker_Validator::is_id($this->id)) {
                $this->{$column_name} = self::select($column_name)->where('id', $this->id)->first()->{$column_name};
            } else { // or return null
                return null;
            }
        }
        return $table_name::where('id', $this->{$column_name})->first();
    }

    protected function belongs_to_many($table_name, $column_name) {
        // if property doesn't exist in instance
        if(!(property_exists($this, $column_name) && Stalker_Validator::is_id($this->{$column_name}))) {
            // fetch it from database if you can
            if(property_exists($this, 'id') && Stalker_Validator::is_id($this->id)) {
                $this->{$column_name} = self::select($column_name)->where('id', $this->id)->first()->{$column_name};
            } else { // or return null
                return null;
            }
        }
        return $table_name::where('id', $this->{$column_name})->fetch();
    }

    protected function has_one_through($target_table_name, $intermediate_table_name, $intermediate_target_column_name, $intermediate_self_column_name) {
        if(!(property_exists($this, 'id') && Stalker_Validator::is_id($this->id))) {
            return null;
        }
        $intermediate_table_name = strtolower($intermediate_table_name);
        $stmt = $this->db->execute("SELECT `INTERMEDIATE_TABLE_NAME`.`{$intermediate_target_column_name}`
                                    FROM `{$intermediate_table_name}` AS `INTERMEDIATE_TABLE_NAME`
                                    INNER JOIN `{$this->table_name}`
                                        ON `{$this->table_name}`.`id` = `INTERMEDIATE_TABLE_NAME`.`{$intermediate_self_column_name}`
                                    WHERE `{$this->table_name}`.`id` = :id
                                    LIMIT 1",
                                array(":id"=>$this->id));

        $results = array_map(function ($record) use ($intermediate_target_column_name) {
            return $record->{$intermediate_target_column_name};
        }, $stmt ->fetchAll());
        if($results) {
            return $target_table_name::where('id', $results[0])->first();
        }
        return null;
    }

    protected function has_many_through($target_table_name, $intermediate_table_name, $intermediate_target_column_name, $intermediate_self_column_name) {
        if(!(property_exists($this, 'id') && Stalker_Validator::is_id($this->id))) {
            return null;
        }
        $lower_intermediate_table_name = strtolower($intermediate_table_name);
        $stmt = $this->db->execute("SELECT `INTERMEDIATE_TABLE_NAME`.`{$intermediate_target_column_name}`
                                    FROM `{$lower_intermediate_table_name}` AS `INTERMEDIATE_TABLE_NAME`
                                    INNER JOIN `{$this->table_name}`
                                        ON `{$this->table_name}`.`id` = `INTERMEDIATE_TABLE_NAME`.`{$intermediate_self_column_name}`
                                    WHERE `{$this->table_name}`.`id` = :id",
                                array(":id"=>$this->id));

        $results = array_map(function ($record) use ($intermediate_target_column_name) {
            return $record->{$intermediate_target_column_name};
        }, $stmt ->fetchAll());
        if($results) {
            return $target_table_name::where('id', $results, 'in')->fetch();
        }
        return [];
    }
}
?>
