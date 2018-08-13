<?php

class Stalker_Migrator extends Stalker_Database
{
    protected static function get_database_tables(){
        $self = new static();
        $stmt = $self->execute("SELECT TABLE_NAME
                                FROM INFORMATION_SCHEMA.TABLES 
                                WHERE TABLE_SCHEMA = ?",
                            array($self->connection->database));
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $results = $stmt ->fetchAll();
        if(!$results) {
            return NULL;
        }
        return array_column($results, 'TABLE_NAME');
    }

    protected static function get_table_description(string $table_name){
        $self = new static();

        $stmt = $self->execute("DESCRIBE `$table_name`");
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $results = $stmt ->fetchAll();
        if(!$results) {
            return NULL;
        }
        return $results;
    }

    public static function table_migrate(Stalker_Table $table) {
        $self = new static();
        $existing_tables = self::get_database_tables();
        if(!in_array($table->table_name, $existing_tables)) {
            $result = $self->create_table($table);
        } else {
            $result = $self->migrate_table($table);
        }
        return $result;
    }

    protected static function table_need_migration(Stalker_Table $table) {
        $self = new static();
        $existing_table_fields = self::get_table_description($table->table_name);
        $sync_cols = array();
        $errors = array();
        foreach ($table->schema() as $name => $col) {
            $key = array_search($name, array_column($existing_table_fields, 'Field'));
            if($key === FALSE) {
                $errors[$name] = array("add", $col, NULL);
                continue;
            }
            $sync_cols[] = $name;
            preg_match('/^(\w+)\(?([^)]+)?\)?$/', $existing_table_fields[$key]['Type'], $matches, PREG_UNMATCHED_AS_NULL);
            if($matches[1] != $col['type'][0]) {
                $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                continue;
            }
            if(array_key_exists(1, $col['type'])){
                if(is_null($matches[2])) {
                    $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                    continue;
                }
                if(is_array($col['type'][1])) {
                    $col['type'][1] = "'" . implode ( "','", $col['type'][1] ) . "'";
                }
                if($col['type'][1] != $matches[2]) {
                    $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                    continue;
                }
            }

            if(array_key_exists('null', $col) && $col['null']) {
                if($existing_table_fields[$key]['Null'] == 'NO') {
                    $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                    continue;
                }
            } else {
                if($existing_table_fields[$key]['Null'] == 'YES') {
                    $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                    continue;
                }
            }

            if(array_key_exists('default', $col)) {
                if(is_null($col['default']) && !is_null($existing_table_fields[$key]['Default'])) {
                    $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                    continue;
                }

                $default_type = gettype($col['default']);
                if(gettype($col['default']) == "boolean") {
                    if($col['default']) {
                        $col['default'] = 1;
                    } else {
                        $col['default'] = 0;
                    }
                }

                if($existing_table_fields[$key]['Default'] != $col['default']) {
                    $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                    continue;
                }
                
            } elseif(!is_null($existing_table_fields[$key]['Default'])) {
                $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                continue;
            }

            if(array_key_exists('ai', $col) && $col['ai'] && $existing_table_fields[$key]['Extra'] != 'auto_increment') {
                $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                continue;
            }

            if(array_key_exists('key', $col) && $col['key'] != $existing_table_fields[$key]['Key']) {
                $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                continue;
            }
        }

        $unsync_cols = array_diff(array_column($existing_table_fields, 'Field'), $sync_cols);
        if($unsync_cols){
            foreach ($unsync_cols as $col) {
                $key = array_search($col, array_column($existing_table_fields, 'Field'));
                $errors[$col] = array("drop", NULL, $existing_table_fields[$key]);
            }
        }
        return $errors;
    }

