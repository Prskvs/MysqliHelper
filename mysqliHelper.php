<?php
class DB{
	private $host;
	private $user;
	private $password;
	private $database;
	private $prefix;
	private $conn;

	function __construct($host, $username, $password, $database, $prefix = ''){
		$this->host = $host;
		$this->user = $username;
		$this->password = $password;
		$this->database = $database;
		$this->prefix = $prefix;
	}

	function __destruct(){

	}

	function connect($use_db = false){
		$con = new mysqli($this->host, $this->user, $this->password);
		if(mysqli_connect_error()){
			throw new Exception('Could not connect to database server');
			return false;
		}

		if(!$con->set_charset('utf8'))
			throw new Exception('Could not change charset');

		if($use_db){
			if(!$con->select_db($this->database))
				throw new Exception('Could not select database ');
		}

		$this->conn = $con;
		unset($con);
		return $this->conn;

	}

	function string_escape($str){
		return $this->conn->real_escape_string($str);
	}

	function createDatabase($params = 'DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci'){
		if(!$this->conn->select_db($this->database)){
		$sql = "CREATE DATABASE `" . $this->database ."` " . $params . ";";
			if(!$this->conn->query($sql)){
				throw new Exception('Could not create database ');
				return false;
			}
		}

		if(!$this->conn->select_db($this->database))
				throw new Exception('Could not select database ');

		return true;
	}

	function createTable($name, $columns = [], $params = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci'){
		if (!is_array($columns)){
			throw new Exception('invalid columns');
			return false;
		}

		$cols = '';

		foreach($columns as $column => $type){
			$cols .= '`' .$column . '` ' . $type . ',';
		}
		$sql = "CREATE TABLE IF NOT EXISTS `" . $this->prefix . $name . "` (" . rtrim($cols, ',') . ") " . $params . ";";
		if(!$this->conn->query($sql)){
			throw new Exception('Could not create table ' . ($this->error_report === 'browser') ? $this->conn->error : '');
			return false;
		}

		return true;
	}

	function insert($table, $values = [], $return_id = false){
		if(is_array($values) && !empty($values)){
			$columns = array_keys($values);
			$sql = "INSERT INTO `" . $this->conn->real_escape_string($this->prefix . $table) . "` (`" . $this->conn->real_escape_string(implode('`,`', $columns)) . "`) VALUES ('" . implode("','", $values) . "');";
			if(!$this->conn->query($sql)){
				throw new Exception('Could not insert data ' . ($this->error_report === 'browser') ? $this->conn->error : '');
				return false;
			}
		}
		else{
			return false;
		}

		if($return_id === true)
			return $this->conn->insert_id;
		else
			return true;
	}

	function select($table, $columns = '*', $condition = '', $join = ''){
		$sql = "SELECT " . $this->conn->real_escape_string(str_replace('#', $this->prefix, $columns))
			   ." FROM `" . $this->conn->real_escape_string($this->prefix . $table) ."` "
			   .((!empty($join))? ' INNER JOIN ' . $this->conn->real_escape_string(str_replace('#', $this->prefix, str_replace('+', 'INNER JOIN', $join))) : '')
			   ." " . str_replace('#', $this->prefix, $condition) . ";";

		if($result = $this->conn->query($sql)){
			if($result->num_rows > 0){
				return $result;
			}
			else
				return false;
		}
		else
			return false;
	}

	function update($table, $values = [], $condition = ''){
		if(is_array($values) && !empty($values)){
			$cols = '';

			foreach($values as $column => $value){
				$cols .= "`" . $column . "` = '" . $this->conn->real_escape_string($value) . "',";
			}
			$sql = "UPDATE `" . $this->conn->real_escape_string($this->prefix . $table) . "` SET " . rtrim($cols, ',') . " " . $condition . ";";
			if(!$this->conn->query($sql)){
				throw new Exception('Could not update data ' . ($this->error_report === 'browser') ? $this->conn->error : '');
				return false;
			}
		}
		else{
			return false;
		}

		return true;
	}

	function delete($table, $condition = ''){
		$sql = "DELETE FROM `" . $this->conn->real_escape_string($this->prefix . $table) ."` " . str_replace('#', $this->prefix, $condition) . ";";

		if(!$this->conn->query($sql)){
			return false;
		}

		return true;
	}

	function close(){
		$this->conn->close();
	}
}

?>
