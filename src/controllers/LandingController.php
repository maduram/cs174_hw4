<?php 
namespace 404_error\hw4\controllers;


use 404_error\hw4\views\LandingView;
use 404_error\hw4\controllers\Controller;
use 404_error\hw4\configs\Config;

/*
 * This is a controller class to handle Landing Page requests.
 */
class LandingController extends Controller
{
	public $views;

	public function invoke()
	{
		if($this->model->connection->ping())
		{
			$this->model->closeConnection();
		}
		$this->data['projtitle']="Web Sheets";
		//$this->data['texttitle']="Chart Title";
		//$this->data['placeholder']="Text label,Value1,Value2,..,Valuen";
		$this->callview();
	}
	public function callview()
	{
		$views=new LandingView();
		$views->render($this->data);
	}
	public function process()
	{
		$hashvalue="";
		$this->data['sheetdata']=$_REQUEST['sheetdata'];
		//Remove trailing newlines
		$this->data['sheetdata']=\rtrim($this->data['sheetdata']);
		//Remove unnecessary spaces
		$this->data['sheetdata']=\preg_replace("/\x20+/","",$this->data['sheetdata']);
		$this->data['sheettitle']=$_REQUEST['sheettitle'];
		//validate the data
		if($this->validateuserdata($this->data['sheetdata']))
		{
			$hashvalue=\hash("md5",$this->data['sheetdata']);
			//convert to JSON format and save into database
			$plaindata=$this->data['sheetdata'];
			$jsondata=$this->convert_to_json_data($plaindata);
			//Data has been converted to JSON format. It is now saved to the database
			$this->model->save_data($hashvalue,$_REQUEST['sheettitle'],$jsondata);
			$this->model->closeConnection();
			header('Location:'.Config::BASE_URL.'/?c=main&m=view&arg1=LineGraph&arg2='.$hashvalue);
			exit;
		}
		else
		{
			//Display error message and show the cleaned version
			//$this->data['chartdataerr']="Chart data format does not comply with the syntax";
			//$this->invoke();
			$this->model->closeConnection();
			if ($this->fix_errors_user_data($this->data['sheetdata']))
				{
					$this->data['sheetdataerr']="The data you entered does not comply with the accepted format. Refer to the display below for suggested changes";
				}
			else
			{
				$this->data['sheetdataerr']="Syntax of data is string,number1,number2..numbern";
			}
					header('Location:'.Config::BASE_URL.'?c=LandingController&a=displayCleaneddata&arg1='.$this->data['sheettitle'].'&arg2='.urlencode($this->data['sheetdata']).'&arg3='.$this->data['sheetdataerr']);
					exit;			
		
		}
		
	}

	/**
	 * This method calls the invoke method to render cleaned and validated data/error message.
	 * @return boolean|true if data is validated.
	 */
	public function displayCleaneddata($argumentslist)
	{
			$this->data['sheetdata']=$argumentslist['arg2'];
			$this->data['sheettitle']=$argumentslist['arg1'];
			$this->data['sheetdataerr']=$argumentslist['arg3'];
			$this->invoke();

	}

	/**
	 * This method validate chart data .
	 * @return boolean|true if data is validated.
	 */
	public function validateuserdata($userdata)
	{
		$formatteddata=\preg_replace("/\r\n/","\n",$userdata);
		//if user has not sent any data, show appropriate error. There is no need to validate or fix anything in such a case.

		if(empty($formatteddata))
		{
			return false;
		}
		else
		{
			$lines=\explode("\n",$formatteddata);
			$num_fields_in_each_line=[];
			$countoflines=-1;
			//check if number of lines exceed the maximum limit. If so, set the error variable MAX_LINES_EXCEEDED to the amount

			if(count($lines) > 50)
			{
				$this->data['error_details_data']['MAX_LINES_EXCEEDED']=count($lines)-50;
			}
			foreach($lines as $line)
			{
				$countoflines++;
				$str=preg_replace("/\s+/","",$line);
				$line=$str;
				//Construct an associative array with each line content as key and number of y axis fields as value. This is to make sure that all lines have same number of y values. In case there is no data provided for a particular field, fill it with empty value.
				$this->check_line_syntax_correct($line,$countoflines);
				$line=$lines[$countoflines];
				//If the second column has a string instead of a number, merge the two columns as x-axis title
				if(isset($this->data['error_details_data']['X_AXIS_STRING_COMMA_LINE_NUMBER']) && in_array($countoflines,$this->data['error_details_data']['X_AXIS_STRING_COMMA_LINE_NUMBER']))
				{
					$num_fields_in_each_line[$line]=count(explode(",",$line))-2; //The number of Y-axis values. It can only have maximum of 5 Y values representing 5 different sources of statistic.
				
				}
				else
				{
					$num_fields_in_each_line[$line]=count(explode(",",$line))-1;
				}

				//If the total number of Y values for a particular X value exceeds 5, it should be marked erraneous and fixed later by removing the excess values
				if($num_fields_in_each_line[$line] >5)
				{
					$num_fields_in_each_line[$line]=5;
					$this->data['error_details_data']['FIELD_LENGTH_EXCEEDS']=true;
				}
				if(strlen($line) > 80)
					{
						//set MAX_CHARS_EXCEEDED_LINE_NUMBER to the exact line number where the error occured. Also add this line number to the list of lines with some kind of error. It is useful when we finally want to delete excessive rows and it is best to delete the ones with errors.
						$this->data['error_details_data']['MAX_CHARS_EXCEEDED_LINE_NUMBER'][]=$countoflines;
						$this->data['error_details_data']['LINES_TO_REMOVE'][]=$countoflines;
						$num_fields_in_each_line[$line]=0;
					}
			}
		
			//If lines have unequal length, we need to adjust all lines to match the max length so that we don't lose any data
			if(count(array_unique(array_values($num_fields_in_each_line))) != 1 ||(isset($this->data['error_details_data']['FIELD_LENGTH_EXCEEDS']) && $this->data['error_details_data']['FIELD_LENGTH_EXCEEDS']==true))
			{
				$this->data['error_details_data']['Y_AXIS_TOTAL_FIELDS_REQUIRED']=\max(array_unique(array_values($num_fields_in_each_line)));	
			}
		
			if(empty ($this->data['error_details_data']))
				return true;	
			else return false;
		}
	}	

