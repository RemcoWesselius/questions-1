<?php

function test_barchart( $atts ){
	$attributes = shortcode_atts( 
		array(
        	'id' => 0,
    	), 
    $atts );
	
	$survey = new Questions_Survey( $attributes[ 'id' ] );
	$export_filename = sanitize_title( $survey->title );
			
	$prepared_data = Questions_AbstractData::order_for_charting( $survey->get_responses_array() );
	
	$html = '';
	foreach ( $prepared_data[ 'questions' ] as $question_id => $question ):
		$html.= Questions_ChartCreator_Dimple::show_bars( $question, $prepared_data['data'][ $question_id ] );
	endforeach;
	
	return $html;
}
add_shortcode( 'show_survey_results', 'test_barchart' );