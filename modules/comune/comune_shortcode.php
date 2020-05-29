<?php

/*****************************************************************
 * Comune                                                        *
 *****************************************************************/
add_shortcode( 'comune', 'gcmi_comune_shortcode' );

function gcmi_comune_shortcode( $atts ) {
	$args                 = shortcode_atts(
		array(
			'name'         => 'comune',
			'kind'         => 'tutti',
			'id'           => '',
			'comu_details' => 'false',
			'class'        => 'gcmi_comune',
		),
		$atts,
		'comune'
	);
	$args['comu_details'] = filter_var( $args['comu_details'], FILTER_VALIDATE_BOOLEAN );

	$gcmi_Comune_SC = new GCMI_COMUNE_ShortCode( $args );
	return $gcmi_Comune_SC->get_html();
}