	/**
	 * This method validates a line in chart data - values for x and y axis. Pads missing values with empty or 0.
	 * @return void
	 */
	public function check_line_syntax_correct($line,$linenumber)
	{
		$fields=explode(",",$line);
		$field_number=-1; //if field_number is 0 it means it is x axis value which should be a string. This is the only field allowed to be a string
		foreach($fields as $field)
		{
			$field_number++;

			if($field_number==0)
			{
			//validate for x-axis
				if(empty($field))
				{
					$this->data['error_details_data']['LINES_TO_REMOVE'][]=$linenumber;
					
				}
			
			}
			else
			{
			//validate all y axis values to make sure they are either empty or number and not string
				if(!empty($field) && !is_numeric ($field))
				{
					if($field_number==1)
					{
						//X axis value has a comma in it. Record this error so that it can be fixed later by merging the 2 strings into one. This is to preserve as much user data as possible and use deletion only as a last option.

						$this->data['error_details_data']['X_AXIS_STRING_COMMA_LINE_NUMBER'][]=$linenumber;
						$this->data['error_details_data']['ERROR_LINES'][]=$linenumber;
					}
					else
					{
						//Y-axis contains invalid value.We store line number as key and field number as value in the FIELD_ERROR associative array to identify and eventually remove the invalid value and replace it with empty value.
						$this->data['error_details_data']['FIELD_ERROR'][$linenumber][]=$field_number;
						$this->data['error_details_data']['ERROR_LINES'][]=$linenumber;
					}
				}
			}
			
		}

	}

