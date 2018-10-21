<?php

class Stalker_Query
{
    protected $table_name;
    protected $db;

    protected $args;
    protected $args_key_counter;

    protected $last_operation;
    protected $order_pairs;

    protected $where;
    protected $group;
    protected $having;
    protected $order;
    protected $limit;

    protected $supported_operands;

    public function __construct($table_name) {
        $this->db = Stalker_Database::instance();
        $this->table_name = $table_name;

        $this->args = array();
        $this->args_key_counter = 0;

        $this->last_operation = null;
        $this->order_pairs = array();

        $this->where = null;
        $this->group = null;
        $this->having = null;
        $this->order = null;
        $this->limit = null;

        $this->supported_operands = array(
            '=', '<>', 'is', 'is not', '>', '>=', '<', '<=', 'like', 'not like'
        );
    }

    public function where($column, $value, $operand='=', $value_is_column=FALSE) {
        if(!$this->check_parameters($column, $operand)) {
            return $this;
        }
        $where = '';
        $args = array();
        if(is_null($this->where)) {
            $where .= " WHERE";
        }
        if($value_is_column) {
            if(!$this->check_parameters($value)) {
                return $this;
            }
            $where .= " `$column` ".$operand." $value";
        } else {
            if(is_null($value)) {
                if(in_array($operand, array('<>', 'is not', 'not like'))) {
                    $where .= " `$column` IS NOT NULL";
                } else {
                    $where .= " `$column` IS NULL";
                }

            } else {
                $where .= " `$column` ".$operand." :".$column.$this->args_key_counter;
                $args[":".$column.$this->args_key_counter] = $value;
                $this->args_key_counter++;
            }
        }
        if(is_null($this->where)) {
            $this->where = $where;
        } else {
            $this->where .= $where;
        }
        $this->args = array_merge($this->args, $args);
        $this->last_operation = "where";
        return $this;
    }

    public function group(...$columns) {
        if($columns) {
            $group = '';
            if(is_null($this->group)) {
                $group = " GROUP BY";
            } else {
                $group = ",";
            }
            foreach($columns as $column) {
                if(!$this->check_parameters($column)) {
                    return $this;
                }
                $group .= " `$column`,";
            }
            $group = substr($group, 0, -1);
            if(is_null($this->group)) {
                $this->group = $group;
            } else {
                $this->group .= $group;
            }
            $this->last_operation = "group";
        }
        return $this;
    }

    public function having($column, $value, $operand='=', $value_is_column=FALSE) {
        if(is_null($this->group)) {
            trigger_error("Can't use 'having' clause without using 'group'", E_USER_WARNING);
            return $this;
        }
        if(!$this->check_parameters($column, $operand)) {
            return $this;
        }
        $having = '';
        $args = array();
        if(is_null($this->having)) {
            $having .= " HAVING";
        }
        if($value_is_column) {
            if(!$this->check_parameters($value)) {
                return $this;
            }
            $having .= " `$column` ".$operand." $value";
        } else {
            if(is_null($value)) {
                if(in_array($operand, array('<>', 'is not', 'not like'))) {
                    $having .= " `$column` IS NOT NULL";
                } else {
                    $having .= " `$column` IS NULL";
                }
            } else {
                $having .= " `$column` ".$operand." :" .$column.$this->args_key_counter;
                $args[":".$column.$this->args_key_counter] = $value;
                $this->args_key_counter++;
            }
        }
        if(is_null($this->where)) {
            $this->having = $having;
        } else {
            $this->having .= $having;
        }
        $this->args = array_merge($this->args, $args);
        $this->last_operation = "having";
        return $this;
    }

    public function order(...$columns) {
        if($columns) {
            $order = '';
            if(is_null($this->order)) {
                $order = " ORDER BY";
            } else {
                $order = ",";
            }
            foreach($columns as $key => $column) {
                if(!is_array($column)) {
                    $column = array($column);
                }
                if(!$this->check_parameters($column[0])) {
                    return $this;
                }
                if(!array_key_exists(1, $column)) {
                    $column[1] = 'ASC';
                }
                if(!in_array(strtoupper($column[1]), array('ASC', 'DESC'))) {
                    trigger_error("Order direction has strange value, ASC used instead", E_USER_WARNING);
                    $column[1] = 'ASC';
                }
                $order .= " `{$column[0]}` ".strtoupper($column[1]).",";
            }
            $order = substr($order, 0, -1);
            if(is_null($this->order)) {
                $this->order = $order;
            } else {
                $this->order .= $order;
            }
            $this->last_operation = "order";
            $this->order_pairs = $columns;
        }
        return $this;
    }

