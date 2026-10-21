<?php
/**
 * Plugin Name: Social Subscribe Box
 * Plugin URI: https://buddydev.com/plugins/social-subscribe-box/
 * Author Name: BuddyDev
 * Author URI: https://buddydev.com
 * Version: 1.0.0
 * Description: Easy MailChimp subscription with social follow buttons for Facebook, Twitter, LinkedIn and .
 * License: GPL2 or above
 * Domain Path: /languages
 * Text Domain: social-subscribe-box
 */

// Exit if file access directly over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use DrewM\MailChimp\MailChimp;

/**
 * PT_Social_Subscribe_Box
 */
class PT_Social_Subscribe_Box {

	/**
	 * Singleton instance.
	 *
	 * @var PT_Social_Subscribe_Box
	 */
	private static $instance = null;

	/**
	 * Absolute plugin directory path
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Absolute url to plugin directory.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * The constructor.
	 */
	private function __construct() {

		$this->path = plugin_dir_path( __FILE__ );
		$this->url  = plugin_dir_url( __FILE__ );

		$this->setup();
	}

	/**
	 * Get singleton instance
	 *
	 * @return PT_Social_Subscribe_Box
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
     * Dynamical property
     *
	 * @param string $name props name.
	 *
	 * @return null|mixed
	 */
	public function __get( $name ) {
	    return isset( $this->{$name}) ? $this->{$name} : null;
	}

	/**
	 * Setup plugin functionality
	 */
	public function setup() {

		// Load plugins file.
		add_action( 'plugins_loaded', array( $this, 'load' ) );
		add_action( 'plugins_loaded', array( $this, 'load_admin' ), 9998 );// PT Settings 1.0.2

	    // handle subscription.
		add_action( 'wp_ajax_nopriv_ptssbox_subscribe', array( $this, 'subscribe' ) );
		add_action( 'wp_ajax_ptssbox_subscribe', array( $this, 'subscribe' ) );

		// load assets.
		add_action( 'wp_enqueue_scripts', array( $this, 'load_assets' ) );

		// Load box template.
		add_action( 'wp_footer', array( $this, 'inject_html' ) );
	}

	/**
	 * Load plugin files
	 */
	public function load() {

		$files = array(
			'social-subscribe-box-functions.php',
			'admin/class-social-subscribe-box-customizer.php',
		);

		if ( ! class_exists( 'MailChimp' ) ) {
			$files[] = 'vendors/mailchimp-api/src/MailChimp.php';
		}

		foreach ( $files as $file ) {
			require_once $this->path . $file;
		}
	}

	/**
	 * Load admin.
	 */
	public function load_admin() {
        $files = array();

        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			$files[] = 'admin/pt-settings/pt-settings-loader.php';
			$files[] = 'admin/class-social-subscribe-box-settings.php';
		}

