<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'tmpcoder_is_finish_setup_dismissed' ) ) {
	/**
	 * Check whether finish setup checklist is dismissed.
	 *
	 * @return bool
	 */
	function tmpcoder_is_finish_setup_dismissed() {
		return 1 === (int) get_option( TMPCODER_PLUGIN_KEY . '_finish_setup_dismissed', 0 );
	}
}

if ( ! function_exists( 'tmpcoder_is_demo_import_complete' ) ) {
	/**
	 * Demo import finished (tmpcoder-plugin-import-end). Finish Setup UI gated on this.
	 *
	 * @return bool
	 */
	function tmpcoder_is_demo_import_complete() {
		return 'yes' === (string) get_option( 'tmpcoder_import_complete', '' );
	}
}

if ( ! function_exists( 'tmpcoder_add_finish_setup_menu' ) ) {
	/**
	 * Register Finish Setup top-level menu page.
	 *
	 * @return void
	 */
	function tmpcoder_add_finish_setup_menu() {
		if ( ! tmpcoder_is_demo_import_complete() ) {
			return;
		}

		$menu_title = __( 'Finish Setup', 'sastra-essential-addons-for-elementor' );
		$counts     = tmpcoder_get_finish_setup_progress_counts();
		$remaining  = isset( $counts['remaining'] ) ? (int) $counts['remaining'] : 0;
		if ( $remaining > 0 ) {
			$menu_title = sprintf(
				/* translators: %1$s is menu title, %2$d is remaining checklist count. */
				'%1$s <span class="awaiting-mod count-%2$d"><span class="pending-count">%2$d</span></span>',
				esc_html__( 'Finish Setup', 'sastra-essential-addons-for-elementor' ),
				$remaining
			);
		} else {
			$menu_title = sprintf(
				/* translators: %s is menu title. */
				'%s <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>',
				esc_html__( 'Finish Setup', 'sastra-essential-addons-for-elementor' )
			);
		}

		add_menu_page(
			__( 'Finish Setup', 'sastra-essential-addons-for-elementor' ),
			$menu_title,
			'manage_options',
			'tmpcoder-finish-setup',
			'tmpcoder_render_finish_setup_page',
			TMPCODER_ADDONS_ASSETS_URL . 'images/finish-setup-menu.svg',
			2
		);
	}
	add_action( 'admin_menu', 'tmpcoder_add_finish_setup_menu', 98 );
}

if ( ! function_exists( 'tmpcoder_maybe_hide_finish_setup_menu' ) ) {
	/**
	 * Hide finish setup menu after dismissal.
	 *
	 * @return void
	 */
	function tmpcoder_maybe_hide_finish_setup_menu() {
		if ( ! tmpcoder_is_finish_setup_dismissed() ) {
			return;
		}

		remove_menu_page( 'tmpcoder-finish-setup' );
	}
	add_action( 'admin_menu', 'tmpcoder_maybe_hide_finish_setup_menu', 999 );
}

if ( ! function_exists( 'tmpcoder_enqueue_finish_setup_assets' ) ) {
	/**
	 * Enqueue Finish Setup assets only on Finish Setup page.
	 *
	 * @return void
	 */
	function tmpcoder_enqueue_finish_setup_assets() {
		$current_screen = get_current_screen();
		if ( ! isset( $current_screen->base ) || ! in_array( $current_screen->base, array( 'toplevel_page_tmpcoder-finish-setup', 'spexo-addons_page_tmpcoder-finish-setup' ), true ) ) {
			return;
		}

		wp_enqueue_style(
			'tmpcoder-finish-setup-css',
			TMPCODER_PLUGIN_URI . 'inc/admin/finish-setup/assets/css/finish-setup.css',
			array( 'dashicons' ),
			TMPCODER_PLUGIN_VER
		);

		wp_enqueue_script(
			'tmpcoder-finish-setup-js',
			TMPCODER_PLUGIN_URI . 'inc/admin/finish-setup/assets/js/finish-setup.js',
			array( 'jquery' ),
			TMPCODER_PLUGIN_VER,
			true
		);

		wp_localize_script(
			'tmpcoder-finish-setup-js',
			'tmpcoderFinishSetupData',
			array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'dismiss_nonce' => wp_create_nonce( 'tmpcoder_finish_setup_dismiss' ),
				'toggle_nonce'  => wp_create_nonce( 'tmpcoder_finish_setup_toggle_item' ),
				'pending_nonce' => wp_create_nonce( 'tmpcoder_finish_setup_mark_pending_item' ),
				'dashboard_url' => admin_url(),
				'menu_title'    => esc_html__( 'Finish Setup', 'sastra-essential-addons-for-elementor' ),
			)
		);
	}
	add_action( 'admin_enqueue_scripts', 'tmpcoder_enqueue_finish_setup_assets' );
}

if ( ! function_exists( 'tmpcoder_finish_setup_dismiss' ) ) {
	/**
	 * Dismiss finish setup and persist preference.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_dismiss() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_finish_setup_dismiss' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'sastra-essential-addons-for-elementor' ),
				)
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Sorry, you are not allowed to do this action.', 'sastra-essential-addons-for-elementor' ),
				)
			);
		}

		update_option( TMPCODER_PLUGIN_KEY . '_finish_setup_dismissed', 1 );
		wp_send_json_success();
	}
	add_action( 'wp_ajax_tmpcoder_finish_setup_dismiss', 'tmpcoder_finish_setup_dismiss' );
}

if ( ! function_exists( 'tmpcoder_get_finish_setup_user_checked_map' ) ) {
	/**
	 * Get user checkbox state overrides for finish setup items.
	 *
	 * @return array<string,int>
	 */
	function tmpcoder_get_finish_setup_user_checked_map() {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$stored = get_user_meta( get_current_user_id(), TMPCODER_PLUGIN_KEY . '_finish_setup_checked_items', true );
		if ( ! is_array( $stored ) ) {
			return array();
		}

		$checked_map = array();
		foreach ( $stored as $item_id => $checked ) {
			$normalized_id = sanitize_key( (string) $item_id );
			if ( '' === $normalized_id ) {
				continue;
			}

			$checked_map[ $normalized_id ] = ! empty( $checked ) ? 1 : 0;
		}

		return $checked_map;
	}
}

