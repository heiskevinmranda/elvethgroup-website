<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
    
require_once (TMPCODER_PLUGIN_DIR . 'inc/admin/includes/tmpcoder-templates-loop.php');

// Register Menus
function tmpcoder_addons_add_theme_builder_menu() {
	add_submenu_page( TMPCODER_THEME.'-welcome', 'Site Builder', 'Site Builder', 'manage_options', 'spexo-welcome&tab=site-builder', 'tmpcoder_addons_theme_builder_page', 20 );

	add_submenu_page( TMPCODER_THEME.'-welcome', 'Popup Builder', 'Popup Builder', 'manage_options', 'spexo-welcome&tab=popup-builder', 'tmpcoder_popup_builder_page', 20 );
}

add_action( 'admin_menu', 'tmpcoder_addons_add_theme_builder_menu', 99 );

/**
 * WPML translate templates link (matches tmpcoder-prebuilt-demo-doc-link button style).
 *
 * @param string $url Admin URL for filtered templates list.
 */
function tmpcoder_render_wpml_translate_templates_link( $url ) {
	?>
	<div class="tmpcoder-prebuilt-demo-doc-link tmpcoder-translate-templates">
		<a href="<?php echo esc_url( $url ); ?>" class="btn-link">
			<i class="dashicons dashicons-admin-site" aria-hidden="true"></i>
			<?php esc_html_e( 'Translate WPML Templates', 'sastra-essential-addons-for-elementor' ); ?>
		</a>
	</div>
	<?php
}

