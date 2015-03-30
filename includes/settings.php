<?php
class BadgeOS_OpenBadgesIssuer_Settings {
	
	function __construct() {	
		add_action('admin_init', array(&$this, 'save'));
		add_action('admin_menu', array( $this, 'plugin_menu' ) );
	}	
	
	/**
	 * Create BadgeOS Settings menus
	 */
	function plugin_menu() {
		add_submenu_page( 'badgeos_badgeos', __( 'Open Badges Issuer Settings', 'badgeos_obi_issuer' ), __( 'Open Badges Issuer Settings', 'badgeos_obi_issuer' ), badgeos_get_manager_capability(), 'open-badges-issuer', array(&$this, 'badgeos_obi_issuer_settings') );
		add_submenu_page( 'badgeos_badgeos', __( 'Open Badges Issuer Log Entries', 'badgeos_obi_issuer' ), __( 'Open Badges Issuer Log Entries', 'badgeos_obi_issuer' ), badgeos_get_manager_capability(), 'edit.php?post_type=open-badge-entry' );
	}
	
	function badgeos_obi_issuer_settings(){

		if (!current_user_can(badgeos_get_manager_capability())) {
			wp_die("You do not have sufficient permissions to access this page.");
		}
		
		?>
		<div class="wrap">
        	<?php settings_errors(); ?>
            <?php $this->json_api_controller_status(); ?>
            <h2>Open Badges Issuer Add-on Settings</h2>
            <form method="post" action="options.php"> 
                <?php @settings_fields('badgeos_obi_issuer_settings'); ?>
                <?php @do_settings_fields('badgeos_obi_issuer_settings'); ?>
        
                <?php do_settings_sections('badgeos_obi_issuer_template'); ?>
        
                <?php @submit_button(); ?>
            </form>
        </div>
        <?php
	}
	
