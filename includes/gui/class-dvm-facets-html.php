<?php

class DVM_Facets_Html
{
	function encode($text)
	{
		return htmlentities($text, ENT_COMPAT, 'UTF-8');
	}

	function write($text, $break=1)
	{
		echo($text);
		echo str_repeat("<br>", $break);
	}
	
	function anchor(&$query, $text, $id=null, $class=null, $style=null)
	{
		echo('<a ');
		if (!is_null($id)) echo("id=\"$id\" ");	
		if (!is_null($class)) echo("class=\"$class\" ");
		if (!is_null($style)) echo("style=\"$style\" ");
	
		echo('href="/?'.$query->to_string().'">'.$text.'</a>');
	}
	
	function debug($data)
	{
		$this->write('<pre>', 0);
		print_r($data);
		$this->write('</pre>', 0);
	}
}

?>