if ( ! function_exists( 'tmpcoder_get_finish_setup_user_pending_map' ) ) {
	/**
	 * Get pending action flags for finish setup items (per user).
	 *
	 * @return array<string,int>
	 */
	function tmpcoder_get_finish_setup_user_pending_map() {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$stored = get_user_meta( get_current_user_id(), TMPCODER_PLUGIN_KEY . '_finish_setup_pending_items', true );
		if ( ! is_array( $stored ) ) {
			return array();
		}

		$pending_map = array();
		foreach ( $stored as $item_id => $pending ) {
			$normalized_id = sanitize_key( (string) $item_id );
			if ( '' === $normalized_id ) {
				continue;
			}

			$pending_map[ $normalized_id ] = ! empty( $pending ) ? 1 : 0;
		}

		return $pending_map;
	}
}

if ( ! function_exists( 'tmpcoder_get_finish_setup_imported_contact_form_meta_key' ) ) {
	/**
	 * Get per-user meta key for latest imported Contact Form 7 ids.
	 *
	 * @return string
	 */
	function tmpcoder_get_finish_setup_imported_contact_form_meta_key() {
		return TMPCODER_PLUGIN_KEY . '_finish_setup_imported_contact_form_ids';
	}
}

if ( ! function_exists( 'tmpcoder_get_finish_setup_imported_contact_form_ids' ) ) {
	/**
	 * Get latest imported Contact Form 7 ids for current user.
	 *
	 * @return int[]
	 */
	function tmpcoder_get_finish_setup_imported_contact_form_ids() {
		if ( ! is_user_logged_in() ) {
			return array();
		}

		$stored = get_user_meta( get_current_user_id(), tmpcoder_get_finish_setup_imported_contact_form_meta_key(), true );
		if ( ! is_array( $stored ) ) {
			return array();
		}

		$form_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', $stored )
				)
			)
		);

		return $form_ids;
	}
}

if ( ! function_exists( 'tmpcoder_set_finish_setup_imported_contact_form_ids' ) ) {
	/**
	 * Persist latest imported Contact Form 7 ids for current user.
	 *
	 * @param int[] $form_ids Contact form ids.
	 * @return void
	 */
	function tmpcoder_set_finish_setup_imported_contact_form_ids( $form_ids ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$form_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', (array) $form_ids )
				)
			)
		);

		if ( empty( $form_ids ) ) {
			delete_user_meta( get_current_user_id(), tmpcoder_get_finish_setup_imported_contact_form_meta_key() );
			return;
		}

		update_user_meta( get_current_user_id(), tmpcoder_get_finish_setup_imported_contact_form_meta_key(), $form_ids );
	}
}

if ( ! function_exists( 'tmpcoder_finish_setup_reset_imported_contact_form_ids_before_import' ) ) {
	/**
	 * Reset latest imported Contact Form 7 ids before a new XML import starts.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_reset_imported_contact_form_ids_before_import() {
		if ( ! wp_doing_ajax() || ! is_user_logged_in() || ! current_user_can( 'customize' ) ) {
			return;
		}

		$nonce = isset( $_REQUEST['_ajax_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_ajax_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'spexo-addons' ) ) {
			return;
		}

		tmpcoder_set_finish_setup_imported_contact_form_ids( array() );
	}
	add_action( 'tmpcoder_before_import_start', 'tmpcoder_finish_setup_reset_imported_contact_form_ids_before_import', 1 );
	add_action( 'wp_ajax_tmpcoder-plugin-import-prepare-xml', 'tmpcoder_finish_setup_reset_imported_contact_form_ids_before_import', 1 );
}

if ( ! function_exists( 'tmpcoder_finish_setup_store_imported_contact_form_id' ) ) {
	/**
	 * Add one imported Contact Form 7 id to current user's latest import set.
	 *
	 * @param int $form_id Contact form id.
	 * @return void
	 */
	function tmpcoder_finish_setup_store_imported_contact_form_id( $form_id ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$form_id = absint( $form_id );
		if ( $form_id <= 0 ) {
			return;
		}

		$form_ids   = tmpcoder_get_finish_setup_imported_contact_form_ids();
		$form_ids[] = $form_id;

		tmpcoder_set_finish_setup_imported_contact_form_ids( $form_ids );
	}
}

if ( ! function_exists( 'tmpcoder_finish_setup_handle_imported_contact_form_post' ) ) {
	/**
	 * Capture Contact Form 7 ids created during demo import.
	 *
	 * @param int   $post_id Imported post id.
	 * @param array $data    Raw imported post data.
	 * @return void
	 */
	function tmpcoder_finish_setup_handle_imported_contact_form_post( $post_id, $data ) {
		$post_type = isset( $data['post_type'] ) ? sanitize_key( (string) $data['post_type'] ) : '';
		if ( 'wpcf7_contact_form' !== $post_type ) {
			return;
		}

		tmpcoder_finish_setup_store_imported_contact_form_id( $post_id );
	}
	add_action( 'tmpcoder_importer.processed.post', 'tmpcoder_finish_setup_handle_imported_contact_form_post', 20, 2 );
}

if ( ! function_exists( 'tmpcoder_finish_setup_find_existing_contact_form_id_from_import_data' ) ) {
	/**
	 * Resolve already-imported Contact Form 7 post id from importer data.
	 *
	 * @param array $data Raw imported post data.
	 * @return int
	 */
	function tmpcoder_finish_setup_find_existing_contact_form_id_from_import_data( $data ) {
		global $wpdb;

		$guid = isset( $data['guid'] ) ? esc_url_raw( (string) $data['guid'] ) : '';
		if ( '' === $guid ) {
			return 0;
		}

		$post_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND guid = %s LIMIT 1",
				'wpcf7_contact_form',
				$guid
			)
		);

		return $post_id > 0 ? $post_id : 0;
	}
}

