<?php
namespace 404_error\hw4\models;

use 404_error\hw4\configs\Config;

/**
 * This is a generic Model class that connects to the database.
 * Its methods are invoked by controller classes.
 */
class Model {
	public $model;
	public $connection;

	public function __construct()
	{
		$this->initiateConnection();
	}

	public function initiateConnection()
	{
		$this->connection=new \mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASSWORD, Config::DB_NAME, Config::DB_PORT);
		if($this->connection->connect_error)
		{
			return false;
		}
		else 
		{
			return true;
		}
	}

	public function closeConnection()
	{
		$this->connection->close();
	}
}
