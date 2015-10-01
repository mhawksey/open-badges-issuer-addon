<?php
/**
 * Plugin Name: Open Badges Issuer Add-on
 * Description: This is a BadgeOS add-on which allows you to host Mozilla Open Badges compatible assertions and allow users to push awarded badges directly to their Mozilla  Backpack
 * Author: mhawksey
 * Version: 1.1.2
 * Author URI: https://mashe.hawksey.info/
 * Plugin URI: http://wordpress.org/plugins/badgeos-open-badges-issuer-add-on/
 * Based on BadgeOS Boilerplate Add-On by Credly https://github.com/opencredit/BadgeOS-Boilerplate-Add-on
 * License: GNU AGPLv3
 * License URI: http://www.gnu.org/licenses/agpl-3.0.html
 */

/**
 * Our main plugin instantiation class
 *
 * This contains important things that our relevant to
 * our add-on running correctly. Things like registering
 * custom post types, taxonomies, posts-to-posts
 * relationships, and the like.
 *
 * @since 1.0.0
 */
class BadgeOS_OpenBadgesIssuer {
	public $depend = array('BadgeOS' => 'http://wordpress.org/plugins/badgeos/',
						   'JSON_API' => 'http://wordpress.org/plugins/json-api/');
	/**
	 * Get everything running.
	 *
	 * @since 1.0.0
	 */
	function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugins_url( dirname( $this->basename ) );

		// Load translations
		load_plugin_textdomain( 'badgeos_obi_issuer', false, dirname( $this->basename ) . '/languages' );