function tmpcoder_addons_theme_builder_page() {
 
?>

<div class="wrap tmpcoder-settings-page-wrap">

    <div class="tmpcoder-settings-page tmpcoder-site-builder-page">
        <form method="post" action="options.php">
            <?php

            // Active Tab
            $active_tab = isset( $_GET['layout_type'] ) ? sanitize_text_field( wp_unslash( $_GET['layout_type'] ) ) : 'type_header';// phpcs:ignore WordPress.Security.NonceVerification.Recommended

            ?>

            <!-- Template ID Holder -->
            <input type="hidden" name="tmpcoder_template" id="tmpcoder_template" value="">


            <div class="change-conditions-popup">
                <!-- Conditions Popup -->
                <?php TMPCODER_Templates_Loop::render_conditions_popup(true); ?>
            </div>

            <!-- Create Templte Popup -->
            <?php TMPCODER_Templates_Loop::render_create_template_popup(); ?>

            <?php TMPCODER_Templates_Loop::render_delete_template_confirm_popup(); ?>

            <!-- Tabs -->
            <div class="site-builder-main common-box-shadow tmpcoder-layout-tabs">
                <header>
                    <div class="tmpcoder-import-demo-left">
                        <div class="tmpcoder-import-demo-logo">
                            <h1><?php esc_html_e('Site Builder', 'sastra-essential-addons-for-elementor'); ?></h1>
                            <div class="tmpcoder-prebuilt-demo-doc-link">
                                <a href="<?php echo esc_url(TMPCODER_DOCUMENTATION_URL . 'site-builder-overview'); ?>" target="_blank" class="btn-link">
                                    <i class="dashicons dashicons-external"></i>
                                    <?php esc_html_e('How to use Site Builder', 'sastra-essential-addons-for-elementor'); ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="tmpcoder-import-demo-right">

                    </div>
                </header>
                <div class="nav-tab-wrapper tmpcoder-nav-tab-wrapper">
                    <a href="?page=spexo-welcome&tab=site-builder&layout_type=type_header" data-title="type_header" class="nav-tab <?php echo ($active_tab == 'type_header') ? 'nav-tab-active' : ''; ?>">
                        <?php esc_html_e( 'Header', 'sastra-essential-addons-for-elementor' ); ?>
                    </a>
                    <a href="?page=spexo-welcome&tab=site-builder&layout_type=type_footer" data-title="type_footer" class="nav-tab <?php echo ($active_tab == 'type_footer') ? 'nav-tab-active' : ''; ?>">
                        <?php esc_html_e( 'Footer', 'sastra-essential-addons-for-elementor' ); ?>
                    </a>
                    <a href="?page=spexo-welcome&tab=site-builder&layout_type=type_archive" data-title="type_archive" class="nav-tab <?php echo ($active_tab == 'type_archive') ? 'nav-tab-active' : ''; ?>">
                        <?php esc_html_e( 'Post Archive', 'sastra-essential-addons-for-elementor' ); ?>
                    </a>
                    <a href="?page=spexo-welcome&tab=site-builder&layout_type=type_single_post" data-title="type_single_post" class="nav-tab <?php echo ($active_tab == 'type_single_post') ? 'nav-tab-active' : ''; ?>">
                        <?php esc_html_e( 'Single Post', 'sastra-essential-addons-for-elementor' ); ?>
                    </a>
                    <a href="?page=spexo-welcome&tab=site-builder&layout_type=type_search_result_page" data-title="type_search_result_page" class="nav-tab <?php echo ($active_tab == 'type_search_result_page') ? 'nav-tab-active' : ''; ?>">
                        <?php esc_html_e( 'Search Results Page', 'sastra-essential-addons-for-elementor' ); ?>
                    </a>
                    <a href="?page=spexo-welcome&tab=site-builder&layout_type=type_404" data-title="type_404" class="nav-tab <?php echo ($active_tab == 'type_404') ? 'nav-tab-active' : ''; ?>">
                        <?php esc_html_e( '404 Page', 'sastra-essential-addons-for-elementor' ); ?>
                    </a>
                    <a href="?page=spexo-welcome&tab=site-builder&layout_type=type_product_archive" data-title="type_product_archive" class="nav-tab <?php echo esc_attr($active_tab == 'type_product_archive' ? 'nav-tab-active' : ''); ?>">
                        <?php esc_html_e( 'Product Archive', 'sastra-essential-addons-for-elementor' ); ?>
                    </a>
                    <a href="?page=spexo-welcome&tab=site-builder&layout_type=type_product_category" data-title="type_product_category" class="nav-tab <?php echo esc_attr($active_tab == 'type_product_category' ? 'nav-tab-active' : ''); ?>">
                        <?php esc_html_e( 'Product Category', 'sastra-essential-addons-for-elementor' ); ?>
                    </a>
                    <a href="?page=spexo-welcome&tab=site-builder&layout_type=type_single_product" data-title="type_single_product" class="nav-tab <?php echo esc_attr($active_tab == 'type_single_product' ? 'nav-tab-active' : ''); ?>">
                        <?php esc_html_e( 'Single Product', 'sastra-essential-addons-for-elementor' ); ?>
                    </a>
                    <a href="?page=spexo-welcome&tab=site-builder&layout_type=type_global_template" data-title="type_global_template" class="nav-tab <?php echo esc_attr($active_tab == 'type_global_template' ? 'nav-tab-active' : ''); ?>">
                        <?php esc_html_e( 'Global Templates', 'sastra-essential-addons-for-elementor' ); ?>
                    </a>
                </div>

        
            <?php if ( $active_tab == 'type_header' ) : ?>

                <!-- Save Conditions -->
                <input type="hidden" name="tmpcoder_type_header_conditions" id="tmpcoder_type_header_conditions" value="<?php echo esc_attr(get_option('tmpcoder_type_header_conditions', '[]')); ?>">

                <?php TMPCODER_Templates_Loop::render_theme_builder_templates( 'type_header' ); ?>

            <?php elseif ( $active_tab == 'type_footer' ) : ?>

                <!-- Save Conditions -->
                <input type="hidden" name="tmpcoder_type_footer_conditions" id="tmpcoder_type_footer_conditions" value="<?php echo esc_attr(get_option('tmpcoder_type_footer_conditions', '[]')); ?>">

                <?php TMPCODER_Templates_Loop::render_theme_builder_templates( 'type_footer' ); ?>

            <?php elseif ( $active_tab == 'type_archive' ) : ?>

                <!-- Save Conditions -->
                <input type="hidden" name="tmpcoder_type_archive_conditions" id="tmpcoder_type_archive_conditions" value="<?php echo esc_attr(get_option('tmpcoder_type_archive_conditions', '[]')); ?>">

                <?php TMPCODER_Templates_Loop::render_theme_builder_templates( 'type_archive' ); ?>

            <?php elseif ( $active_tab == 'type_search_result_page' ) : ?>

                <!-- Save Conditions -->
                <input type="hidden" name="tmpcoder_type_search_result_page_conditions" id="tmpcoder_type_search_result_page_conditions" value="<?php echo esc_attr(get_option('tmpcoder_type_search_result_page_conditions', '[]')); ?>">

                <?php TMPCODER_Templates_Loop::render_theme_builder_templates( 'type_search_result_page' ); ?>

            <?php elseif ( $active_tab == 'type_404' ) : ?>

                <!-- Save Conditions -->
                <input type="hidden" name="tmpcoder_type_404_conditions" id="tmpcoder_type_404_conditions" value="<?php echo esc_attr(get_option('tmpcoder_type_404_conditions', '[]')); ?>">

                <?php TMPCODER_Templates_Loop::render_theme_builder_templates( 'type_404' ); ?>

            <?php elseif ( $active_tab == 'type_single_post' ) : ?>

                <!-- Save Conditions -->
                <input type="hidden" name="tmpcoder_type_single_post_conditions" id="tmpcoder_type_single_post_conditions" value="<?php echo esc_attr(get_option('tmpcoder_type_single_post_conditions', '[]')); ?>">

                <?php TMPCODER_Templates_Loop::render_theme_builder_templates( 'type_single_post' ); ?>

            <?php elseif ( $active_tab == 'type_product_archive' ) : ?>
                
                <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                    <!-- Save Conditions -->
                    <input type="hidden" name="tmpcoder_type_product_archive_conditions" id="tmpcoder_type_product_archive_conditions" value="<?php echo esc_attr(get_option('tmpcoder_type_product_archive_conditions', '[]')); ?>">

                    <?php TMPCODER_Templates_Loop::render_theme_builder_templates( 'type_product_archive' ); ?>
                <?php else : ?>
                    <div class="tmpcoder-activate-woo-notice"><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e('Please install/activate WooCommerce in order to create product archive templates!', 'sastra-essential-addons-for-elementor'); ?></div>
                <?php endif; ?>

            <?php elseif ( $active_tab == 'type_product_category' ) : ?>
                
                <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                    <!-- Save Conditions -->
                    <input type="hidden" name="tmpcoder_type_product_category_conditions" id="tmpcoder_type_product_category_conditions" value="<?php echo esc_attr(get_option('tmpcoder_type_product_category_conditions', '[]')); ?>">

                    <?php TMPCODER_Templates_Loop::render_theme_builder_templates( 'type_product_category' ); ?>
                <?php else : ?>
                    <div class="tmpcoder-activate-woo-notice"><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e('Please install/activate WooCommerce in order to create product archive templates!', 'sastra-essential-addons-for-elementor'); ?></div>
                <?php endif; ?>

            <?php elseif ( $active_tab == 'type_single_product' ) : ?>

                <?php if ( class_exists( 'WooCommerce' ) ) : ?>
                    <!-- Save Conditions -->
                    <input type="hidden" name="tmpcoder_type_single_product_conditions" id="tmpcoder_type_single_product_conditions" value="<?php echo esc_attr(get_option('tmpcoder_type_single_product_conditions', '[]')); ?>">

                    <?php TMPCODER_Templates_Loop::render_theme_builder_templates( 'type_single_product' ); ?>
                <?php else : ?>
                    <div class="tmpcoder-activate-woo-notice"><span class="dashicons dashicons-info-outline"></span> <?php esc_html_e('Please install/activate WooCommerce in order to create product single templates!', 'sastra-essential-addons-for-elementor') ?></div>
                <?php endif ; ?>

            <?php elseif ( $active_tab == 'type_global_template' ) : ?>

            <?php TMPCODER_Templates_Loop::render_elementor_saved_templates( 'type_global_template' ); ?>

            <?php endif; ?>

            <div class="tmpcoder-settings-page-header">
                <!-- Custom Template -->
                <div class="tmpcoder-preview-buttons">
                    <div class="tmpcoder-user-template">
                        <img src="<?php echo esc_url(TMPCODER_ADDONS_ASSETS_URL.'images/create-template-icon.svg'); ?>">

                        <?php 

                        $active_tab_label = ucwords(str_replace('_', ' ', str_replace('type_', '', $active_tab)));

                        if ($active_tab == 'type_404') {
                            $active_tab_label = '404 Page';
                        }
                        if ($active_tab == 'type_global_template') {
                            $active_tab_label = str_replace('Template', '', $active_tab_label);
                        }

                        ?>

                        <span><?php echo esc_html(sprintf( 
                            /* translators: %s: template type */
                            __( 'Create %s Template', 'sastra-essential-addons-for-elementor' ), esc_html($active_tab_label))); ?></span>

                        <?php
                        if ( ! class_exists( 'WooCommerce' ) && isset($_GET['layout_type']) && ('type_product_archive' === $_GET['layout_type'] || 'type_single_product' === $_GET['layout_type'] || 'type_single_product' === $_GET['layout_type'] || 'type_product_category' === $_GET['layout_type'] )) {// phpcs:ignore WordPress.Security.NonceVerification.Recommended
                            echo '<div></div>';
                        }
                        ?>
                    </div>

                    <?php
                        if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
                            $url = '';
                            if ( 'type_global_template' === $active_tab ) {
                                $url = admin_url( 'edit.php?post_type=elementor_library&tabs_group=library' );
                            } else {
                                $url = admin_url( 'edit.php?s&post_status=all&post_type=theme-advanced-hook&layout_type='. str_replace("tmpcoder_tab_", "", $active_tab) .'&filter_action=Filter' );
                            }
                            tmpcoder_render_wpml_translate_templates_link( $url );
                        }
                    ?>

                </div>
            </div>
        </div>

        </form>
    </div>

