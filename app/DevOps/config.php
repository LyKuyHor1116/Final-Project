<?php

session_start();

date_default_timezone_set('Asia/Phnom_Penh');


function getDB()
{
    static $pdo = null;

    if ($pdo === null) {

        $host = "mysql_server";
        $dbname = "mydatabase";
        $username = "root";
        $password = "rootpassword";

        try {

            $pdo = new PDO(
                "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                $username,
                $password
            );

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {

            die("Database connection failed: " . $e->getMessage());
        }
    }

    return $pdo;
}
