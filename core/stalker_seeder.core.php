<?php

class Stalker_Seeder extends Stalker_Database {

    public static function seed_main_seeds(bool $force_seed = FALSE) {
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

    public static function seed_table_main_seeds(string $table_name, bool $force_seed = FALSE) {
        $seed = Stalker_Registerar::get_table_seed($table_name);
        if($seed) {
            self::table_seed($table_name, $seed, TRUE, $force_seed);
        } else {
            return FALSE;
        }
        return TRUE;
    }

    public static function delete_table_main_seeds(string $table_name) {
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

    public static function seed_table_temporary_seeds(string $table_name) {
        $seed = Stalker_Registerar::get_table_seed($table_name);
        if($seed) {
            self::table_seed($table_name, $seed, FALSE);
        } else {
            return FALSE;
        }
        return TRUE;
    }

    public static function delete_table_temporary_seeds(string $table_name) {
        return self::table_delete_seed($table_name, FALSE);
    }

    protected static function table_seed(string $table_name, Stalker_Seed $seed, bool $main_seed, bool $force_seed = FALSE) {
        if($main_seed) {
            $data = $seed->main_seed();
        } else {
            $data = $seed->temporary_seed();
        }
        foreach ($data as $row) {
            if($main_seed && !array_key_exists('id', $row)) {
                trigger_error("Main seeds for table '$table_name' has no 'id' value.", E_USER_WARNING);
			    continue;
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
                    if($force_seed) {
                        self::update_seed_row($table_name, $row);
                    }
                } else {
                    self::insert_seed_row($table_name, $row);
                }
            } else {
                trigger_error("Main seed with id={$row['id']} for table '$table_name' is not valid.", E_USER_WARNING);
                continue;
            }
        }
    }

    protected static function seed_exists(string $table_name, int $id) {
        $table_name = strtolower($table_name);
        $self = new static();
        $stmt = $self->execute("SELECT `id` FROM `$table_name` WHERE `id`=?", array($id));
        if($stmt ->fetchAll()) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    protected static function insert_seed_row(string $table_name, array $data) {
        $table_name = strtolower($table_name);
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

        $stmt = $self->execute("INSERT INTO `$table_name` ($columns) VALUES($values)", $args);
        return $self->lastInsertId();
    }

    protected static function update_seed_row(string $table_name, array $data) {
        $table_name = strtolower($table_name);
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
        $stmt = $self->execute("UPDATE `$table_name` SET $set WHERE `id`=:id LIMIT 1;", $args);
        return true;
    }

    protected static function table_delete_seed(string $table_name, bool $main_seed) {
        $table_name = strtolower($table_name);
        $self = new static();
        $seed_col = Stalker_Schema::SEED_COLUMN;
        if($main_seed) {
            $args[':main_seed'] = 1;
        } else {
            $args[':main_seed'] = 0;
        }
        $stmt = $self->execute("DELETE FROM `$table_name` WHERE `$seed_col`=:main_seed;", $args);
        return true;
    }
}
