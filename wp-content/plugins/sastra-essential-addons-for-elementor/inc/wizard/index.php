<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

require_once TMPCODER_PLUGIN_DIR . 'inc/wizard/wizard-functions.php';
require_once TMPCODER_PLUGIN_DIR . 'inc/wizard/wizard-ajax-api.php';

add_action('admin_enqueue_scripts', 'tmpcoder_enqueue_wizard_script');
/**
 * Detect plugin wizard entry source.
 *
 * @return string theme_wizard_redirect|direct_plugin_activation|manual_open
 */
function tmpcoder_detect_wizard_entry_source() {
    // Preferred explicit source marker.
    if ( isset( $_GET['source'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $source = sanitize_key( wp_unslash( $_GET['source'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( in_array( $source, array( 'theme_wizard_redirect', 'direct_plugin_activation', 'manual_open' ), true ) ) {
            return $source;
        }
    }

    // Activation redirect flow sets this option flag.
    $wizard_redirect = (int) get_option( TMPCODER_PLUGIN_KEY . '_wizard_page_redirect', 0 );
    if ( 1 === $wizard_redirect ) {
        return 'direct_plugin_activation';
    }

    // Fallback infer from referrer when available.
    $referer = wp_get_referer();
    if ( is_string( $referer ) && false !== strpos( $referer, 'tmpcoder-theme-wizard' ) ) {
        return 'theme_wizard_redirect';
    }

    return 'manual_open';
}

function tmpcoder_enqueue_wizard_script(){
    $current_screen = get_current_screen();
    if ( isset($current_screen->base) && $current_screen->base == 'admin_page_tmpcoder-setup-wizard' ){

        /**
         * Phase 6: Demo import runtime must load before wizard JS so `import_demo_content_start`
         * and `tmpcoder_ajax_object` exist for Step 4 import bridge.
         */
        if ( ! function_exists( 'tmpcoder_demo_import_scripts_func' ) ) {
            $import_bootstrap = TMPCODER_PLUGIN_DIR . 'inc/admin/import/tmpcoder-plugin-demo-list.php';
            if ( file_exists( $import_bootstrap ) ) {
                require_once $import_bootstrap;
            }
        }

        if ( function_exists( 'tmpcoder_demo_import_scripts_func' ) ) {
            tmpcoder_demo_import_scripts_func();
        }
        wp_enqueue_media();
        
        wp_enqueue_style( 'spexo-wizard-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', array(), tmpcoder_get_plugin_version() );

        wp_enqueue_style( 'dashicons' );

        wp_enqueue_style( 'tmpcoder-wizard-admin-css', TMPCODER_PLUGIN_URI . '/inc/wizard/css/wizard-style'.Theme_Setup_Wizard_Class::script_suffix().'.css', array( 'dashicons' ), tmpcoder_get_plugin_version() );

        $wizard_deps = array( 'jquery' );
        if ( wp_script_is( 'tmpcoder-plugin-import-demos', 'registered' ) || wp_script_is( 'tmpcoder-plugin-import-demos', 'enqueued' ) ) {
            $wizard_deps[] = 'tmpcoder-plugin-import-demos';
        }
        wp_enqueue_script( 'tmpcoder-wizard-admin-js', TMPCODER_PLUGIN_URI .'/inc/wizard/js/wizard'.Theme_Setup_Wizard_Class::script_suffix().'.js', $wizard_deps, tmpcoder_get_plugin_version(), true );

        $current_theme = (is_object(wp_get_theme()->parent())) ? wp_get_theme()->parent() : wp_get_theme();

        $theme_ready  = wp_get_theme('spexo')->exists() && $current_theme->get_stylesheet() === 'spexo';
        $entry_source = tmpcoder_detect_wizard_entry_source();

        wp_localize_script(
            'tmpcoder-wizard-admin-js',
            'tmpcoderMessages',
            array(
                'theme_active' => $theme_ready ? true : false,
                'theme_ready' => $theme_ready ? true : false,
                'entry_source' => $entry_source,
                'wizard_step' => get_option(TMPCODER_PLUGIN_KEY.'_wizard_step'),
                'form_nonce'  => wp_nonce_field( 'tmpcoder_install_plugins'),
                'get_plugin_nonce'  => wp_create_nonce( 'tmpcoder_get_plugins'),
                'get_pro_addons_info_nonce'  => wp_create_nonce( 'tmpcoder_get_pro_addons_info'),
                'theme_install_nonce' => wp_create_nonce( 'tmpcoder_install_theme' ),
                'wizard_demos_nonce' => wp_create_nonce( 'tmpcoder_wizard_demos' ),
                'wizard_analytics_nonce' => wp_create_nonce( 'tmpcoder_wizard_analytics' ),
                'wizard_whats_new_nonce' => wp_create_nonce( 'tmpcoder_wizard_whats_new' ),
                'wizard_sync_nonce' => wp_create_nonce( 'tmpcoder_wizard_sync' ),
                'final_setup_nonce' => wp_create_nonce( 'tmpcoder_wizard_final_setup' ),
                'wizard_branding_nonce' => wp_create_nonce( 'tmpcoder_wizard_branding' ),
                'wizard_final_saving' => esc_html__( 'Saving your details…', 'sastra-essential-addons-for-elementor' ),
                'wizard_final_remote_failed' => esc_html__( 'We could not save your details to our server, but your site import will continue.', 'sastra-essential-addons-for-elementor' ),
                'purchase_pro_url' => esc_url( TMPCODER_PURCHASE_PRO_URL . '?ref=tmpcoder-setup-wizard-demo' ),
                'prebuilt_theme_installing' => esc_html__( 'Installing Spexo theme…', 'sastra-essential-addons-for-elementor' ),
                'theme_already_active' => esc_html__( 'Spexo theme is already active.', 'sastra-essential-addons-for-elementor' ),
                'loading_prebuilt_demos' => esc_html__( 'Loading Prebuilt Websites…', 'sastra-essential-addons-for-elementor' ),
                'choose_prebuilt_title' => esc_html__( 'Choose a Prebuilt Website', 'sastra-essential-addons-for-elementor' ),
                'choose_prebuilt_subtitle' => esc_html__( 'Select a template to start with. You can customize everything later.', 'sastra-essential-addons-for-elementor' ),
                'search_templates_placeholder' => esc_html__( 'Search templates…', 'sastra-essential-addons-for-elementor' ),
                'wizard_demo_continue' => esc_html__( 'Continue', 'sastra-essential-addons-for-elementor' ),
                'wizard_demo_back' => esc_html__( 'Back', 'sastra-essential-addons-for-elementor' ),
                'wizard_demo_select_first' => esc_html__( 'Please select a template to continue.', 'sastra-essential-addons-for-elementor' ),
                'wizard_demo_none_unlocked' => esc_html__( 'No templates available for your license. Upgrade to unlock more sites.', 'sastra-essential-addons-for-elementor' ),
                'wizard_demo_new_badge' => esc_html__( 'New', 'sastra-essential-addons-for-elementor' ),
                'wizard_demo_new_badge_icon' => esc_url( add_query_arg( 'ver', rawurlencode( tmpcoder_get_plugin_version() ), TMPCODER_PLUGIN_URI . '/assets/images/new-tag-icon.png' ) ),
                'wizard_plugin_icons' => array(
                    'default' => esc_url( add_query_arg( 'ver', rawurlencode( tmpcoder_get_plugin_version() ), TMPCODER_PLUGIN_URI . '/assets/images/plugin-icons/plugin.svg' ) ),
                    'elementor' => esc_url( add_query_arg( 'ver', rawurlencode( tmpcoder_get_plugin_version() ), TMPCODER_PLUGIN_URI . '/assets/images/plugin-icons/elementor.svg' ) ),
                    'woocommerce' => esc_url( add_query_arg( 'ver', rawurlencode( tmpcoder_get_plugin_version() ), TMPCODER_PLUGIN_URI . '/assets/images/plugin-icons/woocommerce.svg' ) ),
                    'variation-swatches-woo' => esc_url( add_query_arg( 'ver', rawurlencode( tmpcoder_get_plugin_version() ), TMPCODER_PLUGIN_URI . '/assets/images/plugin-icons/variation-swatches-woo.svg' ) ),
                ),
                'wizard_demo_pro_badge' => esc_html__( 'Premium', 'sastra-essential-addons-for-elementor' ),
                'wizard_whats_new_feed_url' => esc_url_raw( 'https://spexoaddons.com/changelog/' ),
                'wizard_analytics_title' => esc_html__( 'Share Anonymous Usage Data', 'sastra-essential-addons-for-elementor' ),
                'wizard_analytics_desc' => esc_html__( 'Help us improve templates and setup flow by sharing non-sensitive usage analytics.', 'sastra-essential-addons-for-elementor' ),
                'wizard_analytics_learn_more' => esc_url( trailingslashit( TMPCODER_PLUGIN_SITE_URL ) . 'usage-tracking/' ),
                'wizard_analytics_saved' => esc_html__( 'Usage tracking updated successfully.', 'sastra-essential-addons-for-elementor' ),
                'wizard_whats_new_title' => esc_html__( "What's New", 'sastra-essential-addons-for-elementor' ),
                'wizard_favorites_empty' => esc_html__( 'No favorite templates found for your account.', 'sastra-essential-addons-for-elementor' ),
                'wizard_favorite_add' => esc_html__( 'Add to Favorite', 'sastra-essential-addons-for-elementor' ),
                'wizard_favorite_remove' => esc_html__( 'Remove from Favorite', 'sastra-essential-addons-for-elementor' ),
                'wizard_sync_success' => esc_html__( 'Template library synced successfully.', 'sastra-essential-addons-for-elementor' ),
                'wizard_sync_loading_title' => esc_html__( 'Syncing Templates Library...', 'sastra-essential-addons-for-elementor' ),
                'wizard_sync_loading_desc' => esc_html__( 'Updating the library to include all the latest templates.', 'sastra-essential-addons-for-elementor' ),
                'wizard_terms_url' => esc_url( trailingslashit( TMPCODER_PLUGIN_SITE_URL ) . 'terms-conditions/' ),
                'wizard_privacy_policy_url' => esc_url( trailingslashit( TMPCODER_PLUGIN_SITE_URL ) . 'privacy-policy/' ),
                'wizard_terms_label' => esc_html__( 'Terms', 'sastra-essential-addons-for-elementor' ),
                'wizard_privacy_policy_label' => esc_html__( 'Privacy Policy', 'sastra-essential-addons-for-elementor' ),
                'wizard_saving_demo_choice' => esc_html__( 'Saving your choice…', 'sastra-essential-addons-for-elementor' ),
                'wizard_saving_branding_choice' => esc_html__( 'Saving your branding settings…', 'sastra-essential-addons-for-elementor' ),
                'wizard_demo_upgrade' => esc_html__( 'Upgrade to PRO', 'sastra-essential-addons-for-elementor' ),
                'wizard_demo_preview' => esc_html__( 'Preview', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_logo_title' => esc_html__( 'Select Custom Logo', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_logo_subtitle' => esc_html__( 'Upload your logo to personalize the selected template.', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_logo_upload' => esc_html__( 'Upload File Here', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_logo_change' => esc_html__( 'Change Logo', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_logo_remove' => esc_html__( 'Remove Logo', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_logo_width' => esc_html__( 'Set Logo Width', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_fonts_title' => esc_html__( 'Font Pair', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_body_font' => esc_html__( 'Body Font', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_heading_font' => esc_html__( 'Heading Font', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_font_default' => esc_html__( 'Default', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_live_preview' => esc_html__( 'Live Preview', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_back' => esc_html__( 'Back', 'sastra-essential-addons-for-elementor' ),
                'wizard_branding_continue' => esc_html__( 'Continue', 'sastra-essential-addons-for-elementor' ),
                'wizard_font_options' => array(
                    array( 'family' => 'Inter', 'label' => 'Inter', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'Poppins', 'label' => 'Poppins', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'Roboto', 'label' => 'Roboto', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'Open Sans', 'label' => 'Open Sans', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'EB Garamond', 'label' => 'EB Garamond', 'category' => 'serif', 'weight' => '400' ),
                    array( 'family' => 'IBM Plex Sans', 'label' => 'IBM Plex Sans', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'Lora', 'label' => 'Lora', 'category' => 'serif', 'weight' => '400' ),
                    array( 'family' => 'Montserrat', 'label' => 'Montserrat', 'category' => 'sans-serif', 'weight' => '500' ),
                    array( 'family' => 'Lato', 'label' => 'Lato', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'Nunito', 'label' => 'Nunito', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'Merriweather', 'label' => 'Merriweather', 'category' => 'serif', 'weight' => '400' ),
                    array( 'family' => 'Playfair Display', 'label' => 'Playfair Display', 'category' => 'serif', 'weight' => '500' ),
                    array( 'family' => 'Source Sans 3', 'label' => 'Source Sans 3', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'PT Sans', 'label' => 'PT Sans', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'Ubuntu', 'label' => 'Ubuntu', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'Work Sans', 'label' => 'Work Sans', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'Raleway', 'label' => 'Raleway', 'category' => 'sans-serif', 'weight' => '500' ),
                    array( 'family' => 'Oswald', 'label' => 'Oswald', 'category' => 'sans-serif', 'weight' => '500' ),
                    array( 'family' => 'Fira Sans', 'label' => 'Fira Sans', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'DM Sans', 'label' => 'DM Sans', 'category' => 'sans-serif', 'weight' => '400' ),
                    array( 'family' => 'Manrope', 'label' => 'Manrope', 'category' => 'sans-serif', 'weight' => '500' ),
                    array( 'family' => 'Rubik', 'label' => 'Rubik', 'category' => 'sans-serif', 'weight' => '400' ),
                ),
                'ok_text'     => esc_html("OK",'sastra-essential-addons-for-elementor'),
                "next_step_btn" => esc_html("Next Step",'sastra-essential-addons-for-elementor'),
                'site_setting_saving' => esc_html("Theme Installing...",'sastra-essential-addons-for-elementor'),
                'required_plugin_installing' => esc_html("Required Plugin Installing",'sastra-essential-addons-for-elementor'),
                'getting_required_plugins' => esc_html("Required Plugin Info Getting",'sastra-essential-addons-for-elementor'),
                'loading_license_form' => esc_html("Spexo Addons Pro Info Getting...",'sastra-essential-addons-for-elementor'),
                'install_required_plugins' => esc_html("Install Required Plugins",'sastra-essential-addons-for-elementor'),
                'install_required_plugins_text' => sprintf("Make sure %s is running the most recent version. %s is designed to work with the required plugins listed below.", esc_html(TMPCODER_PLUGIN_NAME), esc_html(TMPCODER_PLUGIN_NAME)),
                'install_and_activate' => esc_html("Install & Activate",'sastra-essential-addons-for-elementor'),
                'installed_and_activate' => esc_html("Installed & Activate",'sastra-essential-addons-for-elementor'),
                'installed_and_activated'  => esc_html("Activated",'sastra-essential-addons-for-elementor'),
                "congrats_message" => esc_html("The Setup Wizard has completed setting up your website successfully. Now, it's time for you to edit your website and explore its prominent features.",'sastra-essential-addons-for-elementor'),
                "network_error" => esc_html("check network connection, try again.",'sastra-essential-addons-for-elementor'),
                'license_error' => esc_html('License register getting error, try again. click on "License Activation" Tab','sastra-essential-addons-for-elementor'),
                'finish_setup_url' => esc_url( admin_url( 'admin.php?page=tmpcoder-finish-setup' ) ),
                'scratch_dashboard_url' => esc_url( admin_url( 'admin.php?page=spexo-welcome' ) ),
            )        
        );
    }
}

class Theme_Setup_Wizard_Class {

    /**
     * @var Theme_Setup_Wizard_Class
     */
    private static $_instance;

    /**
     * @return Theme_Setup_Wizard_Class
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    function __construct(){
        add_action( 'admin_menu', [$this, 'register_newpage'] );
        add_action( 'admin_notices', [$this, 'wizard_admin_notice_success'] );
        add_filter( 'admin_body_class', array( $this, 'admin_body_class_wizard_fullscreen' ) );
        add_action( 'admin_head', array( $this, 'wizard_admin_head_fullscreen' ), 1 );
    }

    public function wizard_admin_head_fullscreen() {
        $screen = get_current_screen();
        if ( ! $screen || 'admin_page_tmpcoder-setup-wizard' !== $screen->base ) {
            return;
        }
        echo '<style id="spexo-plugin-wizard-fullscreen-html">html.wp-toolbar{margin-top:0!important;padding-top:0!important;}</style>';
    }

    public function admin_body_class_wizard_fullscreen( $classes ) {
        $screen = get_current_screen();
        if ( ! $screen || 'admin_page_tmpcoder-setup-wizard' !== $screen->base ) {
            return $classes;
        }
        return $classes . ' spexo-plugin-wizard-fullscreen';
    }

    public static function script_suffix() {
        return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
    }

    function wizard_admin_notice_success() {
        if ( isset($_GET['saved']) && $_GET['saved'] == "plugin-wizard" ){ // phpcs:ignore WordPress.Security.NonceVerification.Recommended 
            delete_option(TMPCODER_PLUGIN_KEY.'_wizard_step');
            update_option(TMPCODER_PLUGIN_KEY.'_wizard_done', 1);
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Congrats, The Setup Wizard has successfully set up your website.', 'sastra-essential-addons-for-elementor' ); ?></p>
            </div>
            <?php
        }
    }

    function register_newpage(){
        add_submenu_page('tmpcoder-setup-wizard', 'Wizard', 'Setup Wizard', 'manage_options', 'tmpcoder-setup-wizard', [$this, 'wps_theme_func']);
    }
    
    function wps_theme_func(){
           
        $theme_slug = 'spexo';
        $current_theme = (is_object(wp_get_theme()->parent())) ? wp_get_theme()->parent() : wp_get_theme();

        $theme_next_label = __('Next', 'sastra-essential-addons-for-elementor');
        $theme_info = tmpcoder_get_theme_info($theme_slug);
        $theme_activated = 0;
        $theme_installed = 0;
        if ( wp_get_theme($theme_slug)->exists() && $current_theme->get_stylesheet() !== $theme_slug ){
            $theme_installed = 1;
            $theme_next_label = __('Activate', 'sastra-essential-addons-for-elementor');
        }else if( wp_get_theme($theme_slug)->exists() && $current_theme->get_stylesheet() == $theme_slug ){
            $theme_activated = 1;
        }else{
            $theme_next_label = __('Install and Activate', 'sastra-essential-addons-for-elementor');
        }
        
        ?>
        <div class="wrap tmpcoder-container tmpcoder-plugin-wizard spexo-plugin-wizard">
            <hr class="wp-header-end">            
            <header class="spexo-plugin-wizard-header">
                <div class="spexo-plugin-wizard-header__brand">
                    <img src="<?php echo esc_url( add_query_arg( 'ver', rawurlencode( tmpcoder_get_plugin_version() ), TMPCODER_PLUGIN_URI . '/assets/images/favicon.svg' ) ); ?>" alt="" class="spexo-plugin-wizard-header__mark-img" loading="eager" />
                    <span class="spexo-plugin-wizard-header__title"><?php esc_html_e( 'Spexo', 'sastra-essential-addons-for-elementor' ); ?></span>
                </div>
                <div class="spexo-plugin-wizard-header__actions">
                    <div class="spexo-plugin-wizard-header__utilities" aria-label="<?php esc_attr_e( 'Wizard tools', 'sastra-essential-addons-for-elementor' ); ?>">
                        <?php
                        /*
                        <button type="button" class="spexo-plugin-wizard-header__utility is-analytics" data-utility="analytics" title="<?php esc_attr_e( 'Analytics', 'sastra-essential-addons-for-elementor' ); ?>" aria-label="<?php esc_attr_e( 'Analytics', 'sastra-essential-addons-for-elementor' ); ?>">
                            <span class="dashicons dashicons-chart-pie" aria-hidden="true"></span>
                        </button>
                        */
                        ?>
                        <button type="button" class="spexo-plugin-wizard-header__utility is-whats-new" data-utility="whats-new" title="<?php esc_attr_e( "What's New", 'sastra-essential-addons-for-elementor' ); ?>" aria-label="<?php esc_attr_e( "What's New", 'sastra-essential-addons-for-elementor' ); ?>">
                            <span class="dashicons dashicons-megaphone" aria-hidden="true"></span>
                        </button>
                        <button type="button" class="spexo-plugin-wizard-header__utility is-favorites" data-utility="favorites" title="<?php esc_attr_e( 'My Favorite', 'sastra-essential-addons-for-elementor' ); ?>" aria-label="<?php esc_attr_e( 'My Favorite', 'sastra-essential-addons-for-elementor' ); ?>">
                            <span class="dashicons dashicons-heart" aria-hidden="true"></span>
                        </button>
                        <button type="button" class="spexo-plugin-wizard-header__utility is-sync" data-utility="sync" title="<?php esc_attr_e( 'Sync Library', 'sastra-essential-addons-for-elementor' ); ?>" aria-label="<?php esc_attr_e( 'Sync Library', 'sastra-essential-addons-for-elementor' ); ?>">
                            <span class="dashicons dashicons-update" aria-hidden="true"></span>
                        </button>
                    </div>
                    <a href="<?php echo esc_url( admin_url() ); ?>" class="tmpcoder-skip-wizard-link spexo-plugin-wizard-header__skip" data-url="<?php echo esc_url( admin_url() ); ?>">
                        <?php esc_html_e( 'Skip Setup', 'sastra-essential-addons-for-elementor' ); ?>
                    </a>
                </div>
            </header>

            <div class="theme-wizard-main">
                <ul class="nav-tab-wrapper theme-wizard-nav wp-clearfix <?php echo $theme_activated ? 'theme-active':'' ?> ">
                    <?php 
                    $wizard_steps = array();
                    $wizard_steps[1] = '<li class="nav-tab theme-installation" data-tab="theme-installation">
                        <span class="step-number">1</span>'.esc_html('Install Theme', 'sastra-essential-addons-for-elementor').'
                    </li>';                    

                    // $wizard_steps[2] = '<li class="nav-tab select-editor disabled" data-tab="select-editor">
                    // <span class="step-number">2</span>'.esc_html('Select Page Builder', 'sastra-essential-addons-for-elementor').'
                    // </li>';       

                    $wizard_steps[3] = '<li class="nav-tab customize-branding disabled" data-tab="customize-branding">
                        <span class="step-number">2</span>'.esc_html('Customize Branding', 'sastra-essential-addons-for-elementor').'
                    </li>';

                    $wizard_steps[4] = '<li class="nav-tab install-plugins disabled" data-tab="install-plugins">
                        <span class="step-number">3</span>'.esc_html('Install Required Plugins', 'sastra-essential-addons-for-elementor').
                    '</li>';

                    $wizard_steps[5] = '<li class="nav-tab final-step disabled" data-tab="final-step">
                        <span class="step-number">4</span>'.esc_html('Final Setup', 'sastra-essential-addons-for-elementor').
                    '</li>';

                    $wizard_steps[6] = '<li class="nav-tab import-progress disabled" data-tab="import-progress">
                        <span class="step-number">5</span>'.esc_html('Import Progress', 'sastra-essential-addons-for-elementor').
                    '</li>';

                    foreach ($wizard_steps as $wizard_key => $wizard_value) {
                        echo wp_kses_post($wizard_value);
                    }

                    ?>
                                    
                </ul>
                <div id="theme-installation" class="tab-content tab-content-theme-installation">
                
                    <div class="tmpcoder-message-box theme-install-part spexo-plugin-step1-wrap">
                        <div class="spexo-plugin-step1" id="spexo-wizard-path-cards">
                            <h2 class="spexo-plugin-step1__section-title"><?php esc_html_e( 'Start building your website your way', 'sastra-essential-addons-for-elementor' ); ?></h2>
                            <p class="spexo-plugin-step1__section-subtitle"><?php esc_html_e( 'Unlock premium design control, faster implementation, and advanced customization tools to create a sophisticated website with ease.', 'sastra-essential-addons-for-elementor' ); ?></p>
                            <div class="spexo-plugin-step1__cards">
                                <button type="button" class="spexo-plugin-card spexo-plugin-card--prebuilt" data-choice="prebuilt">
                                    <span class="spexo-plugin-card__radio" aria-hidden="true"></span>
                                    <span class="spexo-plugin-card__icon" aria-hidden="true"><span class="dashicons dashicons-screenoptions"></span></span>
                                    <strong class="spexo-plugin-card__title"><?php esc_html_e( 'Install Prebuilt Website', 'sastra-essential-addons-for-elementor' ); ?></strong>
                                    <span class="spexo-plugin-card__text"><?php esc_html_e( "Start with a professionally designed template. We'll import the layout, images, and settings so you can launch faster.", 'sastra-essential-addons-for-elementor' ); ?></span>
                                    <span class="spexo-plugin-card__link"><?php esc_html_e( 'Get Started', 'sastra-essential-addons-for-elementor' ); ?> <!-- <span class="spexo-plugin-card__link-arrow" aria-hidden="true">&rarr;</span> --> <span class="dashicons dashicons-arrow-right-alt spexo-plugin-card__link-arrow" aria-hidden="true"></span></span>
                                </button>
                                <button type="button" class="spexo-plugin-card spexo-plugin-card--scratch" data-choice="scratch">
                                    <span class="spexo-plugin-card__radio" aria-hidden="true"></span>
                                    <span class="spexo-plugin-card__icon" aria-hidden="true"><span class="dashicons dashicons-edit"></span></span>
                                    <strong class="spexo-plugin-card__title"><?php esc_html_e( 'Craft From Scratch', 'sastra-essential-addons-for-elementor' ); ?></strong>
                                    <span class="spexo-plugin-card__text"><?php esc_html_e( "Build your website from the ground up. We'll install the essential plugins and take you straight to the site builder.", 'sastra-essential-addons-for-elementor' ); ?></span>
                                    <span class="spexo-plugin-card__link"><?php esc_html_e( 'Start Building', 'sastra-essential-addons-for-elementor' ); ?> <!-- <span class="spexo-plugin-card__link-arrow" aria-hidden="true">&rarr;</span> --> <span class="dashicons dashicons-arrow-right-alt spexo-plugin-card__link-arrow" aria-hidden="true"></span>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="spexo-wizard-demo-step" id="spexo-wizard-demo-step" hidden>
                            <div class="spexo-wizard-demo-step__toolbar">
                                <div class="spexo-wizard-demo-step__intro">
                                    <h2 class="spexo-wizard-demo-step__title"><?php esc_html_e( 'Choose a Prebuilt Website', 'sastra-essential-addons-for-elementor' ); ?></h2>
                                    <p class="spexo-wizard-demo-step__subtitle"><?php esc_html_e( 'Select a template to start with. You can customize everything later.', 'sastra-essential-addons-for-elementor' ); ?></p>
                                </div>
                                <div class="spexo-wizard-demo-step__controls">
                                    <div class="spexo-wizard-demo-step__search-wrap">
                                        <label for="spexo-wizard-demo-search" class="screen-reader-text"><?php esc_html_e( 'Search Websites', 'sastra-essential-addons-for-elementor' ); ?></label>
                                        <span class="spexo-wizard-demo-step__search-icon dashicons dashicons-search" aria-hidden="true"></span>
                                        <input type="search" id="spexo-wizard-demo-search" class="spexo-wizard-demo-step__search" autocomplete="off" placeholder="<?php esc_attr_e( 'Search Websites…', 'sastra-essential-addons-for-elementor' ); ?>" />
                                    </div>
                                    <div class="spexo-wizard-demo-step__price-filter">
                                        <label for="spexo-wizard-demo-price" class="screen-reader-text"><?php esc_html_e( 'Filter by price', 'sastra-essential-addons-for-elementor' ); ?></label>
                    
                                        <select id="spexo-wizard-demo-price" class="spexo-wizard-demo-step__price-select">
                                        <option value="all"><?php esc_html_e( 'Price: All', 'sastra-essential-addons-for-elementor' ); ?></option>
                                            <option value="free"><?php esc_html_e( 'Free', 'sastra-essential-addons-for-elementor' ); ?></option>
                                            <option value="pro"><?php esc_html_e( 'Pro', 'sastra-essential-addons-for-elementor' ); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div id="spexo-wizard-demo-grid" class="spexo-wizard-demo-step__grid" role="list"></div>
                            <p id="spexo-wizard-demo-empty" class="spexo-wizard-demo-step__empty" hidden><?php esc_html_e( 'No templates match your search.', 'sastra-essential-addons-for-elementor' ); ?></p>
                            <p id="spexo-wizard-demo-error" class="spexo-wizard-demo-step__error" hidden></p>
                            <div class="spexo-wizard-demo-step__footer">
                                <div class="spexo-wizard-demo-step__footer-buttons">
                                    <button type="button" class="button button-link spexo-wizard-demo-back">
                                        <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                                        <span class="spexo-wizard-demo-btn__label"><?php esc_html_e( 'Back', 'sastra-essential-addons-for-elementor' ); ?></span>
                                    </button>
                                    <button type="button" class="button button-primary spexo-wizard-demo-continue" disabled>
                                        <span class="spexo-wizard-demo-btn__label"><?php esc_html_e( 'Continue', 'sastra-essential-addons-for-elementor' ); ?></span>
                                        <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="customize-branding" class="tab-content tab-content-customize-branding">
                            <div class="tmpcoder-message-box"></div>
                        </div>
                        <div id="install-plugins" class="tab-content tab-content-install-plugins">
                            <div class="tmpcoder-message-box install-plugin-part">
                            </div>
                        </div>
                        <div id="final-step" class="tab-content tab-content-final-step">
                            <div class="tmpcoder-message-box">

                            </div>
                        </div>
                        <div id="import-progress" class="tab-content tab-content-import-progress">
                            <div class="tmpcoder-message-box">
                                <div class="spexo-wizard-import-progress-step" data-state="idle">
                                    <h2 class="wizard-heading spexo-wizard-import-progress-step__title"><?php esc_html_e( 'We are building your website...', 'sastra-essential-addons-for-elementor' ); ?></h2>
                                    <p class="wizard-title-text spexo-wizard-import-progress-step__subtitle"><?php esc_html_e( 'Import process has not started yet.', 'sastra-essential-addons-for-elementor' ); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="process-loader hide">
                    <span class="loader-image"></span>
                    <span class="loader-text"></span>
                </div>
                <div class="spexo-wizard-sync-overlay" hidden>
                    <div class="spexo-wizard-sync-overlay__card" role="status" aria-live="polite">
                        <span class="spexo-wizard-sync-overlay__spinner" aria-hidden="true"></span>
                        <h4 class="spexo-wizard-sync-overlay__title"><?php esc_html_e( 'Syncing Prebuilt Websites...', 'sastra-essential-addons-for-elementor' ); ?></h4>
                        <p class="spexo-wizard-sync-overlay__desc"><?php esc_html_e( 'Updating the library to include all the latest prebuilt websites.', 'sastra-essential-addons-for-elementor' ); ?></p>
                    </div>
                </div>
                <div class="spexo-wizard-sync-toast" hidden>
                    <span class="spexo-wizard-sync-toast__icon dashicons dashicons-yes-alt" aria-hidden="true"></span>
                    <span class="spexo-wizard-sync-toast__text"></span>
                    <button type="button" class="spexo-wizard-sync-toast__close" aria-label="<?php esc_attr_e( 'Close', 'sastra-essential-addons-for-elementor' ); ?>">
                        <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="tmpcoder-skip-wizard-popup-wrap tmpcoder-admin-popup-wrap">
            <div class="tmpcoder-skip-wizard-popup tmpcoder-admin-popup">
                <div id="tmpcoder-skip-wizard-confirm-popup" class="mfp-hide">
                    <h2 class="popup-heading"> <?php esc_html_e('Skip the Setup Wizard?','sastra-essential-addons-for-elementor') ?> </h2>
                    <div class="popup-content">
                        <p class="popup-message"><?php echo wp_kses_post(__('Heads up! <strong>This action is non-reversible</strong> and you won’t be able to access the setup wizard again. Are you sure you want to skip setup wizard?', 'sastra-essential-addons-for-elementor')); ?></p>
                        <a class="button button-primary popup-close"><?php esc_html_e('Continue Setup', 'sastra-essential-addons-for-elementor') ?></a>
                        <a class="button button-secondary tmpcoder-skip-wizard-confirm-button"><?php esc_html_e('Yes, Skip', 'sastra-essential-addons-for-elementor') ?></a>
                    </div>
                </div>
            </div>
        </div>

        <?php 
    }    
}

new Theme_Setup_Wizard_Class();
