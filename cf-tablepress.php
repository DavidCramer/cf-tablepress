<?php
/*
  Plugin Name: Auto-Populate TablePress from Caldera Forms
  Plugin URI: http://digilab.co.za
  Description: Auto populate a TablePress table with Caldera Forms submissions.
  Author: David Cramer
  Version: 1.0.0
  Author URI: http://digilab.co.za
 */


add_filter('tablepress_shortcode_table_default_shortcode_atts', 'cf_tablepress_add_form_att');
function cf_tablepress_add_form_att($atts){
	$atts['form'] = null;
	return $atts;
};


add_filter( 'tablepress_table_raw_render_data', 'cf_tablepress_populate_table', 10, 2 );
function cf_tablepress_populate_table( $table, $options ){

	if( empty( $options['form'] ) ){
		return $table;
	}
	global $wpdb, $form;
	
	$forms = get_option( '_caldera_forms');
	// get extra classes
	$classes = explode(' ', $options['extra_css_classes'] );

	foreach( $forms as $form_check ){
		if( $form_check['ID'] == $options['form'] || strtolower( $form_check['name'] ) == strtolower( $options['form'] ) ){
			$form = get_option( $form_check['ID'] );
			break;
		}
	}
	if( empty ($form ) ){
		return $table;
	}

	$template = $table['data'][0];
	// labels
	foreach ($form['fields'] as $field_id => $field) {
		$slugs_at = array_search( $field['slug'], $table['data'][0] );
		if( false !== $slugs_at ){
			$has_slugs = true;
			$table['data'][0][$slugs_at] = $field['label'];
			$template[$slugs_at] = '%' . $field['slug'] . '%';
		}
	}

	// get entries
	$row_num = 1;
	$entries = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `" . $wpdb->prefix . "cf_form_entries` WHERE `form_id` = %s AND `status` = 'active' ORDER BY `datestamp` DESC;", $form['ID'] ) );
	if( !empty( $entries ) ){
		foreach( $entries as $entry){
			$row = array();
			foreach( $template as $key=>$value){
				$row[] = Caldera_Forms::do_magic_tags( $value, $entry->id );
			}
			$table['data'][$row_num] = $row;
			$table['visibility']['rows'][$row_num] = 1;
			$row_num++;
		}
	}

	return $table;
}