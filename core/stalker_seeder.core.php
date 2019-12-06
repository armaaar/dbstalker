<?php

class Stalker_Seeder {
    protected $db;

    protected function __construct() {
        $this->db = Stalker_Database::instance();
    }
    public static function seed_main_seeds($force_seed = FALSE) {
        $seeds = Stalker_Registerar::get_seeds();
        if($seeds) {
            foreach ($seeds as $table_name => $seed) {
                self::table_seed($table_name, $seed, TRUE, $force_seed);
            }
        }
        return TRUE;
    }

    public static function delete_main_seeds() {
        $seeds = Stalker_Registerar::get_seeds();
        if($seeds) {
            foreach ($seeds as $table_name => $seed) {
                self::table_delete_seed($table_name, TRUE);
            }
        }
        return TRUE;
    }

    public static function seed_table_main_seeds($table_name, $force_seed = FALSE) {
        $seed = Stalker_Registerar::get_table_seed($table_name);
        if($seed) {
            self::table_seed($table_name, $seed, TRUE, $force_seed);
        } else {
            return FALSE;
        }
        return TRUE;
    }

    public static function delete_table_main_seeds($table_name) {
        return self::table_delete_seed($table_name, TRUE);
    }

    public static function seed_temporary_seeds() {
        $seeds = Stalker_Registerar::get_seeds();
        if($seeds) {
            foreach ($seeds as $table_name => $seed) {
                self::table_seed($table_name, $seed, FALSE);
            }
        }
        return TRUE;
    }

    public static function delete_temporary_seeds() {
        $seeds = Stalker_Registerar::get_seeds();
        if($seeds) {
            foreach ($seeds as $table_name => $seed) {
                self::table_delete_seed($table_name, FALSE);
            }
        }
        return TRUE;
    }

    public static function seed_table_temporary_seeds($table_name) {
        $seed = Stalker_Registerar::get_table_seed($table_name);
        if($seed) {
            self::table_seed($table_name, $seed, FALSE);
        } else {
            return FALSE;
        }
        return TRUE;
    }

    public static function delete_table_temporary_seeds($table_name) {
        return self::table_delete_seed($table_name, FALSE);
    }

    protected static function table_seed($table_name, Stalker_Seed $seed, $main_seed, $force_seed = FALSE) {
        if($main_seed) {
            $data = $seed->main_seed();
        } else {
            $data = $seed->temporary_seed();
        }
        foreach ($data as $index => $row) {
            if($main_seed && !array_key_exists('id', $row)) {
                trigger_error("Main seeds for table '$table_name' has no 'id' value.", E_USER_WARNING);
			    continue;
            }
            $row_force_seed = $force_seed;
            if(array_key_exists('__forced', $row) && $row['__forced'] == TRUE) {
                $row_force_seed = TRUE;
                unset($row['__forced']);
            }
            $table = new $table_name($row);
            $errors = $table->validate();
            if(!$errors) {
                if($main_seed) {
                    $row[Stalker_Schema::SEED_COLUMN] = 1;
                } else {
                    $row[Stalker_Schema::SEED_COLUMN] = 0;
                }
                if($main_seed && self::seed_exists($table_name, $row['id'])) {
                    if($row_force_seed) {
                        self::update_seed_row($table_name, $row);
                    }
                } else {
                    self::insert_seed_row($table_name, $row);
                }
            } else {
                if($main_seed) {
                    trigger_error("Main seed with id={$row['id']} for table '$table_name' is not valid.", E_USER_WARNING);
                } else {
                    trigger_error("Temporary seed with index={$index} for table '$table_name' is not valid.", E_USER_WARNING);
                }
                continue;
            }
        }
    }

    protected static function seed_exists($table_name, $id) {
        $table_name = strtolower($table_name);
        $self = new static();
        $stmt = $self->db->execute("SELECT `id` FROM `$table_name` WHERE `id`=?", array($id));
        if($stmt ->fetchAll()) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    protected static function insert_seed_row($table_name, array $data) {
        $table_name = strtolower($table_name);
        if(!Stalker_Migrator::database_table_exist($table_name)) {
            trigger_error("Table '$table_name' doesn't exist.", E_USER_ERROR);
            exit();
        }
        $self = new static();
        $args=[];
        $columns = '';
        $values = '';

        foreach ($data as $key => $value) {
            $columns .= "`$key`,";
            $values .= ":$key,";
            if($value === TRUE) {
                $value = 1;
            } elseif($value === FALSE) {
                $value = 0;
            }
            $args[":$key"] = $value;
        }
        $columns = rtrim($columns,',');
        $values = rtrim($values,',');

        $stmt = $self->db->execute("INSERT INTO `$table_name` ($columns) VALUES($values)", $args);
        return $self->db->lastInsertId();
    }

    protected static function update_seed_row($table_name, array $data) {
        $table_name = strtolower($table_name);
        if(!Stalker_Migrator::database_table_exist($table_name)) {
            trigger_error("Table '$table_name' doesn't exist.", E_USER_ERROR);
            exit();
        }
        $self = new static();
        $args=[];
        $set = '';
        foreach ($data as $key => $value) {
            if($value === TRUE) {
                $value = 1;
            } elseif($value === FALSE) {
                $value = 0;
            }

            if($key != 'id')
            {
                $set .= "`$key`=:$key,";
            }
            $args[":$key"] = $value;
        }
        $set = rtrim($set,',');
        $stmt = $self->db->execute("UPDATE `$table_name` SET $set WHERE `id`=:id LIMIT 1;", $args);
        return true;
    }

    protected static function table_delete_seed($table_name, $main_seed) {
        $table_name = strtolower($table_name);
        if(!Stalker_Migrator::database_table_exist($table_name)) {
            trigger_error("Table '$table_name' doesn't exist.", E_USER_ERROR);
            exit();
        }
        $self = new static();
        $seed_col = Stalker_Schema::SEED_COLUMN;
        if($main_seed) {
            $args[':main_seed'] = 1;
        } else {
            $args[':main_seed'] = 0;
        }
        $stmt = $self->db->execute("DELETE FROM `$table_name` WHERE `$seed_col`=:main_seed;", $args);
        return true;
    }
}
