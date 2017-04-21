<?php
namespace 404_error\hw4\controllers;

use 404_error\hw4\models\DataModel;

 /** The abstract class Controller */
class Controller
{
	 /** @var Model|null Is instantiated to hold a DataModel object in constructor. */
	public $model;
	
	 /** @var array|null Is instantiated to empty array in constructor. */
	public $data;

	public function __construct()
	{
		$this->data=[];
		$this->data['hashcode']="";
		$this->data['sheettitle']="";
		$this->data['sheetdata']="";
		$this->data['projtitle']="";
		$this->data['sheetdataerr']="";
		$this->data['togglejs']=false;
		$this->data['error_details_data']=[];
		$this->model=new DataModel();
	
	}
}
