<?php

/*
Plugin Name: DVM Facets
Plugin URI: http://www.dvm.nu
Description: Enables faceted search on search.php via a custom widget.
Version: 1.0
Author: Kristoffer Brinch Kjeldby
Author http://www.kb.dk
*/



function dvm_facets_include()
{
	require_once('includes/data/class-dvm-facets-dal.php');
	require_once('includes/data/class-dvm-facets-filter.php');
	require_once('includes/model/class-dvm-facets-facet.php');
	require_once('includes/model/class-dvm-facets-query.php');	
	require_once('includes/gui/class-dvm-facets-html.php');
	require_once('includes/gui/class-dvm-facets-widget.php');
}

function dvm_facets_parse_query(&$wp_query)
{			
	if ($wp_query->is_search)
	{
		dvm_facets_include();
		$dal = new DVM_Facets_Dal();
		
		if (!$dal->get_is_filter_applied())
		{	
			session_start();  
			
			// Saving original query before filtering
			$dal->set_original_query_vars($wp_query->query_vars);
			$dal->set_original_post_type($wp_query->post_type);
			
			// Filtering the wp_query
			$query = $dal->get_query();
			$facets = $dal->get_facets($query->facet_ids);
			$filter = new DVM_Facets_Filter($facets, $dal);
			$filter->add_filter($wp_query);
			$dal->set_is_filter_applied(true);
		}
	}
}
add_action('parse_query', 'dvm_facets_parse_query'); // Add the filter

function dvm_facets_widget_init()
{
	dvm_facets_include();
	register_widget('DVM_Facets_Widget');
}
add_action('widgets_init', 'dvm_facets_widget_init'); // Init the widget

function dvm_facets_get_counts_callback()
{
	global $wp_query;
	
	$dal = new DVM_Facets_Dal();
	
	$wp_query->query_vars = $dal->decode($_REQUEST['query_vars']);
	$wp_query->post_type = $dal->decode($_REQUEST['post_type']);
		
	$response = array();
	
	foreach ($dal->facet_counts($wp_query) as $key => $value)
		$response[] = $key.'='.$value;
	
	die(implode(';', $response)); 
}

add_action('wp_ajax_dvm_facets_get_counts', 'dvm_facets_get_counts_callback');
add_action('wp_ajax_nopriv_dvm_facets_get_counts', 'dvm_facets_get_counts_callback');

?>