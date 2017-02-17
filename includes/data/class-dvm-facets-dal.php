<?php

class DVM_Facets_Dal 
{
	private $facets = array();
	
	function __construct()
	{
		$this->facets[] = new DVM_Facets_Facet(1, 'Nyhedsarkiv', 'post_type', 'post', 'property', 'icon-posts');
		$this->facets[] = new DVM_Facets_Facet(2, 'Udvalgte emner', '', '62352', 'ancestor', 'icon-themes');
		$this->facets[] = new DVM_Facets_Facet(3, 'Tidsskrifter', '', '16525', 'ancestor', 'icon-periodicals');
	}
	
	function &get_all_facets()
	{
		return $this->facets;
	}

	function get_query()
	{
		$query = new DVM_Facets_Query();
		$query->parse_query_string($_SERVER['QUERY_STRING']);
		return $query;
	}
	
	function &get_facet($id)
	{
		foreach ($this->facets as $facet)
			if ($facet->id == $id) return $facet;
		return null;
	}
	
	function &get_facets($ids)
	{
		$facets = array();
		foreach ($ids as $id)
		{
			$facet = $this->get_facet($id);
			if ($facet) $facets[] = $facet;
		}
		return $facets;
	}
	
	function get_is_filter_applied()
	{
		if (!isset($GLOBALS['dvm_facets_is_filter_applied']))
			$this->set_is_filter_applied(false);
		return $GLOBALS['dvm_facets_is_filter_applied'];	
	}
	
	function set_is_filter_applied($value)
	{
		$GLOBALS['dvm_facets_is_filter_applied'] = $value;
	}
	
	function set_original_query_vars($value)
	{
		$_SESSION['dvm_facets_original_query_vars'] = $value;
	}
	
	function get_original_query_vars()
	{
		if (!isset($_SESSION['dvm_facets_original_query_vars']))
			return null;
		return $_SESSION['dvm_facets_original_query_vars'];	
	}
	
	function set_original_post_type($value)
	{
		$_SESSION['dvm_facets_original_post_type'] = $value;
	}
	
	function get_original_post_type()
	{
		if (!isset($_SESSION['dvm_facets_original_post_type']))
			return null;
		return $_SESSION['dvm_facets_original_post_type'];	
	}

	
	function encode($value)
	{
		return base64_encode(serialize($value));
	}
	
	function decode($value)
	{
		return unserialize(base64_decode($value));
	}
	
	function get_cache_key($query_vars, $post_type)
	{
		unset($query_vars['paged']);
		ksort($query_vars); 
		return md5(serialize($query_vars).serialize($post_type));
	}
	
	function get_descendant_ids($post_parent, &$ids, $post_type='page')
	{
		global $wpdb;
		$ids[] = $post_parent;
		$children_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = '$post_type' AND post_parent = $post_parent");
		foreach ($children_ids as $child_id) $this->get_descendant_ids($child_id, $ids, $post_type);
	}
	
	function facet_counts(&$wp_query)
	{
		session_start();

		$facet_counts = array();			

		$cache_key = $this->get_cache_key($wp_query->query_vars, $wp_query->post_type);
		
		if (!isset($_SESSION['dvm_facet_counts']))
			$_SESSION['dvm_facet_counts'] = array();
		
		// Check if cache already contains the facet count
		if (isset($_SESSION['dvm_facet_counts'][$cache_key]))
			return $_SESSION['dvm_facet_counts'][$cache_key];
		
		foreach ($this->get_all_facets() as $facet)
		{
			// Backup original query vars
			$original_query_vars = $wp_query->query_vars;			
			$original_post_type = $wp_query->post_type;			
		
			$filter = new DVM_Facets_Filter(array($facet), $this);		
			$filter->add_filter($wp_query);
			
			$wp_query->post_count = 0;
			$wp_query->get_posts();
			
			$facet_counts[$facet->id] = $wp_query->found_posts;
			
			//Restore original query vars
			$wp_query->query_vars = $original_query_vars;
			$wp_query->post_type = $original_post_type;
		}
		
		$_SESSION['dvm_facet_counts'][$cache_key] = $facet_counts;
		
		return $facet_counts;
		
		
	}
}

?>