    public function limit($number, $index=null){
        if(!Stalker_Validator::regex_check($number, 'number')
        || !is_null($index) && !Stalker_Validator::regex_check($index, 'number')) {
            trigger_error("Limit parameters are not numbers", E_USER_WARNING);
            return $this;
        }
        $this->limit = " LIMIT :limit1";
        $this->args[":limit1"] = $number;
        if(!is_null($index)) {
            $this->limit .= ", :limit2";
            $this->args[":limit2"] = $index;
        }
        $this->last_operation = "limit";
        return $this;
    }

    public function and_q($column, $value, $operand='=') {
        if(!$this->check_parameters($column, $operand)) {
            return $this;
        }
        if(!in_array($this->last_operation, array('where', 'having'))) {
            trigger_error("Can't use '".__FUNCTION__."' for '{$this->last_operation}' operation", E_USER_WARNING);
            return $this;
        }
        $this->{$this->last_operation} .= " AND";
        return $this->{$this->last_operation}($column, $value, $operand);
    }

    public function or_q($column, $value, $operand='=') {
        if(!$this->check_parameters($column, $operand)) {
            return $this;
        }
        if(!in_array($this->last_operation, array('where', 'having'))) {
            trigger_error("Can't use '".__FUNCTION__."' for '{$this->last_operation}' operation", E_USER_WARNING);
            return $this;
        }
        $this->{$this->last_operation} .= " OR";
        return $this->{$this->last_operation}($column, $value, $operand);
    }

    protected function check_parameters($column, $operand=null) {
        if(!Information_Schema::table_has_column($this->table_name, $column)) {
            trigger_error("Table '{$this->table_name}' doesn't have a column named '$column'", E_USER_WARNING);
            return FALSE;
        }

        if(!is_null($operand) && !in_array($operand, $this->supported_operands)) {
            trigger_error("The operand used in a query is not supported", E_USER_WARNING);
            return FALSE;
        }
        return TRUE;
    }

    // get data
    public function fetch() {
        $stmt = $this->db->execute("SELECT *
                                    FROM `{$this->table_name}`
                                    {$this->where}
                                    {$this->group}
                                    {$this->having}
                                    {$this->order}
                                    {$this->limit}",
                                $this->args);
        $results = $stmt ->fetchAll();
        $instances = [];
        foreach ($results as $result)
        {
            $instances[] = new $this->table_name($result);
        }
        return $instances;
    }

    public function first($number_of_records=1) {
        if(!Stalker_Validator::is_id($number_of_records)) {
            $number_of_records=1;
        }
        $results = $this->limit($number_of_records)->fetch();
        if($number_of_records == 1 && count($results) > 0) {
            return $results[0];
        }
        return $results;
    }

    public function last($number_of_records=1) {
        if(!Stalker_Validator::is_id($number_of_records)) {
            $number_of_records=1;
        }
        $reversed_orders = array();
        if(empty($this->order_pairs)) {
            $reversed_orders[] = array('id', 'DESC');
        } else {
            foreach ($this->order_pairs as $key => $value) {
                if(!is_array($value)) {
                    $value = array($value);
                }
                // set defaults
                $reversed_orders[$key][0] = $value[0];
                if(!array_key_exists(1, $value)) {
                    $value[1] = 'ASC';
                    $reversed_orders[$key][1] = 'ASC';
                }
                if(!in_array(strtoupper($value[1]), array('ASC', 'DESC'))) {
                    trigger_error("Order direction has strange value, ASC used instead", E_USER_WARNING);
                    $value[1] = 'ASC';
                    $reversed_orders[$key][1] = 'ASC';
                }
                //reverse order
                if(strtoupper($value[1]) == 'ASC') {
                    $reversed_orders[$key][1] = 'DESC';
                } else {
                    $reversed_orders[1] = 'ASC';
                }
            }
        }
        $this->order = null;
        $results = $this->order(...$reversed_orders)->limit($number_of_records)->fetch();
        if($number_of_records == 1 && count($results) > 0) {
            return $results[0];
        }
        return $results;
    }

}