    protected function create_table(Stalker_Table $table) {
        $table_name = $table->table_name;
        $schema = $table->schema();
        $query="";
        $pri_keys= array();
        $uni_keys= array();
        $mul_keys= array();
        foreach ($schema as $name => $col) {
            $query .= "`$name` {$col['type'][0]}";
            if(array_key_exists(1, $col['type'])) {
                if(is_array($col['type'][1])) {
                    $col['type'][1] = "'" . implode ( "','", $col['type'][1] ) . "'";
                }
                $query .= "({$col['type'][1]})";
            }
            if(array_key_exists('null', $col) && $col['null']) {
                $query .= " NULL";
            } else {
                $query .= " NOT NULL";
            }

            if(array_key_exists('default', $col)) {
                $query .= " DEFAULT";
                if(is_null($col['default'])) {
                    $query .= " NULL";
                } else {
                    $default_type = gettype($col['default']);
                    if($default_type == "string"){
                        $query .= " '{$col['default']}'";
                        
                    } elseif($default_type == "boolean") {
                        if($col['default']) {
                            $query .= " 1";
                        } else {
                            $query .= " 0";
                        }
                    } elseif(in_array($default_type, array('integer', 'double'))) {
                        $query .= " {$col['default']}";
                    } else {
                        error_log("FATAL: default value for column '$name' of unknown type ".$default_type);
			            die();
                    }
                }
            }

            if(array_key_exists('ai', $col) && $col['ai']) {
                $query .= " AUTO_INCREMENT";
            }

            if(array_key_exists('key', $col)) {
                if($col['key'] == "PRI") {
                    $pri_keys[] = $name;
                } elseif($col['key'] == "UNI") {
                    $uni_keys[] = $name;
                } elseif($col['key'] == "MUL") {
                    $mul_keys[] = $name;
                }
            }
            $query .= ",";
        }
        if($pri_keys) {
            $query .= " PRIMARY KEY (`" . implode ( "`, `", $pri_keys ) . "`),";
        }
        if($mul_keys) {
            foreach ($mul_keys as $key) {
                $query .= " INDEX (`$key`),";
            }
        }
        if($uni_keys) {
            foreach ($uni_keys as $key) {
                $query .= " UNIQUE (`$key`),";
            }
        }
        $query = substr($query, 0, -1);

        $this->execute("CREATE TABLE IF NOT EXISTS `$table_name` ($query) ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci");
        return TRUE;
    }
    
    protected function migrate_table(Stalker_Table $table) {
        $cols = self::table_need_migration($table);
        if($cols) {
            $query="";
            $drop_pri = '';
            foreach ($cols as $name => $col) {
                if($col[0] != 'drop') {
                    $query .= strtoupper($col[0])." `$name` {$col[1]['type'][0]}";
                    if(array_key_exists(1, $col[1]['type'])) {
                        if(is_array($col[1]['type'][1])) {
                            $col[1]['type'][1] = "'" . implode ( "','", $col[1]['type'][1] ) . "'";
                        }
                        $query .= "({$col[1]['type'][1]})";
                    }
                    if(array_key_exists('null', $col[1]) && $col[1]['null']) {
                        $query .= " NULL";
                    } else {
                        $query .= " NOT NULL";
                    }
        
                    if(array_key_exists('default', $col[1])) {
                        $query .= " DEFAULT";
                        if(is_null($col[1]['default'])) {
                            $query .= " NULL";
                        } else {
                            $default_type = gettype($col[1]['default']);
                            if($default_type == "string"){
                                $query .= " '{$col[1]['default']}'";
                                
                            } elseif($default_type == "boolean") {
                                if($col[1]['default']) {
                                    $query .= " 1";
                                } else {
                                    $query .= " 0";
                                }
                            } elseif(in_array($default_type, array('integer', 'double'))) {
                                $query .= " {$col[1]['default']}";
                            } else {
                                error_log("FATAL: default value for column '$name' of unknown type ".$default_type);
                                die();
                            }
                        }
                    }
        
                    if(array_key_exists('ai', $col[1]) && $col[1]['ai']) {
                        $query .= " AUTO_INCREMENT";
                    }
                } else {
                    $query .= "DROP COLUMN `$name`";
                }
                
                if($col[0] != 'drop') {
                    if(array_key_exists('key', $col[1])) {
                        if($col[1]['key'] != $col[2]['Key']) {
                            if($col[2]['Key'])
                            {
                                if($col[2]['Key'] == "PRI") {
                                    $drop_pri = "DROP PRIMARY KEY, ";
                                } else {
                                    $query .= ", DROP INDEX `$name`";
                                }
                            }
    
                            if($col[1]['key'] == "PRI") {
                                $query .= ", ADD PRIMARY KEY (`$name`)";
                            } elseif($col[1]['key'] == "UNI") {
                                $query .= ", ADD UNIQUE (`$name`)";
                            } elseif($col[1]['key'] == "MUL") {
                                $query .= ", ADD INDEX (`$name`)";
                            }
                        }
                    } elseif($col[2]['Key']) {
                        if($col[2]['Key'] == "PRI") {
                            $drop_pri = "DROP PRIMARY KEY, ";
                        } else {
                            $query .= ", DROP INDEX `$name`";
                        }
                    }
                }
                $query .= ",";
            }
            
            $query = substr($query, 0, -1);
            var_dump("ALTER TABLE `{$table->table_name}` $drop_pri $query;");
            $this->execute("ALTER TABLE `{$table->table_name}` $drop_pri $query;");
        }
        return TRUE;
    }
}
?>
