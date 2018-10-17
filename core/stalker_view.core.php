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

    protected function reset_object() {
        foreach ($this as $key => $value) {
            unset($this->$key);
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
        $stmt = $self ->db->execute("SELECT * FROM `{$self->view_name}`");
        $results = $stmt ->fetchAll();
        $instances = [];
        foreach ($results AS $result)
        {
            $instance = new static();
            foreach ($result AS $key => $value)
            {
                $instance->{$key} = $value;
            }
            $instances[]=$instance;
        }
        return $instances;
    }

    public static function get($id)
    {
        if(!is_id($id))
        {
            return false;
        }
        $self = new static();
        $stmt = $self ->db->execute("SELECT * FROM `{$self->view_name}` WHERE `id`=:id",['id'=>$id]);
        $results = $stmt ->fetchAll();
        if($results)
        {
            foreach ($results[0] AS $key => $value)
            {
                $self->{$key} = $value;
            }
        } else {
            return null;
        }
        return $self;
    }

}
?>
