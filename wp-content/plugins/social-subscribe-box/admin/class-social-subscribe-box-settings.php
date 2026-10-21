<?php
/**
 * Admin setting
 *
 * @package pt-save-for-later
 **/

// Exit. If file access file directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Namespace.
use \Press_Themes\PT_Settings\Page;
use \DrewM\MailChimp\MailChimp;

/**
 * Class PT_Social_Subscribe_Box_Admin_Settings
 */
class PT_Social_Subscribe_Box_Settings {

	/**
	 * Admin Menu slug
	 *
	 * @var string
	 */
	private $menu_slug;

	/**
	 * Used to keep a reference of the Page, It will be used in rendering the view.
	 *
	 * @var \Press_Themes\PT_Settings\Page
	 */
	private $page;

	/**
	 * The Constructor.
	 */
	public function __construct() {

		$this->menu_slug = 'pt-social-subscribe-box-settings';

		add_action( 'admin_init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
	}

	/**
	 * Add Menu
	 */
	public function add_menu() {

		add_options_page(
			_x( 'Social Subscribe Box', 'Admin settings page title', 'social-subscribe-box' ),
			_x( 'Social Subscribe Box', 'Admin settings menu label', 'social-subscribe-box' ),
			'manage_options',
			$this->menu_slug,
			array( $this, 'render' )
		);
	}

	/**
	 * Show/render the setting page
	 */
	public function render() {
		$this->page->render();
	}

	/**
	 * Is it the setting page?
	 *
	 * @return bool
	 */
	private function needs_loading() {

		global $pagenow;

		// We need to load on options.php otherwise settings won't be registered.
		if ( 'options.php' === $pagenow ) {
			return true;
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] === $this->menu_slug ) {
			return true;
		}

		return false;
	}

	/**
	 * Initialize the admin settings panel and fields
	 */
	public function init() {

		if ( ! $this->needs_loading() ) {
			return;
		}

		$page = new Page( 'ptssbox_settings', __( 'Social Subscribe Box by BuddyDev', 'social-subscribe-box' ) );

		$this->register_settings( $page );

		// Save page for future reference.
		$this->page = $page;

		do_action( 'ptssbox_admin_settings', $page );

		// allow enabling options.
		$page->init();
	}