if ( ! function_exists( 'tmpcoder_finish_setup_handle_already_imported_contact_form_post' ) ) {
	/**
	 * Capture Contact Form 7 ids when importer skips forms that already exist.
	 *
	 * @param array $data Raw imported post data.
	 * @return void
	 */
	function tmpcoder_finish_setup_handle_already_imported_contact_form_post( $data ) {
		$post_type = isset( $data['post_type'] ) ? sanitize_key( (string) $data['post_type'] ) : '';
		if ( 'wpcf7_contact_form' !== $post_type ) {
			return;
		}

		$post_id = tmpcoder_finish_setup_find_existing_contact_form_id_from_import_data( $data );
		if ( $post_id > 0 ) {
			tmpcoder_finish_setup_store_imported_contact_form_id( $post_id );
		}
	}
	add_action( 'tmpcoder_importer.process_already_imported.post', 'tmpcoder_finish_setup_handle_already_imported_contact_form_post', 20, 1 );
}

if ( ! function_exists( 'tmpcoder_get_finish_setup_contact_form_review_url' ) ) {
	/**
	 * Build Contact Form 7 review URL, including imported form highlight ids when available.
	 *
	 * @return string
	 */
	function tmpcoder_get_finish_setup_contact_form_review_url() {
		$base_url = admin_url( 'admin.php?page=wpcf7' );
		$form_ids = tmpcoder_get_finish_setup_imported_contact_form_ids();

		if ( empty( $form_ids ) ) {
			return $base_url;
		}

		return add_query_arg(
			'tmpcoder_highlight_forms',
			implode( ',', $form_ids ),
			$base_url
		);
	}
}

if ( ! function_exists( 'tmpcoder_get_finish_setup_requested_contact_form_ids' ) ) {
	/**
	 * Get requested Contact Form 7 ids to highlight on admin list page.
	 *
	 * @return int[]
	 */
	function tmpcoder_get_finish_setup_requested_contact_form_ids() {
		$raw_ids = isset( $_GET['tmpcoder_highlight_forms'] ) ? sanitize_text_field( wp_unslash( $_GET['tmpcoder_highlight_forms'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $raw_ids ) {
			return array();
		}

		$form_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', explode( ',', $raw_ids ) )
				)
			)
		);

		return $form_ids;
	}
}

if ( ! function_exists( 'tmpcoder_finish_setup_get_action_tracking_item_ids' ) ) {
	/**
	 * Get item IDs that require click->save flow to auto-complete.
	 *
	 * @return string[]
	 */
	function tmpcoder_finish_setup_get_action_tracking_item_ids() {
		return array(
			'admin_email',
			'style_guide',
			'customize_header',
			'customize_footer',
			'review_contact_form',
			'woo_create_product',
			'woo_store_checkout',
			'woo_setup_payment',
			'woo_configure_shipping',
		);
	}
}

if ( ! function_exists( 'tmpcoder_finish_setup_capture_pending_item_from_query' ) ) {
	/**
	 * Capture pending checklist item from URL query parameter.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_capture_pending_item_from_query() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$item_id = isset( $_GET['tmpcoder_finish_item'] ) ? sanitize_key( wp_unslash( $_GET['tmpcoder_finish_item'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $item_id || ! in_array( $item_id, tmpcoder_finish_setup_get_action_tracking_item_ids(), true ) ) {
			return;
		}

		$pending_map            = tmpcoder_get_finish_setup_user_pending_map();
		$pending_map[ $item_id ] = 1;
		update_user_meta( get_current_user_id(), TMPCODER_PLUGIN_KEY . '_finish_setup_pending_items', $pending_map );
	}
	add_action( 'admin_init', 'tmpcoder_finish_setup_capture_pending_item_from_query', 5 );
}

if ( ! function_exists( 'tmpcoder_finish_setup_mark_item_checked' ) ) {
	/**
	 * Mark one finish setup item as checked and clear its pending flag.
	 *
	 * @param string $item_id Item id.
	 * @return void
	 */
	function tmpcoder_finish_setup_mark_item_checked( $item_id ) {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$item_id = sanitize_key( (string) $item_id );
		if ( '' === $item_id ) {
			return;
		}

		$checked_map            = tmpcoder_get_finish_setup_user_checked_map();
		$checked_map[ $item_id ] = 1;
		update_user_meta( get_current_user_id(), TMPCODER_PLUGIN_KEY . '_finish_setup_checked_items', $checked_map );

		$pending_map = tmpcoder_get_finish_setup_user_pending_map();
		if ( array_key_exists( $item_id, $pending_map ) ) {
			unset( $pending_map[ $item_id ] );
			update_user_meta( get_current_user_id(), TMPCODER_PLUGIN_KEY . '_finish_setup_pending_items', $pending_map );
		}
	}
}

