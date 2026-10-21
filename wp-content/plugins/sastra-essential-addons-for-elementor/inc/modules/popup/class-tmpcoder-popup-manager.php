<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Popup Manager Class
 * Handles frontend popup rendering and condition checking
 */
class TMPCODER_Popup_Manager {

	/**
	 * Instance
	 *
	 * @var TMPCODER_Popup_Manager
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return TMPCODER_Popup_Manager
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'template_include', [ $this, 'set_post_type_template' ], 9999 );
		add_action( 'wp', [ $this, 'init_popups' ] );
		add_action( 'wp_footer', [ $this, 'render_popups' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_popup_scripts' ] );

		add_filter( 'rocket_delay_js_exclusions', function( $excluded ) {
			$excluded[] = 'perfect-scrollbar.min.js';
			$excluded[] = 'modal-popups';
			return $excluded;
		});

		add_filter( 'rocket_defer_inline_exclusions', function( $excluded ) {
			$excluded[] = 'modal-popups';
			return $excluded;
		});

		add_filter( 'rocket_exclude_js', function( $excluded ) {
			$excluded[] = 'perfect-scrollbar.min.js';
			$excluded[] = 'modal-popups';
			return $excluded;	
		});
	}

	/**
	 * Set blank template for editor
	 *
	 * @param string $template Template path
	 * @return string
	 */
	public function set_post_type_template( $template ) {
		if ( ! defined( 'TMPCODER_THEME_ADVANCED_HOOKS_POST_TYPE' ) ) {
			return $template;
		}

		if ( is_singular( TMPCODER_THEME_ADVANCED_HOOKS_POST_TYPE ) ) {
			$post_id = get_the_ID();
			$template_type = get_post_meta( $post_id, 'tmpcoder_template_type', true );

			// Check if it's a popup template and we're in preview mode
			if ( 'type_popup' === $template_type && \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
				$document = \Elementor\Plugin::$instance->documents->get( $post_id );
				if ( $document && 'tmpcoder-popup' === $document->get_name() ) {
					$template = TMPCODER_PLUGIN_DIR . 'inc/modules/popup/editor.php';
				}
			}
		}

		return $template;
	}

	/**
	 * Initialize popups
	 */
	public function init_popups() {
		if ( is_admin() || \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			return;
		}

		$this->get_popups_by_conditions();
	}

	/**
	 * Get popups by conditions
	 *
	 * @return array
	 */
	public function get_popups_by_conditions() {
		$target_rules = TMPCODER_Target_Rules_Fields::get_instance();
		
		$option = [
			'location' => 'tmpcoder_target_include_locations',
			'exclusion' => 'tmpcoder_target_exclude_locations',
			'users' => 'tmpcoder_target_user_roles',
		];

		$popups = $target_rules->get_posts_by_conditions( TMPCODER_THEME_ADVANCED_HOOKS_POST_TYPE, $option );

		// Filter only popup templates
		$popup_templates = [];
		if ( ! empty( $popups ) ) {
			foreach ( $popups as $popup_id => $popup_data ) {
				$template_type = get_post_meta( $popup_id, 'tmpcoder_template_type', true );
				if ( 'type_popup' === $template_type ) {
					$popup_templates[ $popup_id ] = $popup_data;
				}
			}
		}

		// Store in transient for JS access
		if ( ! empty( $popup_templates ) ) {
			set_transient( 'tmpcoder_active_popups_' . get_the_ID(), $popup_templates, HOUR_IN_SECONDS );
		}

		return $popup_templates;
	}

	/**
	 * Render popups in footer
	 */
	public function render_popups() {
		if ( is_admin() || \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			return;
		}

		$popups = $this->get_popups_by_conditions();

		if ( empty( $popups ) ) {
			return;
		}

		foreach ( $popups as $popup_id => $popup_data ) {
			$this->render_popup( $popup_id );
		}
	}

