<?php

class Stalker_Schema{
    const SEED_COLUMN = "main_seed";
    private $table_structure = array();
    private $last_col;
    private $lengths;

    private function __construct() {
        $this->lengths = Stalker_Configuration::custom_feilds_lengths();
    }

    public static function build(Closure $callable){
        $self = new static();
        // add primary id
        $self->id("id")->primary();
        $callable($self);
        // check if table has seeds
        $trace = debug_backtrace();
        $caller = $trace[1];
        if(Stalker_Registerar::table_has_seed($caller['class'])) {
            $self->boolean(self::SEED_COLUMN)->nullable()->def(NULL);
        }
        //return schema
        return $self->table_structure;
    }

    // col Types
    public function int($name, $length){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('int', $length);
        $this->table_structure[$name]['validator'] = "number";
        $this->last_col = $name;
        return $this;
    }

    public function varchar($name, $length){
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
            $this->table_structure[$name]['type'] = array('float', "$digits,$points");
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
            $this->table_structure[$name]['type'] = array('double', "$digits,$points");
        } else {
            $this->table_structure[$name]['type'] = array('double');
        }
        $this->table_structure[$name]['validator'] = "float";
        $this->last_col = $name;
        return $this;
    }

    public function decimal($name, $digits, $points){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('decimal', "$digits,$points");
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
        $this->int($name, $this->lengths->id);
        $this->table_structure[$this->last_col]['validator'] = "id";
        return $this;
    }

    public function email($name){
        $this->varchar($name, $this->lengths->email);
        $this->table_structure[$this->last_col]['validator'] = "email";
        return $this;
    }

    public function password($name){
        return $this->varchar($name, $this->lengths->password);
    }

    public function phone($name){
        $this->varchar($name, $this->lengths->phone);
        $this->table_structure[$this->last_col]['validator'] = "phone";
        return $this;
    }

    public function ip($name){
        return $this->varchar($name, $this->lengths->ip);
    }

    public function link($name){
        $this->varchar($name, $this->lengths->link);
        $this->table_structure[$this->last_col]['validator'] = "link";
        return $this;
    }

    // additional attributes
    public function def($val){
        if(!in_array(gettype($val), array('boolean', 'string', 'integer', 'double', 'NULL'))) {
            trigger_error("Default value for column '{$this->last_col}' of unknown type ".gettype($val), E_USER_ERROR);
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
