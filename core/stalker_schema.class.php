<?php

class Stalker_Schema{
    private $table_structure = array();
    private $last_col;

    const ID_LENGTH = 11;
    const EMAIL_LENGTH = 255;
    const PASSWORD_LENGTH = 40;
    const PHONE_LENGTH = 20;
    const IP_LENGTH = 45;
    const LINK_LENGTH = 512;

    private function __construct() {}

    public static function build($callable){
        $self = new static();
        $callable($self);
        return $self->table_structure;
    }

    // col Types
    public function int($name, int $length){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('int', $length);
        $this->table_structure[$name]['validator'] = "number";
        $this->last_col = $name;
        return $this;
    }

    public function varchar($name, int $length){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('varchar', $length);
        $this->last_col = $name;
        return $this;
    }

    public function text($name){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('text');
        $this->last_col = $name;
        return $this;
    }

    public function enum($name, array $vals){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('enum', $vals);
        $this->last_col = $name;
        return $this;
    }

    public function date($name){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('date');
        $this->table_structure[$name]['validator'] = "date";
        $this->last_col = $name;
        return $this;
    }

    public function time($name){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('time');
        $this->table_structure[$name]['validator'] = "24hours";
        $this->last_col = $name;
        return $this;
    }

    public function datetime($name){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('datetime');
        $this->table_structure[$name]['validator'] = "datetime";
        $this->last_col = $name;
        return $this;
    }

    public function float($name, $digits=null, $points=null){
        $this->table_structure[$name] = array();
        if(Stalker_Validator::regexCheck($digits, 'number') 
                && Stalker_Validator::regexCheck($points, 'number')
                && (int)$digits >= (int)$points)
        {
            $this->table_structure[$name]['type'] = array('float', "$digits, $points");
        } else {
            $this->table_structure[$name]['type'] = array('float');
        }
        $this->table_structure[$name]['validator'] = "float";
        $this->last_col = $name;
        return $this;
    }

    public function double($name, $digits=null, $points=null){
        $this->table_structure[$name] = array();
        if(Stalker_Validator::regexCheck($digits, 'number') 
                && Stalker_Validator::regexCheck($points, 'number')
                && (int)$digits >= (int)$points)
        {
            $this->table_structure[$name]['type'] = array('double', "$digits, $points");
        } else {
            $this->table_structure[$name]['type'] = array('double');
        }
        $this->table_structure[$name]['validator'] = "float";
        $this->last_col = $name;
        return $this;
    }

    public function decimal($name, int $digits, int $points){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('decimal', "$digits, $points");
        $this->table_structure[$name]['validator'] = "float";
        $this->last_col = $name;
        return $this;
    }

    public function boolean($name){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('tinyint', 1);
        $this->last_col = $name;
        return $this;
    }
    
    public function id($name){
        $this->int($name, self::ID_LENGTH);
        $this->table_structure[$this->last_col]['validator'] = "id";
        return $this;
    }

    public function email($name){
        $this->varchar($name, self::EMAIL_LENGTH);
        $this->table_structure[$this->last_col]['validator'] = "email";
        return $this;
    }

    public function password($name){
        return $this->varchar($name, self::PASSWORD_LENGTH);
    }

    public function phone($name){
        $this->varchar($name, self::PHONE_LENGTH);
        $this->table_structure[$this->last_col]['validator'] = "phone";
        return $this;
    }

    public function ip($name){
        return $this->varchar($name, self::IP_LENGTH);
    }

    public function link($name){
        $this->varchar($name, self::LINK_LENGTH);
        $this->table_structure[$this->last_col]['validator'] = "link";
        return $this;
    }

    // additional attributes
    public function default($val){
        if(!in_array(gettype($val), array('boolean', 'string', 'integer', 'double', 'NULL'))) {
            error_log("FATAL: default value for column '{$this->last_col}' of unknown type ".gettype($val));
			die();
        }
        if(is_null($val)) {
            $this->nullable();
        }
        $this->table_structure[$this->last_col]['default'] = $val;
        return $this;
    }
    
    public function nullable(){
        $this->table_structure[$this->last_col]['null'] = TRUE;
        return $this;
    }
    
    public function primary(){
        $this->table_structure[$this->last_col]['key'] = 'PRI';
        $this->table_structure[$this->last_col]['ai'] = TRUE;
        return $this;
    }
    
    public function index(){
        $this->table_structure[$this->last_col]['key'] = 'MUL';
        return $this;
    }
    
    public function unique(){
        $this->table_structure[$this->last_col]['key'] = 'UNI';
        return $this;
    }
}