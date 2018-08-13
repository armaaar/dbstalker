<?php

class Stalker_Migrator extends Stalker_Database
{
    public static function get_database_tables(){
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

    public static function get_table_description(string $table_name){
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
            
        } elseif(self::table_need_migration($table)) {

        }
    }

    public static function table_need_migration(Stalker_Table $table) {
        $self = new static();
        $existing_table_fields = self::get_table_description($table->table_name);
        $sync_cols = array();
        $errors = array();
        foreach ($table->schema() as $name => $col) {
            $key = array_search($name, array_column($existing_table_fields, 'Field'));
            if($key === FALSE) {
                $errors[$name] = "add";
                continue;
            }
            $sync_cols[] = $name;
            preg_match('/^(\w+)\(?([^)]+)?\)?$/', $existing_table_fields[$key]['Type'], $matches, PREG_UNMATCHED_AS_NULL);
            if($matches[1] != $col['type'][0]) {
                $errors[$name] = "modify";
                continue;
            }
            if(array_key_exists(1, $col['type'])){
                if(is_null($matches[2])) {
                    $errors[$name] = "modify";
                    continue;
                }
                if(is_array($col['type'][1])) {
                    $col['type'][1] = "'" . implode ( "','", $col['type'][1] ) . "'";
                }
                if($col['type'][1] != $matches[2]) {
                    $errors[$name] = "modify";
                    continue;
                }
            }

            if(array_key_exists('null', $col) && $col['null']) {
                if($existing_table_fields[$key]['Null'] == 'NO') {
                    $errors[$name] = "modify";
                    continue;
                }
            } else {
                if($existing_table_fields[$key]['Null'] == 'YES') {
                    $errors[$name] = "modify";
                    continue;
                }
            }

            if(array_key_exists('default', $col)) {
                if(is_null($col['default']) && !is_null($existing_table_fields[$key]['Default'])) {
                    $errors[$name] = "modify";
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
                    $errors[$name] = "modify";
                    continue;
                }
                
            } elseif(!is_null($existing_table_fields[$key]['Default'])) {
                $errors[$name] = "modify";
                continue;
            }

            if(array_key_exists('ai', $col) && $col['ai'] && $existing_table_fields[$key]['Extra'] != 'auto_increment') {
                $errors[$name] = "modify";
                continue;
            }

            if(array_key_exists('key', $col) && $col['key'] != $existing_table_fields[$key]['Key']) {
                $errors[$name] = "modify";
                continue;
            }
        }

        $unsync_cols = array_diff(array_column($existing_table_fields, 'Field'), $sync_cols);
        if($unsync_cols){
            foreach ($unsync_cols as $col) {
                $errors[$col] = "drop";
            }
        }
        return $errors;
    }
    
}
?>
