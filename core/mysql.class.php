<?php

/**
 * @version    $Id: db.class.php 1 2016-07-19 08:45:53Z tic $
 */

class mysql
{
	public $dbhost, $dbport, $dbname, $dbuser, $dbpassword;
	public $init, $link, $sql, $queryID, $sqls = array();

	public function __construct($dbhost, $dbname, $dbuser, $dbpassword)
	{
		list($this->dbhost, $this->dbport) = strpos($dbhost, ':') !== false ? explode(":", $dbhost) : array($dbhost, '3306');
		$this->dbname = $dbname;
		$this->dbuser = $dbuser;
		$this->dbpassword = $dbpassword;
	}

	public function init()
	{
		if (!function_exists('mysqli_init')) $this->halt('ERROR : mysqli_init not exists');
		$this->link = mysqli_init();
		$this->init = mysqli_real_connect($this->link, $this->dbhost, $this->dbuser, $this->dbpassword, false, $this->dbport);
		if (mysqli_connect_errno()) {
			$this->halt(mysqli_connect_error());
		}
		$version = mysqli_get_server_info($this->link);
		if ($version < '5.0') {
			$this->halt('mysql version must above 5.0');
		}
		mysqli_query($this->link, "SET character_set_connection='utf8',character_set_results='utf8',character_set_client=binary,sql_mode=''");
		if (!mysqli_select_db($this->link, $this->dbname)) $this->halt('Cannot select database');
		return $this->link;
	}

	public function esc($string)
	{
		if (!$this->init) {
			$this->init();
		}
		return mysqli_real_escape_string($this->link, $string);
	}

	public function setquery($sql)
	{
		$this->sql = $sql;
	}

	public function query($sql)
	{
		if (!$this->init) {
			$this->init();
		}
		$this->setquery($sql);
		$start = microtime(TRUE);
		$this->queryID = mysqli_query($this->link, $this->sql) or $this->halt();
		$needtime = number_format(microtime(TRUE) - $start, 6);
		if (!empty($_SERVER['DEBUG']) && $needtime > 3) {
			throw new Exception($needtime . ' | ' . ($this->sql));
		}
		$this->sqls[] = array('time' => $needtime, 'sql' => $this->sql);
		return $this->queryID;
	}

	public function fetch($queryID = false, $type = MYSQLI_ASSOC)
	{
		if (false === $queryID) $queryID = $this->queryID;
		return mysqli_fetch_array($queryID, $type);
	}

	public function select($sql, $key = '')
	{
		$this->query($sql);
		$list = array();
		while ($rs = $this->fetch()) {
			$list[] = $rs;
		}
		$this->free_result();
		if ($key) {
			$list = array_column($list, null, $key);
		}
		return $list;
	}

	public function find($sql, $limit = true)
	{
		if ($limit === true) {
			if (stripos($sql, 'limit') === false) $sql = rtrim($sql, ';') . ' limit 1;';
		}
		$this->query($sql);
		$rs = $this->fetch();
		$this->free_result();
		return $rs;
	}

	public function getfield($sql, $limit = true)
	{
		$result = $this->find($sql, $limit);
		return is_array($result) ? array_pop($result) : $result;
	}

	public function getlastsql()
	{
		return $this->sql;
	}

	public function insert_id()
	{
		return mysqli_insert_id($this->link);
	}

	public function affected_rows()
	{
		return mysqli_affected_rows($this->link);
	}

	public function close()
	{
		mysqli_close($this->link);
		$this->init = false;
	}

	public function free_result($queryID = false)
	{
		if (false === $queryID) $queryID = $this->queryID;
		mysqli_free_result($queryID);
	}

	public function version()
	{
		return mysqli_get_server_info($this->link);
	}

	public function error()
	{
		return $this->init ? mysqli_error($this->link) : '';
	}

	public function errno()
	{
		return intval($this->init ? mysqli_errno($this->link) : '');
	}

	public function halt($msg = '')
	{
		if ($msg) {
			throw new Exception($msg);
		} else {
			throw new Exception("数据库发生错误<br>" . $this->error());
		}
		exit;
	}
}