		foreach ( $files as $file ) {
			require_once $this->path . $file;
		}
    }

	/**
	 * Load plugin assets
	 */
	public function load_assets() {
		wp_register_style( 'jquery-tab-slide-out', $this->url . 'vendors/jquery-tab-slide-out/jquery.tabSlideOut.css' );
		wp_register_style( 'pt-social-subscribe-box', $this->url . 'assets/css/social-subscribe-box.css', array( 'jquery-tab-slide-out' ) );

		wp_register_script( 'jquery-cookie', $this->url . 'vendors/jquery-cookie.js', array( 'jquery' ) );
		wp_register_script( 'jquery-tab-slide-out', $this->url . 'vendors/jquery-tab-slide-out/jquery.tabSlideOut.js', array( 'jquery' ) );

		$dep = array( 'jquery-tab-slide-out' );

		if ( ! wp_script_is( 'bp-jquery-cookie' ) ) {
			$dep[] = 'jquery-cookie';
		} else {
			$dep[] = 'bp-jquery-cookie';
		}

		wp_register_script( 'pt-social-subscribe-box', $this->url . 'assets/js/social-subscribe-box.js', $dep );

		wp_localize_script( 'pt-social-subscribe-box', 'PTSocailSubscribeBox', array(
			'ajax_url'         => admin_url( 'admin-ajax.php' ),
			'is_mobile'        => wp_is_mobile(),
			'tab_title_closed' => ptssbox_get_option( 'tab_title_closed' ),
			'tab_title_open'   => ptssbox_get_option( 'tab_title_open' ),
			'tab_location'     => ptssbox_get_option( 'tab_location' ),
			'tab_offset'       => ptssbox_get_option( 'tab_offset' ),
			'tab_offset_from'  => ptssbox_get_option( 'tab_offset_from' ),
		) );

		wp_enqueue_style( 'pt-social-subscribe-box' );

		wp_enqueue_script( 'pt-social-subscribe-box' );

	}

	/**
	 * Inject html for showing the popup.
	 */
	public function inject_html() {
        require_once $this->path . 'assets/social-subscribe-box-form.php';
	}

	/**
	 * Handle ajax subscription.
	 */
	public function subscribe() {

		check_ajax_referer( 'ptssbox_subscribe' );

		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

		if ( empty( $email ) || ! is_email( $email ) ) {
			wp_send_json_error( array(
				'message' => $this->get_feedback_message('email'),
			) );
		}

		$first_name = isset( $_POST['first_name'] ) ? esc_html( $_POST['first_name'] ): '';
		$last_name = isset( $_POST['last_name'] ) ? esc_html( $_POST['last_name'] ): '';

		if ( empty( $first_name ) ) {
			wp_send_json_error( array(
				'message' => $this->get_feedback_message('first_name'),
			) );
		}

		$api_key = ptssbox_get_option( 'api_key' );
		$list_id = ptssbox_get_option( 'list_id' );
		$status  = ptssbox_get_option( 'email_verification' ) ? 'pending' : 'subscribed';

		if ( empty( $api_key ) || empty( $list_id ) ) {
			wp_send_json_error( array(
				'message' =>$this->get_feedback_message('fail'),
			) );
        }

		// $status  = 'pending';// 'subscribed';
		$mail_chimp = new MailChimp( $api_key );

		$subscription_data = array(
			'email_address' => $email,
			'status'        => $status,
        );

		if ( $first_name || $last_name ) {
		   $subscription_data['merge_fields'] = array( 'FNAME'=>$first_name, 'LNAME'=>$last_name);
        }

		// 'merge_fields' => ['FNAME'=>'Brajesh', 'LNAME'=>'Singh'],
		$result = $mail_chimp->post( "lists/$list_id/members",  $subscription_data );

		if ( $result && isset($result['status']) && $result['status'] == 400 ) {
			wp_send_json_success( array(
				'message' =>$this->get_feedback_message('exists'),
			) );
        }


		if ( ! $mail_chimp->success() ) {
			wp_send_json_error( array(
				'message' =>$this->get_feedback_message('fail'),
			) );
		}

		wp_send_json_success( array(
			'message' => $this->get_feedback_message('success' ),
		) );
	}

	private function get_feedback_message( $type ) {

		$messages = array(
			'email'      => __( 'Someone is getting old :D', 'social-subscribe-box' ),
			'first_name' => __( 'Let us not be ashamed of our names!', 'social-subscribe-box' ),
			'exists'     => __( 'Thank you. You are already in our awesome list.', 'social-subscribe-box' ),
			'fail'       => __( 'Our server has gone on strike, please try again later!', 'social-subscribe-box' ),
			'success'    => __( 'Sweet! You are awesome!', 'social-subscribe-box' ),
		);

		return $messages[$type];
	}

	/**
     * Get the success image.
     *
	 * @return string
	 */
	public function get_success_image() {
		$images = array(
			'pt-success-1.png',
			'pt-success-2.png',
		);
		shuffle( $images );

		return $this->url . 'assets/icons/success/' . array_pop( $images );
	}

	/**
     * Get the funny image.
     *
	 * @return string
	 */
	public function get_poke_image() {
		$images = array(
			'pt-fun-1.png',
			'pt-fun-2.png',
			'pt-fun-3.png',
			'pt-fun-4.png',
			'pt-fun-5.png',
			'pt-fun-6.png',
			'pt-fun-7.png',
			'pt-fun-8.png',
			'pt-fun-9.png',
			'pt-fun-10.png',
			'pt-fun-11.png',
			'pt-fun-12.png',
		);
		shuffle( $images );

		return $this->url . 'assets/icons/fun/' . array_pop( $images );
	}

	/**
     * Get the error image.
     *
	 * @return string
	 */
	public function get_error_image() {
		$images = array(
			'pt-err-1.png',
			'pt-err-2.png',
			'pt-err-3.png',
			'pt-err-4.png',
			'pt-err-5.png',
		);
		shuffle( $images );

		return $this->url . 'assets/icons/error/' . array_pop( $images );
	}

	public function get_loader_image() {
	    return $this->url . 'assets/icons/loaders/loader.gif';
    }

	/**
	 * Update settings on activation.
	 */
	public function on_activation() {
		if ( ! get_option( 'ptssbox_settings' ) ) {
			require_once $this->path . 'social-subscribe-box-functions.php';

			update_option( 'ptssbox_settings', ptssbox_get_defaults() );
		}
	}
}

PT_Social_Subscribe_Box::get_instance();
