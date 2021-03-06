<?php
namespace 404_error\hw4\configs;

use 404_error\hw4\configs\Config;

require_once('Config.php');

 /** @var Connection| holds a connection object to database
 * with values defined in Config file 
 */
$conn = new \mysqli(Config::DB_HOST, Config::DB_USER, Config::DB_PASSWORD,"", Config::DB_PORT);

 /** @var string| create database query */
$query="create database ".Config::DB_NAME;
$conn->query($query);

if($conn->connect_error)
{
	print("Error creating database");
	exit;
}
//This block creates tables and populates them as per "insert.sql" 
else
{
	$conn->select_db(Config::DB_NAME);
	$conn->query("drop table if exists spreadsheettable");
	$handle = fopen("insert.sql", "r");
    	if ($handle) {
    		while (($line = fgets($handle)) !== false) {
    			$query = $line;
    			$conn->query($query); 
    			}
	fclose($handle);
	}
	else
	{
		print("insertsql file not found!");
	}
$conn->close();
	
}
