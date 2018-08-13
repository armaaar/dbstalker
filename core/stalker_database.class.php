<?php

class Stalker_Database extends Stalker_Singleton {

	protected $db;
	protected $connection;
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

	/**
	 * Make constructor protected, so nobody can call "new Class" but children.
	 */
	protected function __construct() {
		$this -> connection = json_decode(file_get_contents("./stalker_config.json"));
		$this -> db = new PDO('mysql:host=' . $this -> connection -> host . ';dbname=' . $this -> connection -> database . ';charset=utf8', $this -> connection -> user, $this -> connection -> password);
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

	protected function execute($query, $args = array())
	{
		if(empty($query))
		{
			$trace = debug_backtrace();
			$caller = $trace[1];
			error_log("FATAL: " .$caller['class']. "::" .$caller['function']. " -> Empty query string");
			die();
			return false;
		}
		$stmt = $this -> db -> prepare($query);
		try {
			$stmt -> execute($args);
		} catch(PDOException $ex) {
			$trace = debug_backtrace();
			$caller = $trace[1];
			error_log("FATAL: " .$caller['class']. "::" .$caller['function']. " -> " . $ex -> getMessage());
			die();
			return false;
		}
	  	return $stmt;
	}
  
	protected function lastInsertId($name = null)
	{
	  return $this -> db -> lastInsertId($name);
	}
}
?>
