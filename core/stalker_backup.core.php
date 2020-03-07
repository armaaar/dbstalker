<?php

class Stalker_Backup extends Information_Schema
{

    const SQL_SEPARATOR = "\n--\n-- --------------------------------------------------------\n--\n";

    protected static function create_table_query($table_name, $file) {
        $table_cols = self::get_table_description($table_name);
        $pri_keys= array();
        $uni_keys= array();
        $mul_keys= array();
        // create if not exist
        $query = "CREATE TABLE IF NOT EXISTS `$table_name` (\n";
        foreach ($table_cols as $col) {
            $query .= "  `{$col['Field']}` {$col['Type']}";

            if($col['Null'] == 'YES') {
                $query .= " NULL";
            } else {
                $query .= " NOT NULL";
            }

            if($col['Extra'] == "auto_increment") {
                $query .= " AUTO_INCREMENT";
            }

            if(!is_null($col['Default'])) {
                $query .= " DEFAULT '{$col['Default']}'";
            } elseif($col['Null'] == 'YES') {
                $query .= " DEFAULT NULL";
            }

            $query .= ",\n";

            if($col['Key'] == "PRI") {
                $pri_keys[] = $col['Field'];
            } elseif($col['Key'] == "UNI") {
                $uni_keys[] = $col['Field'];
            } elseif($col['Key'] == "MUL") {
                $mul_keys[] = $col['Field'];
            }
        }
        if($pri_keys) {
            $query .= "  PRIMARY KEY (`" . implode ( "`, `", $pri_keys ) . "`),\n";
        }
        if($mul_keys) {
            foreach ($mul_keys as $key) {
                $query .= "  INDEX (`$key`),\n";
            }
        }
        if($uni_keys) {
            foreach ($uni_keys as $key) {
                $query .= "  UNIQUE (`$key`),\n";
            }
        }
        // remove last comma and newline
        $query = substr($query, 0, -2);
        $query .= "\n";
        // add table info schema
        $table_info = self::get_table_info($table_name);
        $table_charset = explode("_", $table_info['TABLE_COLLATION']);
        $table_charset = $table_charset[0];
        $query .= ")";
        $query .= " ENGINE={$table_info['ENGINE']}";
        $query .= " AUTO_INCREMENT={$table_info['AUTO_INCREMENT']}";
        $query .= " CHARACTER SET {$table_charset}";
        $query .= " COLLATE {$table_info['TABLE_COLLATION']};";



        file_put_contents($file, $query, FILE_APPEND | LOCK_EX);
        return true;
    }

    protected static function dump_table_query($table_name, $file) {
        $query = "";
        $self = new static();
        $stmt = $self->db->execute("SELECT * FROM `$table_name`");
        $rows = $stmt ->fetchAll();
        if($rows) {
            // start insert into statement
            $query .= "INSERT INTO `$table_name` (";
            // get cols names
            foreach ($rows[0] as $key => $value) {
                $query .= "`$key`, ";
            }
            // remove last comma and space
            $query = substr($query, 0, -2);
            $query .= ") VALUES\n";

            file_put_contents($file, $query, FILE_APPEND | LOCK_EX);
            $query = "";

            // dump data
            $iterator = 1;
            $rows_count = count($rows);
            foreach ($rows as $row) {
                $in_iterator = 1;
                $values_count = count((array)$row);
                $query .= "(";
                foreach ($row as $key => $value) {
                    if(is_null($value)) {
                        $query .= "NULL";
                    } elseif(is_string($value)) {
                        $query .= "'".addcslashes($value,"\"'\n")."'";
                    } else {
                        $query .= "$value";
                    }
                    if($in_iterator++ < $values_count) {
                        $query .= ", ";
                    }
                }
                $query .= ")";
                if($iterator++ < $rows_count) {
                    $query .= ",\n";
                }
                file_put_contents($file, $query, FILE_APPEND | LOCK_EX);
                $query = "";
            }
            $query .= ";";
            file_put_contents($file, $query, FILE_APPEND | LOCK_EX);
            $query = "";
        }
        return true;
    }

