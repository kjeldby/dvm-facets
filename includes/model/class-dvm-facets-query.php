<?php

class DVM_Facets_Query {
	public $facet_ids = array();	
	public $s = null;
	
	private function compact()
	{
		if (sizeof($this->facet_ids) > 0)
			$this->facet_ids = implode('-', $this->facet_ids);
		else
			$this->facet_ids = null;
	}
	
	private function decompact()
	{
		if (strlen($this->facet_ids) > 0)
			$this->facet_ids = explode('-', $this->facet_ids);
		else
			$this->facet_ids = array();
	}
	
	function to_string()
	{
		$compact = clone $this;
		$compact->compact();
		return http_build_query($compact);
	}
	
	function parse_query_string($string)
	{
		$vars = array();
		$pairs = explode('&', $string);
		foreach ($pairs as $pair)
		{
			$parts = explode("=", $pair);
			if (sizeof($parts) > 1) 
				$vars[$parts[0]] = urldecode($parts[1]);
		}
		
		$this->s = $this->array_value('s', $vars);
		$this->facet_ids = $this->array_value('facet_ids', $vars);
		
		$this->decompact();
	}
	
	private function array_value($key, $array, $default=null)
	{
		if (isset($array) &&
			is_array($array) &&
			array_key_exists($key, $array))
			{
				$trimmed = trim($array[$key]);
				if (strlen($trimmed) > 0) return $trimmed;
			}
		return $default;
	}
	
	public function has_facet_id($facet_id)
	{
		for ($i = 0; $i < sizeof($this->facet_ids); $i++)
			if ($facet_id == $this->facet_ids[$i]) return true;
		return false;
	}
	
	public function add_facet_id($facet_id)
	{
		if (!$this->has_facet_id($facet_id))
		{
			$this->facet_ids[] = $facet_id;
			return true;
		}
		return false;
	}
	
	public function clear_facet_ids()
	{
		$this->facet_ids = array();
	}
	
	public function remove_facet_id($facet_id)
	{
		for ($i = 0; $i < sizeof($this->facet_ids); $i++)
		{
			if ($facet_id = $this->facet_ids[$i])
			{
				unset($this->facet_ids[$i]);
				return true;
			}
		}
		return false;
	}
	
} 

?>