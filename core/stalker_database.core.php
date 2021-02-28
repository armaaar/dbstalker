<?php

class Stalker_Database {

	protected $db;
	/**
	 * @var array Database drivers that support SAVEPOINT * statements.
	 */
	protected static $_supportedDrivers = array("pgsql", "mysql");
	/**
	 * @var bool if database driver support savepoints
	 */
	protected $_hasSavepoint = FALSE;
	/**
	 * @var int the current transaction depth
	 */
    protected $_transactionDepth = 0;

    // Use singleton structure to only open 1 db connection
    /**
     * Call this method to get singleton
     */
    public static function instance($force_new_instance = false)
    {
        static $instance = null;
        if( $instance === null || $force_new_instance ) {
            // Late static binding (PHP 5.3+)
            $instance = new static();
        }

        return $instance;
    }
    /**
     * Make clone magic method private, so nobody can clone instance.
     */
    private function __clone() {}

    /**
     * Make sleep magic method private, so nobody can serialize instance.
     */
    private function __sleep() {}

    /**
     * Make wakeup magic method private, so nobody can unserialize instance.
     */
    private function __wakeup() {}
	/**
	 * Make constructor protected, so nobody can call "new Class" but children.
	 */
	private function __construct() {
        $connection = Stalker_Configuration::database_connection();
		$this -> db = new PDO('mysql:host=' . $connection -> host . ';dbname=' . $connection -> database . ';charset=utf8', $connection -> user, $connection -> password);
		$this -> db -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this -> db -> setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
		$this -> db -> setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$this->_hasSavepoint = in_array(
			$this-> db ->getAttribute(PDO::ATTR_DRIVER_NAME), self::$_supportedDrivers
		);
		// adjusting timezone
		$now = new DateTime();
		$mins = $now -> getOffset() / 60;

		$sgn = ($mins < 0 ? -1 : 1);
		$mins = abs($mins);
		$hrs = floor($mins / 60);
		$mins -= $hrs * 60;

		$offset = sprintf('%+d:%02d', $hrs * $sgn, $mins);

		$this -> db -> exec("SET time_zone='$offset';");
	}

	/**
	 * Start transaction
	 *
	 * @return bool|void
	 */
	public function beginTransaction()
	{
		if($this->_transactionDepth == 0 || !$this->_hasSavepoint) {
			$this -> db -> beginTransaction();
		} else {
			$this->db->exec("SAVEPOINT LEVEL{$this->_transactionDepth}");
		}

		$this->_transactionDepth++;
	}

	/**
	 * Commit current transaction
	 *
	 * @return bool|void
	 */
	public function commit()
	{
		$this->_transactionDepth--;

		if($this->_transactionDepth == 0 || !$this->_hasSavepoint) {
			$this -> db -> commit();
		} else {
			$this->db->exec("RELEASE SAVEPOINT LEVEL{$this->_transactionDepth}");
		}
	}

	/**
	 * Rollback current transaction,
	 *
	 * @throws PDOException if there is no transaction started
	 * @return bool|void
	 */
	public function rollBack()
	{

		if ($this->_transactionDepth == 0) {
			throw new PDOException('Rollback error : There is no transaction started');
		}

		$this->_transactionDepth--;

		if($this->_transactionDepth == 0 || !$this->_hasSavepoint) {
			$this -> db -> rollBack();
		} else {
			$this->db->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->_transactionDepth}");
		}
	}

	public function execute($query, $args = array())
	{
		if(empty($query))
		{
			$trace = debug_backtrace();
			$caller = $trace[1];
			trigger_error($caller['class']. "::" .$caller['function']. " -> Empty query string", E_USER_ERROR);
			die();
			return false;
		}
		$stmt = $this -> db -> prepare($query);
		try {
			$stmt -> execute($args);
		} catch(PDOException $ex) {
			$trace = debug_backtrace();
			$caller = $trace[1];
			trigger_error($caller['class']. "::" .$caller['function']. " -> " . $ex -> getMessage(), E_USER_ERROR);
			die();
		}
        return $stmt;
	}

	public function unprepared_execute($query)
	{
		if(empty($query))
		{
			$trace = debug_backtrace();
			$caller = $trace[1];
			trigger_error($caller['class']. "::" .$caller['function']. " -> Empty query string", E_USER_ERROR);
			die();
			return false;
		}
		try {
			$stmt = $this -> db -> exec($query);
		} catch(PDOException $ex) {
			$trace = debug_backtrace();
			$caller = $trace[1];
            trigger_error($caller['class']. "::" .$caller['function']. " -> " . $ex -> getMessage(), E_USER_ERROR);
			die();
		}
        return $stmt;
	}

	public function lastInsertId($name = null)
	{
        return $this -> db -> lastInsertId($name);
	}
}
?>
