<?php
/**
 * Modified
 * Logging Functionality
 *
 * @package BadgeOS
 * @subpackage Logging
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://credly.com
 */

/**
 * Posts a log entry when a user unlocks any achievement post
 *
 * @since  1.0.0
 * @param  integer $object_id    The post id of the activity we're logging
 * @param  integer $user_id    The user ID
 * @param  string  $action     The action word to be used for the generated title
 * @param  string  $title      An optional default title for the log post
 * @return integer             The post ID of the newly created log entry
 */
class BadgeOS_OpenBadgesIssuer_Logging {
	
	function __construct() {	
		add_filter( 'badgeos_obi_post_log_entry', array( $this, 'badgeos_post_log_entry'), 10, 2 );
	}
	
	function badgeos_obi_post_log_entry( $object_id, $user_id = 0, $action = 'success', $content = '', $title = '' ) {
	
		// Get the current user if no ID specified
		if ( empty( $user_id ) )
			$user_id = get_current_user_id();
	
		// Setup our args to easily pass through a filter
		$args = array(
			'user_id'   => $user_id,
			'action'    => $action,
			'object_id' => $object_id,
			'title'     => $title,
			'content' 	=> $content
		);
	
		// Write log entry via filter so it can be modified by third-parties
		$log_post_id = apply_filters( 'badgeos_obi_post_log_entry', 0, $args );
	
		// Available action for other processes
		//do_action( 'badgeos_obi_create_log_entry', $log_post_id, $object_id, $user_id, $action );
	
		return $log_post_id;
	}
	
	/**
	 * Filter to create a badgeos-log-entry post
	 *
	 * @since  1.2.0
	 * @param  integer $log_post_id The ID of the log entry (default: 0)
	 * @param  array   $args        Available args to use for writing our new post
	 * @return integer              The updated log entry ID
	 */
	function badgeos_post_log_entry( $log_post_id, $args ) {
		$parsed = parse_url($args['object_id']);
		parse_str($parsed['query'], $query);
		
		// If we weren't explicitly given a title, let's build one
		if ( empty( $args['title'] ) ) {
			$user              = get_userdata( $args['user_id'] );
			$achievement       = $query['uid'];
			$status			   = $args['action'];
			$args['title']     = ! empty( $title ) ? $title : apply_filters( 'badgeos_obi_log_entry_title', "{$user->user_login} {$args['action']} sending {$achievement} ", $args );
		}
	
		// Insert our entry as a 'badgeos-log-entry' post
		$log_post_id = wp_insert_post( array(
			'post_title'  => $args['title'],
			'post_content'=> $args['content'],
			'post_author' => absint( $args['user_id'] ),
			'post_type'   => 'open-badge-entry',
			'tags_input'  =>  $args['action'],
			'post_status' => 'publish',
		) );
	
		// Return the ID of the newly created post
		return $log_post_id;
	}

	
}