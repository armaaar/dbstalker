<?php

class Stalker_Migrator extends Information_Schema
{

    public static function table_need_migration(Stalker_Table $table) {
        if(!self::database_table_exist( strtolower(get_class($table)) )) {
            return TRUE;
        } else {
           return self::table_migration_info($table);
        }
    }

    public static function table_migrate(Stalker_Table $table) {
        if(!self::database_table_exist( strtolower(get_class($table)) )) {
            $result = self::create_table($table);
        } else {
            $result = self::migrate_table($table);
        }
        return $result;
    }

    public static function need_migration() {
        $tables = Stalker_Registerar::get_registerd_tables();
        if($tables) {
            foreach ($tables as $table_name => $table) {
                if(self::table_need_migration($table))
                {
                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    public static function migrate($drop_extra=TRUE) {
        // migrate defined tables
        $tables = Stalker_Registerar::get_registerd_tables();
        if($tables) {
            foreach ($tables as $table_name => $table) {
                self::table_migrate($table);
            }
        }
        // drop additional db tables
        if($drop_extra) {
            $existing_tables = self::get_database_tables();
            $tables_to_drop = array_diff($existing_tables, array_keys($tables));
            if($tables_to_drop) {
                foreach ($tables_to_drop as $table_name) {
                    self::drop_table($table_name);
                }
            }
        }
        // migrate defined views
        self::migrate_views($drop_extra);
        return TRUE;
    }

    public static function need_migration_data() {
        $tables = Stalker_Registerar::get_registerd_tables();
        if($tables) {
            $data = array();
            foreach ($tables as $table_name => $table) {
                if(!self::database_table_exist($table_name)) {
                    $data[$table_name] = "create";
                } else {
                    $info = self::table_migration_info($table, TRUE);
                    if($info) {
                        $data[$table_name] = $info;
                    }
                }
            }
            return $data;
        }
        return FALSE;
    }

    public static function database_table_exist($table_name) {
        $existing_tables = self::get_database_tables();
        if($existing_tables) {
            return in_array($table_name, $existing_tables);
        }
        return FALSE;
    }

    protected static function table_migration_info(Stalker_Table $table, $return_errors=FALSE) {
        $existing_table_fields = self::get_table_description(strtolower(get_class($table)));
        $sync_cols = array();
        $errors = array();
        foreach ($table->schema as $name => $col) {
            $key = array_search($name, array_column($existing_table_fields, 'Field'));
            if($key === FALSE) {
                if(!$return_errors) {
                    return TRUE;
                }
                $errors[$name] = array("add", $col, NULL);
                continue;
            }
            $sync_cols[] = $name;
            preg_match('/^(\w+)(?:\((.+)?\))?\s?(.+(?!\)))?/', $existing_table_fields[$key]['Type'], $matches);
            if(!array_key_exists(2, $matches)) {
                $matches[2] = NULL;
            }
            if(!array_key_exists(3, $matches)) {
                $matches[3] = NULL;
            }
            if($matches[1] != $col['type'][0]) {
                if(!$return_errors) {
                    return TRUE;
                }
                $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                continue;
            }
            if(array_key_exists(1, $col['type'])){
                if(is_null($matches[2])) {
                    if(!$return_errors) {
                        return TRUE;
                    }
                    $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                    continue;
                }
                if(is_array($col['type'][1])) {
                    $col['type'][1] = "'" . implode ( "','", $col['type'][1] ) . "'";
                }
                if($col['type'][1] != $matches[2]) {
                    if(!$return_errors) {
                        return TRUE;
                    }
                    $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                    continue;
                }
            }

            if(!array_key_exists('attribute', $col) && !is_null($matches[3])
            || array_key_exists('attribute', $col) && is_null($matches[3])
            || array_key_exists('attribute', $col) && !is_null($matches[3])
                && strtolower($matches[3]) != strtolower($col['attribute'])) {
                if(!$return_errors) {
                    return TRUE;
                }
                $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                continue;
            }

            if(array_key_exists('null', $col) && $col['null']) {
                if($existing_table_fields[$key]['Null'] == 'NO') {
                    if(!$return_errors) {
                        return TRUE;
                    }
                    $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                    continue;
                }
            } else {
                if($existing_table_fields[$key]['Null'] == 'YES') {
                    if(!$return_errors) {
                        return TRUE;
                    }
                    $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                    continue;
                }
            }

            if(array_key_exists('default', $col)) {
                if(is_null($col['default']) && !is_null($existing_table_fields[$key]['Default'])) {
                    if(!$return_errors) {
                        return TRUE;
                    }
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
                    if(!$return_errors) {
                        return TRUE;
                    }
                    $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                    continue;
                }

            } elseif(!is_null($existing_table_fields[$key]['Default'])) {
                if(!$return_errors) {
                    return TRUE;
                }
                $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                continue;
            }

            if(array_key_exists('ai', $col) && $col['ai'] && $existing_table_fields[$key]['Extra'] != 'auto_increment') {
                if(!$return_errors) {
                    return TRUE;
                }
                $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                continue;
            }

            if( !array_key_exists('key', $col) && $existing_table_fields[$key]['Key']
            || array_key_exists('key', $col) && $col['key'] != $existing_table_fields[$key]['Key']) {
                if(!$return_errors) {
                    return TRUE;
                }
                $errors[$name] = array("modify", $col, $existing_table_fields[$key]);
                continue;
            }
        }

        $unsync_cols = array_diff(array_column($existing_table_fields, 'Field'), $sync_cols);
        if($unsync_cols){
            if(!$return_errors) {
                return TRUE;
            }
            foreach ($unsync_cols as $col) {
                $key = array_search($col, array_column($existing_table_fields, 'Field'));
                $errors[$col] = array("drop", NULL, $existing_table_fields[$key]);
            }
        }
        if(!$return_errors) {
            return FALSE;
        }
        return $errors;
    }

    protected static function create_table(Stalker_Table $table) {
        $self = new static();
        $table_name = strtolower(get_class($table));
        $schema = $table->schema;
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
            if(array_key_exists('attribute', $col)) {
                $query .= " ".$col['attribute'];
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
                        trigger_error("Default value for column '$name' of unknown type ".$default_type, E_USER_ERROR);
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
        $settings = Stalker_Configuration::table_settings();
        $self->db->execute("CREATE TABLE IF NOT EXISTS `$table_name` ($query)
                        ENGINE={$settings->engine}
                        CHARACTER SET {$settings->charset}
                        COLLATE {$settings->collation}");
        return TRUE;
    }

    protected static function migrate_table(Stalker_Table $table) {
        $self = new static();
        $cols = self::table_migration_info($table, TRUE);
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
                    if(array_key_exists('attribute', $col[1])) {
                        $query .= " ".$col[1]['attribute'];
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
                                trigger_error("Default value for column '$name' of unknown type ".$default_type, E_USER_ERROR);
                                die();
                            }
                        }
                    }

                    if(array_key_exists('ai', $col[1]) && $col[1]['ai']) {
                        $query .= " AUTO_INCREMENT";
                    }

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
                } else {
                    $query .= "DROP COLUMN `$name`";
                }
                $query .= ",";
            }

            $query = substr($query, 0, -1);
            $table_name = strtolower(get_class($table));
            $self->db->execute("ALTER TABLE `{$table_name}` $drop_pri $query;");
        }
        return TRUE;
    }

    protected static function drop_table($table_name) {
        $self = new static();
        $self->db->execute("DROP TABLE `$table_name`;");
        return TRUE;
    }

    // views
    public static function migrate_views($drop_extra=TRUE) {
        // migrate defined views
        $views = Stalker_Registerar::get_registerd_views();
        if($views) {
            foreach ($views as $view_name => $view) {
                self::view_migrate($view);
            }
        }
        // drop additional db views
        if($drop_extra) {
            $existing_views = self::get_database_views();
            $views_to_drop = array_diff($existing_views, array_keys($views));
            if($views_to_drop) {
                foreach ($views_to_drop as $view_name) {
                    self::drop_view($view_name);
                }
            }
        }
        return TRUE;
    }

    public static function view_migrate(Stalker_View $view) {
        $self = new static();
        $view_name = $view->view_name;
        $query = $view->view_query();
        $self->db->execute("CREATE OR REPLACE VIEW `$view_name` AS $query;");
        return TRUE;
    }

    protected static function drop_view($view_name) {
        $self = new static();
        $self->db->execute("DROP VIEW `$view_name`;");
        return TRUE;
    }
}
?>
