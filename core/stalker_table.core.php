<?php

class Stalker_Table
{
    public $table_name;
    protected $db;

    public function __construct($data=null)
    {
        //register the table
        $this->table_name = strtolower(get_class($this));
        if(!Stalker_Registerar::is_table_registered($this->table_name)){
            Stalker_Registerar::register_table($this->table_name, $this);
        }

        $this -> db = Stalker_Database::instance();
        if(is_array($data) || is_object($data))
        {
            foreach ($data as $key => $value)
            {
                $this->{$key} = $value;
            }
        }
    }

    public function data() {
        $args = array();
        foreach ($this->schema() as $name => $col) {
            if($name != Stalker_Schema::SEED_COLUMN) {
                if(property_exists($this, $name)) {
                    $args[$name] = $this->$name;
                } elseif(array_key_exists("default", $col)) {
                    $args[$name] = $col["default"];
                }
            }
        }
        return $args;
    }

    public function serialize() {
        return json_encode($this->data());
    }

    public static function fetch_all()
    {
        $self = new static();
        $stmt = $self ->db->execute("SELECT * FROM `{$self->table_name}`");
        $results = $stmt ->fetchAll();
        $instances = [];
        foreach ($results as $result)
        {
            $instance = new static();
            foreach ($result as $key => $value)
            {
                $instance->{$key} = $value;
            }
            $instances[]=$instance;
        }
        return $instances;
    }

    public static function get($id)
    {
        if(!Stalker_Validator::is_id($id))
        {
            return false;
        }
        $self = new static();
        $stmt = $self ->db->execute("SELECT * FROM `{$self->table_name}` WHERE `id`=:id",['id'=>$id]);
        $results = $stmt ->fetchAll();
        if($results)
        {
            foreach ($results[0] as $key => $value)
            {
                $self->{$key} = $value;
            }
        } else {
            return null;
        }
        return $self;
    }

    public function update_object($data) {
        if(is_array($data) || is_object($data))
        {
        foreach ($data as $key => $value)
        {
            $this->{$key} = $value;
        }
        } else {
            return false;
        }
    }

    public function validate() {
        return Stalker_Validator::validate_to_schema($this, $this->schema());
    }

    public function save()
    {
        $errors = $this->validate();
        if(!$errors)
        {
            if(property_exists($this, 'id') && Stalker_Validator::is_id($this->id))
            {
                $exist = $this->get($this->id);
                if($exist)
                {
                    $done = $this->update();
                } else {
                    $done = $this->create();
                }
            } else {
                $done = $this->create();
            }
            return $done;
        } else {
            return false;
        }

    }

    protected function update()
    {
        $data = $this->data();
        $args=[];
        $set = '';
        foreach ($data as $key => $value) {
            if($key != 'id')
            {
                $set .= "`$key`=:$key,";
            }
            $args[":$key"] = $value;
        }
        $set = rtrim($set,',');
        $stmt = $this->db->execute("UPDATE `{$this->table_name}` SET $set WHERE `id`=:id LIMIT 1;", $args);
        return true;
    }

    protected function create()
    {
        $data = $this->data();
        $args=[];
        $columns = '';
        $values = '';
        foreach ($data as $key => $value) {
            if($key == 'id')
            {
                continue;
            }
            $columns .= "`$key`,";
            $values .= ":$key,";
            $args[":$key"] = $value;
        }
        $columns = rtrim($columns,',');
        $values = rtrim($values,',');
        $stmt = $this ->db->execute("INSERT INTO `{$this->table_name}` ($columns) VALUES($values)", $args);

        $this->id = $this->db->lastInsertId();
        return true;
    }

    public function delete()
    {
        if(!property_exists($this, 'id') || !Stalker_Validator::is_id($this->id))
        {
            return false;
        }
        $stmt = $this->db->execute("DELETE FROM `{$this->table_name}` WHERE `id`=:id LIMIT 1",['id'=>$this->id]);
        $this->reset_object();
        return true;
    }

    protected function reset_object() {
        foreach ($this as $key => $value) {
            unset($this->$key);
        }
    }

    // queries
    public static function query() {
        $self = new static();
        return new Stalker_Query($self->table_name);
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
        if(method_exists($this, $key) && !method_exists("Stalker_Table", $key)) {
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

}
?>
