<?php
/**
 * Plugin core function file
 *
 * @package social-subscribe-box
 */

// Exit if file access directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Get all defaults
 *
 * @return array
 */
function ptssbox_get_defaults() {

	$defaults = array(
		'tab_title_closed' => __( 'Subscribe', 'social-subscribe-box' ),
		'tab_title_open'   => __( 'Close', 'social-subscribe-box' ),
		'tab_location'     => 'right',
		'tab_offset'       => 200,
		'tab_offset_from'  => 'bottom',

		'line_1' => __( 'Join Our', 'social-subscribe-box' ),
		'line_2' => __( 'Newsletter', 'social-subscribe-box' ),

		'api_key'             => '',
		'list_id'             => '',
		'force_lists_refresh' => 0,
		'email_verification'  => 1,

		'social_tagline'  => __( 'Follow Us <span>@yourtwitterhandle</span>', 'social-subscribe-box' ),
		'fb_url'          => '',
		'twitter_url'     => '',
		'google_plus_url' => '',
		'linkedin_url'    => '',
	);

	return $defaults;
}

/**
 * Get setting option value
 *
 * @param string $option_key Option key.
 *
 * @return string
 */
function ptssbox_get_option( $option_key = '' ) {

	$settings = get_option( 'ptssbox_settings', ptssbox_get_defaults() );

	if ( isset( $settings[ $option_key ] ) ) {
		return $settings[ $option_key ];
	}

	return '';
}
