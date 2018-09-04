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

  public function serialize($to_array = false)
  {
    $declared_keys = array_keys(getClassProperties($this));
    $args = get_object_vars($this);
    foreach ($declared_keys as $key) {
      unset($args[$key]);
    }
    if($to_array)
    {
      return $args;
    }
    return json_encode($args);
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
