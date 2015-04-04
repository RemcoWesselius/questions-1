<?php

class Questions_Survey{
	public $id;
	public $title;
	
	public $elements = array();
	public $response_errors = array();
	public $splitter_count = 0;
	public $db_responses = array();	
	
	public function __construct( $id = null ){
		if( null != $id )
			$this->populate( $id );
	}
	
	private function populate( $id ){
		global $wpdb, $questions_global;
		
		$this->reset();
		
		$survey = get_post( $id );
		
		$this->id = $id;
		$this->title = $survey->post_title;
		
		$this->elements = $this->get_elements( $id );
	}
	
	private function get_elements( $id = null ){
		global $questions_global, $wpdb;
		
		if( null == $id )
			$id = $this->id;
		
		if( '' == $id )
			return FALSE;
		
		$sql = $wpdb->prepare( "SELECT * FROM {$questions_global->tables->questions} WHERE questions_id = %s ORDER BY sort ASC", $id );
		$results = $wpdb->get_results( $sql );
		
		$elements = array();
		
		if( is_array( $results ) ):
			foreach( $results AS $result ):
				if( class_exists( 'questions_SurveyElement_' . $result->type ) ):
					$class = 'Questions_SurveyElement_' . $result->type;
					$object = new $class( $result->id );
					$elements[] = $object;
					
					if( $object->splitter ):
						$this->splitter_count++;
					endif;
				else:
					// If class do not exist -> Put in Error message here				
				endif;
			endforeach;
		endif;
		
		return $elements;
	}
	
	private function add_element( $element, $element_type, $order = null ){
		global $questions_global;
		
		if( !array_key_exists( $element_type, $questions_global->element_types ) )
			return FALSE;
		
		$class = 'Questions_SurveyElement_' . $element_type;
		
		if( null == $element_id )
			$object = new $class();
		else
			$object = new $class( $element_id );
		
		$object->element( $element, $order );
		
		if( count( $answers ) > 0 )
			foreach( $answers AS $answer )
				$object->answer( $answer['text'], $answer['order'], $answer['id'] );
			
		
		if( !$this->add_element_obj( $object, $order ) ):
			return FALSE;
		else:
			
		endif;
	}
	
	public function get_responses_array(){
		global $wpdb, $suveyval_global;
		
		if( is_array( $this->elements ) ):
			$this->add_responses( '_user_id',  $this->get_user_ids() ); // Adding User IDs to response
			$this->add_responses( '_datetime',  $this->get_timestrings() ); // Adding Time to response
			
			foreach( $this->elements AS $element ):

				if( !$element->is_question )
					continue;
				$responses = $element->get_responses();
				
				$this->add_responses( $element->id, $element->get_responses() );
				
			endforeach;
		endif;
		
		return $this->db_responses;
	}
	
	private function get_user_ids(){
		global $wpdb, $questions_global;
		
		$sql = $wpdb->prepare( "SELECT * FROM {$questions_global->tables->responds} WHERE questions_id = %s", $this->id );
		$results = $wpdb->get_results( $sql );
		
		$responses = array();
		$responses[ 'question' ] = __( 'User ID', 'questions-locale' );
		$responses[ 'sections' ] = FALSE;
		$responses[ 'array' ] = FALSE;
		$responses[ 'responses' ] = array();
		
		if( is_array( $results ) ):
			foreach( $results AS $result ):
				$responses[ 'responses' ][ $result->id ] = $result->user_id;
			endforeach;
		endif;
		
		return $responses;
	}
	
	private function get_timestrings( $timeformat = 'd.m.Y H:i' ){
		global $wpdb, $questions_global;
		
		$sql = $wpdb->prepare( "SELECT * FROM {$questions_global->tables->responds} WHERE questions_id = %s", $this->id );
		$results = $wpdb->get_results( $sql );
		
		$responses = array();
		$responses[ 'question' ] = __( 'Date/Time', 'questions-locale' );
		$responses[ 'sections' ] = FALSE;
		$responses[ 'array' ] = FALSE;
		$responses[ 'responses' ] = array();
		
		if( is_array( $results ) ):
			foreach( $results AS $result ):
				$responses[ 'responses' ][ $result->id ] = date( $timeformat, $result->timestamp );
			endforeach;
		endif;
		
		return $responses;
		
	}
	
	private function add_responses( $element_id, $responses ){
		$this->db_responses[ $element_id ] = $responses;
		unset( $responses );
	}
	
	
	private function add_element_obj( $element_object, $order = null ){
		if( !is_object( $element_object ) || 'Questions_SurveyElement' != get_parent_class( $element_object ) )
			return FALSE;
		
		if( null == $order )
			$order = count( $this->elements );
		
		$this->elements[$order] = $element_object;
		
		return TRUE;
	}
	
	// Need to be here?
	public function participated_polls( $user_id = NULL ){
		global $wpdb, $current_user, $questions_global;
		
		if( '' == $user_id ):
			get_currentuserinfo();
			$user_id = $user_id = $current_user->ID;
		endif;
		
		$sql = $wpdb->prepare( "SELECT id FROM {$questions_global->tables->responds} WHERE  user_id=%s", $user_id );
		return $wpdb->get_col( $sql );
	}
	
	private function reset(){
		$this->elements = array();
	}
}