<?php
namespace 404_error\hw4\views\elements;

use 404_error\hw4\views\View;
use 404_error\hw4\configs\Config;

class HeaderElement extends Element
{
	public function render($data)
	{ ?><!DOCTYPE html>
		<html>
		<head>
		<title><a href="<?php Config::BASE_URL ?>"><?= $data['projtitle'] ?></a></title>
		<base href="<?= Config::BASE_DIRECTORY ?>/">
		<link rel="stylesheet" type="text/css" href="src/styles/common.css">
		<script src="src/scripts/spreadsheet.js"></script>
    		</head>
		
	<?php }
} ?>
