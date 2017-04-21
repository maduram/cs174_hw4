<?php
namespace 404_error\hw4\views;

use 404_error\hw4\views\View;
use 404_error\hw4\configs\Config;
use 404_error\hw4\views\elements\HeaderElement;

/*
 * This class renders the Landing Page.
 */
class LandingView extends View
{
	public $headersdisplay;

	public function __construct()
	{
		
		$this->headersdisplay = new HeaderElement($this);
	}

	/*
 	* The function to render the elements for the landing page.
 	*/
	public function render($data)
	{
		if(!isset($_SESSION['toggle']))
		{
			$_SESSION['toggle']=true;
		}
		$this->headersdisplay->render($data); ?>
		
		<!--start HTML-->
		<body>
		<h1><?=$data['projtitle']?></h1>
		
		<span id="erroroutput" class="erroroutputclass">
			<?=$data['sheetdataerr'] ?>
		</span>
		
		<p id="erroroutputjsid"></p>

		<!--This is to display the error message for few seconds when there is error in user written chart data. The error message disappears after few seconds. -->
		<script>
			setTimeout(function(){
			document.getElementById('erroroutput').className =	'errordisappearclass'; 
			}, 1000);
		</script>
		
		<?php if($_SESSION['toggle']){ ?>
		<form action="<?= Config::BASE_URL ?>" onsubmit="return validatedata('erroroutputjsid');">
		<?php } else{ ?>
		<form action="<?= Config::BASE_URL ?>">
		<?php } $_SESSION['toggle']=!$_SESSION['toggle']; ?>
			<input type="text" placeholder="New sheet name or code" id="sheetname" value="<?= $data['sheettitle'] ?>">
			<input type="submit" value="Go" id="submit">
		</form>
		
		
		
	<?php }
} ?>
