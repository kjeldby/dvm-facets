<?php

class DVM_Facets_Facet
{
	public $name;
	public $id;
	public $key;
	public $value;
	public $type;
	public $icon;
	
	function __construct($id, $name, $key, $value, $type='property', $icon)
	{
		$this->id = $id;
		$this->name = $name;
		$this->key = $key;
		$this->value = $value;
		$this->type = $type;		
		$this->icon = $icon;
	}
	
	function to_query() {
		$query = new DVM_Facets_Query();
		$query->facets_id = $this->id;
		return $query;
	}
}	

?>