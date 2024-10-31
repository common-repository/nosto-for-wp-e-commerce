<?php
/**
 * Holds all admin related functionality for the plugin.
 * Action hooks are added in the main plugin file init method.
 *
 * @package WP e-Commerce Nosto Tagging
 */

/**
 * Helper class for the "Nosto Tagging" admin settings.
 *
 * @since 1.0.0
 */
class WPEC_Nosto_Tagging_Admin
{
	/**
	 * Hook callback for adding a new tab on the store settings page.
	 *
	 * @since 1.0.0
	 * @param WPSC_Settings_Page $settings_page The settings page object to which to add the new tab
	 */
	public function register_tab( $settings_page ) {
		WPEC_Nosto_Tagging::get_instance()->load_class( 'WPSC_Settings_Tab_Nosto_Tagging' );

		$settings_page->register_tab(
			WPSC_Settings_Tab_Nosto_Tagging::TAB_KEY,
			WPSC_Settings_Tab_Nosto_Tagging::TAB_NAME
		);
	}

	/**
	 * Registers action links for the plugin.
	 * Add a shortcut link to the settings page.
	 *
	 * @since 1.0.0
	 * @param array  $links       Array of already defined links
	 * @param string $plugin_file The plugin base name
	 * @return array
	 */
	public function register_action_links( $links, $plugin_file ) {
		if ( $plugin_file === WPEC_Nosto_Tagging::get_instance()->get_plugin_name() ) {
			$url     = admin_url( 'options-general.php?page=wpsc-settings&tab=nosto_tagging' );
			$links[] = '<a href="' . esc_attr( $url ) . '">' . esc_html__( 'Settings' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Registers all settings for the settings tab using the WP Settings API.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {
		add_settings_section(
			'nosto_tagging_general_settings',
			__( 'General Settings' ),
			'',
			'nosto_tagging'
		);

		/*add_settings_field(
			'nosto_tagging_server_address',
			__( 'Server Address' ),
			array( 'WPSC_Settings_Tab_Nosto_Tagging', 'display_setting' ),
			'nosto_tagging',
			'nosto_tagging_general_settings',
			array(
				 'type'         => 'text',
				 'desc'         => __( 'The server address for the Nosto marketing automation service.' ),
				 'html_options' => array(
					 'name'  => 'wpsc_options[nosto_tagging_server_address]',
					 'value' => get_option( 'nosto_tagging_server_address', '' ),
					 'size'  => 40,
				 ),
			)
		);*/
		add_settings_field(
			'nosto_tagging_account_id',
			__( 'Account ID' ),
			array( 'WPSC_Settings_Tab_Nosto_Tagging', 'display_setting' ),
			'nosto_tagging',
			'nosto_tagging_general_settings',
			array(
				 'type'         => 'text',
				 'desc'         => __( 'Your Nosto marketing automation service account id.' ),
				 'html_options' => array(
					 'name'  => 'wpsc_options[nosto_tagging_account_id]',
					 'value' => get_option( 'nosto_tagging_account_id', '' ),
					 'size'  => 40,
				 )
			)
		);
		add_settings_field(
			'nosto_tagging_use_default_elements',
			__( 'Use default Nosto elements' ),
			array( 'WPSC_Settings_Tab_Nosto_Tagging', 'display_setting' ),
			'nosto_tagging',
			'nosto_tagging_general_settings',
			array(
				 'type'          => 'radio',
				 'desc'          => __( 'Use default Nosto elements for showing product recommendations.' ),
				 'default_value' => (int) get_option( 'nosto_tagging_use_default_elements', 1 ),
				 'options'       => array(
					 array(
						 'html_options' => array(
							 'id'    => 'nosto_tagging_use_default_elements_yes',
							 'name'  => 'wpsc_options[nosto_tagging_use_default_elements]',
							 'value' => 1,
						 ),
						 'label'        => __( 'Yes' ),
					 ),
					 array(
						 'html_options' => array(
							 'id'    => 'nosto_tagging_use_default_elements_no',
							 'name'  => 'wpsc_options[nosto_tagging_use_default_elements]',
							 'value' => 0,
						 ),
						 'label'        => __( 'No' ),
					 ),
				 ),
			)
		);

		/*register_setting(
			'nosto_tagging',
			'nosto_tagging_server_address',
			array( $this, 'validate_setting_server_address' )
		);*/
		register_setting(
			'nosto_tagging',
			'nosto_tagging_account_id',
			array( $this, 'validate_setting_account_id' )
		);
		register_setting(
			'nosto_tagging',
			'nosto_tagging_use_default_elements',
			array( $this, 'validate_setting_use_default_elements' )
		);
	}

	/**
	 * Validation callback function for the server address setting.
	 *
	 * @since 1.0.0
	 * @param string $server_address The server address given by the user
	 * @return string|bool
	 */
	public function validate_setting_server_address( $server_address ) {
		$valid = get_option( 'nosto_tagging_server_address' );

		if ( empty( $server_address ) ) {
			add_settings_error(
				'nosto_tagging_server_address',
				'nosto_tagging_server_address',
				__( 'Server address is required.' )
			);
		} elseif ( ! $this->is_valid_url( $server_address ) ) {
			add_settings_error(
				'nosto_tagging_server_address',
				'nosto_tagging_server_address',
				__( 'Invalid server address. Please note that the address cannot include the protocol (http or https).' )
			);
		} else {
			$valid = $server_address;
		}

		return $valid;
	}

	/**
	 * Validation callback function for the account id setting.
	 *
	 * @since 1.0.0
	 * @param string $account_id The account id given by the user
	 * @return string|bool
	 */
	public function validate_setting_account_id( $account_id ) {
		$valid = get_option( 'nosto_tagging_account_id' );

		if ( empty( $account_id ) ) {
			add_settings_error(
				'nosto_tagging_account_id',
				'nosto_tagging_account_id',
				__( 'Account ID is required.' )
			);
		} else {
			$valid = $account_id;
		}

		return $valid;
	}

	/**
	 * Validation callback function for the use default elements setting.
	 *
	 * @since 1.0.0
	 * @param string $use_default_elements The option selected by the user
	 * @return string|bool
	 */
	public function validate_setting_use_default_elements( $use_default_elements ) {
		$valid = get_option( 'nosto_tagging_use_default_elements' );

		if ( '1' !== $use_default_elements && '0' !== $use_default_elements ) {
			add_settings_error(
				'nosto_tagging_use_default_elements',
				'nosto_tagging_use_default_elements',
				__( 'Use default Nosto elements is required.' )
			);
		} else {
			$valid = $use_default_elements;
		}

		return $valid;
	}

	/**
	 * Validates that he given url is properly formatted.
	 *
	 * We check the format of the url by converting it to a valid url
	 * with http protocol restriction. If that converted url does not match the
	 * given url with the http protocol appended, then it is invalid.
	 *
	 * The http protocol is appended because the nosto url cannot contain
	 * the protocol, so we actually check two things here:
	 *  - that the url is formatted as it should
	 *  - that it does NOT include the protocol
	 *
	 * @since 1.0.0
	 * @param string $url The url to validate
	 * @return bool
	 */
	public function is_valid_url( $url ) {
		if ( 'http://' . $url === esc_url_raw( $url, array( 'http' ) ) ) {
			return true;
		}

		return false;
	}
}
