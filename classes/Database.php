<?php

namespace Database;

class Database
{
    private $conn;
    const TABLE = [
        'orders' => 'ut_o_connect.php',
        'admin' => 'ut_a_connect.php',
        'mailing_list' => 'ut_m_connect.php',
        'content' => 'ut_c_connect.php'
    ];

    function __construct($table='content')
    {
        if (!$table || !in_array($table, array_keys(self::TABLE))) die("No Database table specified");
        require(base_path("../secure/scripts/" . self::TABLE[$table]));

        $this->conn = $db;
    }

    public function query($query, $params=null)
    {
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($stmt)
    {
        return $stmt->fetchAll();
    }

    public function fetch($stmt)
    {
        return $stmt->fetch();
    }

    public function fetchColumn($stmt)
    {
        return $stmt->fetchColumn();
    }

    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }

    public function beginTransaction()
    {
        return $this->conn->beginTransaction();
    }

    public function commit()
    {
        return $this->conn->commit();
    }

    public function rollback()
    {
        return $this->conn->rollBack();
    }
    public function rowCount($stmt)
    {
        return $stmt->rowCount();
    }
}