	/**
	 * Register settings fields for plugin
	 *
	 * @param \Press_Themes\PT_Settings\Page $page object.
	 */
	public function register_settings( $page ) {
		$link = "https://buddydev.com/docs/social-subscribe-box/finding-mailchimp-api-key/";

		$defaults = ptssbox_get_defaults();
		// General settings tab.
		$panel_newsletter = $page->add_panel( 'settings', _x( 'Newsletter', 'Admin settings panel title', 'social-subscribe-box' ) );

		$section_mailchimp = $panel_newsletter->add_section( 'mailchimp-settings', _x( 'Newsletter Settings', 'Admin settings section title', 'social-subscribe-box' ) );

		try {
			$lists = $this->get_lists();
			if ( ! empty( $lists ) ) {
				array_unshift( $lists, __( 'Select list', 'social-subscribe-box' ) );
			}

		} catch ( Exception $e ) {
			$lists = array();
		}

		$section_mailchimp->add_field( array(
				'name'    => 'line_1',
				'label'   => __( "Newsletter box title line 1", 'social-subscribe-box' ),
				'type'    => 'text',
				'default' => $defaults['line_1'],
				'desc'    => __( 'Appears as the first line in the box', 'social-subscribe-box' ),
			)
		)->add_field( array(
				'name'    => 'line_2',
				'label'   => __( 'Newsletter box title line 2', 'social-subscribe-box' ),
				'type'    => 'text',
				'default' => $defaults['line_2'],
				'desc'    => __( 'Appears as the second line in the box', 'social-subscribe-box' ),
			)
		);
		$section_mailchimp->add_fields( array(
				array(
					'name'    => 'api_key',
					'label'   => _x( 'Enter MailChimp API Key', 'Admin settings', 'social-subscribe-box' ),
					'type'    => 'text',
					'default' => $defaults['api_key'],
					'desc'    => sprintf( __( 'Having trouble finding the key? Please see %s', 'social-subscribe-box' ), $link )
				),
				array(
					'name'    => 'list_id',
					'label'   => _x( 'Select List', 'Admin settings', 'social-subscribe-box' ),
					'type'    => 'select',
					'options' => $lists,
					'default' => $defaults['list_id'],
					'desc'    => __( 'After you add your MailChimp API key,  Please save settings to populate the list.', 'social-subscribe-box' ),
				),
				array(
					'name'    => 'force_lists_refresh',
					'label'   => _x( 'Force MailChimp lists refresh', 'Admin settings', 'social-subscribe-box' ),
					'type'    => 'checkbox',
					'default' => $defaults['force_lists_refresh'],
					'desc'    => __( 'Use it to re-fetch the lists from MailChimp', 'social-subscribe-box' )
				),
				array(
					'name'    => 'email_verification',
					'label'   => _x( 'Email Verification', 'Admin settings', 'social-subscribe-box' ),
					'type'    => 'checkbox',
					'default' => $defaults['email_verification'],
					'desc'    => _x( 'User will need to verify joining the list via email if it is enabled', 'social-subscribe-box' ),
				),
			)
		);

		$panel_social = $page->add_panel( 'ptssbox-social-panel', __( 'Social', 'social-subscribe-box' ), __( 'Add the social network links here.', 'social-subscribe-box' ) );


		$social_section = $panel_social->add_section( 'social-settings', __( 'Links', 'social-subscribe-box' ), __( 'Your social buttons settings', 'social-subscribe-box' ) );

		$social_section->add_fields( array(
			array(
				'name'    => 'social_tagline',
				'label'   => _x( 'Tagline for social section', 'Admin settings', 'social-subscribe-box' ),
				'type'    => 'rawtext',
				'default' => $defaults['social_tagline'],
				'desc'    => __( 'If specified, appear as the 1st line in social buttons box.', 'social-subscribe-box' ),
			),

			array(
				'name'    => 'fb_url',
				'label'   => _x( 'Facebook URL', 'Admin settings', 'social-subscribe-box' ),
				'type'    => 'text',
				'default' => $defaults['fb_url'],
				'desc'    => __( 'Link to your facebook page. e.g https://fb.me/TheBuddyDev', 'social-subscribe-box' ),
			),
			array(
				'name'    => 'twitter_url',
				'label'   => _x( 'Twitter URL', 'Admin settings', 'social-subscribe-box' ),
				'type'    => 'text',
				'default' => $defaults['twitter_url'],
				'desc'    => __( 'Link to your twitter profile. e.g https://twitter.com/BuddyDev', 'social-subscribe-box' ),
			),
			array(
				'name'    => 'google_plus_url',
				'label'   => _x( 'Google Plus URL', 'Admin settings', 'social-subscribe-box' ),
				'type'    => 'text',
				'default' => $defaults['google_plus_url'],
				'desc'    => __( 'Link to Google+ profile.', 'social-subscribe-box' ),
			),
			array(
				'name'    => 'linkedin_url',
				'label'   => _x( 'LinkedIn URL', 'Admin settings', 'social-subscribe-box' ),
				'type'    => 'text',
				'default' => $defaults['linkedin_url'],
				'desc'    => __( 'Link to your LinkedIn profile.', 'social-subscribe-box' ),
			),
		) );

		$panel_appearance = $page->add_panel( 'ptssbox-behaviour-panel', __( 'Appearance', 'social-subscribe-box' ), __( 'You can customize the behaviour and appearance here', 'social-subscribe-box' ) );
		$section_labels = $panel_appearance->add_section( 'ptssbox-behaviour-panel-box-section', __( 'Subscribe Box', 'social-subscribe-box' ), __( 'Customize behaviour/appearance.', 'social-subscribe-box' ) );

		$section_labels->add_field( array(
				'name'    => 'tab_title_closed',
				'label'   => __( 'Tab title(closed)', 'social-subscribe-box' ),
				'type'    => 'text',
				'default' => $defaults['tab_title_closed'],
				'desc'    => __( 'Tab title when the box is closed', 'social-subscribe-box' ),
			)
		)->add_field( array(
				'name'    => 'tab_title_open',
				'label'   => __( 'Tab title(open)', 'social-subscribe-box' ),
				'type'    => 'text',
				'default' => $defaults['tab_title_open'],
				'desc'    => __( 'Tab title when the box is open', 'social-subscribe-box' ),
			)
		)->add_field( array(
				'name'    => 'tab_location',
				'label'   => __( 'Tab location', 'social-subscribe-box' ),
				'type'    => 'select',
				'options' => array(
					'top'    => __( 'Top', 'social-subscribe-box' ),
					'right'  => __( 'Right', 'social-subscribe-box' ),
					'bottom' => __( 'Bottom', 'social-subscribe-box' ),
					'left'   => __( 'Left', 'social-subscribe-box' ),
				),
				'default' => $defaults['tab_location'],
				'desc'    => __( 'Where the Tab will be aligned to?', 'social-subscribe-box' ),
			)
		)->add_field( array(
				'name'    => 'tab_offset',
				'label'   => __( 'Tab Offset', 'social-subscribe-box' ),
				'type'    => 'text',
				'default' => $defaults['tab_offset'],
				'desc'    => __( 'Offset in Pixels', 'social-subscribe-box' ),
			)
		)->add_field( array(
				'name'    => 'tab_offset_from',
				'label'   => __( 'Tab Offset From', 'social-subscribe-box' ),
				'type'    => 'select',
				'options' => array(
					'top'    => __( 'Top', 'social-subscribe-box' ),
					'right'  => __( 'Right', 'social-subscribe-box' ),
					'bottom' => __( 'Bottom', 'social-subscribe-box' ),
					'left'   => __( 'Left', 'social-subscribe-box' ),
				),
				'default' => $defaults['tab_offset_from'],
				'desc'    => __( 'From where the offset should be used.', 'social-subscribe-box' ),
			)
		);

		$customize_section = $panel_appearance->add_section( 'ptssbox-behaviour-panel-customize', __( 'Appearance Customization', 'social-subscribe-box' ),
			sprintf( __( 'You can customize the color/background of the box in the customizer. <a href="%s">Customize now</a>.' ), admin_url( 'customize.php?autofocus[section]=ptssbox_customizer_settings' ) ) );

	}

	/**
	 * Get MailChimp lists
	 *
	 * @return array|mixed
	 *
	 * @throws Exception
	 */
	public function get_lists() {

		$lists = unserialize( get_transient( 'pt_social_subscribe_mailing_list' ) );

		$api_key = ptssbox_get_option( 'api_key' );

		if ( empty( $api_key ) ) {
			return array();
		}

		if ( empty( $lists ) || ! empty( $settings['force_lists_refresh'] ) ) {

			$lists = array();

			$api    = new MailChimp( $api_key );
			$retval = $api->get( 'lists' );

			if ( $api->getLastError() ) {
				$lists['false'] = __( 'Unable to load MailChimp lists, check your API Key.', 'social-subscribe-box' );
			} else {

				if ( 0 == $retval['total_items'] ) {
					$lists['false'] = __( 'You have not created any lists at MailChimp', 'social-subscribe-box' );

					return $lists;
				}

				foreach ( $retval['lists'] as $list ) {
					$lists[ $list['id'] ] = $list['name'];
				}

				set_transient( 'pt_social_subscribe_mailing_list', serialize( $lists ), 86400 );
			}
		}

		return $lists;
	}
}

// Initialize class.
new PT_Social_Subscribe_Box_Settings();