	function json_api_controller_status(){
		$json_api_controllers = explode(",", get_option( 'json_api_controllers' ));
		if(!in_array('badge',$json_api_controllers)){	
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'Open Badges Issuer requires the JSON API Mozilla Open Badges Generator to be active. Please <a href="">activate in JSON API settings</a>', 'obissuer' ),  admin_url( 'options-general.php?page=json-api' ) ) . '</p>';
			echo '</div>';
		}
	}
	
	public function badgeos_obi_issuer_settings_section_about()
	{
		?>
        <p>This plugin extends BadgeOS to allow you to host and issue Open Badges compatible assertions. This means 
        users can directly add BadgeOS awarded badges to their Mozilla Backpack. To enable users to send create a new page and 
        include the shortcode <code>[badgeos_backpack_push]</code>.</p> 

		<p>If you are a developer and would like to support the development of this plugin issues and contributions can 
        be made to <a href="https://github.com/mhawksey/badgeos-open-badges-issuer">https://github.com/mhawksey/badgeos-open-badges-issuer</a></p>
        
        <p>This add-on has been developed by the <a href="https://alt.ac.uk">Association for Learning Technology</a></p>
        <?php
	}
	
	public function badgeos_obi_issuer_settings_section_general()
	{
		// Think of this as help text for the section.
	}
	
	public function badgeos_obi_issuer_settings_section_override()
	{
		// Think of this as help text for the section.
		echo __('These are optional settings to set the <a href="https://github.com/mozilla/openbadges-specification/blob/master/Assertion/latest.md#issuerorganization">IssuerOrganiztion</a>. 
		By default the add-on will use the blog name and url.', 'badgeos_obi_issuer');
	}

	/**
	 * This function provides text inputs for settings fields
	 */
	public function settings_field_input_text($args)
	{
		// Get the field name from the $args array
		$field = $args['name'];
		// Get the value of this setting
		$value = get_option($field);
		// echo a proper input type="text"
		echo sprintf('<input type="text" name="%s" id="%s" value="%s" />', $field, $field, $value);
		echo '<p class="description">'.$args['description'].'</p>';
	} // END public function settings_field_input_text($args)
	
	/**
	 * This function provides text inputs for settings fields
	 */
	public function settings_field_input_textarea($args)
	{
		// Get the field name from the $args array
		$field = $args['name'];
		// Get the value of this setting
		$value = get_option($field);
		// echo a proper input type="text"
		echo sprintf('<textarea type="text" name="%s" id="%s">%s</textarea>', $field, $field, $value);
		echo '<p class="description">'.$args['description'].'</p>';
	} // END public function settings_field_input_text($args)
	
	/**
	* This function provides slect inputs for settings fields
	*/
	public function settings_field_input_select($args)
	{
		// Get the field name from the $args array
		$field = $args['name'];
		// Get the value of this setting
		$value = get_option($field);
		echo '
		      <select
            	name="'.$field.'"
                id="'.$field.'">
				<option value="">'.__('- Primary Email Only -', badgeos).'</option>';
		foreach($args['choices'] as $val => $trans){ 
			echo '<option value="'.$val.'" '.selected( $value, $val, FALSE ).'>'.$trans.'</option>';
	 	}
		echo '</select>';
		echo '<p class="description">'.$args['description'].'</p>';
	} // END public function settings_field_input_page_select($args)
		
	/**
	* This function provides text inputs for settings fields
	*/
	public function settings_field_input_radio($args)
	{
		// Get the field name from the $args array
		$field = $args['name'];
		$value = get_option($field);

		foreach($args['choices'] as $val => $trans)
		{
			$val = esc_attr($val);

			echo '
			<input id="'.$field.'-'.$val.'" type="radio" name="'.$field.'" value="'.$val.'" '.checked($val, $value, FALSE).' />
			<label for="'.$field.'-'.$val.'">'.esc_html($trans).'</label>';
		}

		echo '<p class="description">'.$args['description'].'</p>';
		
	} // END public function settings_field_input_radio($args)
	
	function save() {
		register_setting('badgeos_obi_issuer_settings', 'badgeos_obi_issuer_public_evidence');
		register_setting('badgeos_obi_issuer_settings', 'badgeos_obi_issuer_alt_email');
		register_setting('badgeos_obi_issuer_settings', 'badgeos_obi_issuer_org_name');
		register_setting('badgeos_obi_issuer_settings', 'badgeos_obi_issuer_org_url');
		register_setting('badgeos_obi_issuer_settings', 'badgeos_obi_issuer_org_description');
		register_setting('badgeos_obi_issuer_settings', 'badgeos_obi_issuer_org_image');
		register_setting('badgeos_obi_issuer_settings', 'badgeos_obi_issuer_org_email');
		register_setting('badgeos_obi_issuer_settings', 'badgeos_obi_issuer_org_revocationList');
		// add your settings section
		add_settings_section(
			'badgeos_obi_issuer_template-section-about', 
			__('About', 'badgeos_obi_issuer'), 
			array(&$this, 'badgeos_obi_issuer_settings_section_about'), 
			'badgeos_obi_issuer_template'
		);
		
		add_settings_section(
			'badgeos_obi_issuer_template-section', 
			__('General Settings', 'badgeos_obi_issuer'), 
			array(&$this, 'badgeos_obi_issuer_settings_section_general'), 
			'badgeos_obi_issuer_template'
		);

		// add your setting's fields
		// add your setting's fields
		add_settings_field(
			'badgeos_obi_issuer_alt_email', 
			__('Alternative Email', 'badgeos_obi_issuer'), 
			array(&$this, 'settings_field_input_select'), 
			'badgeos_obi_issuer_template', 
			'badgeos_obi_issuer_template-section',
			array(
				'name' => 'badgeos_obi_issuer_alt_email',
				'choices' => wp_get_user_contact_methods(),
				'description' => __('Specify an optional additional email field if you would like users to be able to collect badges using a different address', 'badgeos_obi_issuer'),
			)
		);
		
		add_settings_field(
			'badgeos_obi_issuer_public_evidence', 
			__('Public evidence', 'badgeos_obi_issuer'), 
			array(&$this, 'settings_field_input_radio'), 
			'badgeos_obi_issuer_template', 
			'badgeos_obi_issuer_template-section',
			array(  'name' => 'badgeos_obi_issuer_public_evidence',
					'choices' => array( 'true' => 'Enable',
										'false' => 'Disable'),
					'description' => __('Enable or Disable public badge evidence for submissions', 'badgeos_obi_issuer'),
			)
		);
		
		add_settings_section(
			'badgeos_obi_issuer_template-section2', 
			__('Issuer Organization Override', 'badgeos_obi_issuer'), 
			array(&$this, 'badgeos_obi_issuer_settings_section_override'), 
			'badgeos_obi_issuer_template'
		);
		
		add_settings_field(
			'badgeos_obi_issuer_org_name', 
			__('Name', 'badgeos_obi_issuer'), 
			array(&$this, 'settings_field_input_text'), 
			'badgeos_obi_issuer_template', 
			'badgeos_obi_issuer_template-section2',
			array(  'name' => 'badgeos_obi_issuer_org_name',
					'description' => __('The name of the issuing organization.', 'badgeos_obi_issuer'),
			)
		);
		
		add_settings_field(
			'badgeos_obi_issuer_org_url', 
			__('Url', 'badgeos_obi_issuer'), 
			array(&$this, 'settings_field_input_text'), 
			'badgeos_obi_issuer_template', 
			'badgeos_obi_issuer_template-section2',
			array(  'name' => 'badgeos_obi_issuer_org_url',
					'description' => __('URL of the institution', 'badgeos_obi_issuer'),
			)
		);
		
		add_settings_field(
			'badgeos_obi_issuer_org_description', 
			__('Description', 'badgeos_obi_issuer'), 
			array(&$this, 'settings_field_input_textarea'), 
			'badgeos_obi_issuer_template', 
			'badgeos_obi_issuer_template-section2',
			array(  'name' => 'badgeos_obi_issuer_org_description',
					'description' => __('A short description of the institution', 'badgeos_obi_issuer'),
			)
		);
		
		add_settings_field(
			'badgeos_obi_issuer_org_image', 
			__('Image', 'badgeos_obi_issuer'), 
			array(&$this, 'settings_field_input_text'), 
			'badgeos_obi_issuer_template', 
			'badgeos_obi_issuer_template-section2',
			array(  'name' => 'badgeos_obi_issuer_org_image',
					'description' => __('An image representing the institution', 'badgeos_obi_issuer'),
			)
		);
		
		add_settings_field(
			'badgeos_obi_issuer_org_email', 
			__('Email', 'badgeos_obi_issuer'), 
			array(&$this, 'settings_field_input_text'), 
			'badgeos_obi_issuer_template', 
			'badgeos_obi_issuer_template-section2',
			array(  'name' => 'badgeos_obi_issuer_org_email',
					'description' => __('Contact address for someone at the organization.', 'badgeos_obi_issuer'),
			)
		);
		
		add_settings_field(
			'badgeos_obi_issuer_org_revocationList', 
			__('Revocation List Url', 'badgeos_obi_issuer'), 
			array(&$this, 'settings_field_input_text'), 
			'badgeos_obi_issuer_template', 
			'badgeos_obi_issuer_template-section2',
			array(  'name' => 'badgeos_obi_issuer_org_revocationList',
					'description' => __('URL of the Badge Revocation List. The endpoint should be a JSON representation of an object where the keys are the uid a revoked badge assertion, and the values are the reason for revocation. This is only necessary for signed badges.', 'badgeos_obi_issuer'),
			)
		);
	}
}