if ( ! function_exists( 'tmpcoder_finish_setup_mark_pending_item' ) ) {
	/**
	 * Mark item as pending after user clicks Setup/Review action button.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_mark_pending_item() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_finish_setup_mark_pending_item' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'sastra-essential-addons-for-elementor' ),
				)
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Sorry, you are not allowed to do this action.', 'sastra-essential-addons-for-elementor' ),
				)
			);
		}

		$item_id = isset( $_POST['item_id'] ) ? sanitize_key( wp_unslash( $_POST['item_id'] ) ) : '';
		if ( '' === $item_id || ! in_array( $item_id, tmpcoder_finish_setup_get_action_tracking_item_ids(), true ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid checklist item.', 'sastra-essential-addons-for-elementor' ),
				)
			);
		}

		$pending_map            = tmpcoder_get_finish_setup_user_pending_map();
		$pending_map[ $item_id ] = 1;
		update_user_meta( get_current_user_id(), TMPCODER_PLUGIN_KEY . '_finish_setup_pending_items', $pending_map );

		wp_send_json_success();
	}
	add_action( 'wp_ajax_tmpcoder_finish_setup_mark_pending_item', 'tmpcoder_finish_setup_mark_pending_item' );
}

if ( ! function_exists( 'tmpcoder_finish_setup_handle_settings_save_completion' ) ) {
	/**
	 * Mark tracked items completed when related settings form is saved.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_handle_settings_save_completion() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$pending_map = tmpcoder_get_finish_setup_user_pending_map();
		if ( empty( $pending_map ) ) {
			return;
		}

		$option_page = isset( $_POST['option_page'] ) ? sanitize_key( wp_unslash( $_POST['option_page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$action      = isset( $_POST['action'] ) ? sanitize_key( wp_unslash( $_POST['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( 'update' !== $action ) {
			return;
		}

		if ( 'general' === $option_page && ! empty( $pending_map['admin_email'] ) ) {
			tmpcoder_finish_setup_mark_item_checked( 'admin_email' );
		}

		$redux_posted = defined( 'TMPCODER_THEME_OPTION_NAME' ) && isset( $_POST[ TMPCODER_THEME_OPTION_NAME ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if (
			! empty( $pending_map['style_guide'] )
			&& (
				( defined( 'TMPCODER_THEME_OPTION_NAME' ) && sanitize_key( TMPCODER_THEME_OPTION_NAME ) === $option_page )
				|| $redux_posted
			)
		) {
			tmpcoder_finish_setup_mark_item_checked( 'style_guide' );
		}
	}
	add_action( 'admin_init', 'tmpcoder_finish_setup_handle_settings_save_completion' );
}

if ( ! function_exists( 'tmpcoder_finish_setup_handle_redux_save_completion' ) ) {
	/**
	 * Mark style guide complete when Redux options are saved.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_handle_redux_save_completion() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$pending_map = tmpcoder_get_finish_setup_user_pending_map();
		if ( ! empty( $pending_map['style_guide'] ) ) {
			tmpcoder_finish_setup_mark_item_checked( 'style_guide' );
		}
	}

	if ( defined( 'TMPCODER_THEME_OPTION_NAME' ) ) {
		add_action( 'redux/options/' . TMPCODER_THEME_OPTION_NAME . '/saved', 'tmpcoder_finish_setup_handle_redux_save_completion' );
	}
}

if ( ! function_exists( 'tmpcoder_finish_setup_handle_template_save_completion' ) ) {
	/**
	 * Mark header/footer item checked when Site Builder template is created/updated.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	function tmpcoder_finish_setup_handle_template_save_completion( $post_id ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! defined( 'TMPCODER_THEME_ADVANCED_HOOKS_POST_TYPE' ) || get_post_type( $post_id ) !== TMPCODER_THEME_ADVANCED_HOOKS_POST_TYPE ) {
			return;
		}

		$pending_map = tmpcoder_get_finish_setup_user_pending_map();
		if ( empty( $pending_map ) ) {
			return;
		}

		$template_type = sanitize_key( (string) get_post_meta( $post_id, 'tmpcoder_template_type', true ) );
		if ( 'type_header' === $template_type && ! empty( $pending_map['customize_header'] ) ) {
			tmpcoder_finish_setup_mark_item_checked( 'customize_header' );
		}

		if ( 'type_footer' === $template_type && ! empty( $pending_map['customize_footer'] ) ) {
			tmpcoder_finish_setup_mark_item_checked( 'customize_footer' );
		}
	}
	add_action( 'save_post', 'tmpcoder_finish_setup_handle_template_save_completion' );
}

if ( ! function_exists( 'tmpcoder_finish_setup_handle_contact_form_save_completion' ) ) {
	/**
	 * Mark contact form item checked when contact form is saved.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_handle_contact_form_save_completion() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$pending_map = tmpcoder_get_finish_setup_user_pending_map();
		if ( ! empty( $pending_map['review_contact_form'] ) ) {
			tmpcoder_finish_setup_mark_item_checked( 'review_contact_form' );
		}
	}
	add_action( 'wpcf7_after_save', 'tmpcoder_finish_setup_handle_contact_form_save_completion' );
}

if ( ! function_exists( 'tmpcoder_finish_setup_maybe_render_contact_form_highlight_notice' ) ) {
	/**
	 * Show which imported Contact Form 7 forms should be reviewed.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_maybe_render_contact_form_highlight_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'wpcf7' !== $page ) {
			return;
		}

		$form_ids = tmpcoder_get_finish_setup_requested_contact_form_ids();
		if ( empty( $form_ids ) ) {
			return;
		}

		$links = array();
		foreach ( $form_ids as $form_id ) {
			$title = get_the_title( $form_id );
			if ( '' === $title ) {
				$title = sprintf(
					/* translators: %d is Contact Form 7 post id. */
					__( 'Form #%d', 'sastra-essential-addons-for-elementor' ),
					$form_id
				);
			}

			$links[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( 'admin.php?page=wpcf7&post=' . absint( $form_id ) . '&action=edit' ) ),
				esc_html( $title )
			);
		}

		if ( empty( $links ) ) {
			return;
		}
		?>
		<div class="notice notice-info">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						/* translators: %s is a comma-separated list of Contact Form 7 edit links. */
						__( 'These Contact Form 7 forms were imported with your latest demo import and are highlighted below: %s', 'sastra-essential-addons-for-elementor' ),
						implode( ', ', $links )
					)
				);
				?>
			</p>
		</div>
		<?php
	}
	add_action( 'admin_notices', 'tmpcoder_finish_setup_maybe_render_contact_form_highlight_notice' );
}

