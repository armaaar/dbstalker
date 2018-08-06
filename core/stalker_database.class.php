<?php

class Stalker_Database extends Stalker_Singleton {

  protected $db;
  protected $active_transaction;

  /**
   * Make constructor protected, so nobody can call "new Class" but children.
   */
  protected function __construct() {
    $connection = json_decode(file_get_contents("./stalker_config.json"));
		$this -> db = new PDO('mysql:host=' . $connection -> host . ';dbname=' . $connection -> database . ';charset=utf8', $connection -> user, $connection -> password);
		$this -> db -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this -> db -> setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
		$this -> db -> setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

		// adjusting timezone
		$now = new DateTime();
		$mins = $now -> getOffset() / 60;

		$sgn = ($mins < 0 ? -1 : 1);
		$mins = abs($mins);
		$hrs = floor($mins / 60);
		$mins -= $hrs * 60;

		$offset = sprintf('%+d:%02d', $hrs * $sgn, $mins);

		$this -> db -> exec("SET time_zone='$offset';");
		$this -> active_transaction = false;
  }

  public function beginTransaction()
	{
		if(!$this->active_transaction)
		{
			try {
			    $this -> db -> beginTransaction();
			} catch(PDOException $ex) {
			    //Something went wrong rollback!
			    $this->rollBack();
			    err("FATAL: " . __CLASS__ . "::" . __FUNCTION__ . " -> " . $ex -> getMessage());
				die();
			}
			
		}
		$this->active_transaction = [$this->active_transaction];
	}

	public function rollBack()
	{
		if($this->active_transaction)
		{
			$this->active_transaction = $this->active_transaction[0];
		}
		if(!$this->active_transaction)
		{
			$this -> db -> rollBack();
		}
	}

	public function commit()
	{
		if($this->active_transaction)
		{
			$this->active_transaction = $this->active_transaction[0];
		}
		if(!$this->active_transaction)
		{
			try {
			    $this -> db -> commit();
			} catch(PDOException $ex) {
			    //Something went wrong rollback!
			    $this->rollBack();
			    err("FATAL: " . __CLASS__ . "::" . __FUNCTION__ . " -> " . $ex -> getMessage());
				die();
			}
		}
	}

	public function transaction_is_active()
	{
		if($this->active_transaction)
		{
			return true;
		}
		return false;
	}

	public function __destruct() {
		$this -> db = null;
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
