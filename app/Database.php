<?php

namespace App;

use \PDO;
use \PDOException;


class Database
{
    private $connection;
    private static $_instance;

    private $dbhost = "localhost"; // Ip Address of database if external connection.
    private $dbuser = "root"; // Username for DB
    private $dbpass = ""; // Password for DB
    private $dbname = "CHUNK_EXCEL"; // DB Name


    // public static function getInstance()
    // {
    //     if (!self::$_instance) {
    //         self::$_instance = new self();
    //     }
    //     return self::$_instance;
    // }


    // Constructor
    public function __construct()
    {
        try {
            $this->connection = new PDO('mysql:host=' . $this->dbhost . ';dbname=' . $this->dbname, $this->dbuser, $this->dbpass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Error handling
        } catch (PDOException $e) {
            die("Failed to connect to DB: " . $e->getMessage());
        }
    }

    // Magic method clone is empty to prevent duplication of connection
    private function __clone()
    {
    }

    // Get the connection	
    public function getConnection()
    {
        return $this->connection;
    }
}


// $dbh = App\Database::getInstance();
// $dbh = $dbh->connection;