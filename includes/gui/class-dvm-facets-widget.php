<?php

class DVM_Facets_Widget extends WP_Widget
{
	var $default_instance = array (
		'title'			=> 'Facets',
		'multiple'		=> '0'
	);
	
    function DVM_Facets_Widget()
	{
		$widget_ops = array (
			'classname' => 'dvm_facets_widget',
			'description' => 'Enables faceted search if used on search.php'
		);
			
		$control_ops = array (
			'width' => 300,
			'height' => 300
		);
		
		parent::WP_Widget('dvm_facets_widget', 'DVM Facets - Select facets', $widget_ops, $control_ops);
    }
 
    function widget($args, $instance)
	{
			extract($args);
			global $wp_query, $facet_anchor_style, $facet_anchor_style_active;

			
			foreach (array_keys($this->default_instance) as $var)
				if (!isset($instance[$var])) $instance[$var] = $this->default_instance[$var];
				
			// Load dissertation data and gui
			$dal = new DVM_Facets_Dal();
			$html = new DVM_Facets_Html();		
			
			// Get query from querystring
			$query = $dal->get_query();
		
			$facets = $dal->get_all_facets();

			// Write result
			echo $before_widget;
			echo $before_title;
			$html->write($instance['title']);
			echo ($after_title);
			
			$facet_ids = array();
		
			foreach ($facets as $facet)
			{
				$anchor_query = clone $query;
				$anchor_id = 'dvm_facet_anchor_'.$facet->id;
				$anchor_text = $html->encode($facet->name)." ".
						'<div '.
						'style="display: none;" '.
						'id="dvm_facet_count_'.$facet->id.'" '.
						'class="dvm_facet_counts">'.
						'</div>';
						
				$class = $facet->icon.' search-facet';
	  		
				if ($query->has_facet_id($facet->id))
				{
					$anchor_query->remove_facet_id($facet->id);
					$class .= ' search-facet-active';
				}
				else
				{
					if (!$instance['multiple'])
						$anchor_query->clear_facet_ids();
					$anchor_query->add_facet_id($facet->id);
				}
				$html->anchor(
					$anchor_query,
					$anchor_text, 
					$anchor_id,
					$class);
			}
			
			add_action('wp_print_footer_scripts', array($this, 'dvm_facets_get_counts'));
			echo $after_widget;
	}
	
    function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		foreach (array_keys($this->default_instance) as $key)
			$instance[$key] = strip_tags(stripslashes($new_instance[$key]));
		print_r($instance);
		return $instance;
	}
	
	function form($instance)
	{
		// Output the admin form
		if (!isset($instance)) $instance = array();
		$instance = wp_parse_args((array) $instance, $this->default_instance);
		
		foreach (array_keys($this->default_instance) as $key)
		{
			$value = strip_tags(stripslashes($instance[$key]));
			$name = $this->get_field_name($key);
			$label = ucfirst(str_replace('_', ' ', $key));
			echo("<p>\n<label for=\"$name\">$label</label><br>\n");
			echo("<input style=\"width: 100%;\" id=\"$name\" name=\"$name\" type=\"text\" value=\"$value\" />\n</p>\n");
		}
	}
 
	function dvm_facets_get_counts() {
		global $wp_query;
		$ajaxurl = admin_url('admin-ajax.php');
		$action = 'dvm_facets_get_counts';
		$dal = new DVM_Facets_Dal();
		$query_vars = $dal->get_original_query_vars();
		$post_type = $dal->get_original_post_type();
		$cache_key = $dal->get_cache_key($query_vars, $post_type);
		?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					ajaxurl = '<?php echo $ajaxurl ?>';
					data = { 
						action:     '<?php echo $action ?>', 
						cache_key:  '<?php echo $cache_key ?>', 
						query_vars: '<?php echo $dal->encode($query_vars) ?>', 
						post_type:  '<?php echo $dal->encode($post_type) ?>' 
					};
					
					jQuery.post(ajaxurl, data, function(response) {
						kvs = response.split(';');
						for (var i in kvs) {
							kv = kvs[i].split('=');
							facet_id = kv[0];
							count = kv[1];
							count_id = '#dvm_facet_count_'.concat(facet_id);
							html = '('.concat(count, ')');
							jQuery(count_id).html(html);
							if (count < 1)
							{

								anchor_id = '#dvm_facet_anchor_'.concat(facet_id);						
								if (!jQuery(anchor_id).hasClass('search-facet-active'))
								{
									jQuery(anchor_id).addClass('search-facet-inactive');
									$(anchor_id).removeAttr('href'); 
								}
							}
						}
						jQuery('.dvm_facet_counts').css('display', 'inline');
						
					});
				});
			</script>
		<?php
	}
}
