<?php
namespace protomuncher;

class Database
{
    private static $datasource = 'sqlite:'.__DIR__.'/../conf/config.sqlite';
    private $db;
    private $error;

    //make the constructor private and empty so that no code will create an object of this class.
    function __construct(){
        try{
            $this->db = new \PDO($this->datasource);
        }
        catch(\PDOException $e)
        {
            echo $this->error = $e->getMessage(); //variable $error can be used in the database_error.php file
            exit();
        }
    }
}