    private static function create_backup_query($file) {
        $self = new static();
        // add general info
        $query = "-- Database Stalker SQL Dump\n";
        $query .= "--\n";
        $query .= "-- Host: {$_SERVER['SERVER_NAME']}\n";
        $query .= "-- Generation Time: ".date("M j, Y")." at ".date("h:i A")."\n";
        $query .= "-- Server Vversion: {$self->version['sql']}\n";
        $query .= "-- PHP Version: {$self->version['php']}\n";
        $query .= "\n";
        // set default transaction settings
        $query .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";'."\n";
        $query .= 'SET AUTOCOMMIT = 0;'."\n";
        $query .= 'START TRANSACTION;'."\n";
        $query .= 'SET time_zone = "+00:00";'."\n";

        file_put_contents($file, $query, FILE_APPEND | LOCK_EX);
        $query = "";

        // insert tables
        $db_tables = self::get_database_tables();
        if($db_tables) {
            foreach ($db_tables as $table_name) {
                $query .= self::SQL_SEPARATOR;

                $query .= "\n--\n-- Table structure for table `$table_name`\n--\n\n";
                // drop table if exist
                $query .= "DROP TABLE IF EXISTS `$table_name`;\n";
                // create table schema

                file_put_contents($file, $query, FILE_APPEND | LOCK_EX);
                $query = "";

                self::create_table_query($table_name, $file);
                $query .= "\n";

                // insert data into table
                $query .= "\n--\n-- Dumping data for table `$table_name`\n--\n\n";

                file_put_contents($file, $query, FILE_APPEND | LOCK_EX);
                $query = "";

                self::dump_table_query($table_name, $file);
                $query .= "\n";

                file_put_contents($file, $query, FILE_APPEND | LOCK_EX);
                $query = "";
            }
        }

        // insert views
        $db_views = self::get_database_views();
        if($db_views) {
            foreach ($db_views as $view_name) {
                $query .= self::SQL_SEPARATOR;

                $query .= "\n--\n-- Table structure for view `$view_name`\n--\n\n";
                // drop table if exist
                $query .= "DROP TABLE IF EXISTS `$view_name`;\n";
                // create table schema
                $query .= "CREATE OR REPLACE VIEW `$view_name` AS ";
                $query .= str_replace("`{$self->connection->database}`.", "", self::get_view_definition($view_name));
                $query .= ";\n";

                file_put_contents($file, $query, FILE_APPEND | LOCK_EX);
                $query = "";
            }
        }

        // commit query
        $query .= "\n".'COMMIT;'."\n";

        file_put_contents($file, $query, FILE_APPEND | LOCK_EX);
        return true;
    }