		// Run our activation and deactivation hooks
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );

		// Include our other plugin files
		add_action( 'init', array( $this, 'includes' ) );
		
		// add open badges logging
		add_action( 'init', array( $this, 'open_badges_log_post_type' ) );
		
		add_action( 'init', array( $this, 'register_scripts_and_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );
			
		add_shortcode( 'badgeos_backpack_push', array(&$this, 'badgeos_backpack_push_shortcode') );
		add_shortcode( 'badgeos_backpack_registered_email', array(&$this, 'badgeos_backpack_reg_email_shortcode') );
		
		add_filter('badgeos_public_submissions', array(&$this, 'set_public_badge_submission'), 999, 1);
		
		add_action( 'wp_ajax_open_badges_recorder', array(&$this, 'badgeos_ajax_open_badges_recorder'));
		add_filter('badgeos_render_achievement', array( $this, 'badgeos_render_openbadge_button'), 11 ,2);
		
	} /* __construct() */


	/**
	 * Include our plugin dependencies
	 *
	 * @since 1.0.0
	 */
	public function includes() {
		$this->maybe_disable_plugin();
		// If BadgeOS is available...
		if ( $this->meets_requirements() ) {
			// add custom JSON API controllers
			add_filter('json_api_controllers', array(&$this,'add_badge_controller'));
			add_filter('json_api_badge_controller_path', array(&$this,'set_badge_controller_path'));
		}
		// Initialize Settings
		require_once(sprintf("%s/includes/settings.php", $this->directory_path));
		$BadgeOS_OpenBadgesIssuer_Settings = new BadgeOS_OpenBadgesIssuer_Settings();
		
		// Add logging functions
		require_once(sprintf("%s/includes/logging-functions.php", $this->directory_path));
		$BadgeOS_OpenBadgesIssuer_Logging = new BadgeOS_OpenBadgesIssuer_Logging();
		

	} /* includes() */
	
	/**
	 * Register all core scripts and styles
	 *
	 * @since  1.1.0
	 */
	function register_scripts_and_styles(){
		wp_register_script( 'badgeos-backpack', $this->directory_url . '/js/badgeos-backpack.js', array( 'jquery' ), '1.1.1', true );
		wp_register_script( 'mozilla-issuer-api', 'https://backpack.openbadges.org/issuer.js', array('badgeos-backpack'), null );
		wp_register_style( 'badgeos-backpack-style', $this->directory_url . '/css/badgeos-backpack.css', null, '1.1.0' );
	}
	
	/**
	 * Register open badges logging post type
	 *
	 * @since 1.0.0
	 */
	function open_badges_log_post_type(){	
		// Register Log Entries CPT
		register_post_type( 'open-badge-entry', array(
			'labels'             => array(
				'name'               => __( 'Log Entries', 'badgeos' ),
				'singular_name'      => __( 'Log Entry', 'badgeos' ),
				'add_new'            => __( 'Add New', 'badgeos' ),
				'add_new_item'       => __( 'Add New Log Entry', 'badgeos' ),
				'edit_item'          => __( 'Edit Log Entry', 'badgeos' ),
				'new_item'           => __( 'New Log Entry', 'badgeos' ),
				'all_items'          => __( 'Log Entries', 'badgeos' ),
				'view_item'          => __( 'View Log Entries', 'badgeos' ),
				'search_items'       => __( 'Search Log Entries', 'badgeos' ),
				'not_found'          => __( 'No Log Entries found', 'badgeos' ),
				'not_found_in_trash' => __( 'No Log Entries found in Trash', 'badgeos' ),
				'parent_item_colon'  => '',
				'menu_name'          => __( 'Open Badges Issuer Log Entries', 'badgeos_obi_issuer' )
			),
			'public'             => false,
			'publicly_queryable' => false,
			'taxonomies'         => array( 'post_tag' ),
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_nav_menus'  => false,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'open-badge-log' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => array( 'title', 'editor', 'author', 'comments' )
		) );
		
	}
	
	
	/**
	* Register controllers for custom JSON_API end points.
	*
	* @since 1.0.0
	* @param object $controllers JSON_API.
	* @return object $controllers.
	*/
	public function add_badge_controller($controllers) {
	  $controllers[] = 'badge';
	  return $controllers;
	}
	
	/**
	* Register frontend css/js.
	*
	* @since 1.1.0
	*/
	function frontend_scripts() {
		wp_enqueue_script( 'mozilla-issuer-api' );
		wp_enqueue_script( 'badgeos-backpack' );
		if (get_option('badgeos_obi_issuer_css_bypass') == "true"){
			wp_enqueue_style( 'badgeos-backpack-style' );
		}
	}
	
	/**
	 * Render an achievement override to include send to Mozilla Backpack
	 *
	 * @since  1.1.0
	 * @param  string $output The output from the original filter
	 * @param  integer $achievement The achievement's post ID
	 * @return string               Concatenated markup
	 */
	public function badgeos_render_openbadge_button($output, $achievement = 0) {
		global $user_ID;
		
		// user earned badge
		$earned = badgeos_get_user_achievements( array( 'user_id' => $user_ID, 'achievement_id' => absint( $achievement ) ) );
		
		// handle case of buddypress achievements tab
		if (function_exists('bp_displayed_user_id') && bp_displayed_user_id() !== 0 && bp_displayed_user_id() !== $user_ID){
			$earned = false;
		}
		
		// if logged in user has earned badge append send to mozilla button to output
		if($earned){
			// for submissions need to convert achievement id into badge uid		
			$args = array('author' => $user_ID,
						  'fields' => 'ids',
						  'post_type' => 'submission',
						  'meta_query' => array(
							array(
								'key'     => '_badgeos_submission_achievement_id',
								'value'   => $achievement,
							)),
						);
			$badges = get_posts($args);
			// if no submission fallback onto achievement id
			$achievement_id = (!empty($badges)) ? $badges[0] : $achievement;
			//build assertion uri
			$base_url = site_url().'/'.get_option('json_api_base', 'api').'/badge/assertion/?uid=';
			$uid = $achievement_id . "-" . get_post_time('U', true, $achievement_id) . "-" . $user_ID;
			// let user know if already attempted to send badge
			$pushed_badges = ( $pushed_items = get_user_meta( absint( $user_ID ), '_badgeos_backpack_pushed' ) ) ? (array) $pushed_items : array();
			$button_text = (!in_array($base_url.$uid, $pushed_badges)) ? __( 'Send to Mozilla Backpack', 'badgeos_obi_issuer' ) : __( 'Resend to Mozilla Backpack', 'badgeos_obi_issuer' ); 
			// append mozilla backpack button to output
			$output .= '<div class="badgeos_backpack_action">';
			$output .= '<a href="" class="badgeos_backpack button" data-uid="'.$base_url.$uid.'">'.$button_text.'</a> ';
			$output .= '<input type="checkbox" value="'.$base_url.$uid.'" name="badgeos_backpack_issues[]"/>';
			$output .= '</div>';
		} else {
			$output .= '<div class="badgeos_backpack_action"></div>';
		}
		return $output;
	}
	
	
	/**
	 * Set if badge submission evidence is public
	 *
	 * @since  1.0.0
	 * @param  boolean $public submission display
	 * @return boolen               submission display
	 */
	function set_public_badge_submission($public){
		return get_option('badgeos_obi_issuer_public_evidence');
	}
	
	/**
	 * Handle ajax request to record sending of badges to backpage
	 *
	 * @since  1.0.0
	 */
	function badgeos_ajax_open_badges_recorder(){
		global $user_ID;
		// Setup our AJAX query vars
		$successes = isset( $_REQUEST['successes'] ) ? $_REQUEST['successes'] : false;
		$errors = isset( $_REQUEST['errors'] ) ? $_REQUEST['errors'] : false;
		$user_id = isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : $user_ID;
		
		if ($successes){
			foreach ($successes as $success => $uid){
				add_user_meta( $user_id, '_badgeos_backpack_pushed', $uid, false);
				BadgeOS_OpenBadgesIssuer_Logging::badgeos_obi_post_log_entry($uid, $user_id, 'success');
			}
		}
		
		if ($errors){
			foreach ($errors as $error){
				//add_user_meta( $user_id, '_badgeos_backpack_pushed', $uid, false);
				$uid = $error['assertion'];
				BadgeOS_OpenBadgesIssuer_Logging::badgeos_obi_post_log_entry($uid, $user_id, 'failed', json_encode($error));
			}
		}
		
		wp_send_json_success( array(
			'successes'     => get_user_meta( $user_id, '_badgeos_backpack_pushed'),
			'resend_text'	=> __( 'Resend to Mozilla Backpack', 'badgeos_obi_issuer' ),
		) );
	}
	

	
	/**
	 * Achievement List with Backpack Push Short Code
	 *
	 * @since  1.0.0
	 * @param  array $atts Shortcode attributes
	 * @return string 	   The concatinated markup
	 */
	function badgeos_backpack_push_shortcode( $atts = array () ){
		global $user_ID;
		// check if shortcode has already been run
		if ( isset( $GLOBALS['badgeos_backpack_push'] ) )
			return;
		if ( !is_user_logged_in() ) {
			return __( 'Please log in to push badges to Mozilla Backpack', 'badgeos_obi_issuer' );
		}

		$output = '<style>.badgeos_backpack_action input[type=checkbox]{display: inline-block;}</style>';
		$output .= '<div class="badgeos_backpack_action"><a href="" class="badgeos_backpack_all button">'.__( 'Send selected to Mozilla Backpack', 'badgeos_obi_issuer' ).'</a></div>';
		$output .= do_shortcode('[badgeos_achievements_list limit="10" show_filter="false" show_search="true" orderby="menu_order" order="ASC" wpms="false" user_id="'.$user_ID.'"]');
		// Save a global to prohibit multiple shortcodes
		$GLOBALS['badgeos_backpack_push'] = true;
		return $output;
	}
	
	/**
	 * Achievement List with Backpack Push Short Code
	 *
	 * @since  1.0.0
	 * @param  array $atts Shortcode attributes
	 * @return string 	   The concatinated markup
	 */
	function badgeos_backpack_reg_email_shortcode( $atts = array () ){
		if ( !is_user_logged_in() ) {
			return "<em>".__( 'Please log in to push badges to Mozilla Backpack', 'badgeos_obi_issuer' )."</em>";
		}
		extract( shortcode_atts( array(
				 'user_id'     => $user_ID,
		), $atts ) );
		return $this->registered_email();
	}
	
	public function registered_email($user_id = 0){
		$user_id = ($user_id) ? $user_id : get_current_user_id();
		$email_alt_field = get_option( 'badgeos_obi_issuer_alt_email', FALSE);
		if (($email_alt_field || $email_alt_field !== "") && get_user_meta( $user_id, $email_alt_field, TRUE) !== ""){
			return get_user_meta( $user_id, $email_alt_field, TRUE);
		} else {
			$user = get_userdata( $user_id );
			return $user->user_email;
		}	
	}
	
	/**
	* Register controllers define path custom JSON_API end points.
	*
	* @since 1.0.0
	*/
	public function set_badge_controller_path() {
	  return sprintf("%s/api/badge.php", $this->directory_path);
	}
	
	
	/**
	 * Activation hook for the plugin.
	 *
	 * @since 1.0.0
	 */
	public function activate() {

		// If BadgeOS is available, run our activation functions
		if ( $this->meets_requirements() ) {
			$json_api_controllers = explode(",", get_option( 'json_api_controllers' ));
			if(!in_array('badge',$json_api_controllers)){
				$json_api_controllers[] = 'badge';
				JSON_API::save_option('json_api_controllers', implode(',', $json_api_controllers));
			}
			
			// Do some activation things

		}

	} /* activate() */

	/**
	 * Deactivation hook for the plugin.
	 *
	 * Note: this plugin may auto-deactivate due
	 * to $this->maybe_disable_plugin()
	 *
	 * @since 1.0.0
	 */
	public function deactivate() {

		// Do some deactivation things.

	} /* deactivate() */

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( class_exists('BadgeOS') && class_exists('JSON_API'))
			return true;
		else
			return false;

	} /* meets_requirements() */
	
	/**
	 * Potentially output a custom error message and deactivate
	 * this plugin, if we don't meet requriements.
	 *
	 * This fires on admin_notices.
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
			foreach ($this->depend as $class => $url){ 
				if ( !class_exists($class)) {
					$extra = sprintf('<a href="%s">%s</a>', $url, $class); 
					echo '<p>' . sprintf( __( 'Open Badges Issuer requires %s and has been <a href="%s">deactivated</a>. Please install and activate %s and then reactivate this plugin.', 'badgeos_obi_issuer' ),  $extra, admin_url( 'plugins.php' ), $extra ) . '</p>';
				}
			}
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}

	} /* maybe_disable_plugin() */

} /* BadgeOS_Addon */

// Instantiate our class to a global variable that we can access elsewhere
$GLOBALS['badgeos_openbadgesissuer'] = new BadgeOS_OpenBadgesIssuer();
