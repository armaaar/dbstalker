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
    public function tinyint($name, $length){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('tinyint', $length);
        $this->table_structure[$name]['validator'] = "number";
        $this->last_col = $name;
        return $this;
    }
    public function smallint($name, $length){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('smallint', $length);
        $this->table_structure[$name]['validator'] = "number";
        $this->last_col = $name;
        return $this;
    }
    public function mediumint($name, $length){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('mediumint', $length);
        $this->table_structure[$name]['validator'] = "number";
        $this->last_col = $name;
        return $this;
    }
    public function bigint($name, $length){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('bigint', $length);
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

    public function json($name){
        $this->table_structure[$name] = array();
        $this->table_structure[$name]['type'] = array('json');
        $this->table_structure[$name]['validator'] = "json";
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
        if(Stalker_Validator::regex_check($digits, 'number')
                && Stalker_Validator::regex_check($points, 'number')
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
        if(Stalker_Validator::regex_check($digits, 'number')
                && Stalker_Validator::regex_check($points, 'number')
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
        return $this->tinyint($name, 1);
    }

    public function id($name){
        $this->int($name, $this->lengths->id)->unsigned();
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

    public function unsigned(){
        if($this->has_numeric_type($this->last_col)) {
            $this->table_structure[$this->last_col]['attribute'] = "unsigned";
        }
        return $this;
    }

    public function unsigned_zerofill(){
        if($this->has_numeric_type($this->last_col)) {
            $this->table_structure[$this->last_col]['attribute'] = "unsigned zerofill";
        }
        return $this;
    }

    public function zero_allowed(){
        $this->table_structure[$this->last_col]['validator'] = "zero_allowed_id";
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

    // helping functions
    protected function has_numeric_type($col_name) {
        if(in_array($this->table_structure[$col_name]["type"][0],
            array('int', 'bigint', 'mediumint', 'smallint', 'tinyint', 'float', 'double', 'decimal'))
        ) {
            return TRUE;
        }
        return FALSE;
    }
}