	/**
	 * Render single popup
	 *
	 * @param int $popup_id Popup ID
	 */
	public function render_popup( $popup_id ) {
		if ( \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
			return;
		}

		if ( defined('ICL_LANGUAGE_CODE') ) {
			$default_language_code = apply_filters('wpml_default_language', null);
			$current_language_code = apply_filters( 'wpml_current_language', NULL );

			IF ( ICL_LANGUAGE_CODE !== $default_language_code ) {
				$popup_id = apply_filters('wpml_object_id', $popup_id, 'theme-advanced-hook', true, $current_language_code);
			}
		}

		$document = \Elementor\Plugin::$instance->documents->get( $popup_id );
		if ( ! $document ) {
			return;
		}

		$settings = $document->get_settings();
		$elementor_content = \Elementor\Plugin::instance()->frontend->get_builder_content( $popup_id, false );

		if ( '' === $elementor_content ) {
			return;
		}

		// Get default settings
		$popup_settings = $this->get_popup_default_settings($popup_id);

		// Check user roles (Pro feature - for now always return true in free)
		if ( ! $this->check_available_user_roles( isset( $popup_settings['popup_show_for_roles'] ) ? $popup_settings['popup_show_for_roles'] : '' ) ) {
			return;
		}

		// Encode settings for data attribute
		$encoded_settings = wp_json_encode( $popup_settings );
		$template_settings_attr = "data-settings='" . esc_attr( $encoded_settings ) . "'";

		?>
		<div id="tmpcoder-popup-id-<?php echo esc_attr( $popup_id ); ?>" class="tmpcoder-template-popup" <?php echo $template_settings_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="tmpcoder-template-popup-inner">
				<div class="tmpcoder-popup-overlay"></div>
				<div class="tmpcoder-popup-container">
					<?php
					if ( \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_font_icon_svg' ) ) {
						echo '<div class="tmpcoder-popup-close-btn"><i class="fa fa-times"></i></div>';
					} else {
						echo '<div class="tmpcoder-popup-close-btn"><i class="eicon-close"></i></div>';
					}
					?>
					<div class="tmpcoder-popup-container-inner">
						<?php echo $elementor_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get popup default settings
	 *
	 * @return array
	 */
	private function get_popup_default_settings($popup_id) {

		$settings = [];
    	$defaults = [];

		$meta_settings = get_post_meta( $popup_id, '_elementor_page_settings', true );

		$popup_defaults = [
			'popup_trigger' => 'load',
			'popup_load_delay' => 1,
			'popup_scroll_progress' => 10,
			'popup_inactivity_time' => 15,
			'popup_element_scroll' => '',
			'popup_custom_trigger' => '',
			'popup_specific_date' => gmdate( 'Y-m-d H:i', strtotime( '+1 month' ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ),
			'popup_stop_after_date' => false,
			'popup_stop_after_date_select' => gmdate( 'Y-m-d H:i', strtotime( '+1 day' ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ),
			'popup_show_again_delay' => 1,
			'popup_disable_esc_key' => false,
			'popup_automatic_close_switch' => false,
			'popup_automatic_close_delay' => 10,
			'popup_animation' => 'fade',
			'popup_animation_duration' => 1,
			'popup_show_for_roles' => '',
			'popup_show_via_referral' => false,
			'popup_referral_keyword' => '',
			'popup_display_as' => 'modal',
			'popup_show_on_device' => true,
			'popup_show_on_device_mobile' => true,
			'popup_show_on_device_tablet' => true,
			'popup_disable_page_scroll' => true,
			'popup_overlay_disable_close' => false,
			'popup_close_button_display_delay' => 0,
		];

		// Determine Template
		// if ( strpos( $slug, 'popup') ) {
			$defaults = $popup_defaults;
		// }

		foreach( $defaults as $option => $value ) {
			if ( isset($meta_settings[$option]) ) {
				$settings[$option] = $meta_settings[$option];
			}
		}

    	return array_merge( $defaults, $settings );
	}

	/**
	 * Check available user roles
	 *
	 * @param array|string $selected_roles Selected roles
	 * @return bool
	 */
	private function check_available_user_roles( $selected_roles ) {
		if ( empty( $selected_roles ) ) {
			return true;
		}

		// Pro feature - in free version, always return true
		if ( ! tmpcoder_is_availble() ) {
			return true;
		}

		$current_user = wp_get_current_user();

		if ( ! empty( $current_user->roles ) ) {
			$role = $current_user->roles[0];
		} else {
			$role = 'guest';
		}

		if ( is_array( $selected_roles ) && in_array( $role, $selected_roles, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Enqueue popup scripts
	 */
	public function enqueue_popup_scripts() {
		if ( is_admin() ) {
			return;
		}

		$popups = $this->get_popups_by_conditions();

		if ( empty( $popups ) ) {
			return;
		}

		// Enqueue PerfectScrollbar for popup scrolling
		wp_enqueue_script(
			'tmpcoder-popup-scroll-js',
			TMPCODER_PLUGIN_URI . 'assets/js/lib/perfect-scrollbar/perfect-scrollbar.min.js',
			[ 'jquery' ],
			'0.4.9',
			true
		);

		// Enqueue popup script
		wp_enqueue_script(
			'tmpcoder-modal-popups',
			TMPCODER_PLUGIN_URI . 'assets/js/modal-popups' . tmpcoder_script_suffix() . '.js',
			[ 'jquery', 'tmpcoder-popup-scroll-js' ],
			TMPCODER_PLUGIN_VER,
			true
		);
	}

}


// Initialize
TMPCODER_Popup_Manager::get_instance();