    public static function create_backup() {
        $self = new static();
        $backup_dir = './backups';
        $backup_file = "stalker-backup~{$self->connection->database}~".date("Y-m-d~His").".sql";

        try {
            if (!file_exists($backup_dir)) {
                mkdir($backup_dir, 0777, true);
            } elseif($self->backup_settings->per_day > 0 || $self->backup_settings->max > 0) {
                $today = date("Y-m-d");
                $today_backups = array();
                $backups = array();
                // get all database backups
                foreach ( glob($backup_dir."/*.sql") as $file ) {
                    $explosion = explode('~', $file);
                    $backup_database = $explosion[1];
                    $backup_date = $explosion[2];
                    $backup_series = $explosion[3];
                    // if the backup is for the right database
                    if($backup_database == $self->connection->database) {
                        $backups[$backup_date.$backup_series] = $file;
                        // if there is another backup today
                        if($backup_date == $today) {
                            $today_backups[$backup_series] = $file;
                        }
                    }
                }
                // delete additional backups for today
                if($self->backup_settings->per_day > 0) {
                    if(!empty($today_backups)) {
                        // +1 cause I'm gonna add another backup at the end of the function
                        $files_to_delete = count($today_backups) - $self->backup_settings->per_day + 1;
                        if($files_to_delete > 0) {
                            // sort backups by series desc
                            krsort($today_backups);
                            for ($i=0; $i < $files_to_delete ; $i++) {
                                // get file
                                $file = end($today_backups);
                                // get series
                                $file_series = key($today_backups);
                                // delete file from today backups array
                                unset($today_backups[$file_series]);
                                // delete file from database backups array
                                unset($backups[$today.$file_series]);
                                // delete backup
                                unlink($file);
                            }
                        }
                    }
                }
                // delete additional backups
                if($self->backup_settings->max > 0) {
                    if(!empty($backups)) {
                        // +1 cause I'm gonna add another backup at the end of the function
                        $files_to_delete = count($backups) - $self->backup_settings->max + 1;
                        if($files_to_delete > 0) {
                            // sort backups by date and series desc
                            krsort($backups);
                            for ($i=0; $i < $files_to_delete ; $i++) {
                                // get file
                                $file = end($backups);
                                // get key
                                $file_key = key($backups);
                                // delete file from database backups array
                                unset($backups[$file_key]);
                                // delete backup
                                unlink($file);
                            }
                        }
                    }
                }

            }
            // create the backup file
            self::create_backup_query($backup_dir.'/'.$backup_file);
        } catch (Exception $ex) {
			$trace = debug_backtrace();
			$caller = $trace[1];
			trigger_error($caller['class']. "::" .$caller['function']. " -> " . $ex -> getMessage(), E_USER_ERROR);
            return FALSE;
        }
        return TRUE;
    }

    public static function restore_backup($date=null, $series=null) {
        $self = new static();
        $backup_file = null;
        $max_date = "0000-00-00";
        $max_series = "000000.sql";

        $date_valid = (!empty($date) && Stalker_Validator::regex_check($date, 'date'));
        $series_valid = (
            !empty($series) &&
            Stalker_Validator::regex_check($series, 'number') &&
            $series[0] != '-' &&
            strlen($series) == 6
        );

        // check parameter errors
        if(!$date_valid && !is_null($date)) {
            return FALSE;
        }
        if($series_valid) {
            if(!$date_valid) {
                $series_valid = FALSE;
            } else {
                $series .= ".sql";
            }
        } elseif(!is_null($series)) {
            return FALSE;
        }
        foreach ( glob("./backups/*.sql") as $file ) {
            $explosion = explode('~', $file);
            $backup_database = $explosion[1];
            $backup_date = $explosion[2];
            $backup_series = $explosion[3];
            // if the backup is for the right database
            if($backup_database == $self->connection->database) {
                // if a date and series are specified
                if($date_valid && $series_valid) {
                    if($backup_date == $date && $backup_series == $series) {
                        $backup_file = $file;
                        break;
                    }
                } elseif($date_valid) {
                    if($backup_date == $date && $backup_series > $max_series) {
                        $max_series = $backup_series;
                        $backup_file = $file;
                    }
                } else { // get the latest backup
                    if($backup_date > $max_date
                    || $backup_date == $max_date && $backup_series > $max_series) {
                        $max_date = $backup_date;
                        $max_series = $backup_series;
                        $backup_file = $file;
                    }
                }
            }
        }
        if(is_null($backup_file)) {
            return FALSE;
        }
        // get queries to run
        $self->db->beginTransaction();
        $file_pointer = fopen($backup_file, "r");
        $query = "";
        while(!feof($file_pointer)) {
            $query .= fgets($file_pointer);
            if (substr($query, -2) === ";\n") {
                if(strlen(trim($query)) != 0) {
                    if(
                        strpos(strtoupper($query), "START TRANSACTION") !== false ||
                        strpos(strtoupper($query), "COMMIT") !== false
                    ) {
                        $query = "";
                        continue;
                    }
                    $stmt = $self->db->unprepared_execute($query);
                }
                $query = "";
            }
        }
        fclose($file_pointer);
        $self->db->commit();
        return TRUE;
    }
}
