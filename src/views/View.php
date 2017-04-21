<?php
namespace 404_error\hw4\views;
/**
 * The abstract view class with render method.
 */
abstract class View
{
    public function __construct() {}
    public abstract function render($data);
}?>
