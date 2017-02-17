<?php

class DVM_Facets_Filter 
{
	public $facets = array();
	private $dal;
	
	function __construct($facets, $dal)
	{
		 $this->facets = $facets;
		 $this->dal = $dal;
	}

	function add_filter(&$wp_query)
	{
		foreach ($this->facets as $facet)
		{
			switch ($facet->type) {
			case 'property':
				$wp_query->query_vars[$facet->key] = $facet->value;
				break;
			case 'meta':
				$wp_query->query_vars['meta_query'][] = 
					array
					(
						'key' => $facet->key,
						'value' => $facet->value,
						'compare' => 'LIKE'
					);
				break;
			case 'ancestor':
				$ids = array();
				$this->dal->get_descendant_ids($facet->value, $ids);
				$wp_query->query_vars['post__in'] = $ids;
				break;
			default:
				die('Unknown facet type: '.$facet->type);
			}
		}
	}	
}

?>