if ( ! function_exists( 'tmpcoder_finish_setup_maybe_enqueue_contact_form_highlight_assets' ) ) {
	/**
	 * Highlight imported Contact Form 7 rows on admin list page.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_maybe_enqueue_contact_form_highlight_assets() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'wpcf7' !== $page ) {
			return;
		}

		$form_ids = tmpcoder_get_finish_setup_requested_contact_form_ids();
		if ( empty( $form_ids ) ) {
			return;
		}

		wp_register_style( 'tmpcoder-finish-setup-contact-form-highlight', false, array(), TMPCODER_PLUGIN_VER );
		wp_enqueue_style( 'tmpcoder-finish-setup-contact-form-highlight' );
		wp_add_inline_style(
			'tmpcoder-finish-setup-contact-form-highlight',
			'#wpcf7-contact-form-list-table tr.tmpcoder-finish-setup-highlight{background:#f5efff !important;box-shadow:inset 4px 0 0 #5729d9;}#wpcf7-contact-form-list-table tr.tmpcoder-finish-setup-highlight td{background:transparent !important;}#wpcf7-contact-form-list-table tr.tmpcoder-finish-setup-highlight .row-title{color:#5729d9;font-weight:600;}'
		);

		wp_register_script( 'tmpcoder-finish-setup-contact-form-highlight', false, array( 'jquery' ), TMPCODER_PLUGIN_VER, true );
		wp_enqueue_script( 'tmpcoder-finish-setup-contact-form-highlight' );
		wp_add_inline_script(
			'tmpcoder-finish-setup-contact-form-highlight',
			'(function($){var ids=' . wp_json_encode( array_values( $form_ids ) ) . ';$(function(){var firstRow=null;ids.forEach(function(id){var $row=$("#wpcf7-contact-form-list-table a.row-title").filter(function(){var href=$(this).attr("href")||"";return href.indexOf("post="+id)!==-1;}).first().closest("tr");if($row.length){$row.addClass("tmpcoder-finish-setup-highlight");if(!firstRow){firstRow=$row;}}});if(firstRow&&firstRow.length&&firstRow.get(0)&&typeof firstRow.get(0).scrollIntoView==="function"){firstRow.get(0).scrollIntoView({behavior:"smooth",block:"center"});}});})(jQuery);'
		);
	}
	add_action( 'admin_enqueue_scripts', 'tmpcoder_finish_setup_maybe_enqueue_contact_form_highlight_assets' );
}

if ( ! function_exists( 'tmpcoder_finish_setup_handle_woo_product_save_completion' ) ) {
	/**
	 * Mark Woo product setup item checked when product is created/updated.
	 *
	 * @param int $post_id Product post id.
	 * @return void
	 */
	function tmpcoder_finish_setup_handle_woo_product_save_completion( $post_id ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$pending_map = tmpcoder_get_finish_setup_user_pending_map();
		if ( ! empty( $pending_map['woo_create_product'] ) ) {
			tmpcoder_finish_setup_mark_item_checked( 'woo_create_product' );
		}
	}
	add_action( 'save_post_product', 'tmpcoder_finish_setup_handle_woo_product_save_completion' );
}

if ( ! function_exists( 'tmpcoder_finish_setup_mark_woo_settings_complete' ) ) {
	/**
	 * Mark Woo settings checklist item checked and clear pending.
	 *
	 * @param string $item_id Checklist item ID.
	 * @return void
	 */
	function tmpcoder_finish_setup_mark_woo_settings_complete( $item_id ) {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$item_id = sanitize_key( (string) $item_id );
		if ( '' === $item_id ) {
			return;
		}

		$pending_map = tmpcoder_get_finish_setup_user_pending_map();
		if ( ! empty( $pending_map[ $item_id ] ) ) {
			tmpcoder_finish_setup_mark_item_checked( $item_id );
		}
	}
}

if ( ! function_exists( 'tmpcoder_finish_setup_handle_woo_checkout_settings_save' ) ) {
	/**
	 * Mark store checkout setup complete when WooCommerce advanced settings are saved.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_handle_woo_checkout_settings_save() {
		tmpcoder_finish_setup_mark_woo_settings_complete( 'woo_store_checkout' );
	}
	add_action( 'woocommerce_settings_save_advanced', 'tmpcoder_finish_setup_handle_woo_checkout_settings_save' );
}

if ( ! function_exists( 'tmpcoder_finish_setup_handle_woo_payment_gateways_save' ) ) {
	/**
	 * Mark payment setup complete when WooCommerce payment gateway settings are saved.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_handle_woo_payment_gateways_save() {
		tmpcoder_finish_setup_mark_woo_settings_complete( 'woo_setup_payment' );
	}
	add_action( 'woocommerce_update_options_checkout', 'tmpcoder_finish_setup_handle_woo_payment_gateways_save' );
}

if ( ! function_exists( 'tmpcoder_finish_setup_handle_woo_shipping_settings_save' ) ) {
	/**
	 * Mark shipping setup complete when WooCommerce shipping settings are saved.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_handle_woo_shipping_settings_save() {
		tmpcoder_finish_setup_mark_woo_settings_complete( 'woo_configure_shipping' );
	}
	add_action( 'woocommerce_settings_save_shipping', 'tmpcoder_finish_setup_handle_woo_shipping_settings_save' );
}

if ( ! function_exists( 'tmpcoder_finish_setup_handle_wc_settings_save_by_tab' ) ) {
	/**
	 * Fallback completion detector for WooCommerce settings save by active tab.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_handle_wc_settings_save_by_tab() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$page   = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_POST['save'] ) ? sanitize_text_field( wp_unslash( $_POST['save'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( 'wc-settings' !== $page || '' === $action ) {
			return;
		}

		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'advanced' === $tab ) {
			tmpcoder_finish_setup_mark_woo_settings_complete( 'woo_store_checkout' );
		} elseif ( 'checkout' === $tab ) {
			tmpcoder_finish_setup_mark_woo_settings_complete( 'woo_setup_payment' );
		} elseif ( 'shipping' === $tab ) {
			tmpcoder_finish_setup_mark_woo_settings_complete( 'woo_configure_shipping' );
		}
	}
	add_action( 'admin_init', 'tmpcoder_finish_setup_handle_wc_settings_save_by_tab', 25 );
}

if ( ! function_exists( 'tmpcoder_finish_setup_toggle_item' ) ) {
	/**
	 * Persist manual checked/unchecked item state for finish setup checklist.
	 *
	 * @return void
	 */
	function tmpcoder_finish_setup_toggle_item() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_finish_setup_toggle_item' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Security check failed.', 'sastra-essential-addons-for-elementor' ),
				)
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Sorry, you are not allowed to do this action.', 'sastra-essential-addons-for-elementor' ),
				)
			);
		}

		$item_id = isset( $_POST['item_id'] ) ? sanitize_key( wp_unslash( $_POST['item_id'] ) ) : '';
		if ( '' === $item_id ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid checklist item.', 'sastra-essential-addons-for-elementor' ),
				)
			);
		}

		$checked_state = isset( $_POST['checked'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['checked'] ) ) ? 1 : 0;
		$checked_map   = tmpcoder_get_finish_setup_user_checked_map();
		$checked_map[ $item_id ] = $checked_state;

		update_user_meta( get_current_user_id(), TMPCODER_PLUGIN_KEY . '_finish_setup_checked_items', $checked_map );
		$counts = tmpcoder_get_finish_setup_progress_counts();

		wp_send_json_success(
			array(
				'item_id'    => $item_id,
				'is_checked' => $checked_state,
				'counts'     => $counts,
				'remaining'  => isset( $counts['remaining'] ) ? (int) $counts['remaining'] : 0,
			)
		);
	}
	add_action( 'wp_ajax_tmpcoder_finish_setup_toggle_item', 'tmpcoder_finish_setup_toggle_item' );
}

