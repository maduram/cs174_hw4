<?php namespace 404_error\hw4\views\elements;

use 404_error\hw4\views\View;

abstract class Element
{
	public $view;
	
	public function __construct(View $currentview)
	{
		$this->view=$currentview;
	}
	public abstract function render($data);
} ?>
