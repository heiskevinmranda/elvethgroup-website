<?php
/**
 * Dashboard notice for Finish Setup flow.
 *
 * @package Spexo_Addons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'TMPCODER_Finish_Setup_Dashboard_Notice' ) ) {
	/**
	 * Handles Finish Setup dashboard notice rendering and dismissal.
	 */
	class TMPCODER_Finish_Setup_Dashboard_Notice {
		/**
		 * Dismiss transient key prefix.
		 *
		 * @var string
		 */
		const DISMISS_TRANSIENT_KEY = 'tmpcoder_finish_setup_notice_dismissed_';

		/**
		 * AJAX action.
		 *
		 * @var string
		 */
		const AJAX_ACTION = 'tmpcoder_dismiss_finish_setup_notice';

		/**
		 * Nonce action.
		 *
		 * @var string
		 */
		const NONCE_ACTION = 'tmpcoder_finish_setup_notice_dismiss';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'admin_notices', array( $this, 'render_notice' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_dismiss_script' ) );
			add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'dismiss_notice' ) );
		}

		/**
		 * Render dashboard notice.
		 *
		 * @return void
		 */
		public function render_notice() {
			if ( ! $this->should_render_notice() ) {
				return;
			}

			$finish_setup_url = add_query_arg(
				array(
					'page'   => 'tmpcoder-finish-setup',
					'source' => 'dashboard-banner',
				),
				admin_url( 'admin.php' )
			);

			?>
			<div class="notice1 notice-info is-dismissible tmpcoder-finish-setup-dashboard-notice tmpcoder-upgrade-pro-notice tmpcoder-upgrade-pro-notice-dismissible tmpcoder-upgrade-pro-notice-extended tmpcoder-notice-banner">
			    <i class="tmpcoder-upgrade-pro-notice-dismiss finish-setup-dashboard-notice" role="button" aria-label="Dismiss this notice." tabindex="0"></i>
				<div class="tmpcoder-license-expiration-notice-aside">
					<div class="tmpcoder-license-expiration-notice-icon-wrapper">
						<img src="<?php echo esc_url( TMPCODER_ADDONS_ASSETS_URL . 'images/notice-rocket.svg' ); ?>" width="24" height="24" alt="">
					</div>
				</div>
				<div class="tmpcoder-license-expiration-notice-content">
					<h3><?php esc_html_e( 'Your Website Is Almost Ready!', 'sastra-essential-addons-for-elementor' ); ?></h3>
					<p><?php esc_html_e( 'You’re just a few steps away. Use this quick checklist to complete final settings, refine your site, and launch with confidence.', 'sastra-essential-addons-for-elementor' ); ?></p>
					<div class="tmpcoder-license-expiration-notice-actions">
						<a href="<?php echo esc_url( $finish_setup_url ); ?>" class="button button-primary tmpcoder-license-renew-button tmpcoder-upgrade-pro-button">
							<?php esc_html_e( 'Continue Finish Setup', 'sastra-essential-addons-for-elementor' ); ?>
						</a>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Enqueue inline JS for persistent dismissal.
		 *
		 * @return void
		 */
		public function enqueue_dismiss_script() {
			if ( ! $this->should_render_notice() ) {
				return;
			}

			$script_data = array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::AJAX_ACTION,
				'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
			);

			wp_add_inline_script(
				'jquery-core',
				'window.tmpcoderFinishSetupNoticeData = ' . wp_json_encode( $script_data ) . ';',
				'before'
			);

			wp_add_inline_script(
				'jquery-core',
				"(function($){
					$(document).on('click', '.tmpcoder-finish-setup-dashboard-notice .notice-dismiss', function(){
						var payload = window.tmpcoderFinishSetupNoticeData || {};
						if (!payload.ajaxUrl || !payload.action || !payload.nonce) {
							return;
						}

						$.post(payload.ajaxUrl, {
							action: payload.action,
							nonce: payload.nonce
						});
					});
				})(jQuery);",
				'after'
			);
		}

		/**
		 * Handle notice dismissal.
		 *
		 * @return void
		 */
		public function dismiss_notice() {
			check_ajax_referer( self::NONCE_ACTION, 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Unauthorized action.', 'sastra-essential-addons-for-elementor' ),
					)
				);
			}

			set_transient(
				$this->get_transient_key(),
				1,
				14 * DAY_IN_SECONDS
			);

			wp_send_json_success();
		}

		/**
		 * Check if notice should render.
		 *
		 * @return bool
		 */
		private function should_render_notice() {
			if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! $screen || 'dashboard' !== $screen->base ) {
				return false;
			}

			if ( get_transient( $this->get_transient_key() ) ) {
				return false;
			}

			if ( function_exists( 'tmpcoder_is_finish_setup_dismissed' ) && tmpcoder_is_finish_setup_dismissed() ) {
				return false;
			}

			if ( ! function_exists( 'tmpcoder_is_demo_import_complete' ) || ! tmpcoder_is_demo_import_complete() ) {
				return false;
			}

			return true;
		}

		/**
		 * Get user-specific transient key.
		 *
		 * @return string
		 */
		private function get_transient_key() {
			return self::DISMISS_TRANSIENT_KEY . get_current_user_id();
		}
	}
}

new TMPCODER_Finish_Setup_Dashboard_Notice();
