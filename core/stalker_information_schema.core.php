<?php

class Information_Schema
{
    protected $db;
	protected $connection;
    protected $version;

    protected function __construct() {
        $this->db = Stalker_Database::instance();
		$this->connection = Stalker_Configuration::database_connection();
		$this->backup_settings = Stalker_Configuration::backup_settings();
        $this->version = array();
        $this->version['php'] = phpversion();
        $stmt = $this->db->execute("select version()");
        $this->version['sql'] = $stmt ->fetchColumn();
    }

    // tables methods
    protected static function get_database_tables(){
        $self = new static();
        $stmt = $self->db->execute("SELECT TABLE_NAME
                                    FROM INFORMATION_SCHEMA.TABLES
                                    WHERE TABLE_SCHEMA = ?
                                        AND TABLE_TYPE = 'BASE TABLE'",
                                array($self->connection->database));
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $results = $stmt ->fetchAll();
        if(!$results) {
            return array();
        }
        return array_column($results, 'TABLE_NAME');
    }

    protected static function get_table_description($table_name){
        $self = new static();

        $stmt = $self->db->execute("DESCRIBE `$table_name`");
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $results = $stmt ->fetchAll();
        if(!$results) {
            return array();
        }
        return $results;
    }

    protected static function get_table_info($table_name){
        $self = new static();
        $stmt = $self->db->execute("SELECT `TABLE_NAME`, `ENGINE`, `AUTO_INCREMENT`, `TABLE_COLLATION`
                                    FROM INFORMATION_SCHEMA.TABLES
                                    WHERE `TABLE_SCHEMA` = ?
                                        AND `TABLE_TYPE` = 'BASE TABLE'
                                        AND `TABLE_NAME` = ?",
                                array($self->connection->database, $table_name));
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $results = $stmt ->fetchAll();
        return $results[0];
    }

    // views methods
    protected static function get_database_views(){
        $self = new static();
        $stmt = $self->db->execute("SELECT TABLE_NAME
                                    FROM INFORMATION_SCHEMA.VIEWS
                                    WHERE TABLE_SCHEMA = ?",
                                array($self->connection->database));
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $results = $stmt ->fetchAll();
        if(!$results) {
            return array();
        }
        return array_column($results, 'TABLE_NAME');
    }

    protected static function get_view_definition($view_name){
        $self = new static();
        $stmt = $self->db->execute("SELECT VIEW_DEFINITION
                                    FROM INFORMATION_SCHEMA.VIEWS
                                    WHERE TABLE_SCHEMA = ?
                                        AND `TABLE_NAME` = ?",
                                array($self->connection->database, $view_name));
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $results = $stmt ->fetchAll();
        return $results[0]['VIEW_DEFINITION'];
    }

    // helper methods
    public static function database_exists($db_name){
        $self = new static();
        $stmt = $self->db->execute("SELECT SCHEMA_NAME
                                    FROM INFORMATION_SCHEMA.SCHEMATA
                                    WHERE SCHEMA_NAME = ?",
                                array($db_name));
        $results = $stmt ->fetchAll();
        return boolval($results);
    }

    public static function table_has_column($table_name, $column_name) {
        $cols = self::get_table_description($table_name);
        $key = array_search($column_name, array_column($cols, 'Field'));
        if($key === FALSE) {
            return FALSE;
        }
        return TRUE;
    }
}
