<?php
/**
 * Class helps to add new panel for customizer to add color to different sections of box with live view.
 *
 * @package pt-social-subscribe-box
 */

// If file accessed directly. It will exit.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/***
 * Class PT_Social_Subscribe_Box_Customizer_Settings_Helper
 */
class PT_Social_Subscribe_Box_Customizer {

	/**
	 * Class Instance
	 *
	 * @var PT_Social_Subscribe_Box_Customizer
	 */
	private static $instance = null;

	/**
	 * The constructor.
	 */
	private function __construct() {
		$this->setup();
	}

	/**
	 * Get instance of class
	 *
	 * @return PT_Social_Subscribe_Box_Customizer
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup settings
	 */
	public function setup() {
		add_action( 'customize_register', array( $this, 'add_customizer_settings' ) );
		//load our own customizer preview js
		add_action( 'customize_preview_init', array( $this, 'load_preview_js' ) );

		add_action( 'wp_head', array( $this, 'apply_theme_customizer_settings' ) );
	}
	/**
	 * Load customizer preview js
	 *
	 */
	public function load_preview_js() {
		wp_enqueue_script( 'cb-preview-js', PT_Social_Subscribe_Box::get_instance()->url . 'assets/js/ssbox-preview.js', array( 'customize-preview', 'jquery' ) );
	}


