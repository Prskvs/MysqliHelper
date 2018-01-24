<?php
class MysqliHelper {
	private $host;
	private $user;
	private $password;
	private $database;
	private $prefix;
	private $conn;

	function __construct ($host, $username, $password, $database, $prefix = '') {
		$this->host = $host;
		$this->user = $username;
		$this->password = $password;
		$this->database = $database;
		$this->prefix = $prefix;
	}

	function __destruct () {

	}

	public function connect ($use_db = false) {
		$con = new mysqli ($this->host, $this->user, $this->password);
		if (mysqli_connect_error ()) {
			throw new Exception ('Could not connect to database server.');
			return false;
		}

		if (!$con->set_charset ('utf8'))
			throw new Exception ('Could not change charset.');

		if ($use_db) {
			if (!$con->select_db ($this->database))
				throw new Exception ('Could not select database.');
		}

		$this->conn = $con;
		unset ($con);
		return $this->conn;

	}

	function string_escape ($str) {
		return $this->conn->real_escape_string ($str);
	}

	public function createDatabase ($params = 'DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci') {
		if (!$this->conn->select_db ($this->database)) {
		$sql = "CREATE DATABASE `" . $this->database ."` " . $params . ";";
			if (!$this->conn->query ($sql)) {
				throw new Exception ('Could not create database.');
				return false;
			}
		}

		if (!$this->conn->select_db ($this->database)) {
				throw new Exception ('Could not select database.');
                return false;
            }

		return true;
	}

	public function createTable ($name, $columns = [], $params = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci') {
		if (!is_array ($columns)) {
			throw new Exception ('invalid columns.');
			return false;
		}

		$cols = '';

		foreach ($columns as $column => $type) {
			$cols .= '`' .$column . '` ' . $type . ',';
		}
		$sql = "CREATE TABLE IF NOT EXISTS `" . $this->prefix . $name . "` (" . rtrim ($cols, ',') . ") " . $params . ";";
		if (!$this->conn->query ($sql)) {
			throw new Exception ('Could not create table.');
			return false;
		}

		return true;
	}

	public function insert ($table, $values = [], $return_id = false) {
		if (is_array ($values) && !empty ($values)) {
			$columns = array_keys ($values);
			$sql = "INSERT INTO `" . $this->conn->real_escape_string ($this->prefix . $table) . "` (`" . $this->conn->real_escape_string (implode ('`,`', $columns)) . "`) VALUES ('" . implode ("','", $values) . "');";
			if (!$this->conn->query ($sql)) {
				throw new Exception ('Could not insert data.');
				return false;
			}
		}
		else {
			return false;
		}

		if ($return_id === true)
			return $this->conn->insert_id;
		else
			return true;
	}

	public function insertPrepared ($table, $columns = [], $values, $return_id = false) {
		if (is_array ($columns) && !empty ($columns) && is_array ($values) && !empty ($values)) {
			$cols = implode ('`,`', $columns);
			$placehold = str_replace (array_values ($columns), '?', $columns);
			$sql = "INSERT INTO `" . $this->conn->real_escape_string ($this->prefix . $table) . "` (`" . $this->conn->real_escape_string ($cols) . "`) VALUES (" . implode (",", $placehold) . ");";

			if ($stmt = $this->conn->prepare ($sql)) {
				if (is_array ($values[0])) {
					foreach ($values as &$vals) {
						$this->bind ($stmt, $vals);
						$stmt->execute ();
					}
				}
				else {
					$this->bind ($stmt, $values);
					$stmt->execute ();
				}

				$stmt->close ();
			}
			else {
				throw new Exception ('Could not insert data.');
				return false;
			}
		}
		else {
			throw new Exception ('Could not prepare query.');
			return false;
		}

		if ($return_id === true)
			return $this->conn->insert_id;
		else
			return true;
	}

	public function select ($table, $columns = '*', $condition = '', $join = '') {
		$sql = "SELECT " . $this->conn->real_escape_string (str_replace('#', $this->prefix, $columns))
			   ." FROM `" . $this->conn->real_escape_string ($this->prefix . $table) ."` "
			   .(!empty ($join) ? ' INNER JOIN ' . $this->conn->real_escape_string (str_replace (['#', '+'], [$this->prefix, 'INNER JOIN'], $join)) : '')
			   ." " . str_replace ('#', $this->prefix, $condition) . ";";

		if ($result = $this->conn->query ($sql)) {
			if ($result->num_rows > 0) {
				return $result;
			}
		}

		throw new Exception ('Could not select data.');
		return false;
	}

	public function selectPrepared ($table, $columns = '*', $condition = '', $values = [],  $join = '') {
		$sql = "SELECT " . $this->conn->real_escape_string (str_replace('#', $this->prefix, $columns))
			   ." FROM `" . $this->conn->real_escape_string ($this->prefix . $table) ."` "
			   .(!empty ($join) ? ' INNER JOIN ' . $this->conn->real_escape_string (str_replace (['#', '+'], [$this->prefix, 'INNER JOIN'], $join)) : '');

		if (!empty ($condition)) {
			if (!empty ($values) && is_array ($values)) {
				$sql .= " " . str_replace ('#', $this->prefix, $condition) . ";";
			}
			else {
				throw new Exception ('Could not prepare query.');
				return false;
			}
		}
		else
			$sql .= ";";

		if ($stmt = $this->conn->prepare ($sql)) {
			if (empty ($values)) {
				$stmt->execute ();
			}
			elseif (is_array ($values[0])) {
				foreach ($values as &$vals) {
					$this->bind ($stmt, $vals);
					$stmt->execute ();
				}
			}
			else {
				$this->bind ($stmt, $values);
				$stmt->execute ();
			}

			$result = $stmt->get_result ();
			$stmt->close ();
			return $result;
		}

		throw new Exception ('Could not select data.');
		return false;
	}

	private function bind ($stmt,array &$values) {
		if (is_array ($values) && !empty ($values)) {
			$type = '';
			$params = [];
			foreach ($values as $key => &$value) {
				switch (true) {
					case is_int ($value):
						$type .= 'i';
						break;
					case is_double ($value):
						$type .= 'd';
						break;
					case is_string ($value):
						$type .= 's';
						break;
					case is_blob ($value):
						$type .= 'b';
						break;
					default: case is_null ($value):
						return false;
						break;
				}

				$params[$key] = & $values[$key];
			}

			$params = array_merge ([$type], $params);
			call_user_func_array ([$stmt, 'bind_param'], $params);

			return true;
		}

		return false;
	}

	public function update ($table, $values = [], $condition = '') {
		if (is_array ($values) && !empty ($values)) {
			$cols = '';

			foreach ($values as $column => $value) {
				$cols .= "`" . $column . "` = '" . $this->conn->real_escape_string ($value) . "',";
			}
			$sql = "UPDATE `" . $this->conn->real_escape_string ($this->prefix . $table) . "` SET " . rtrim ($cols, ',') . " " . $condition . ";";
			if (!$this->conn->query ($sql)) {
				throw new Exception ('Could not update data.');
				return false;
			}
		}
		else {
			return false;
		}

		return true;
	}

	public function delete ($table, $condition = '') {
		$sql = "DELETE FROM `" . $this->conn->real_escape_string ($this->prefix . $table) ."` " . str_replace ('#', $this->prefix, $condition) . ";";

		if (!$this->conn->query ($sql)) {
			return false;
		}

		return true;
	}

	public function close () {
		$this->conn->close ();
	}
}

?>
