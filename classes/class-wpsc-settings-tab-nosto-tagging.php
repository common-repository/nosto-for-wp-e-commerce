<?php
/**
 * Holds the settings tab class used to display a separate
 * settings tab for the plugin under the Settings->Store page.
 *
 * The tab is registered in the WPEC_Nosto_Tagging_Admin class.
 *
 * @package WP e-Commerce Nosto Tagging
 */

/**
 * Class for the "Nosto Tagging" settings tab under Settings->Store.
 *
 * @since 1.0.0
 */
class WPSC_Settings_Tab_Nosto_Tagging extends WPSC_Settings_Tab
{
	/**
	 * Value used as tab id.
	 * Used in WPEC_Nosto_Tagging_Admin.
	 *
	 * @since 1.0.0
	 */
	const TAB_KEY = 'nosto_tagging';

	/**
	 * Value used as tab display name.
	 * Used in WPEC_Nosto_Tagging_Admin.
	 *
	 * @since 1.0.0
	 */
	const TAB_NAME = 'Nosto Tagging';

	/**
	 * Display tab content.
	 *
	 * @since 1.0.0
	 */
	public function display() {
		settings_fields( 'nosto_tagging' );
		do_settings_sections( 'nosto_tagging' );
	}

	/**
	 * Display a registered setting.
	 *
	 * @since 1.0.0
	 * @param array $args List of registered setting field arguments
	 */
	public function display_setting( $args = array() ) {
		$html = '';

		switch ( $args['type'] ) {
			case 'text':
				$html .= '<input type="text"';
				foreach ( $args['html_options'] as $key => $value ) {
					$html .= ' ' . $key . '="' . esc_attr( $value ) . '"';
				}
				$html .= ' />';

				$html .= '<span class="howto">' . esc_html( $args['desc'] ) . '</span>';
				break;

			case 'radio':
				foreach ( $args['options'] as $option ) {
					$html .= '<input type="radio"';
					foreach ( $option['html_options'] as $key => $value ) {
						$html .= ' ' . $key . '="' . esc_attr( $value ) . '"';
					}
					$html .= checked( $option['html_options']['value'], $args['default_value'], false );
					$html .= ' />';
					$html .= '&nbsp;';
					$html .= '<label for="' . esc_attr( $option['html_options']['id'] ) . '">';
					$html .= esc_html( $option['label'] ) . '</label>';
					$html .= '&nbsp;';
				}

				$html .= '<span class="howto">' . esc_html( $args['desc'] ) . '</span>';
				break;

			default:
				break;
		}

		echo $html;
	}
}
