<?php
namespace 404_error\hw4\models;


use 404_error\hw4\controllers\Controller;
use 404_error\hw4\configs\Config;

/**
 * This class extends Model class. This executes the Insert and Select queries for Chart data.
 */
class DataModel extends Model
{
	/**
	 * This method inserts chart data to database.
	 */
	public function save_data($hashvalue, $charttitle, $chartdata)
	{
		$query="insert into spreadsheetDBtable values('".$hashvalue."','".$charttitle."','".$chartdata."')";
		$result=$this->connection->query($query);
	}

	/**
	 * This method gets chart data from database for md5hashdata.
	 * @param string $md5hashdata Md5 hash value of the chart data used to retreive data.
	 */
	public function get_data($md5hashdata)
	{
		$query="select title,data from chartdatatable where md5hashdata='".$md5hashdata."'";
		$title_and_data=[];
		$result=$this->connection->query($query);
		if($result)
		{
			$row=$result->fetch_assoc();
			$title_and_data['title']=$row['title'];
			$title_and_data['data']=$row['data'];
			return $title_and_data;
		}
	}
	
} ?>