if ( ! function_exists( 'tmpcoder_get_finish_setup_progress_counts' ) ) {
	/**
	 * Calculate finish setup progress counts.
	 *
	 * @return array<string,int>
	 */
	function tmpcoder_get_finish_setup_progress_counts() {
		$completed = 0;
		$total     = 0;
		$sections  = tmpcoder_get_finish_setup_sections();
		foreach ( $sections as $section ) {
			if ( empty( $section['items'] ) || ! is_array( $section['items'] ) ) {
				continue;
			}

			foreach ( $section['items'] as $item ) {
				$total++;
				if ( ! empty( $item['is_checked'] ) ) {
					$completed++;
				}
			}
		}

		return array(
			'completed' => $completed,
			'total'     => $total,
			'remaining' => max( 0, $total - $completed ),
		);
	}
}

if ( ! function_exists( 'tmpcoder_get_finish_setup_sections' ) ) {
	/**
	 * Check whether a published user-created Site Builder template exists for given type.
	 *
	 * Uses the same `user-` slug convention as Site Builder listing logic.
	 *
	 * @param string $template_type Template type slug.
	 * @return bool
	 */
	function tmpcoder_finish_setup_has_user_template_type( $template_type ) {
		$template_type = sanitize_key( (string) $template_type );
		if ( '' === $template_type || ! defined( 'TMPCODER_THEME_ADVANCED_HOOKS_POST_TYPE' ) ) {
			return false;
		}

		$template_ids = get_posts(
			array(
				'post_type'      => TMPCODER_THEME_ADVANCED_HOOKS_POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_key'       => 'tmpcoder_template_type',
				'meta_value'     => $template_type,
				'meta_compare'   => '=',
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $template_ids ) || ! is_array( $template_ids ) ) {
			return false;
		}

		foreach ( $template_ids as $template_id ) {
			$slug = get_post_field( 'post_name', (int) $template_id );
			if ( is_string( $slug ) && false !== strpos( $slug, 'user-' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build Global Options URL for a specific Redux section.
	 *
	 * @param string $section_id Redux section ID.
	 * @return string
	 */
	function tmpcoder_get_global_options_tab_url( $section_id = '' ) {
		$base_url = admin_url( 'admin.php?page=spexo_addons_global_settings' );
		if ( '' === $section_id ) {
			return $base_url;
		}

		if ( ! class_exists( 'ReduxFrameworkInstances' ) || ! defined( 'TMPCODER_THEME_OPTION_NAME' ) ) {
			return $base_url;
		}

		$redux_instance = ReduxFrameworkInstances::get_instance( TMPCODER_THEME_OPTION_NAME );
		if ( ! is_object( $redux_instance ) || ! isset( $redux_instance->sections ) || ! is_array( $redux_instance->sections ) ) {
			return $base_url;
		}

		foreach ( $redux_instance->sections as $tab_index => $section ) {
			if ( ! is_array( $section ) || ! isset( $section['id'] ) ) {
				continue;
			}

			if ( $section_id === (string) $section['id'] ) {
				return add_query_arg(
					array(
						'page' => 'spexo_addons_global_settings',
						'tab'  => (string) $tab_index,
					),
					admin_url( 'admin.php' )
				);
			}
		}

		return $base_url;
	}

	/**
	 * Build finish setup checklist sections.
	 *
	 * @return array
	 */
	function tmpcoder_get_finish_setup_sections() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$site_title_done        = '' !== trim( (string) get_option( 'blogname', '' ) );
		$permalink_done         = '' !== (string) get_option( 'permalink_structure', '' );
		$search_visibility_done = 1 === (int) get_option( 'blog_public', 1 );
		$logo_icon_done         = (bool) get_theme_mod( 'custom_logo' ) || (bool) get_option( 'site_icon' );

		$contact_form_done      = function_exists( 'is_plugin_active' ) && (
			is_plugin_active( 'contact-form-7/wp-contact-form-7.php' )
			|| is_plugin_active( 'fluentform/fluentform.php' )
			|| is_plugin_active( 'wpforms-lite/wpforms.php' )
		);

		$yoast_plugin_active    = function_exists( 'is_plugin_active' ) && is_plugin_active( 'wordpress-seo/wp-seo.php' );
		$woocommerce_active     = function_exists( 'is_plugin_active' ) && is_plugin_active( 'woocommerce/woocommerce.php' );
		$checked_map            = tmpcoder_get_finish_setup_user_checked_map();

		$sections = array(
			array(
				'title'       => __( 'WordPress Basics', 'sastra-essential-addons-for-elementor' ),
				'is_open'     => true,
				'play_video'  => false,
				'description' => __( "Get your site's foundation rock-solid so it looks professional and runs smoothly from day one.", 'sastra-essential-addons-for-elementor' ),
				'items'       => array(
					array(
						'id'         => 'site_title_tagline',
						'label'      => __( 'Set Site Title & Tagline', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Set Up', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'options-general.php#:~:text=Site%20Title,Tagline' ),
						'help_url'   => '',
						'done'       => $site_title_done,
					),
					array(
						'id'         => 'admin_email',
						'label'      => __( 'Review Admin Email', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Review', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'options-general.php#:~:text=Administration%20Email%20Address' ),
						'help_url'   => '',
						'done'       => false,
					),
					array(
						'id'         => 'permalink_structure',
						'label'      => __( 'Choose how your page links look', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Set Up', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'options-permalink.php' ),
						'help_url'   => '',
						'done'       => $permalink_done,
					),
					array(
						'id'         => 'search_engine_visibility',
						'label'      => __( 'Search Engine Visibility', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Review', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'options-reading.php#:~:text=Search%20engine%20visibility' ),
						'help_url'   => '',
						'done'       => $search_visibility_done,
					),
				),
			),
			array(
				'title'       => __( 'Design, Style & Theme', 'sastra-essential-addons-for-elementor' ),
				'is_open'     => false,
				'play_video'  => false,
				'description' => __( 'Create a memorable brand experience that looks professional and instantly recognizable.', 'sastra-essential-addons-for-elementor' ),
				'items'       => array(
					array(
						'id'         => 'style_guide',
						'label'      => __( 'Customize Global Style', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Set Up', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'admin.php?page=spexo_addons_global_settings' ),
						'help_url'   => esc_url( TMPCODER_DOCUMENTATION_URL . 'global-options-overview/' ),
						'done'       => false,
					),
					array(
						'id'         => 'logo_and_site_icon',
						'label'      => __( 'Add Logo and Site Icon', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Set Up', 'sastra-essential-addons-for-elementor' ),
						'action_url' => tmpcoder_get_global_options_tab_url( 'tmpcoder_header_options' ),
						'help_url'   => esc_url( TMPCODER_DOCUMENTATION_URL . 'header/' ),
						'done'       => $logo_icon_done,
					),
					array(
						'id'         => 'customize_header',
						'label'      => __( 'Customize Header', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Set Up', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'admin.php?page=spexo-welcome&tab=site-builder&layout_type=type_header' ),
						'help_url'   => esc_url( TMPCODER_DOCUMENTATION_URL . 'custom-header/' ),
						'done'       => false,
					),
					array(
						'id'         => 'customize_footer',
						'label'      => __( 'Customize Footer', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Set Up', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'admin.php?page=spexo-welcome&tab=site-builder&layout_type=type_footer' ),
						'help_url'   => esc_url( TMPCODER_DOCUMENTATION_URL . 'custom-footer/' ),
						'done'       => false,
					),
				),
			),
		);

		if ( $contact_form_done ) {
			$sections[] = array(
				'title'       => __( 'Contact Form', 'sastra-essential-addons-for-elementor' ),
				'is_open'     => false,
				'play_video'  => false,
				'description' => __( 'Make it effortless for visitors to reach out to you, turning curious browsers into real leads.', 'sastra-essential-addons-for-elementor' ),
				'items'       => array(
					array(
						'id'         => 'review_contact_form',
						'label'      => __( 'Review contact form', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Review', 'sastra-essential-addons-for-elementor' ),
						'action_url' => tmpcoder_get_finish_setup_contact_form_review_url(),
						'help_url'   => esc_url( TMPCODER_DOCUMENTATION_URL . 'form-styler/' ),
						'done'       => false,
					),
				),
			);
		}

		if ( $yoast_plugin_active ) {
			$sections[] = array(
				'title'       => __( 'Optimize Your Site', 'sastra-essential-addons-for-elementor' ),
				'is_open'     => false,
				'play_video'  => false,
				'description' => __( 'Make your pages accessible and optimized for users, AI bots and search engines.', 'sastra-essential-addons-for-elementor' ),
				'items'       => array(
					array(
						'id'         => 'review_site_optimization',
						'label'      => __( 'Review Site Optimization', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Review', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'admin.php?page=wpseo_dashboard' ),
						'help_url'   => '',
						'done'       => false,
					),
				),
			);
		}

		if ( $woocommerce_active ) {
			$sections[] = array(
				'title'       => __( 'Start Selling Online', 'sastra-essential-addons-for-elementor' ),
				'is_open'     => false,
				'play_video'  => false,
				'description' => __( 'Set up your products, checkout, payments, and shipping to start selling smoothly.', 'sastra-essential-addons-for-elementor' ),
				'items'       => array(
					array(
						'id'         => 'woo_create_product',
						'label'      => __( "Create the product you're selling", 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Set Up', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'post-new.php?post_type=product' ),
						'help_url'   => '',
						'done'       => false,
					),
					array(
						'id'         => 'woo_store_checkout',
						'label'      => __( 'Setup Store Checkout', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Set Up', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'admin.php?page=wc-settings&tab=advanced' ),
						'help_url'   => '',
						'done'       => false,
					),
					array(
						'id'         => 'woo_setup_payment',
						'label'      => __( 'Setup Payment', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Set Up', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'admin.php?page=wc-settings&tab=checkout' ),
						'help_url'   => '',
						'done'       => false,
					),
					array(
						'id'         => 'woo_configure_shipping',
						'label'      => __( 'Configure Shipping', 'sastra-essential-addons-for-elementor' ),
						'action'     => __( 'Set Up', 'sastra-essential-addons-for-elementor' ),
						'action_url' => admin_url( 'admin.php?page=wc-settings&tab=shipping' ),
						'help_url'   => '',
						'done'       => false,
					),
				),
			);
		}

		foreach ( $sections as $section_index => $section ) {
			if ( empty( $section['items'] ) || ! is_array( $section['items'] ) ) {
				continue;
			}

			foreach ( $section['items'] as $item_index => $item ) {
				$item_id      = isset( $item['id'] ) ? sanitize_key( (string) $item['id'] ) : '';
				$is_auto_done = ! empty( $item['done'] );
				$is_checked   = $is_auto_done;

				if ( ! $is_auto_done && '' !== $item_id && array_key_exists( $item_id, $checked_map ) ) {
					$is_checked = ! empty( $checked_map[ $item_id ] );
				}

				$sections[ $section_index ]['items'][ $item_index ]['id']         = $item_id;
				$sections[ $section_index ]['items'][ $item_index ]['is_checked'] = $is_checked;
			}
		}

		return $sections;
	}
}

if ( ! function_exists( 'tmpcoder_render_finish_setup_page' ) ) {
	/**
	 * Render Finish Setup checklist page.
	 *
	 * @return void
	 */
	function tmpcoder_render_finish_setup_page() {
		if ( tmpcoder_is_finish_setup_dismissed() ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		if ( ! tmpcoder_is_demo_import_complete() ) {
			wp_safe_redirect( admin_url() );
			exit;
		}

		$sections          = tmpcoder_get_finish_setup_sections();
		$progress_counts   = tmpcoder_get_finish_setup_progress_counts();
		$progress_total    = isset( $progress_counts['total'] ) ? (int) $progress_counts['total'] : 0;
		$progress_done     = isset( $progress_counts['completed'] ) ? (int) $progress_counts['completed'] : 0;
		$progress_percent  = $progress_total > 0 ? (int) round( ( $progress_done / $progress_total ) * 100 ) : 0;
		if ( defined( 'TMPCODER_PRO_ADDONS_ASSETS_URL' ) ) {
			$header_logo = TMPCODER_PRO_ADDONS_ASSETS_URL . 'images/spexo-logo-web-pro.svg';
		} else {
			$header_logo = TMPCODER_ADDONS_ASSETS_URL . 'images/spexo-logo-web.svg';
		}
		?>
		<div class="wrap tmpcoder-finish-setup-wrap">
			<div class="tmpcoder-finish-setup-header">
				<div class="tmpcoder-finish-setup-header__left">
					<div class="tmpcoder-finish-setup-header__logo">
						<img src="<?php echo esc_url( $header_logo ); ?>" alt="Spexo-logo">
					</div>
				</div>
				<a href="<?php echo esc_url( admin_url() ); ?>" class="tmpcoder-finish-setup-header__dismiss">
					<?php esc_html_e( 'Dismiss Setup', 'sastra-essential-addons-for-elementor' ); ?>
					<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
				</a>
			</div>
			<div class="tmpcoder-finish-setup-content">
				<div class="tmpcoder-finish-setup-header-sub">
					<h1><?php esc_html_e( 'Finish Setting Up Your Website', 'sastra-essential-addons-for-elementor' ); ?></h1>
					<p><?php esc_html_e( "Let's get your new website fully ready. Follow this quick checklist to customize, configure, and launch with confidence.", 'sastra-essential-addons-for-elementor' ); ?></p>
				</div>

				<div class="tmpcoder-finish-setup-main">
					
					<div class="tmpcoder-finish-setup-card">
						<h2 class="tmpcoder-finish-setup-card__title"><?php esc_html_e( 'Set Up The Basics', 'sastra-essential-addons-for-elementor' ); ?></h2>
						<div class="tmpcoder-finish-setup-progress" data-total="<?php echo esc_attr( $progress_total ); ?>">
							<div class="tmpcoder-finish-setup-progress__head">
								<span class="tmpcoder-finish-setup-progress__label"><?php esc_html_e( 'Overall Progress', 'sastra-essential-addons-for-elementor' ); ?></span>
								<div class="tmpcoder-finish-setup-progress__meta">
									<span class="tmpcoder-finish-setup-progress__percent"><?php echo esc_html( $progress_percent ); ?>%</span>
									<?php if ( $progress_total > 0 && $progress_done >= $progress_total ) : ?>
										<span class="tmpcoder-finish-setup-progress__ready"><?php esc_html_e( 'Completed Setup', 'sastra-essential-addons-for-elementor' ); ?></span>
									<?php endif; ?>
								</div>
							</div>
							<div class="tmpcoder-finish-setup-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $progress_percent ); ?>">
								<div class="tmpcoder-finish-setup-progress__fill" style="width: <?php echo esc_attr( $progress_percent ); ?>%;"></div>
							</div>
						</div>

						<?php foreach ( $sections as $section ) : ?>
							<?php
							$total_items = count( $section['items'] );
							$done_items  = count(
								array_filter(
									$section['items'],
									static function( $item ) {
										return ! empty( $item['is_checked'] );
									}
								)
							);
							?>
							<div class="tmpcoder-finish-setup-accordion <?php echo ! empty( $section['is_open'] ) ? 'is-open' : ''; ?>">
								<button type="button" class="tmpcoder-finish-setup-accordion__head" aria-expanded="<?php echo ! empty( $section['is_open'] ) ? 'true' : 'false'; ?>">
									<span class="tmpcoder-finish-setup-accordion__left">
										<span class="dashicons dashicons-arrow-right-alt2 tmpcoder-finish-setup-accordion__icon" aria-hidden="true"></span>
										<span class="tmpcoder-finish-setup-accordion__name"><?php echo esc_html( $section['title'] ); ?></span>
									</span>
									<span class="tmpcoder-finish-setup-accordion__right">
										<?php if ( ! empty( $section['play_video'] ) ) : ?>
											<span class="tmpcoder-finish-setup-accordion__video">
												<span class="dashicons dashicons-video-alt3" aria-hidden="true"></span>
												<?php esc_html_e( 'Play Video', 'sastra-essential-addons-for-elementor' ); ?>
											</span>
										<?php endif; ?>
										<span class="tmpcoder-finish-setup-accordion__count"><?php echo esc_html( $done_items . '/' . $total_items ); ?></span>
									</span>
								</button>

								<div class="tmpcoder-finish-setup-accordion__body">
									<p class="tmpcoder-finish-setup-accordion__desc"><?php echo esc_html( $section['description'] ); ?></p>

									<?php foreach ( $section['items'] as $item ) : ?>
										<div class="tmpcoder-finish-setup-item <?php echo ! empty( $item['is_checked'] ) ? 'is-checked' : ''; ?>" data-item-id="<?php echo esc_attr( $item['id'] ); ?>">
											<div class="tmpcoder-finish-setup-item__left">
												<?php $checkbox_id = 'tmpcoder-finish-setup-item-' . sanitize_html_class( (string) $item['id'] ); ?>
												<input type="checkbox" id="<?php echo esc_attr( $checkbox_id ); ?>" class="tmpcoder-finish-setup-item__checkbox" data-item-id="<?php echo esc_attr( $item['id'] ); ?>" <?php checked( ! empty( $item['is_checked'] ) ); ?> />
												<label class="tmpcoder-finish-setup-item__label" for="<?php echo esc_attr( $checkbox_id ); ?>"><?php echo esc_html( $item['label'] ); ?></label>
											</div>
											<div class="tmpcoder-finish-setup-item__right">
											<?php if ( ! empty( $item['help_url'] ) ) : ?>
												<a class="tmpcoder-finish-setup-item__help" href="<?php echo esc_url( $item['help_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn how', 'sastra-essential-addons-for-elementor' ); ?></a>
											<?php endif; ?>

												<?php
												$action_url_for_render = $item['action_url'];
												if ( in_array( $item['id'], tmpcoder_finish_setup_get_action_tracking_item_ids(), true ) ) {
													$action_url_for_render = add_query_arg( 'tmpcoder_finish_item', $item['id'], $action_url_for_render );
												}
												?>
												<a class="button tmpcoder-finish-setup-item__action" href="<?php echo esc_url( $action_url_for_render ); ?>" target="_blank" rel="noopener noreferrer">
													<?php echo esc_html( $item['action'] ); ?>
													<span class="dashicons dashicons-external" aria-hidden="true"></span>
												</a>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
