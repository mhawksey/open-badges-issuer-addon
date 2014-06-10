<?php
class BadgeOS_OpenBadgesIssuer_Settings {
	
	function __construct() {	
		add_action('admin_init', array(&$this, 'save'));
		//add_action('admin_menu', array( $this, 'plugin_menu' ) );
	}	
	
	function open_badges_issuer_settings(){
		$badgeos_settings = get_option( 'badgeos_settings' );
		if (!current_user_can($badgeos_settings['minimum_role'])) {
			wp_die("You do not have sufficient permissions to access this page.");
		}
		
		?>
		<div class="wrap">
        	<?php settings_errors(); ?>
            <h2>Open Badges Issuer Settings</h2>
            <form method="post" action="options.php"> 
                <?php @settings_fields('open_badges_issuer_settings'); ?>
                <?php @do_settings_fields('open_badges_issuer_settings'); ?>
        
                <?php do_settings_sections('open_badges_issuer_template'); ?>
        
                <?php @submit_button(); ?>
            </form>
        </div>
        <?php
	}
	
	
	public function settings_section_open_badges_issuer_template()
	{
		// Think of this as help text for the section.
		echo 'The pages below can be set for custom templates for evidence and hypothesis data';
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
	} // END public function settings_field_input_page_select($args)
		
	/**
	* This function provides text inputs for settings fields
	*/
	public function settings_field_input_radio($args)
	{
		// Get the field name from the $args array
		$field = $args['name'];
		$value = get_option($field);
		echo '
		<div id="obi_settings">';

		foreach($args['choices'] as $val => $trans)
		{
			$val = esc_attr($val);

			echo '
			<input id="'.$field.'-'.$val.'" type="radio" name="'.$field.'" value="'.$val.'" '.checked($val, $value, FALSE).' />
			<label for="'.$field.'-'.$val.'">'.esc_html($trans).'</label>';
		}

		echo '
			<p class="description">'.$args['description'].'</p>
		</div>';
		
	} // END public function settings_field_input_radio($args)
	
	function save() {
		register_setting('open_badges_issuer_settings', 'open_badges_issuer_public_evidence');
		register_setting('open_badges_issuer_settings', 'open_badges_issuer_alt_email');
		// add your settings section
		add_settings_section(
			'open_badges_issuer_template-section', 
			'Settings', 
			array(&$this, 'settings_section_open_badges_issuer_template'), 
			'open_badges_issuer_template'
		);

		// add your setting's fields
		// add your setting's fields
		add_settings_field(
			'open_badges_issuer_alt_email', 
			'Alternative Email', 
			array(&$this, 'settings_field_input_select'), 
			'open_badges_issuer_template', 
			'open_badges_issuer_template-section',
			array(
				'name' => 'open_badges_issuer_alt_email',
				'choices' => wp_get_user_contact_methods(),
				'description' => __('Specify an optional additional email field if you would like users to be able to collect badges using a different address', 'badgeos_obi_issuer'),
			)
		);
		
		add_settings_field(
			'open_badges_issuer_public_evidence', 
			'Public evidence', 
			array(&$this, 'settings_field_input_radio'), 
			'open_badges_issuer_template', 
			'open_badges_issuer_template-section',
			array(  'name' => 'open_badges_issuer_public_evidence',
					'choices' => array( 'yes' => 'Enable',
										'no' => 'Disable'),
					'description' => __('Enable or Disable public badge evidence for submissions', 'badgeos_obi_issuer'),
			)
		);
	}
}