	/**
	 * Add customizer settings
	 *
	 * @param WP_Customize_Manager $wp_customize WP_Customize_Manager instance.
	 */
	public function add_customizer_settings( $wp_customize ) {

		$wp_customize->add_setting( 'ptssbox_tab_bg_color' , array(
			'default'     => '#808080',
			'transport'   => 'postMessage',
		) );

		$wp_customize->add_setting( 'ptssbox_tab_text_color' , array(
			'default'     => '#fff',
			'transport'   => 'postMessage',
		) );

		$wp_customize->add_setting( 'ptssbox_header_bg_color' , array(
			'default'     => '#111',
			'transport'   => 'postMessage',
		) );

		$wp_customize->add_setting( 'ptssbox_header_text_color' , array(
			'default'     => '#fff',
			'transport'   => 'postMessage',
		) );

		$wp_customize->add_setting( 'ptssbox_subscribe_btn_bg_color' , array(
			'default'     => '#E5D416',
			'transport'   => 'postMessage',
		) );

		$wp_customize->add_setting( 'ptssbox_subscribe_btn_hover_color' , array(
			'default'     => '#E5D416',
			'transport'   => 'postMessage',
		) );

		$wp_customize->add_setting( 'ptssbox_subscribe_btn_text_color' , array(
			'default'     => '#000',
			'transport'   => 'postMessage',
		) );

		$wp_customize->add_setting( 'ptssbox_subscribe_btn_hover_text_color' , array(
			'default'     => '#000',
			'transport'   => 'postMessage',
		) );

		$wp_customize->add_setting( 'ptssbox_footer_bg_color' , array(
			'default'     => '#ffffff',
			'transport'   => 'postMessage',
		) );

		$wp_customize->add_setting( 'ptssbox_footer_text_color' , array(
			'default'     => '#111',
			'transport'   => 'postMessage',
		) );

		$wp_customize->add_section( 'ptssbox_customizer_settings' , array(
			'title'      => __( 'Social Subscribe Box', 'social-subscribe-box' ),
			'priority'   => 1199,
		) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ptssbox_tab_color_1', array(
			'settings'      => 'ptssbox_tab_bg_color',
			'section'       => 'ptssbox_customizer_settings',
			'label'         => __( 'Tab color', 'social-subscribe-box' ),
		) ) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ptssbox_tab_color_2', array(
			'settings'      => 'ptssbox_tab_text_color',
			'section'       => 'ptssbox_customizer_settings',
			'label'         => __( 'Tab text color', 'social-subscribe-box' ),
		) ) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ptssbox_box_header_color_1', array(
			'settings'      => 'ptssbox_header_bg_color',
			'section'       => 'ptssbox_customizer_settings',
			'label'         => __( 'Box header background color', 'social-subscribe-box' ),
		) ) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ptssbox_box_header_color_2', array(
			'settings'      => 'ptssbox_header_text_color',
			'section'       => 'ptssbox_customizer_settings',
			'label'         => __( 'Box header text color', 'social-subscribe-box' ),
		) ) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ptssbox_box_header_color_3', array(
			'settings'      => 'ptssbox_subscribe_btn_bg_color',
			'section'       => 'ptssbox_customizer_settings',
			'label'         => __( 'Subscribe button background color', 'social-subscribe-box' ),
		) ) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ptssbox_box_header_color_4', array(
			'settings'      => 'ptssbox_subscribe_btn_hover_color',
			'section'       => 'ptssbox_customizer_settings',
			'label'         => __( 'Subscribe button hover background color', 'social-subscribe-box' ),
		) ) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ptssbox_box_header_color_5', array(
			'settings'      => 'ptssbox_subscribe_btn_text_color',
			'section'       => 'ptssbox_customizer_settings',
			'label'         => __( 'Subscribe button text color', 'social-subscribe-box' ),
		) ) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ptssbox_box_header_color_5', array(
			'settings'      => 'ptssbox_subscribe_btn_hover_text_color',
			'section'       => 'ptssbox_customizer_settings',
			'label'         => __( 'Subscribe button Hover text color', 'social-subscribe-box' ),
		) ) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ptssbox_box_footer_color_1', array(
			'settings'      => 'ptssbox_footer_bg_color',
			'section'       => 'ptssbox_customizer_settings',
			'label'         => __( 'Box footer background color', 'social-subscribe-box' ),
		) ) );

		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'ptssbox_box_footer_color_2', array(
			'settings'      => 'ptssbox_footer_text_color',
			'section'       => 'ptssbox_customizer_settings',
			'label'         => __( 'Box footer text color', 'social-subscribe-box' ),
		) ) );
	}

	/**
	 * Apply customizer settings
	 */
	public function apply_theme_customizer_settings() {
		?>
		<style type="text/css">
			.ui-slideouttab-handle {
				background-color: <?php echo get_theme_mod( 'ptssbox_tab_bg_color', '#808080' ); ?>;
				color: <?php echo get_theme_mod( 'ptssbox_tab_text_color', '#fff' ); ?>;
			}
			.pt-social-subscribe-box-header {
				background-color: <?php echo get_theme_mod( 'ptssbox_header_bg_color', '#111' ); ?>;
			}
			.pt-social-subscribe-box-header{
				color: <?php echo get_theme_mod( 'ptssbox_header_text_color', '#fff' ); ?>;
			}
			.pt-social-subscribe-box-submit-btn {
				background-color: <?php echo get_theme_mod( 'ptssbox_subscribe_btn_bg_color', '#E5D416' ); ?>;
				color: <?php echo get_theme_mod( 'ptssbox_subscribe_btn_text_color', '#000' ); ?>;
			}
			.pt-social-subscribe-box-submit-btn:hover {
				background-color: <?php echo get_theme_mod( 'ptssbox_subscribe_btn_hover_color', '#E5D416' ); ?>;
				color: <?php echo get_theme_mod( 'ptssbox_subscribe_btn_hover_text_color', '#000' ); ?>;
			}
			.pt-social-subscribe-box-feedback-message {
				color: <?php echo get_theme_mod( 'ptssbox_header_text_color', '#fffeff' ); ?>;
			}
			.pt-social-subscribe-box-footer {
				background: <?php echo get_theme_mod( 'ptssbox_footer_bg_color', '#fff' ); ?>;
			}
			.pt-social-subscribe-box-follow-label {
				color: <?php echo get_theme_mod( 'ptssbox_footer_text_color', '#333' ); ?>;
			}
		</style>
		<?php
	}
}

PT_Social_Subscribe_Box_Customizer::get_instance();
