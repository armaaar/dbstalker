<?php

class Stalker_Table extends Stalker_Database
{
  public $table_name;

  public function __construct($data=null)
  {
    //register the table
    $this->table_name = strtolower(get_class($this));
    if(!Stalker_Registerar::is_table_registered($this->table_name)){
      Stalker_Registerar::register($this->table_name, $this);
    }

    $this -> db = Stalker_Database::instance();
    if(is_array($data) || is_object($data))
    {
      foreach ($data AS $key => $value)
      {
        $this->{$key} = $value;
      }
    }
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
    $stmt = $self ->db->execute("SELECT * FROM `{$self->table_name}`");
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
    $stmt = $self ->db->execute("SELECT * FROM `{$self->table_name}` WHERE `id`=:id",['id'=>$id]);
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

  public function update_object($data) {
    if(is_array($data) || is_object($data))
    {
      foreach ($data AS $key => $value)
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
      if(property_exists($this, 'id') && is_id($this->id))
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
    $data = $this->serialize(true);
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
    $stmt = $this ->db->execute("UPDATE `{$this->table_name}` SET $set WHERE `id`=:id LIMIT 1;", $args);
    return true;
  }

  protected function create()
  {
    $data = $this->serialize(true);
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

    $this->id = $this -> db -> lastInsertId();
    return true;
  }

  public function delete()
  {
        $stmt = $this ->db->execute("DELETE FROM `{$this->table_name}` WHERE `id`=:id LIMIT 1",['id'=>$this->id]);
        $this->reset_object();
        return true;
  }

}
?>
