<?php 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

add_action("wp_ajax_tmpcoder_theme_install_func", "tmpcoder_theme_install_func");
add_action("wp_ajax_nopriv_tmpcoder_theme_install_func", "tmpcoder_theme_install_func");
function tmpcoder_theme_install_func(){

    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_install_theme' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Security check failed. Please refresh the page and try again.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    if ( ! is_user_logged_in() ) {
        wp_send_json_error(
            array(
                'message' => __( 'You must be logged in to run the setup wizard.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $theme_slug      = 'spexo';
    $current_theme   = ( is_object( wp_get_theme()->parent() ) ) ? wp_get_theme()->parent() : wp_get_theme();
    $theme_exists    = wp_get_theme( $theme_slug )->exists();
    $theme_active    = ( $current_theme->get_stylesheet() === $theme_slug );

    if ( $theme_active ) {
        update_option( TMPCODER_PLUGIN_KEY . '_wizard_step', '1' );
        wp_send_json_success(
            array(
                'message'      => __( 'Spexo theme is already active.', 'sastra-essential-addons-for-elementor' ),
                'theme_active' => true,
            )
        );
    }

    if ( $theme_exists ) {
        if ( ! current_user_can( 'switch_themes' ) ) {
            wp_send_json_error(
                array(
                    'message' => __( 'Sorry, you are not allowed to switch themes on this site.', 'sastra-essential-addons-for-elementor' ),
                )
            );
        }

        switch_theme( $theme_slug );
        flush_rewrite_rules();

        update_option( TMPCODER_PLUGIN_KEY . '_wizard_step', '1' );
        update_option( 'sastrawp_wizard_page', 1 );
        update_option( 'spexo_wizard_page', 1 );

        wp_send_json_success(
            array(
                'message'      => __( 'Recommended theme activated successfully.', 'sastra-essential-addons-for-elementor' ),
                'theme_active' => true,
            )
        );
    }

    if ( ! current_user_can( 'install_themes' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Sorry, you are not allowed to install themes on this site.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $theme_info = tmpcoder_get_theme_info( $theme_slug );

    if ( ! is_object( $theme_info ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Could not retrieve the Spexo theme. Check your connection and try again.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/theme.php';

    // Theme_Upgrader may echo progress HTML; buffer it so ajax returns pure JSON.
    $buffer_level = ob_get_level();
    ob_start();
    $upgrader = new Theme_Upgrader( new Automatic_Upgrader_Skin() );
    $result   = $upgrader->install( $theme_info->download_link );
    while ( ob_get_level() > $buffer_level ) {
        ob_end_clean();
    }

    if ( is_wp_error( $result ) ) {
        wp_send_json_error(
            array(
                'message' => $result->get_error_message(),
            )
        );
    }

    if ( ! $result ) {
        wp_send_json_error(
            array(
                'message' => __( 'Theme installation failed. Please try again.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    switch_theme( $theme_slug );
    flush_rewrite_rules();

    update_option( TMPCODER_PLUGIN_KEY . '_wizard_step', '1' );

    wp_send_json_success(
        array(
            'message'      => __( 'Recommended theme installed and activated successfully.', 'sastra-essential-addons-for-elementor' ),
            'theme_active' => true,
        )
    );
}

add_action( 'wp_ajax_tmpcoder_wizard_get_prebuilt_demos', 'tmpcoder_wizard_get_prebuilt_demos' );
/**
 * Return sanitized prebuilt website list for the plugin setup wizard (Phase 2).
 */
function tmpcoder_wizard_get_prebuilt_demos() {

    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_demos' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Security check failed. Please refresh the page and try again.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'You do not have permission to load demos.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    if ( ! class_exists( 'TMPCODER_Remote_Api' ) ) {
        $remote = TMPCODER_PLUGIN_DIR . 'inc/library/class-tmpcoder-plugin-remote-api.php';
        if ( file_exists( $remote ) ) {
            require_once $remote;
        }
    }

    if ( ! class_exists( 'TMPCODER_Remote_Api' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Could not load demo data.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $import_demos_resp = TMPCODER_Remote_Api::get_prebuilt_demos();

    if ( ! is_array( $import_demos_resp ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Could not load demos. Please try again later.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    if ( ! isset( $import_demos_resp['status'] ) || 'success' !== $import_demos_resp['status'] ) {
        $import_error_msg = isset( $import_demos_resp['message'] ) ? $import_demos_resp['message'] : __( 'Could not load demos.', 'sastra-essential-addons-for-elementor' );
        wp_send_json_error(
            array(
                'message' => sanitize_text_field( $import_error_msg ),
            )
        );
    }

    $import_demos = isset( $import_demos_resp['data'] ) ? $import_demos_resp['data'] : array();
    if ( ! is_array( $import_demos ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'No demos were returned.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $favorite_slugs = tmpcoder_wizard_get_user_favorite_demo_slugs();
    $demos = array();
    foreach ( $import_demos as $demo_value ) {
        $normalized = tmpcoder_wizard_normalize_prebuilt_demo_row( $demo_value );
        if ( '' !== $normalized['slug'] && '' !== $normalized['name'] ) {
            $normalized['is_favorite'] = in_array( $normalized['slug'], $favorite_slugs, true );
            $demos[] = $normalized;
        }
    }

    wp_send_json_success(
        array(
            'demos'          => $demos,
            'favorite_slugs' => $favorite_slugs,
        )
    );
}

add_action( 'wp_ajax_tmpcoder_wizard_toggle_favorite_demo', 'tmpcoder_wizard_toggle_favorite_demo' );
/**
 * Add or remove a demo slug from the current user's favorite list.
 *
 * @return void
 */
function tmpcoder_wizard_toggle_favorite_demo() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_demos' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Security check failed. Please refresh the page and try again.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'You do not have permission to update favorites.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $demo_slug = isset( $_POST['demo_slug'] ) ? sanitize_key( wp_unslash( $_POST['demo_slug'] ) ) : '';
    if ( '' === $demo_slug ) {
        wp_send_json_error(
            array(
                'message' => __( 'Invalid template selected.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $favorite = isset( $_POST['favorite'] ) ? sanitize_text_field( wp_unslash( $_POST['favorite'] ) ) : '0';
    $is_favorite = ( '1' === $favorite );
    $favorite_slugs = tmpcoder_wizard_get_user_favorite_demo_slugs();

    if ( $is_favorite ) {
        if ( ! in_array( $demo_slug, $favorite_slugs, true ) ) {
            $favorite_slugs[] = $demo_slug;
        }
    } else {
        $favorite_slugs = array_values(
            array_filter(
                $favorite_slugs,
                static function ( $slug ) use ( $demo_slug ) {
                    return $slug !== $demo_slug;
                }
            )
        );
    }

    tmpcoder_wizard_save_user_favorite_demo_slugs( $favorite_slugs );

    wp_send_json_success(
        array(
            'demo_slug'       => $demo_slug,
            'is_favorite'     => $is_favorite,
            'favorite_slugs'  => $favorite_slugs,
            'message'         => $is_favorite
                ? __( 'Added to favorites.', 'sastra-essential-addons-for-elementor' )
                : __( 'Removed from favorites.', 'sastra-essential-addons-for-elementor' ),
        )
    );
}

add_action( 'wp_ajax_tmpcoder_wizard_get_favorite_demos', 'tmpcoder_wizard_get_favorite_demos' );
/**
 * Return current user's favorite demo slugs.
 *
 * @return void
 */
function tmpcoder_wizard_get_favorite_demos() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_demos' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Security check failed. Please refresh the page and try again.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'You do not have permission to load favorites.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    wp_send_json_success(
        array(
            'favorite_slugs' => tmpcoder_wizard_get_user_favorite_demo_slugs(),
        )
    );
}

add_action( 'wp_ajax_tmpcoder_wizard_save_selected_demo', 'tmpcoder_wizard_save_selected_demo' );
/**
 * Persist the wizard’s chosen prebuilt demo slug for later import steps.
 */
function tmpcoder_wizard_save_selected_demo() {

    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_demos' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Security check failed. Please refresh the page and try again.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'You do not have permission to save this choice.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $slug = isset( $_POST['demo_slug'] ) ? sanitize_key( wp_unslash( $_POST['demo_slug'] ) ) : '';
    if ( '' === $slug ) {
        wp_send_json_error(
            array(
                'message' => __( 'Please select a website template.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    if ( ! class_exists( 'TMPCODER_Remote_Api' ) ) {
        $remote = TMPCODER_PLUGIN_DIR . 'inc/library/class-tmpcoder-plugin-remote-api.php';
        if ( file_exists( $remote ) ) {
            require_once $remote;
        }
    }

    if ( ! class_exists( 'TMPCODER_Remote_Api' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Could not validate template access. Please try again.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $import_demos_resp = TMPCODER_Remote_Api::get_prebuilt_demos();
    if (
        ! is_array( $import_demos_resp )
        || ! isset( $import_demos_resp['status'] )
        || 'success' !== $import_demos_resp['status']
        || ! isset( $import_demos_resp['data'] )
        || ! is_array( $import_demos_resp['data'] )
    ) {
        wp_send_json_error(
            array(
                'message' => __( 'Could not validate template access. Please try again.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $selected_demo = null;
    $selected_demo_raw = array();
    foreach ( $import_demos_resp['data'] as $demo_value ) {
        $normalized = tmpcoder_wizard_normalize_prebuilt_demo_row( $demo_value );
        if ( $normalized['slug'] === $slug ) {
            $selected_demo = $normalized;
            $selected_demo_raw = is_array( $demo_value ) ? $demo_value : (array) $demo_value;
            break;
        }
    }

    if ( ! is_array( $selected_demo ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Selected template is not available.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    if ( ! empty( $selected_demo['locked'] ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'This template requires an active Spexo Addons Pro license.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    update_option( TMPCODER_PLUGIN_KEY . '_wizard_selected_demo', $slug );
    $required_list  = isset( $selected_demo_raw['require-plugins-list'] ) ? $selected_demo_raw['require-plugins-list'] : array();
    $required_slugs = tmpcoder_wizard_extract_required_plugin_slugs( $required_list );
    update_option( TMPCODER_PLUGIN_KEY . '_wizard_selected_demo_required_plugins', $required_slugs );
	// Re-enable Finish Setup for each new import journey.
	update_option( TMPCODER_PLUGIN_KEY . '_finish_setup_dismissed', 0 );
	if ( is_user_logged_in() ) {
		delete_user_meta( get_current_user_id(), TMPCODER_PLUGIN_KEY . '_finish_setup_checked_items' );
		delete_user_meta( get_current_user_id(), TMPCODER_PLUGIN_KEY . '_finish_setup_pending_items' );
	}

    wp_send_json_success(
        array(
            // Intentionally omit success notice text; Step 2 should transition quietly to Step 3.
            'message' => '',
        )
    );
}

if ( ! function_exists( 'tmpcoder_wizard_branding_payload_option_key' ) ) {
    /**
     * Option key used to store temporary wizard branding payload.
     *
     * @return string
     */
    function tmpcoder_wizard_branding_payload_option_key() {
        return TMPCODER_PLUGIN_KEY . '_wizard_branding_payload';
    }
}

if ( ! function_exists( 'tmpcoder_wizard_sanitize_font_family' ) ) {
    /**
     * Sanitize font family string.
     *
     * @param string $font_family Raw font family.
     * @return string
     */
    function tmpcoder_wizard_sanitize_font_family( $font_family ) {
        $font_family = sanitize_text_field( (string) $font_family );
        $font_family = preg_replace( '/[^a-zA-Z0-9\-\s]/', '', $font_family );
        return trim( (string) $font_family );
    }
}

if ( ! function_exists( 'tmpcoder_wizard_sanitize_branding_payload' ) ) {
    /**
     * Sanitize wizard branding payload.
     *
     * @param mixed $raw_payload Payload from request/option.
     * @return array
     */
    function tmpcoder_wizard_sanitize_branding_payload( $raw_payload ) {
        if ( is_string( $raw_payload ) ) {
            $decoded = json_decode( $raw_payload, true );
            if ( is_array( $decoded ) ) {
                $raw_payload = $decoded;
            }
        }

        if ( ! is_array( $raw_payload ) ) {
            return array();
        }

        $logo_id          = isset( $raw_payload['logo_id'] ) ? absint( $raw_payload['logo_id'] ) : 0;
        $logo_url         = isset( $raw_payload['logo_url'] ) ? esc_url_raw( (string) $raw_payload['logo_url'] ) : '';
        $logo_width       = isset( $raw_payload['logo_width'] ) ? absint( $raw_payload['logo_width'] ) : 160;
        $body_font_family = isset( $raw_payload['body_font_family'] ) ? tmpcoder_wizard_sanitize_font_family( $raw_payload['body_font_family'] ) : '';
        $body_font_weight = isset( $raw_payload['body_font_weight'] ) ? sanitize_text_field( (string) $raw_payload['body_font_weight'] ) : '';
        $body_category    = isset( $raw_payload['body_font_category'] ) ? sanitize_text_field( (string) $raw_payload['body_font_category'] ) : '';
        $heading_family   = isset( $raw_payload['heading_font_family'] ) ? tmpcoder_wizard_sanitize_font_family( $raw_payload['heading_font_family'] ) : '';
        $heading_weight   = isset( $raw_payload['heading_font_weight'] ) ? sanitize_text_field( (string) $raw_payload['heading_font_weight'] ) : '';
        $heading_category = isset( $raw_payload['heading_font_category'] ) ? sanitize_text_field( (string) $raw_payload['heading_font_category'] ) : '';

        if ( $logo_width < 40 ) {
            $logo_width = 40;
        } elseif ( $logo_width > 420 ) {
            $logo_width = 420;
        }

        if ( '' !== $body_font_family && ! in_array( $body_category, array( 'sans-serif', 'serif', 'monospace' ), true ) ) {
            $body_category = 'sans-serif';
        }
        if ( '' !== $heading_family && ! in_array( $heading_category, array( 'sans-serif', 'serif', 'monospace' ), true ) ) {
            $heading_category = 'sans-serif';
        }

        return array(
            'logo_id'               => $logo_id,
            'logo_url'              => $logo_url,
            'logo_width'            => $logo_width,
            'body_font_family'      => $body_font_family,
            'body_font_weight'      => $body_font_weight,
            'body_font_category'    => $body_category,
            'heading_font_family'   => $heading_family,
            'heading_font_weight'   => $heading_weight,
            'heading_font_category' => $heading_category,
        );
    }
}

if ( ! function_exists( 'tmpcoder_wizard_get_branding_payload' ) ) {
    /**
     * Read sanitized wizard branding payload.
     *
     * @return array
     */
    function tmpcoder_wizard_get_branding_payload() {
        $saved = get_option( tmpcoder_wizard_branding_payload_option_key(), array() );
        return tmpcoder_wizard_sanitize_branding_payload( $saved );
    }
}

if ( ! function_exists( 'tmpcoder_wizard_build_logo_width_css' ) ) {
    /**
     * Build custom CSS block for logo width override.
     *
     * @param int $logo_width Width in px.
     * @return string
     */
    function tmpcoder_wizard_build_logo_width_css( $logo_width ) {
        $logo_width = absint( $logo_width );
        if ( $logo_width <= 0 ) {
            return '';
        }

        return "/* tmpcoder-wizard-logo-width-start */\n" .
            '.tmpcoder-site-logo img,.tmpcoder-site-logo-set img{max-width:' . $logo_width . "px;width:100%;height:auto;}\n" .
            "/* tmpcoder-wizard-logo-width-end */";
    }
}

if ( ! function_exists( 'tmpcoder_wizard_apply_branding_payload_to_redux_options' ) ) {
    /**
     * Merge wizard branding payload into imported Redux options.
     *
     * @param array $redux_options_data Imported redux options.
     * @return array
     */
    function tmpcoder_wizard_apply_branding_payload_to_redux_options( $redux_options_data ) {
        if ( ! is_array( $redux_options_data ) ) {
            $redux_options_data = array();
        }

        $payload = tmpcoder_wizard_get_branding_payload();
        if ( empty( $payload ) ) {
            return $redux_options_data;
        }

        if ( ! empty( $payload['logo_url'] ) ) {
            $redux_options_data['tmpcoder_logo_image'] = array(
                'url' => esc_url_raw( $payload['logo_url'] ),
                'id'  => absint( $payload['logo_id'] ),
            );
        }

        $body_font_family = $payload['body_font_family'];
        $heading_family   = $payload['heading_font_family'];

        if ( '' !== $body_font_family ) {
            if ( ! isset( $redux_options_data['site_fonts_options'] ) || ! is_array( $redux_options_data['site_fonts_options'] ) ) {
                $redux_options_data['site_fonts_options'] = array();
            }
            $redux_options_data['site_fonts_options']['font-family'] = $body_font_family;
            $redux_options_data['site_fonts_options']['font-weight'] = $payload['body_font_weight'];
            $redux_options_data['site_fonts_options']['font-style']  = $payload['body_font_weight'];
            $redux_options_data['site_fonts_options']['google']      = true;
            $redux_options_data['site_fonts_options']['subsets']     = '';
            if ( ! isset( $redux_options_data['button_style'] ) || ! is_array( $redux_options_data['button_style'] ) ) {
                $redux_options_data['button_style'] = array();
            }
            $redux_options_data['button_style']['font-family'] = $body_font_family;
            $redux_options_data['button_style']['font-weight'] = $payload['body_font_weight'];
            $redux_options_data['button_style']['font-style']  = $payload['body_font_weight'];
            $redux_options_data['button_style']['google']      = true;
            $redux_options_data['button_style']['subsets']     = '';
        }

        if ( '' !== $heading_family ) {
            for ( $i = 1; $i <= 6; $i++ ) {
                $heading_key = 'heading_' . $i;
                if ( ! isset( $redux_options_data[ $heading_key ] ) || ! is_array( $redux_options_data[ $heading_key ] ) ) {
                    $redux_options_data[ $heading_key ] = array();
                }
                $redux_options_data[ $heading_key ]['font-family'] = $heading_family;
                $redux_options_data[ $heading_key ]['font-weight'] = $payload['heading_font_weight'];
                $redux_options_data[ $heading_key ]['font-style']  = $payload['heading_font_weight'];
                $redux_options_data[ $heading_key ]['google']      = true;
                $redux_options_data[ $heading_key ]['subsets']     = '';
            }
        }

        $custom_css_block = tmpcoder_wizard_build_logo_width_css( $payload['logo_width'] );
        if ( '' !== $custom_css_block ) {
            $existing_css = isset( $redux_options_data['tmpcoder_custom_css'] ) ? (string) $redux_options_data['tmpcoder_custom_css'] : '';
            $existing_css = preg_replace( '/\/\* tmpcoder-wizard-logo-width-start \*\/[\s\S]*?\/\* tmpcoder-wizard-logo-width-end \*\//', '', $existing_css );
            $existing_css = trim( (string) $existing_css );
            $redux_options_data['tmpcoder_custom_css'] = '' === $existing_css ? $custom_css_block : $existing_css . "\n\n" . $custom_css_block;
        }

        return $redux_options_data;
    }
}

add_action( 'wp_ajax_tmpcoder_wizard_get_branding_payload', 'tmpcoder_wizard_get_branding_payload_ajax' );
/**
 * Return saved branding payload for current wizard run.
 *
 * @return void
 */
function tmpcoder_wizard_get_branding_payload_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_branding' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    wp_send_json_success(
        array(
            'payload' => tmpcoder_wizard_get_branding_payload(),
        )
    );
}

add_action( 'wp_ajax_tmpcoder_wizard_save_branding_payload', 'tmpcoder_wizard_save_branding_payload_ajax' );
/**
 * Save branding payload chosen in wizard customize step.
 *
 * @return void
 */
function tmpcoder_wizard_save_branding_payload_ajax() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_branding' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    $payload = tmpcoder_wizard_sanitize_branding_payload(
        array(
            'logo_id'               => isset( $_POST['logo_id'] ) ? wp_unslash( $_POST['logo_id'] ) : 0,
            'logo_url'              => isset( $_POST['logo_url'] ) ? wp_unslash( $_POST['logo_url'] ) : '',
            'logo_width'            => isset( $_POST['logo_width'] ) ? wp_unslash( $_POST['logo_width'] ) : 160,
            'body_font_family'      => isset( $_POST['body_font_family'] ) ? wp_unslash( $_POST['body_font_family'] ) : '',
            'body_font_weight'      => isset( $_POST['body_font_weight'] ) ? wp_unslash( $_POST['body_font_weight'] ) : '400',
            'body_font_category'    => isset( $_POST['body_font_category'] ) ? wp_unslash( $_POST['body_font_category'] ) : 'sans-serif',
            'heading_font_family'   => isset( $_POST['heading_font_family'] ) ? wp_unslash( $_POST['heading_font_family'] ) : '',
            'heading_font_weight'   => isset( $_POST['heading_font_weight'] ) ? wp_unslash( $_POST['heading_font_weight'] ) : '600',
            'heading_font_category' => isset( $_POST['heading_font_category'] ) ? wp_unslash( $_POST['heading_font_category'] ) : 'sans-serif',
        )
    );

    update_option( tmpcoder_wizard_branding_payload_option_key(), $payload );

    wp_send_json_success(
        array(
            'payload' => $payload,
            'message' => __( 'Branding settings saved.', 'sastra-essential-addons-for-elementor' ),
        )
    );
}

add_action( 'wp_ajax_tmpcoder_wizard_get_selected_demo_import_context', 'tmpcoder_wizard_get_selected_demo_import_context' );
/**
 * Return selected demo context needed by import bridge (Phase 1 contract).
 *
 * This endpoint is read-only and does not start any import process.
 */
function tmpcoder_wizard_get_selected_demo_import_context() {

    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_demos' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Security check failed. Please refresh the page and try again.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'You do not have permission to load selected demo data.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $selected_slug = sanitize_key( (string) get_option( TMPCODER_PLUGIN_KEY . '_wizard_selected_demo', '' ) );
    if ( '' === $selected_slug ) {
        wp_send_json_error(
            array(
                'message' => __( 'Please select a website template first.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    if ( ! class_exists( 'TMPCODER_Remote_Api' ) ) {
        $remote = TMPCODER_PLUGIN_DIR . 'inc/library/class-tmpcoder-plugin-remote-api.php';
        if ( file_exists( $remote ) ) {
            require_once $remote;
        }
    }

    if ( ! class_exists( 'TMPCODER_Remote_Api' ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Could not load demo data.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $import_demos_resp = TMPCODER_Remote_Api::get_prebuilt_demos();
    if (
        ! is_array( $import_demos_resp )
        || ! isset( $import_demos_resp['status'] )
        || 'success' !== $import_demos_resp['status']
        || ! isset( $import_demos_resp['data'] )
        || ! is_array( $import_demos_resp['data'] )
    ) {
        wp_send_json_error(
            array(
                'message' => __( 'Could not validate selected template data.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $selected_demo = array();
    foreach ( $import_demos_resp['data'] as $demo_value ) {
        $demo = is_array( $demo_value ) ? $demo_value : (array) $demo_value;
        $slug = isset( $demo['theme-demo-slug'] ) ? sanitize_key( (string) $demo['theme-demo-slug'] ) : '';
        if ( $slug === $selected_slug ) {
            $selected_demo = $demo;
            break;
        }
    }

    if ( empty( $selected_demo ) ) {
        wp_send_json_error(
            array(
                'message' => __( 'Selected template is not available.', 'sastra-essential-addons-for-elementor' ),
            )
        );
    }

    $required_plugin_slugs  = array();
    $required_plugin_states = array();
    $required_list          = isset( $selected_demo['require-plugins-list'] ) ? $selected_demo['require-plugins-list'] : array();
    $required_plugin_slugs  = tmpcoder_wizard_extract_required_plugin_slugs( $required_list );

    foreach ( $required_plugin_slugs as $key ) {
        $required_plugin_states[ $key ] = function_exists( 'tmpcoder_is_plugin_active_by_slug' ) ? (bool) tmpcoder_is_plugin_active_by_slug( $key ) : false;
    }

    $theme_status = function_exists( 'tmpcoder_get_theme_status' ) ? tmpcoder_get_theme_status() : 'req-theme-not-installed';

    wp_send_json_success(
        array(
            'selected_demo' => array(
                'slug'                   => $selected_slug,
                'name'                   => isset( $selected_demo['name'] ) ? sanitize_text_field( (string) $selected_demo['name'] ) : '',
                'preview_url'            => isset( $selected_demo['preview-url'] ) ? esc_url_raw( (string) $selected_demo['preview-url'] ) : '',
                'required_plugin_slugs'  => array_values( array_unique( $required_plugin_slugs ) ),
                'required_plugin_states' => $required_plugin_states,
                'theme_status'           => sanitize_key( (string) $theme_status ),
            ),
        )
    );
}

add_action("wp_ajax_tmpcoder_wizard_pro_addons_info", "tmpcoder_wizard_pro_addons_info");
add_action("wp_ajax_nopriv_tmpcoder_wizard_pro_addons_info", "tmpcoder_wizard_pro_addons_info");
function tmpcoder_wizard_pro_addons_info(){

    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'tmpcoder_get_pro_addons_info') ) {
    	exit; // Get out of here, the nonce is rotten!
    }
    
    if ( ! is_user_logged_in() ){
        esc_html_e("You must log in to site setup", 'sastra-essential-addons-for-elementor');
        die();
    }

    $import_demo_url = admin_url().'admin.php?page=tmpcoder-import-demo&saved=plugin-wizard';

    ob_start();
        echo wp_kses_post(sprintf(
            /* translators: %s is License Activation Heading */
            '<h2 class="wizard-heading">%s</h2>', __("Get Spexo Addons Pro", 'sastra-essential-addons-for-elementor')));
            echo '<p>'.esc_html('Unlock access to all our premium widgets and features.').'</p>';
            echo '<ul class="tmpcoder-wizard-pro-features-list">
                    <li>'.esc_html('80+ Pro Widgets').'</li>
                    <li>'.esc_html('210+ Pro Prebuilt Blocks').'</li>
                    <li>'.esc_html('150+ Pro Prebuilt Sections').'</li>
                    <li>'.esc_html('50+ Pro Prebuilt Websites').'</li>
                </ul>';

            echo "<a target='_blank' href='".esc_url(TMPCODER_PURCHASE_PRO_URL.'?ref=tmpcoder-setup-wizard')."' class='tmpcoder-get-pro-btn'>";
            ?>

            <img src="<?php echo esc_url(TMPCODER_ADDONS_ASSETS_URL.'images/pro-icon.svg'); ?>">
            <span><?php echo esc_html__( 'Get Pro Now', 'sastra-essential-addons-for-elementor' ); ?></span>

            <?php
            echo "</a>";

            echo '<div class="next-step-action">';
            echo '<a href='.esc_url($import_demo_url).' class="button button-primary next-step-btn">'.esc_html('Done').'</a>';
            echo '</div>';

        $GLOBALS['show_on_wizard'] = 1;

    $output = ob_get_contents();
    ob_end_clean();

    wp_send_json_success(array( 'data'=> $output ));
}

if ( ! function_exists( 'tmpcoder_wizard_extract_required_plugin_slugs' ) ) {
    /**
     * Normalize required plugin slugs from raw `require-plugins-list` payload.
     *
     * Supports associative maps and list items with `slug`.
     *
     * @param mixed $required_list Raw required plugin payload.
     * @return array
     */
    function tmpcoder_wizard_extract_required_plugin_slugs( $required_list ) {
        $required_slugs = array();
        $excluded_slugs = array( 'sastra-essential-addons-for-elementor' );

        if ( is_object( $required_list ) ) {
            $required_list = (array) $required_list;
        }

        if ( ! is_array( $required_list ) ) {
            return $required_slugs;
        }

        foreach ( $required_list as $plugin_key => $plugin_meta ) {
            $key_slug = sanitize_key( (string) $plugin_key );
            if ( '' !== $key_slug && ! is_numeric( (string) $plugin_key ) ) {
                if ( in_array( $key_slug, $excluded_slugs, true ) ) {
                    continue;
                }
                $required_slugs[] = $key_slug;
                continue;
            }

            if ( is_object( $plugin_meta ) ) {
                $plugin_meta = (array) $plugin_meta;
            }

            if ( is_array( $plugin_meta ) && ! empty( $plugin_meta['slug'] ) ) {
                $meta_slug = sanitize_key( (string) $plugin_meta['slug'] );
                if ( '' !== $meta_slug ) {
                    if ( in_array( $meta_slug, $excluded_slugs, true ) ) {
                        continue;
                    }
                    $required_slugs[] = $meta_slug;
                }
            }
        }

        return array_values( array_unique( $required_slugs ) );
    }
}

if ( ! function_exists( 'tmpcoder_wizard_get_user_favorite_demo_slugs' ) ) {
    /**
     * Read and sanitize favorite demo slugs from current user meta.
     *
     * @param int $user_id Optional user id.
     * @return array
     */
    function tmpcoder_wizard_get_user_favorite_demo_slugs( $user_id = 0 ) {
        $user_id = $user_id ? absint( $user_id ) : get_current_user_id();
        if ( $user_id <= 0 ) {
            return array();
        }

        $meta_key = TMPCODER_PLUGIN_KEY . '_wizard_favorite_demos';
        $saved = get_user_meta( $user_id, $meta_key, true );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }

        $slugs = array();
        foreach ( $saved as $slug ) {
            $slug = sanitize_key( (string) $slug );
            if ( '' !== $slug ) {
                $slugs[] = $slug;
            }
        }

        return array_values( array_unique( $slugs ) );
    }
}

if ( ! function_exists( 'tmpcoder_wizard_save_user_favorite_demo_slugs' ) ) {
    /**
     * Persist favorite demo slugs for current user.
     *
     * @param array $slugs Favorite slugs.
     * @param int   $user_id Optional user id.
     * @return void
     */
    function tmpcoder_wizard_save_user_favorite_demo_slugs( $slugs, $user_id = 0 ) {
        $user_id = $user_id ? absint( $user_id ) : get_current_user_id();
        if ( $user_id <= 0 ) {
            return;
        }

        $meta_key = TMPCODER_PLUGIN_KEY . '_wizard_favorite_demos';
        $cleaned  = array();

        if ( is_array( $slugs ) ) {
            foreach ( $slugs as $slug ) {
                $slug = sanitize_key( (string) $slug );
                if ( '' !== $slug ) {
                    $cleaned[] = $slug;
                }
            }
        }

        $cleaned = array_values( array_unique( $cleaned ) );
        update_user_meta( $user_id, $meta_key, $cleaned );
    }
}

if ( ! function_exists( 'tmpcoder_wizard_get_selected_demo_required_plugins' ) ) {
    /**
     * Resolve required plugin slugs from currently selected prebuilt demo.
     *
     * @return array|WP_Error
     */
    function tmpcoder_wizard_get_selected_demo_required_plugins() {
        $selected_slug = sanitize_key( (string) get_option( TMPCODER_PLUGIN_KEY . '_wizard_selected_demo', '' ) );
        if ( '' === $selected_slug ) {
            return new WP_Error( 'missing_demo', __( 'Please select a website template first.', 'sastra-essential-addons-for-elementor' ) );
        }

        $stored_required = get_option( TMPCODER_PLUGIN_KEY . '_wizard_selected_demo_required_plugins', array() );
        if ( is_array( $stored_required ) && ! empty( $stored_required ) ) {
            $sanitized_stored_required = array();
            foreach ( $stored_required as $stored_slug ) {
                $key = sanitize_key( (string) $stored_slug );
                if ( '' !== $key ) {
                    $sanitized_stored_required[] = $key;
                }
            }
            if ( ! empty( $sanitized_stored_required ) ) {
                return array_values( array_unique( $sanitized_stored_required ) );
            }
        }

        if ( ! class_exists( 'TMPCODER_Remote_Api' ) ) {
            $remote = TMPCODER_PLUGIN_DIR . 'inc/library/class-tmpcoder-plugin-remote-api.php';
            if ( file_exists( $remote ) ) {
                require_once $remote;
            }
        }

        if ( ! class_exists( 'TMPCODER_Remote_Api' ) ) {
            return new WP_Error( 'api_unavailable', __( 'Could not load demo data.', 'sastra-essential-addons-for-elementor' ) );
        }

        $import_demos_resp = TMPCODER_Remote_Api::get_prebuilt_demos();
        if (
            ! is_array( $import_demos_resp )
            || ! isset( $import_demos_resp['status'] )
            || 'success' !== $import_demos_resp['status']
            || ! isset( $import_demos_resp['data'] )
            || ! is_array( $import_demos_resp['data'] )
        ) {
            return new WP_Error( 'api_invalid', __( 'Could not validate selected template plugins.', 'sastra-essential-addons-for-elementor' ) );
        }

        foreach ( $import_demos_resp['data'] as $demo_value ) {
            $demo = is_array( $demo_value ) ? $demo_value : (array) $demo_value;
            $slug = isset( $demo['theme-demo-slug'] ) ? sanitize_key( $demo['theme-demo-slug'] ) : '';
            if ( $slug !== $selected_slug ) {
                continue;
            }

            $required_list = isset( $demo['require-plugins-list'] ) ? $demo['require-plugins-list'] : array();
            return tmpcoder_wizard_extract_required_plugin_slugs( $required_list );
        }

        return new WP_Error( 'demo_not_found', __( 'Selected template is not available.', 'sastra-essential-addons-for-elementor' ) );
    }
}

if ( ! function_exists( 'tmpcoder_wizard_optional_feature_plugins' ) ) {
    /**
     * Optional feature plugins shown on setup wizard step 3.
     *
     * @param array $feature_suggestions Dynamic feature suggestions keyed by plugin_key.
     * @return array
     */
    function tmpcoder_wizard_optional_feature_plugins( $feature_suggestions = array() ) {
        $optional_plugins = array(
            'woocommerce' => array(
                'slug'           => 'woocommerce',
                'name'           => 'WooCommerce',
                'file_path'      => 'woocommerce/woocommerce.php',
                'link'           => 'https://wordpress.org/plugins/woocommerce/',
                'ui_icon'        => 'dashicons-cart',
                'ui_description' => __( 'Sell your products online', 'sastra-essential-addons-for-elementor' ),
            ),
            'wordpress-seo' => array(
                'slug'           => 'wordpress-seo',
                'name'           => 'Yoast SEO',
                'file_path'      => 'wordpress-seo/wp-seo.php',
                'link'           => 'https://wordpress.org/plugins/wordpress-seo/',
                'ui_icon'        => 'dashicons-chart-line',
                'ui_description' => __( 'Optimize your website for search engines', 'sastra-essential-addons-for-elementor' ),
            ),
        );

        foreach ( (array) $feature_suggestions as $plugin_key => $item ) {
            $plugin_slug = sanitize_key( (string) $plugin_key );
            if ( '' === $plugin_slug ) {
                continue;
            }

            $item = is_array( $item ) ? $item : (array) $item;
            $name = isset( $item['name'] ) ? sanitize_text_field( (string) $item['name'] ) : '';
            if ( '' === $name && isset( $item['title'] ) ) {
                $name = sanitize_text_field( (string) $item['title'] );
            }
            if ( '' === $name ) {
                $name = ucwords( str_replace( '-', ' ', $plugin_slug ) );
            }

            $description = isset( $item['description'] ) ? sanitize_text_field( (string) $item['description'] ) : '';
            $feature_key = isset( $item['feature_key'] ) ? sanitize_key( (string) $item['feature_key'] ) : '';
            $icon_url    = isset( $item['icon'] ) ? esc_url_raw( (string) $item['icon'] ) : '';

            // Keep plugin install flow unchanged while enabling dynamic feature visibility.
            // file_path uses a best-effort default and is superseded by TGMPA/file lookup when available.
            $optional_plugins[ $plugin_slug ] = array(
                'slug'           => $plugin_slug,
                'name'           => $name,
                'file_path'      => $plugin_slug . '/' . $plugin_slug . '.php',
                'link'           => 'https://wordpress.org/plugins/' . $plugin_slug . '/',
                'ui_icon'        => 'dashicons-admin-plugins',
                'ui_description' => $description,
                'feature_key'    => $feature_key,
                'icon_url'       => $icon_url,
            );
        }

        return $optional_plugins;
    }
}

if ( ! function_exists( 'tmpcoder_wizard_mandatory_feature_plugins' ) ) {
    /**
     * Mandatory plugins that must always appear in Step 3.
     *
     * @return array
     */
    function tmpcoder_wizard_mandatory_feature_plugins() {
        return array(
            'elementor' => array(
                'slug'      => 'elementor',
                'name'      => 'Elementor',
                'file_path' => 'elementor/elementor.php',
                'link'      => 'https://wordpress.org/plugins/elementor/',
            ),
            'redux-framework' => array(
                'slug'      => 'redux-framework',
                'name'      => 'Redux Framework',
                'file_path' => 'redux-framework/redux-framework.php',
                'link'      => 'https://wordpress.org/plugins/redux-framework/',
            ),
        );
    }
}

if ( ! function_exists( 'tmpcoder_wizard_ordered_feature_slugs' ) ) {
    /**
     * Build canonical ordered slug list for Step 3 display.
     *
     * Order: mandatory -> selected demo required -> optional.
     *
     * @param array $required_plugins Required slugs from selected demo.
     * @param array $optional_plugins Optional feature plugin map.
     *
     * @return array
     */
    function tmpcoder_wizard_ordered_feature_slugs( $required_plugins, $optional_plugins ) {
        $ordered = array();

        foreach ( array_keys( tmpcoder_wizard_mandatory_feature_plugins() ) as $mandatory_slug ) {
            $ordered[] = sanitize_key( (string) $mandatory_slug );
        }

        foreach ( (array) $required_plugins as $required_slug ) {
            $slug = sanitize_key( (string) $required_slug );
            if ( '' !== $slug ) {
                $ordered[] = $slug;
            }
        }

        foreach ( array_keys( (array) $optional_plugins ) as $optional_slug ) {
            $slug = sanitize_key( (string) $optional_slug );
            if ( '' !== $slug ) {
                $ordered[] = $slug;
            }
        }

        return array_values( array_unique( $ordered ) );
    }
}

if ( ! function_exists( 'tmpcoder_wizard_get_dynamic_feature_suggestions' ) ) {
    /**
     * Fetch dynamic feature card suggestions from remote API action `suggestion_plugin_lists`.
     *
     * @param array  $required_plugins Selected demo required plugin slugs.
     * @param string $selected_demo_slug Selected demo slug.
     * @return array
     */
    function tmpcoder_wizard_get_dynamic_feature_suggestions( $required_plugins = array(), $selected_demo_slug = '' ) {
        if ( ! defined( 'TMPCODER_UPDATES_URL' ) ) {
            return array();
        }

        $remote_action = apply_filters( 'tmpcoder_wizard_feature_suggestions_remote_action', 'suggestion_plugin_lists' );
        $req_params    = array(
            'action'         => $remote_action,
            'theme'          => defined( 'TMPCODER_CURRENT_THEME_NAME' ) ? TMPCODER_CURRENT_THEME_NAME : '',
            'version'        => defined( 'TMPCODER_CURRENT_THEME_VERSION' ) ? TMPCODER_CURRENT_THEME_VERSION : '',
            'plugin'         => 'sastra-essential-addons-for-elementor',
            'plugin_version' => defined( 'TMPCODER_PLUGIN_VER' ) ? TMPCODER_PLUGIN_VER : '',
        );

        $req_params = apply_filters( 'tmpcoder_wizard_feature_suggestions_remote_query_args', $req_params );
        $api_url    = add_query_arg( $req_params, TMPCODER_UPDATES_URL );

        $payload = array(
            'selected_demo_slug' => sanitize_key( (string) $selected_demo_slug ),
            'required_plugins'   => array_values(
                array_filter(
                    array_map(
                        function( $slug ) {
                            return sanitize_key( (string) $slug );
                        },
                        (array) $required_plugins
                    )
                )
            ),
        );

        $response = wp_safe_remote_request(
            $api_url,
            array(
                'headers'     => array(
                    'Referer' => site_url(),
                ),
                'body'        => wp_json_encode( $payload ),
                'timeout'     => 15,
                'method'      => 'POST',
                'httpversion' => '1.1',
                'user-agent'  => 'templatescoder-user-agent',
            )
        );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code < 200 || $code >= 300 ) {
            return array();
        }

        $raw = wp_remote_retrieve_body( $response );
        if ( ! is_string( $raw ) || '' === $raw ) {
            return array();
        }

        $decoded = json_decode( $raw, true );
        if ( ! is_array( $decoded ) ) {
            return array();
        }

        // Support either direct list response or wrapped `data` list.
        $rows = $decoded;
        if ( isset( $decoded['data'] ) && is_array( $decoded['data'] ) ) {
            $rows = $decoded['data'];
        }

        $feature_suggestions = array();
        foreach ( (array) $rows as $row ) {
            $item = is_array( $row ) ? $row : (array) $row;
            $plugin_key = isset( $item['plugin_key'] ) ? sanitize_key( (string) $item['plugin_key'] ) : '';
            if ( '' === $plugin_key ) {
                continue;
            }

            $feature_suggestions[ $plugin_key ] = array(
                'plugin_key'  => $plugin_key,
                'feature_key' => isset( $item['feature_key'] ) ? sanitize_key( (string) $item['feature_key'] ) : '',
                'title'       => isset( $item['title'] ) ? sanitize_text_field( (string) $item['title'] ) : '',
                'description' => isset( $item['description'] ) ? sanitize_text_field( (string) $item['description'] ) : '',
                'icon'        => isset( $item['icon'] ) ? esc_url_raw( (string) $item['icon'] ) : '',
                'name'        => isset( $item['name'] ) ? sanitize_text_field( (string) $item['name'] ) : '',
            );
        }

        return $feature_suggestions;
    }
}

add_action("wp_ajax_tmpcoder_get_required_plugins_func", "tmpcoder_get_required_plugins_func");
add_action("wp_ajax_nopriv_tmpcoder_get_required_plugins_func", "tmpcoder_get_required_plugins_func");
function tmpcoder_get_required_plugins_func(){

    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'tmpcoder_get_plugins') ) {
    	exit; // Get out of here, the nonce is rotten!
    }
    
    if ( ! is_user_logged_in() ){
        esc_html_e("You must log in to site setup", 'sastra-essential-addons-for-elementor');
        die();
    }

    $wizard_flow = isset( $_POST['wizard_flow'] ) ? sanitize_key( (string) wp_unslash( $_POST['wizard_flow'] ) ) : 'prebuilt';
    if ( ! in_array( $wizard_flow, array( 'prebuilt', 'scratch' ), true ) ) {
        $wizard_flow = 'prebuilt';
    }

    $required_plugins = array();
    $selected_demo_slug = sanitize_key( (string) get_option( TMPCODER_PLUGIN_KEY . '_wizard_selected_demo', '' ) );
    if ( 'prebuilt' === $wizard_flow ) {
        $required_plugins = tmpcoder_wizard_get_selected_demo_required_plugins();
        if ( is_wp_error( $required_plugins ) ) {
            wp_send_json_error( array( 'message' => $required_plugins->get_error_message() ) );
        }
    }

    $feature_suggestions = tmpcoder_wizard_get_dynamic_feature_suggestions( $required_plugins, $selected_demo_slug );
    $tgmpaClass         = $GLOBALS['tgmpa'];
    $plugins            = array();
    $next_step          = __('Next', 'sastra-essential-addons-for-elementor');
    $skip_this          = false;
    $extra_features     = tmpcoder_wizard_optional_feature_plugins( $feature_suggestions );
    $mandatory_plugins  = tmpcoder_wizard_mandatory_feature_plugins();
    $mandatory_slugs    = array_keys( $mandatory_plugins );
    $ordered_slugs      = tmpcoder_wizard_ordered_feature_slugs( $required_plugins, $extra_features );
    $tgmpa_plugins_map  = array();
    $required_slug_map  = array_fill_keys( array_values( (array) $required_plugins ), true );

    if ( is_object( $tgmpaClass ) ) {
        if ( empty( $tgmpaClass->plugins ) ) {
            $tmpcoder_mainClass = new Tmpcoder_Main_Class();
            $tmpcoder_mainClass->tmpcoder_require_plugins();
            $tgmpaClass = $GLOBALS['tgmpa'];
        }

        if ( ! empty( $tgmpaClass->plugins ) ) {
            foreach ( $tgmpaClass->plugins as $plugin ) {
                if ( empty( $plugin['slug'] ) || 'sastra-essential-addons-for-elementor' === $plugin['slug'] ) {
                    continue;
                }
                $tgmpa_plugins_map[ sanitize_key( (string) $plugin['slug'] ) ] = $plugin;
            }
        }
    }

    foreach ( $ordered_slugs as $plugin_slug ) {
        $plugin_slug = sanitize_key( (string) $plugin_slug );
        if ( '' === $plugin_slug ) {
            continue;
        }

        $tgmpa_plugin     = isset( $tgmpa_plugins_map[ $plugin_slug ] ) ? $tgmpa_plugins_map[ $plugin_slug ] : array();
        $mandatory_meta   = isset( $mandatory_plugins[ $plugin_slug ] ) ? $mandatory_plugins[ $plugin_slug ] : array();
        $optional_meta    = isset( $extra_features[ $plugin_slug ] ) ? $extra_features[ $plugin_slug ] : array();
        $plugin_file_path = '';

        if ( ! empty( $tgmpa_plugin['file_path'] ) ) {
            $plugin_file_path = sanitize_text_field( (string) $tgmpa_plugin['file_path'] );
        } elseif ( ! empty( $mandatory_meta['file_path'] ) ) {
            $plugin_file_path = sanitize_text_field( (string) $mandatory_meta['file_path'] );
        } elseif ( ! empty( $optional_meta['file_path'] ) ) {
            $plugin_file_path = sanitize_text_field( (string) $optional_meta['file_path'] );
        }

        $plugin_name = '';
        if ( ! empty( $tgmpa_plugin['name'] ) ) {
            $plugin_name = sanitize_text_field( (string) $tgmpa_plugin['name'] );
        } elseif ( ! empty( $mandatory_meta['name'] ) ) {
            $plugin_name = sanitize_text_field( (string) $mandatory_meta['name'] );
        } elseif ( ! empty( $optional_meta['name'] ) ) {
            $plugin_name = sanitize_text_field( (string) $optional_meta['name'] );
        } else {
            $plugin_name = ucwords( str_replace( '-', ' ', $plugin_slug ) );
        }

        $plugin_link = '';
        if ( ! empty( $tgmpa_plugin['link'] ) ) {
            $plugin_link = esc_url_raw( (string) $tgmpa_plugin['link'] );
        } elseif ( ! empty( $mandatory_meta['link'] ) ) {
            $plugin_link = esc_url_raw( (string) $mandatory_meta['link'] );
        } elseif ( ! empty( $optional_meta['link'] ) ) {
            $plugin_link = esc_url_raw( (string) $optional_meta['link'] );
        } else {
            $plugin_link = 'https://wordpress.org/plugins/' . $plugin_slug;
        }

        $plugin_image = '';
        $plugin_info  = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.0/' . $plugin_slug . '.json?fields=banners,icons' );
        if ( is_array( $plugin_info ) && ! is_wp_error( $plugin_info ) ) {
            $body = json_decode( $plugin_info['body'], true );
            if ( isset( $body['icons'] ) && is_array( $body['icons'] ) ) {
                if ( ! empty( $body['icons']['svg'] ) ) {
                    $plugin_image = $body['icons']['svg'];
                } elseif ( ! empty( $body['icons']['2x'] ) ) {
                    $plugin_image = $body['icons']['2x'];
                } elseif ( ! empty( $body['icons']['1x'] ) ) {
                    $plugin_image = $body['icons']['1x'];
                } elseif ( ! empty( $body['icons']['default'] ) ) {
                    $plugin_image = $body['icons']['default'];
                }
            }
        }

        $is_required = in_array( $plugin_slug, $mandatory_slugs, true ) || isset( $required_slug_map[ $plugin_slug ] );
        $is_optional = ! $is_required && isset( $extra_features[ $plugin_slug ] );
        $is_selected = $is_required;

        $plugin_row = array(
            'name'           => $plugin_name,
            'slug'           => $plugin_slug,
            'file_path'      => $plugin_file_path,
            'link'           => $plugin_link,
            'image'          => $plugin_image,
            'is_required'    => $is_required,
            'is_optional'    => $is_optional,
            'is_selected'    => $is_selected,
            'ui_icon'        => isset( $optional_meta['ui_icon'] ) ? $optional_meta['ui_icon'] : 'dashicons-admin-plugins',
            'ui_description' => isset( $optional_meta['ui_description'] ) ? $optional_meta['ui_description'] : '',
        );

        if ( '' !== $plugin_file_path && is_plugin_installed( $plugin_file_path ) ) {
            $plugin_row['installed'] = true;
            $next_step = __('Install & Activate', 'sastra-essential-addons-for-elementor');
        }

        if ( '' !== $plugin_file_path && is_plugin_active( $plugin_file_path ) ) {
            $plugin_row['activated'] = true;
            if ( $is_optional ) {
                $plugin_row['is_selected'] = true;
            }
        }

        if ( empty( $plugin_row['activated'] ) ) {
            $next_step = __('Install & Activate', 'sastra-essential-addons-for-elementor');
        }

        $plugins[] = $plugin_row;
    }

    $all_required_active = true;
    foreach ( $plugins as $plugin_row ) {
        if ( ! empty( $plugin_row['is_required'] ) && empty( $plugin_row['activated'] ) ) {
            $all_required_active = false;
            break;
        }
    }
    if ( $all_required_active ) {
        $skip_this = true;
        update_option(TMPCODER_PRO_PLUGIN_KEY.'_wizard_step', '2');
    }

    if ( !empty($plugins) ){
        wp_send_json_success(
            array(
                'plugins' => $plugins,
                'message' => __('Plugins getting successfully.','sastra-essential-addons-for-elementor'),
                'next_step' => $next_step,
                'skip_this' => $skip_this,
                'feature_suggestions' => $feature_suggestions,
            )
        );
    }else{
        $error = __('No Required plugins found','sastra-essential-addons-for-elementor');
        wp_send_json_error(array('message'=> $error ));
    }
}

add_action("wp_ajax_tmpcoder_install_required_plugins_func", "tmpcoder_install_required_plugins_func");
add_action("wp_ajax_nopriv_tmpcoder_install_required_plugins_func", "tmpcoder_install_required_plugins_func");
function tmpcoder_install_required_plugins_func(){

    // Check if nonce is valid.
    if ( ! isset($_POST['_wpnonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash ($_POST['_wpnonce'])), 'tmpcoder_install_plugins' ) ) {
        exit;
    }
    
    if ( ! is_user_logged_in() ){
        esc_html_e("You must log in to site setup", 'sastra-essential-addons-for-elementor');
        die();
    }

    if ( ! current_user_can('install_plugins') ) {
        esc_html_e('Invalid User', 'sastra-essential-addons-for-elementor');
        die();
    }

    $requested_plugins = array();
    if ( isset( $_POST['selected_plugins'] ) && is_array( $_POST['selected_plugins'] ) ) {
        foreach ( wp_unslash( $_POST['selected_plugins'] ) as $raw_slug ) {
            $slug = sanitize_key( (string) $raw_slug );
            if ( '' !== $slug ) {
                $requested_plugins[ $slug ] = '1';
            }
        }
    }
    if ( empty( $requested_plugins ) && isset( $_POST['plugins'] ) && is_array($_POST['plugins'])) {
        // Fallback for legacy payload: plugins[slug] = 1
        foreach ( wp_unslash( $_POST['plugins'] ) as $raw_slug => $raw_value ) {
            $slug  = sanitize_key( (string) $raw_slug );
            $value = sanitize_text_field( (string) $raw_value );
            if ( '' !== $slug && '1' === $value ) {
                $requested_plugins[ $slug ] = '1';
            }
        }
    }

    $wizard_flow = isset( $_POST['wizard_flow'] ) ? sanitize_key( (string) wp_unslash( $_POST['wizard_flow'] ) ) : 'prebuilt';
    if ( ! in_array( $wizard_flow, array( 'prebuilt', 'scratch' ), true ) ) {
        $wizard_flow = 'prebuilt';
    }

    $required_plugins = array();
    $selected_demo_slug = sanitize_key( (string) get_option( TMPCODER_PLUGIN_KEY . '_wizard_selected_demo', '' ) );
    if ( 'prebuilt' === $wizard_flow ) {
        $required_plugins = tmpcoder_wizard_get_selected_demo_required_plugins();
        if ( is_wp_error( $required_plugins ) ) {
            wp_send_json_error( array( 'message' => $required_plugins->get_error_message() ) );
        }
    }

    $mandatory_plugins     = tmpcoder_wizard_mandatory_feature_plugins();
    $mandatory_slugs       = array_keys( $mandatory_plugins );
    $feature_suggestions   = tmpcoder_wizard_get_dynamic_feature_suggestions( $required_plugins, $selected_demo_slug );
    $optional_plugins      = tmpcoder_wizard_optional_feature_plugins( $feature_suggestions );
    $selected_optional     = array();
    $excluded_required     = array( 'sastra-essential-addons-for-elementor' );
    $target_slugs          = array();
    $tgmpaClass            = $GLOBALS['tgmpa'];
    $tgmpa_plugins_by_slug = array();
    $error                 = array();
    $processed_slugs       = array();
    $plugin_results        = array();

    foreach ( $requested_plugins as $slug => $value ) {
        if ( isset( $optional_plugins[ $slug ] ) && '1' === $value ) {
            $selected_optional[] = $slug;
        }
    }

    $target_slugs = array_values(
        array_unique(
            array_merge(
                $mandatory_slugs,
                (array) $required_plugins,
                $selected_optional
            )
        )
    );

    $target_slugs = array_values(
        array_filter(
            $target_slugs,
            function( $slug ) use ( $excluded_required ) {
                $key = sanitize_key( (string) $slug );
                return '' !== $key && ! in_array( $key, $excluded_required, true );
            }
        )
    );

    if ( empty( $target_slugs ) ) {
        wp_send_json_error( array( 'message' => __( 'Please select at least one plugin.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    ob_start(); // default print off

    if ( is_object($tgmpaClass) ){
        if ( empty($tgmpaClass->plugins) ){
            $tmpcoder_mainClass = new Tmpcoder_Main_Class();
            $tmpcoder_mainClass->tmpcoder_require_plugins();
            $tgmpaClass = $GLOBALS['tgmpa'];
        }

        if ( !empty($tgmpaClass->plugins) ) {
            foreach( $tgmpaClass->plugins as $plugin ){
                $plugin_slug = isset( $plugin['slug'] ) ? sanitize_key( (string) $plugin['slug'] ) : '';
                if ( '' !== $plugin_slug ) {
                    $tgmpa_plugins_by_slug[ $plugin_slug ] = $plugin;
                }
            }
        }
    }

    foreach ( $target_slugs as $plugin_slug ) {
        $plugin_slug = sanitize_key( (string) $plugin_slug );
        if ( '' === $plugin_slug ) {
            continue;
        }

        $plugin_name      = ucwords( str_replace( '-', ' ', $plugin_slug ) );
        $plugin_file_path = '';

        if ( isset( $tgmpa_plugins_by_slug[ $plugin_slug ] ) ) {
            $plugin = $tgmpa_plugins_by_slug[ $plugin_slug ];
            if ( ! empty( $plugin['name'] ) ) {
                $plugin_name = sanitize_text_field( (string) $plugin['name'] );
            }
            if ( ! empty( $plugin['file_path'] ) ) {
                $plugin_file_path = sanitize_text_field( (string) $plugin['file_path'] );
            }
        } elseif ( isset( $optional_plugins[ $plugin_slug ] ) ) {
            $plugin_name = sanitize_text_field( (string) $optional_plugins[ $plugin_slug ]['name'] );
            $plugin_file_path = sanitize_text_field( (string) $optional_plugins[ $plugin_slug ]['file_path'] );
        } elseif ( isset( $mandatory_plugins[ $plugin_slug ] ) ) {
            $plugin_name = sanitize_text_field( (string) $mandatory_plugins[ $plugin_slug ]['name'] );
            $plugin_file_path = sanitize_text_field( (string) $mandatory_plugins[ $plugin_slug ]['file_path'] );
        }

        $resolved_file_path = plugin_basefile_path( $plugin_slug );
        if ( '' === $resolved_file_path && '' !== $plugin_file_path ) {
            $resolved_file_path = $plugin_file_path;
        }

        if ( '' !== $resolved_file_path && is_plugin_active( $resolved_file_path ) ) {
            $processed_slugs[] = $plugin_slug;
            $plugin_results[ $plugin_slug ] = array(
                'name'    => $plugin_name,
                'status'  => 'active',
                'message' => __( 'Plugin is already active.', 'sastra-essential-addons-for-elementor' ),
            );
            continue;
        }

        if ( '' !== $plugin_file_path && is_plugin_active( $plugin_file_path ) ) {
            $processed_slugs[] = $plugin_slug;
            $plugin_results[ $plugin_slug ] = array(
                'name'    => $plugin_name,
                'status'  => 'active',
                'message' => __( 'Plugin is already active.', 'sastra-essential-addons-for-elementor' ),
            );
            continue;
        }

        if ( '' !== $resolved_file_path && is_plugin_installed( $resolved_file_path ) ) {
            tmpcoder_update_plugin( $resolved_file_path );
            $installed = true;
        } elseif ( '' !== $plugin_file_path && is_plugin_installed( $plugin_file_path ) ) {
            tmpcoder_update_plugin( $plugin_file_path );
            $installed = true;
        } else {
            $installed = tmpcoder_install_plugin( $plugin_slug );
        }

        if ( is_wp_error( $installed ) || ! $installed ) {
            $resolved_after_fail = plugin_basefile_path( $plugin_slug );
            if ( '' !== $resolved_after_fail ) {
                if ( is_plugin_active( $resolved_after_fail ) ) {
                    $processed_slugs[] = $plugin_slug;
                    $plugin_results[ $plugin_slug ] = array(
                        'name'    => $plugin_name,
                        'status'  => 'active',
                        'message' => __( 'Plugin became active after install check.', 'sastra-essential-addons-for-elementor' ),
                    );
                    continue;
                }

                $activate_after_fail = activate_plugin( $resolved_after_fail );
                if ( ! is_wp_error( $activate_after_fail ) && is_plugin_active( $resolved_after_fail ) ) {
                    $processed_slugs[] = $plugin_slug;
                    $plugin_results[ $plugin_slug ] = array(
                        'name'    => $plugin_name,
                        'status'  => 'activated',
                        'message' => __( 'Plugin activated after install recovery.', 'sastra-essential-addons-for-elementor' ),
                    );
                    continue;
                }
            }

            $error[] = $plugin_name;
            $plugin_results[ $plugin_slug ] = array(
                'name'    => $plugin_name,
                'status'  => 'failed',
                'message' => __( 'Plugin installation failed.', 'sastra-essential-addons-for-elementor' ),
            );
            continue;
        }

        $resolved_file_path = plugin_basefile_path( $plugin_slug );
        if ( '' === $resolved_file_path && '' !== $plugin_file_path ) {
            $resolved_file_path = $plugin_file_path;
        }

        if ( '' === $resolved_file_path ) {
            $error[] = $plugin_name . ': ' . __( 'Plugin file not found after install.', 'sastra-essential-addons-for-elementor' );
            $plugin_results[ $plugin_slug ] = array(
                'name'    => $plugin_name,
                'status'  => 'failed',
                'message' => __( 'Plugin file not found after install.', 'sastra-essential-addons-for-elementor' ),
            );
            continue;
        }

        if ( is_plugin_active( $resolved_file_path ) ) {
            $processed_slugs[] = $plugin_slug;
            $plugin_results[ $plugin_slug ] = array(
                'name'    => $plugin_name,
                'status'  => 'active',
                'message' => __( 'Plugin is active.', 'sastra-essential-addons-for-elementor' ),
            );
            continue;
        }

        $activate = activate_plugin( $resolved_file_path );
        if ( is_wp_error( $activate ) ) {
            if ( is_plugin_active( $resolved_file_path ) ) {
                $processed_slugs[] = $plugin_slug;
                $plugin_results[ $plugin_slug ] = array(
                    'name'    => $plugin_name,
                    'status'  => 'active',
                    'message' => __( 'Plugin is active.', 'sastra-essential-addons-for-elementor' ),
                );
                continue;
            }
            $error[] = $plugin_name . ': ' . $activate->get_error_message();
            $plugin_results[ $plugin_slug ] = array(
                'name'    => $plugin_name,
                'status'  => 'failed',
                'message' => $activate->get_error_message(),
            );
            continue;
        }

        if ( is_plugin_active( $resolved_file_path ) ) {
            $processed_slugs[] = $plugin_slug;
            $plugin_results[ $plugin_slug ] = array(
                'name'    => $plugin_name,
                'status'  => 'activated',
                'message' => __( 'Plugin installed and activated.', 'sastra-essential-addons-for-elementor' ),
            );
        } else {
            $error[] = $plugin_name . ': ' . __( 'Activation verification failed.', 'sastra-essential-addons-for-elementor' );
            $plugin_results[ $plugin_slug ] = array(
                'name'    => $plugin_name,
                'status'  => 'failed',
                'message' => __( 'Activation verification failed.', 'sastra-essential-addons-for-elementor' ),
            );
        }
    }

    ob_end_clean();

    if ( empty($error) && count( array_unique( $processed_slugs ) ) === count( $target_slugs ) ){

        update_option(TMPCODER_PLUGIN_KEY.'_wizard_step', '2');

        wp_send_json_success(
            array(
                'message'   => '',
                'processed' => array_values( array_unique( $processed_slugs ) ),
                'target'    => $target_slugs,
                'failed'    => array(),
                'details'   => $plugin_results,
            )
        );

    }else{
        if ( empty( $error ) ) {
            $missing = array_diff( $target_slugs, $processed_slugs );
            if ( ! empty( $missing ) ) {
                $error[] = sprintf(
                    /* translators: %s plugin slugs */
                    __( 'Not processed: %s', 'sastra-essential-addons-for-elementor' ),
                    implode( ', ', $missing )
                );
            }
        }
        $error = implode(', ', $error). __(' Could not install','sastra-essential-addons-for-elementor');
        $failed = array_values( array_diff( $target_slugs, $processed_slugs ) );
        wp_send_json_error(
            array(
                'message'   => $error,
                'processed' => array_values( array_unique( $processed_slugs ) ),
                'target'    => $target_slugs,
                'failed'    => $failed,
                'details'   => $plugin_results,
            )
        );
    }
}

add_action( 'wp_ajax_tmpcoder_wizard_save_final_setup', 'tmpcoder_wizard_save_final_setup' );

/**
 * Save Final Setup form data to TemplatesCoder updates API (server-side), then the client starts import.
 *
 * Remote action name can be filtered with {@see 'tmpcoder_wizard_final_setup_remote_action'}.
 *
 * @return void
 */
function tmpcoder_wizard_save_final_setup() {

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_final_setup' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Security check failed. Please refresh the page and try again.', 'sastra-essential-addons-for-elementor' ),
			)
		);
	}

	if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Sorry, you are not allowed to perform this action.', 'sastra-essential-addons-for-elementor' ),
			)
		);
	}

	$name          = isset( $_POST['user_name'] ) ? sanitize_text_field( wp_unslash( $_POST['user_name'] ) ) : '';
	$email         = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
	$help_improve  = isset( $_POST['help_improve'] ) && (string) wp_unslash( $_POST['help_improve'] ) === '1';
	$keep_existing = isset( $_POST['keep_existing_data'] ) && (string) wp_unslash( $_POST['keep_existing_data'] ) === '1';

	if ( '' !== $email && ! is_email( $email ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Please enter a valid email address.', 'sastra-essential-addons-for-elementor' ),
			)
		);
	}

	$remote_action = apply_filters( 'tmpcoder_wizard_final_setup_remote_action', 'save_wizard_final_setup' );

	if ( ! defined( 'TMPCODER_UPDATES_URL' ) ) {
		wp_send_json_success(
			array(
				'remote_saved' => false,
				'message'      => __( 'Updates API URL is not configured.', 'sastra-essential-addons-for-elementor' ),
			)
		);
	}

	$body = array(
		'user_name'  => $name,
		'user_email' => $email,
	);

	if ( $help_improve ) {
		$demo_slug = sanitize_key( (string) get_option( TMPCODER_PLUGIN_KEY . '_wizard_selected_demo', '' ) );
		$body      = array_merge(
			$body,
			array(
				'help_improve'        => true,
				'keep_existing_data'  => $keep_existing,
				'demo_slug'           => $demo_slug,
				'site_url'            => home_url(),
				'wp_version'          => get_bloginfo( 'version' ),
				'wizard_entry_source' => function_exists( 'tmpcoder_detect_wizard_entry_source' ) ? tmpcoder_detect_wizard_entry_source() : '',
			)
		);
	}

	$body = apply_filters( 'tmpcoder_wizard_final_setup_remote_body', $body );

	$req_params = array(
		'action'         => $remote_action,
		'theme'          => defined( 'TMPCODER_CURRENT_THEME_NAME' ) ? TMPCODER_CURRENT_THEME_NAME : '',
		'version'        => defined( 'TMPCODER_CURRENT_THEME_VERSION' ) ? TMPCODER_CURRENT_THEME_VERSION : '',
		'plugin'         => 'sastra-essential-addons-for-elementor',
		'plugin_version' => defined( 'TMPCODER_PLUGIN_VER' ) ? TMPCODER_PLUGIN_VER : '',
	);

	$req_params = apply_filters( 'tmpcoder_wizard_final_setup_remote_query_args', $req_params );

	$api_url = add_query_arg( $req_params, TMPCODER_UPDATES_URL );

	$response = wp_safe_remote_request(
		$api_url,
		array(
			'headers'     => array(
				'Referer' => site_url(),
			),
			'body'        => wp_json_encode( $body ),
			'timeout'     => 15,
			'method'      => 'POST',
			'httpversion' => '1.1',
			'user-agent'  => 'templatescoder-user-agent',
		)
	);

	if ( is_wp_error( $response ) ) {
		wp_send_json_success(
			array(
				'remote_saved' => false,
				'message'      => $response->get_error_message(),
			)
		);
	}

	$code = wp_remote_retrieve_response_code( $response );
	$raw  = wp_remote_retrieve_body( $response );

	if ( $code < 200 || $code >= 300 ) {
		wp_send_json_success(
			array(
				'remote_saved' => false,
				/* translators: %s: HTTP status code */
				'message'      => sprintf( __( 'Remote API returned HTTP %s.', 'sastra-essential-addons-for-elementor' ), (string) $code ),
			)
		);
	}

	$remote_saved = true;
	if ( is_string( $raw ) && '' !== $raw ) {
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) && array_key_exists( 'success', $decoded ) ) {
			$remote_saved = ! empty( $decoded['success'] );
		}
	}

	wp_send_json_success(
		array(
			'remote_saved' => $remote_saved,
			'message'      => '',
		)
	);
}

add_action( 'wp_ajax_tmpcoder_wizard_get_analytics_optin', 'tmpcoder_wizard_get_analytics_optin' );
/**
 * Return site-wide analytics opt-in value.
 *
 * @return void
 */
function tmpcoder_wizard_get_analytics_optin() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_analytics' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    $value = get_site_option( TMPCODER_PLUGIN_KEY . '_usage_optin', 'no' );
    wp_send_json_success(
        array(
            'enabled' => ( 'yes' === $value ),
        )
    );
}

add_action( 'wp_ajax_tmpcoder_wizard_set_analytics_optin', 'tmpcoder_wizard_set_analytics_optin' );
/**
 * Save site-wide analytics opt-in value.
 *
 * @return void
 */
function tmpcoder_wizard_set_analytics_optin() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_analytics' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    $enabled = ! empty( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );
    update_site_option( TMPCODER_PLUGIN_KEY . '_usage_optin', $enabled ? 'yes' : 'no' );
    if ( ! $enabled ) {
        delete_site_transient( TMPCODER_PLUGIN_KEY . '_usage_track' );
    }

    wp_send_json_success(
        array(
            'enabled' => $enabled,
            'message' => __( 'Usage tracking updated successfully.', 'sastra-essential-addons-for-elementor' ),
        )
    );
}

add_action( 'wp_ajax_tmpcoder_wizard_get_whats_new_feed', 'tmpcoder_wizard_get_whats_new_feed' );
/**
 * Fetch and cache What's New items from remote Spexo changelog CPT.
 *
 * @return void
 */
function tmpcoder_wizard_get_whats_new_feed() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_whats_new' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    $feed_url = 'https://spexoaddons.com/changelog/';
    $api_base = 'https://spexoaddons.com/wp-json/wp/v2/';
    $post_type = 'sastra-changelog';
    $category_slug = 'spexo-addons';
    $category_class = 'sastra-changelog-category-' . sanitize_title( $category_slug );
    $cache_key = TMPCODER_PLUGIN_KEY . '_wizard_whats_new_cache';
    $cached = get_transient( $cache_key );
    if (
        is_array( $cached )
        && isset( $cached['items'] )
        && isset( $cached['source'] )
        && 'remote_sastra_changelog_v2' === $cached['source']
        && isset( $cached['category'] )
        && $category_slug === $cached['category']
    ) {
        wp_send_json_success( $cached );
    }

    $posts_request = wp_remote_get(
        add_query_arg(
            array(
                'per_page' => 20,
                'orderby'  => 'date',
                'order'    => 'desc',
                '_fields'  => 'title,link,date,class_list,excerpt,content',
            ),
            $api_base . $post_type
        ),
        array(
            'timeout' => 15,
        )
    );
    if ( is_wp_error( $posts_request ) ) {
        wp_send_json_error( array( 'message' => $posts_request->get_error_message() ) );
    }

    $posts_code = (int) wp_remote_retrieve_response_code( $posts_request );
    if ( 200 !== $posts_code ) {
        wp_send_json_error( array( 'message' => __( 'Could not fetch changelog posts.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    $posts_body = wp_remote_retrieve_body( $posts_request );
    $posts_data = json_decode( $posts_body, true );
    if ( ! is_array( $posts_data ) ) {
        wp_send_json_error( array( 'message' => __( 'Invalid changelog response.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    $items = array();
    foreach ( $posts_data as $post_row ) {
        $class_list = array();
        if ( isset( $post_row['class_list'] ) && is_array( $post_row['class_list'] ) ) {
            $class_list = $post_row['class_list'];
        }

        if ( empty( $class_list ) || ! in_array( $category_class, $class_list, true ) ) {
            continue;
        }

        $title = '';
        if ( isset( $post_row['title']['rendered'] ) ) {
            $title = sanitize_text_field(
                html_entity_decode(
                    wp_strip_all_tags( $post_row['title']['rendered'] ),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
        }
        $content_html = '';
        if ( isset( $post_row['excerpt']['rendered'] ) && is_string( $post_row['excerpt']['rendered'] ) && '' !== trim( $post_row['excerpt']['rendered'] ) ) {
            $content_html = wp_kses_post( $post_row['excerpt']['rendered'] );
        } elseif ( isset( $post_row['content']['rendered'] ) && is_string( $post_row['content']['rendered'] ) ) {
            $content_html = wp_kses_post( $post_row['content']['rendered'] );
        }
        $content_text = '';
        if ( '' !== $content_html ) {
            $content_text = sanitize_text_field(
                html_entity_decode(
                    wp_strip_all_tags( $content_html ),
                    ENT_QUOTES,
                    'UTF-8'
                )
            );
            if ( function_exists( 'mb_substr' ) ) {
                $content_text = mb_substr( $content_text, 0, 280 );
            } else {
                $content_text = substr( $content_text, 0, 280 );
            }
        }

        $raw_date = isset( $post_row['date'] ) ? sanitize_text_field( $post_row['date'] ) : '';
        $formatted_date = $raw_date ? gmdate( 'M j, Y', strtotime( $raw_date ) ) : '';

        $items[] = array(
            'title'        => $title,
            'link'         => isset( $post_row['link'] ) ? esc_url_raw( $post_row['link'] ) : esc_url_raw( $feed_url ),
            'date'         => $formatted_date,
            'content_html' => $content_html,
            'content_text' => $content_text,
        );

        if ( count( $items ) >= 6 ) {
            break;
        }
    }

    $payload = array(
        'feed_url'  => esc_url_raw( $feed_url ),
        'items'     => $items,
        'source'    => 'remote_sastra_changelog_v2',
        'category'  => $category_slug,
    );
    set_transient( $cache_key, $payload, 2 * HOUR_IN_SECONDS );
    wp_send_json_success( $payload );
}

add_action( 'wp_ajax_tmpcoder_wizard_sync_library', 'tmpcoder_wizard_sync_library' );
/**
 * Validate and refresh wizard library cache state.
 *
 * @return void
 */
function tmpcoder_wizard_sync_library() {
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tmpcoder_wizard_sync' ) ) {
        wp_send_json_error( array( 'message' => __( 'Security check failed.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => __( 'You are not allowed to do this.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    if ( ! class_exists( 'TMPCODER_Remote_Api' ) ) {
        $remote = TMPCODER_PLUGIN_DIR . 'inc/library/class-tmpcoder-plugin-remote-api.php';
        if ( file_exists( $remote ) ) {
            require_once $remote;
        }
    }

    if ( ! class_exists( 'TMPCODER_Remote_Api' ) ) {
        wp_send_json_error( array( 'message' => __( 'Could not load template API.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    delete_transient( TMPCODER_PLUGIN_KEY . '_wizard_whats_new_cache' );
    $import_demos_resp = TMPCODER_Remote_Api::get_prebuilt_demos();
    if (
        ! is_array( $import_demos_resp )
        || ! isset( $import_demos_resp['status'] )
        || 'success' !== $import_demos_resp['status']
        || ! isset( $import_demos_resp['data'] )
        || ! is_array( $import_demos_resp['data'] )
    ) {
        wp_send_json_error( array( 'message' => __( 'Could not sync template library.', 'sastra-essential-addons-for-elementor' ) ) );
    }

    wp_send_json_success(
        array(
            'count'   => count( $import_demos_resp['data'] ),
            'message' => __( 'Template library synced.', 'sastra-essential-addons-for-elementor' ),
        )
    );
}