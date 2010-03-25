<?php
// $Id$

// define the query types
define('SQL_NONE', 1);
define('SQL_ALL', 2);
define('SQL_INIT', 3);

// define the query formats
define('SQL_ASSOC', PDO::FETCH_ASSOC);
define('SQL_INDEX', PDO::FETCH_NUM);
define('SQL_OBJ', PDO::FETCH_OBJ);

// define the parameter formats
define('SQL_INT', PDO::PARAM_INT);
define('SQL_STR', PDO::PARAM_STR);

class DB {

    var $pdo = null;
    var $result = null;
    var $error = null;
    var $record = null;

    function DB() {

    }

    function connect($dsn, $username, $password) {
        try {
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->query("SET NAMES utf8");
            return true;
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    function disconnect() {
        $this->pdo = null;
    }

    function getTables() {
        $query = "SHOW TABLES";
        $this->query($query, SQL_ALL, SQL_INDEX);
        return $this->record;
    }

    function prepare($query) {
        return $this->pdo->prepare($query);
    }

    function query($query, $type = SQL_NONE, $format = SQL_INDEX) {
        try {
            #echo "<pre>$query</pre>";
            $this->result = $this->pdo->query($query);
            switch ($type) {
                case SQL_ALL:
                    $this->record = $this->result->fetchAll($format);
                    break;
                case SQL_INIT:
                    $this->record = $this->result->fetch($format);
                    break;
                case SQL_NONE:
                default:
                    break;
            }
            return true;
        } catch(PDOException $e) {
            die("<br />QUERY failure: {$query}<br />".$e->getMessage());
        }
        return true;
    }

    function next($format = SQL_INDEX) {
        $this->record = $this->result->fetch($format);
        if ($this->record) {
            return $this->record;
        } else {
            return false;
        }
    }

    function escape($input) {
        return $this->pdo->quote($input);
    }

    function lastInsertID() {
        return $this->pdo->lastInsertId();
    }

    function numRows() {
        try {
            return $this->result->rowCount();
        } catch(PDOException $e) {
            die($e->getMessage());
        }
    }

    private function select($table, $where = NULL, $sort = NULL, $order = 'ASC', $limit = 0, $type = SQL_ALL, $format = SQL_ASSOC) {
        $query = "SELECT * FROM `{$table}`";

        if ($where) {
            $conditions = NULL;
            foreach($where as $key => $value) {
                $conditions .= sprintf("`%s` = %s AND ", $key, $this->escape($value));
            }
            $query .= " WHERE " . substr($conditions, 0, -5);
        }

        if ($sort) {
            $query .= " ORDER BY `{$sort}` {$order}";
        }

        if ($limit) {
            $query .= " LIMIT {$limit}";
        }

        $this->query($query, $type, $format);

        return $this->record;
    }

    function selectOne($table, $where = NULL, $sort = NULL, $order = 'ASC', $limit = 0) {
        return $this->select($table, $where, $sort, $order, $limit, SQL_INIT, $format = SQL_ASSOC);
    }
    function selectAll($table, $where = NULL, $sort = NULL, $order = 'ASC', $limit = 0) {
        return $this->select($table, $where, $sort, $order, $limit, SQL_ALL, $format = SQL_ASSOC);
    }

    function insert($table, $array) {
        $query = "INSERT INTO `{$table}` SET ";

        foreach($array as $key => $value) {
            $query .= sprintf("`%s` = %s, ", $key, $this->escape($value));
        }
        $query = substr($query, 0, -2) . ";";

        if ($this->query($query)) {
            return $this->lastInsertID();
        } else {
            return 0;
        }
    }

    function update($table, $array, $where) {
        $data = null;
        foreach($array as $key => $value) {
            $data .= sprintf("`%s` = %s, ", $key, $this->escape($value));
        }
        $data = substr($data, 0, -2);

        $conditions = null;
        foreach($where as $key => $value) {
            $conditions .= sprintf("`%s` = %s AND ", $key, $this->escape($value));
        }
        $conditions = substr($conditions, 0, -5);

        $query = sprintf("UPDATE `%s` SET %s WHERE %s", $table, $data, $conditions);

        return $this->query($query);
    }

    function delete($table, $data) {
        if (is_array($data)) {
            $query = "DELETE FROM `{$table}` WHERE ";
            foreach($data as $key => $value) {
                $query .= sprintf("`%s` = %s AND ", $key, $this->escape($value));
            }
            $query = substr($query, 0, -5);
        } else {
            $query = sprintf("DELETE FROM `%s` WHERE `id` = %s", $table, $this->escape($data));
        }
        return $this->query($query);
    }

    function log($type, $level, $event) {
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
        $query = sprintf("INSERT INTO `history` (`user_id`, `ip_address`, `type`, `level`, `event`)
			VALUES (%d, '%s', '%s', '%s', %s)",
                $user_id, $_SERVER['REMOTE_ADDR'], $type, $level, $this->escape($event));
        return $this->query($query);
    }

}

?>