	/**
	 * This method cleans and calls function to validate chart data .
	 * @return boolean|true if data is validated after cleanup.
	 */
	public function fix_errors_user_data($userdata)
	{
		$formatteddata=\preg_replace("/\r\n/","\n",$userdata);

		//if data is empty, nothing can be fixed. Show appropriate error.
		if(empty($formatteddata))
		{
			//set the error explaining that the data is empty
			return false;
		}
		
		$lines=\explode("\n",$formatteddata);
		$count=-1;
		foreach($lines as $line)
		{	
			$count++;
			$str=preg_replace("/\s+/","",$line);
			$lines[$count]=$str;
		}
		
		//First delete all the lines that must be absolutely deleted(there is no way of fixing them because there is no x-axis title or they have exceeded 80 characters)
		if(isset($this->data['error_details_data']['LINES_TO_REMOVE']))
		{
			$index_of_lines_to_remove=\array_unique($this->data['error_details_data']['LINES_TO_REMOVE']);
			foreach($index_of_lines_to_remove as $line_to_remove)
			{
				unset($lines[$line_to_remove]);
			}
			//Update the final tally of MAX_LINES_EXCEEDED
			if(isset($this->data['error_details_data']['MAX_LINES_EXCEEDED']))
			{
				$this->data['error_details_data']['MAX_LINES_EXCEEDED']=$this->data['error_details_data']['MAX_LINES_EXCEEDED']-\count($index_of_lines_to_remove);
			}
		}
		
		//check if total number of lines exceeded. If so, delete lines that have errors
		if(count($lines) > 50)
		{
			$errorlines=[];
			if(isset($this->data['error_details_data']['ERROR_LINES']))
			{
				$errorlines=\array_unique($this->data['error_details_data']['ERROR_LINES']);
				
			}
			for($i=0;$i<intval($this->data['error_details_data']['MAX_LINES_EXCEEDED']);$i++)
			{
				if(empty($errorlines))
				{
					\array_pop($lines);
				}
				else
				{
					$errorlinenumber=each($errorlines);
					if($errorlinenumber!==FALSE)//Exhaust the error lines first before removing the excess lines from last
					{
						$index_to_remove=intval($errorlinenumber);
						if(isset($lines[$index_to_remove]))					
						{ unset($lines[$index_to_remove]); }
					}
					else
					{
						$errorlines=[];
						\array_pop($lines);
					}
				}
			}
		}
		
		//fix the syntax error in x axis (x axis has comma)
		
		if (isset($this->data['error_details_data']['X_AXIS_STRING_COMMA_LINE_NUMBER']))
		{
			foreach($this->data['error_details_data']['X_AXIS_STRING_COMMA_LINE_NUMBER'] as $index_to_fix_x_axis)
			{
				if(isset($lines[$index_to_fix_x_axis]))
				{
					$fixed_line=preg_replace("/,/","",$lines[$index_to_fix_x_axis],1);
					$lines[$index_to_fix_x_axis]=$fixed_line;
				}
			}
		}

		//fix error in y axis fields
		
		if(isset($this->data['error_details_data']['FIELD_ERROR']))
		{
			foreach($this->data['error_details_data']['FIELD_ERROR'] as $line_number=>$field_number)
			{
				if(isset($lines[$line_number]))
				{
					$linetofix=$lines[$line_number];
					$totalfields=\explode(",",$linetofix);
					foreach($field_number as $fieldindex)
					{
						$totalfields[$fieldindex]="";
					}
					$lines[$line_number]=\implode(",",$totalfields);
				}
			}
		}
		
		//Consolidate the array of lines to fix missing indexes due to unset.
		$new_data_list=\array_values($lines);
		//fix the total number of fields in all rows to be the same
		
		if(isset($this->data['error_details_data']['Y_AXIS_TOTAL_FIELDS_REQUIRED']))
		{
			$totalfields_required=intval($this->data['error_details_data']['Y_AXIS_TOTAL_FIELDS_REQUIRED']);
			$countlines=-1;
			foreach($new_data_list as $line)
			{
				$countlines++;
				$fields=\explode(",",$line);
				//if the line has fields in excess of the maximum allowed(5 in total), remove them
				if((count($fields)-1)>$totalfields_required)
				{
					$excess_fields_count=count($fields)-1-5;
					for($j=0;$j<$excess_fields_count;$j++)
					{
						\array_pop($fields);
					}
				}
				else
				{
					//In this case, the total number of fields is inadequate. Pad it with empty values so that total number of fields remains the same for all rows
					$missing_fields=intval($totalfields_required)-(intval(count($fields))-1);
					if($missing_fields > 0)
						{
								while($missing_fields)
								{
									$fields[]="";
									$missing_fields--;
								}
						}
				}
					$new_data_list[$countlines]=\implode(",",$fields);
			}
		}
		//
		//Assign the cleaned data to chartdata 
		$this->data['sheetdata']=implode("\n",$new_data_list);
		$this->data['error_details_data']=[];
		$this->data['sheetdataerr']="";
		//Fix $this->data['chartdata'] and verify that it is fixed by calling validatedata
		if($this->validateuserdata($this->data['sheetdata']))
			return true;
		return false;
	}

	/**
	 * This method converts chart data to json format.
	 * @return string|Json chart data.
	 */
	public function convert_to_json_data($plaindata)
	{
		$formatteddata=\preg_replace("/\r\n/","\n",$plaindata);
		$jsondata='{';
		$lines=\explode("\n",$formatteddata);
		$isfirstline=true;
		foreach($lines as $line)
		{
			if(!$isfirstline)
			{
				$jsondata.=',';
			}
			else $isfirstline=false;

			list($xvalue,$yvalue) = explode(',',$line,2);
			$jsondata.='"'.$xvalue.'":';
			$yaxis=explode(',',$yvalue);
			if(count($yaxis)>1)//More than 1 y value. Store it as array of y values
			{
				$jsondata.='[';
				foreach($yaxis as $val)
				{
					if(empty($val) && $val!=='0')
					{
						//$jsondata.='"",';
						$jsondata.='null,';
					}
					else
					{
						$jsondata.=$val.',';
					}
				}
				$newjson=\rtrim($jsondata,",");
				$jsondata=$newjson;
				$jsondata.=']';
			}
			else
			{
				if(empty($yvalue) && $yvalue!=='0')
				{
					//$jsondata.='""';
					$jsondata.='null';
				}
				else
				{
					$jsondata.=$yvalue;
				}
			}
			
		}
		$jsondata.='}';
		return $jsondata;	
	}
}
?>