</div>


<?php

} // End tmpcoder_addons_theme_builder_page()

/**
 * Popup Builder page.
 */
function tmpcoder_popup_builder_page() {
	
	?>
<div class="wrap tmpcoder-settings-page-wrap">
    <div class="tmpcoder-settings-page tmpcoder-site-builder-page tmpcoder-popup-builder-page">
        <form method="post" action="options.php">
            <input type="hidden" name="tmpcoder_template" id="tmpcoder_template" value="">
            <div class="change-conditions-popup">
                <?php TMPCODER_Templates_Loop::render_conditions_popup( true, false, 'type_popup' ); ?>
            </div>
            <?php TMPCODER_Templates_Loop::render_create_template_popup(); ?>
            <?php TMPCODER_Templates_Loop::render_delete_template_confirm_popup(); ?>

            <!-- Prebuilt Popup/Block Library (Step 1: choose design or scratch) -->
            <div id="tmpcoder-popup-library-wrap" class="tmpcoder-popup-library-wrap tmpcoder-admin-popup-wrap" style="display:none;" data-post-id="">
                <div class="tmpcoder-popup-library tmpcoder-admin-popup">
                    <header class="tmpcoder-popup-library-header">
                        <div class="tmpcoder-popup-library-logo">
                            <img src="<?php echo esc_url(TMPCODER_ADDONS_ASSETS_URL.'images/logo-40x40.svg'); ?>">
                            <?php esc_html_e( 'Library', 'sastra-essential-addons-for-elementor' ); ?>
                        </div>
                        <ul class="tmpcoder-popup-library-tabs">
                            <li class="tmpcoder-popup-library-tab" data-tab="blocks"><?php esc_html_e( 'Blocks', 'sastra-essential-addons-for-elementor' ); ?></li>
                            <li class="tmpcoder-popup-library-tab active" data-tab="popups"><?php esc_html_e( 'Popups', 'sastra-essential-addons-for-elementor' ); ?></li>
                        </ul>
                        <div class="tmpcoder-popup-library-actions">
                            <button type="button" class="tmpcoder-popup-library-create-scratch button button-primary">+ <?php esc_html_e( 'Create from Scratch', 'sastra-essential-addons-for-elementor' ); ?></button>
                            <span class="tmpcoder-popup-library-close dashicons dashicons-no-alt"></span>
                        </div>
                    </header>
                    <div class="tmpcoder-popup-library-content">
                        <div class="tmpcoder-popup-library-loading" style="display:none;">
                            <div class="elementor-loader-wrapper">
                                <div class="elementor-loader">
                                    <div class="elementor-loader-boxes">
                                        <div class="elementor-loader-box"></div>
                                        <div class="elementor-loader-box"></div>
                                        <div class="elementor-loader-box"></div>
                                        <div class="elementor-loader-box"></div>
                                    </div>
                                </div>
                                <div class="elementor-loading-title"><?php esc_html_e( 'Loading', 'sastra-essential-addons-for-elementor' ); ?></div>
                            </div>
                        </div>
                        <div class="tmpcoder-popup-library-grid-wrap tmpcoder-tplib-template-gird elementor-clearfix"></div>
                    </div>
                </div>
            </div>

            <!-- Popup Setup (Step 2: name + conditions) -->
            <div id="tmpcoder-popup-setup-wrap" class="tmpcoder-popup-setup-wrap tmpcoder-admin-popup-wrap" style="display:none;">
                <div class="tmpcoder-popup-setup tmpcoder-admin-popup">
                    <header class="tmpcoder-popup-setup-header">
                        <h2><?php esc_html_e( 'Popup Setup', 'sastra-essential-addons-for-elementor' ); ?></h2>
                        <h3><?php esc_html_e( 'Where Would You Like to See Your Popup Presented?', 'sastra-essential-addons-for-elementor' ); ?></h3>
                        <?php esc_html_e( 'Define the rules that establish how and where your popup appears on your website.', 'sastra-essential-addons-for-elementor' ); ?>
                    </header>
                    <span class="close-popup tmpcoder-popup-setup-close dashicons dashicons-no-alt"></span>
                    <table class="tmpcoder-options-table widefat">
                        <tbody>
                            <tr class="tmpcoder-options-row">
                                <td class="tmpcoder-options-row-heading">
                                    <label for="tmpcoder_popup_setup_name"><?php esc_html_e( 'Popup Name', 'sastra-essential-addons-for-elementor' ); ?></label>
                                </td>
                                <td class="tmpcoder-options-row-content text-center">
                                    <input type="text" id="tmpcoder_popup_setup_name" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. Summer Sale Popup', 'sastra-essential-addons-for-elementor' ); ?>">
                                </td>
                            </tr>
                            <tr class="bsf-target-rules-row tmpcoder-options-row">
                                <td class="bsf-target-rules-row-heading tmpcoder-options-row-heading">
                                    <label><?php esc_html_e( 'Display On', 'sastra-essential-addons-for-elementor' ); ?></label>
                                    <i class="bsf-target-rules-heading-help dashicons dashicons-editor-help">
                                        <span class="tooltip"><?php esc_attr_e( 'Add locations for where this template should appear.', 'sastra-essential-addons-for-elementor' ); ?></span>
                                    </i>
                                </td>
                                <td class="bsf-target-rules-row-content tmpcoder-options-row-content tmpcoder-popup-setup-conditions-cell">
                                    <div class="tmpcoder-popup-setup-conditions-placeholder">
                                        <span class="spinner is-active" style="float:none;margin:0;"></span>
                                        <?php esc_html_e( 'Loading display rules…', 'sastra-essential-addons-for-elementor' ); ?>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <footer class="tmpcoder-popup-setup-footer">
                        <button type="button" class="button tmpcoder-popup-setup-back"><?php esc_html_e( 'Back', 'sastra-essential-addons-for-elementor' ); ?></button>
                        <button type="button" class="button button-primary tmpcoder-popup-setup-save"><?php esc_html_e( 'Save Conditions', 'sastra-essential-addons-for-elementor' ); ?></button>
                    </footer>
                </div>
            </div>

            <!-- Hidden tab for JS: getActiveFilter() and target-rule use .tmpcoder-layout-tabs .nav-tab-active -->
            <div class="site-builder-main common-box-shadow tmpcoder-layout-tabs" style="display:none;">
                <div class="nav-tab-wrapper tmpcoder-nav-tab-wrapper">
                    <a href="#" data-title="type_popup" class="nav-tab nav-tab-active"><?php esc_html_e( 'Popup', 'sastra-essential-addons-for-elementor' ); ?></a>
                </div>
            </div>

            <div class="site-builder-main common-box-shadow tmpcoder-layout-tabs">
                <header>
                    <div class="tmpcoder-import-demo-left">
                        <div class="tmpcoder-import-demo-logo">
                            <h1><?php esc_html_e( 'Popup Builder', 'sastra-essential-addons-for-elementor' ); ?></h1>
                            <div class="tmpcoder-prebuilt-demo-doc-link">
                                <a href="<?php echo esc_url( TMPCODER_DOCUMENTATION_URL . 'popup-builder-overview/' ); ?>" target="_blank" class="btn-link">
                                    <i class="dashicons dashicons-external"></i>
                                    <?php esc_html_e( 'How to use Popup Templates', 'sastra-essential-addons-for-elementor' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="tmpcoder-import-demo-right">
                        <?php
                            if ( is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) ) {
                                $wpml_popup_templates_url = admin_url( 'edit.php?s&post_status=all&post_type=theme-advanced-hook&layout_type=type_popup&filter_action=Filter' );
                                tmpcoder_render_wpml_translate_templates_link( $wpml_popup_templates_url );
                            }
                        ?>
                        <div class="tmpcoder-user-template tmpcoder-create-popup-btn" role="button" tabindex="0">
                            <span class="tmpcoder-create-popup-icon">+</span>
                            <span><?php esc_html_e( 'Create Popup', 'sastra-essential-addons-for-elementor' ); ?></span>
                        </div>
                    </div>
                </header>

                <input type="hidden" name="tmpcoder_type_popup_conditions" id="tmpcoder_type_popup_conditions" value="<?php echo esc_attr( get_option( 'tmpcoder_type_popup_conditions', '[]' ) ); ?>">
                <?php TMPCODER_Templates_Loop::render_theme_builder_templates( 'type_popup' ); ?>
            </div>
        </form>
    </div>
</div>
	<?php
}

/**
 * Enqueue scripts and styles for theme builder page
 */
function tmpcoder_theme_builder_enqueue_scripts( $hook ) {
	$is_site_builder = ( false !== strpos( $hook, 'spexo-welcome' ) && isset( $_GET['tab'] ) && 'site-builder' === sanitize_key( $_GET['tab'] ) );
	$is_popup_builder = ( isset( $_GET['tab'] ) && 'popup-builder' === sanitize_key( $_GET['tab'] ) );

	if ( $is_site_builder || $is_popup_builder ) {
		wp_enqueue_style( 'tmpcoder-plugin-import-demos', plugins_url( 'inc/admin/import/assets/css/tmpcoder-plugin-import-demos' . tmpcoder_script_suffix() . '.css', TMPCODER_PLUGIN_FILE ), [], TMPCODER_PLUGIN_VER, false );

        // wp_enqueue_style('tmpcoder-prebuild-style', TMPCODER_PLUGIN_URI . 'assets/css/admin/prebuild-style'.tmpcoder_script_suffix().'.css', array(), tmpcoder_get_plugin_version(), false  );
	}
}
add_action( 'admin_enqueue_scripts', 'tmpcoder_theme_builder_enqueue_scripts' );