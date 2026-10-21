<?php 

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( !function_exists('is_plugin_installed') ){
    function is_plugin_installed( $slug ) {
        if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        
        if ( !empty( $all_plugins[$slug] ) ) {
        return true;
        } else {
        return false;
        }
    }
}

if ( !function_exists('plugin_basefile_path') ){
    function plugin_basefile_path( $slug ) {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();
        $plugin_basefile_path = '';

        foreach( $all_plugins as $plg_key => $plg_val ){
            $plug_folderArr = explode('/', $plg_key);
            $plugin_folder_slug = $plug_folderArr[0];
            if ( $plugin_folder_slug == $slug ){
            //if ( $plg_val['TextDomain'] == $slug ){
                $plugin_basefile_path = $plg_key;
                break;
            }
        }
        return $plugin_basefile_path;
    }
}

if ( !function_exists('tmpcoder_get_plugin_info') ){
    function tmpcoder_get_plugin_info($plugin_slug){
        require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        
        $basename = str_replace('/', '', basename($plugin_slug));

        $info = plugins_api( 'plugin_information', array( 'slug' => $basename ) );

        if ( ! $info or is_wp_error( $info ) ) {
            return false;
        }

        return $info;
    }
}

if ( !function_exists('tmpcoder_get_theme_info') ){
    function tmpcoder_get_theme_info($theme_slug){
        
        // After wordpress.org live theme
        if ( ! function_exists( 'themes_api' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/theme.php' );
        }
        if ( ! empty( $theme_slug ) ) {
            $args = array(
                'slug' => $theme_slug,
            );
        }
        /** Prepare our query */
        $call_api = themes_api( 'theme_information', $args );
        return $call_api;
    }
}

/**
** Install a plugin.
*/
if (!function_exists('tmpcoder_install_plugin')) {
    
    function tmpcoder_install_plugin( $plugin_slug ) {
        if ( ! current_user_can( 'install_plugins' ) ) {
            return new WP_Error( 'permission_denied', __( 'You do not have permission to install plugins.', 'sastra-essential-addons-for-elementor' ) );
        }

        if ( ! function_exists( 'plugins_api' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }
        if ( ! class_exists( 'WP_Upgrader' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }

        if ( false === filter_var( $plugin_slug, FILTER_VALIDATE_URL ) ) {
            $api = plugins_api(
                'plugin_information',
                [
                    'slug'   => $plugin_slug,
                    'fields' => [
                        'short_description' => false,
                        'sections'          => false,
                        'requires'          => false,
                        'rating'            => false,
                        'ratings'           => false,
                        'downloaded'        => false,
                        'last_updated'      => false,
                        'added'             => false,
                        'tags'              => false,
                        'compatibility'     => false,
                        'homepage'          => false,
                        'donate_link'       => false,
                    ],
                ]
            );

            if ( is_wp_error( $api ) || empty( $api->download_link ) ) {
                return new WP_Error(
                    'plugin_info_unavailable',
                    sprintf(
                        /* translators: %s plugin slug */
                        __( 'Could not fetch plugin package for %s.', 'sastra-essential-addons-for-elementor' ),
                        sanitize_text_field( (string) $plugin_slug )
                    )
                );
            }

            $download_link = $api->download_link;
        } else {
            $download_link = $plugin_slug;
        }

        // Use AJAX upgrader skin instead of plugin installer skin.
        // ref: function wp_ajax_install_plugin().
        $upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );

        $install = $upgrader->install( $download_link );

        if ( false === $install ) {
            return false;
        } else {
            return true;
        }
    }
}

/**
** Update a plugin.
*/
if (!function_exists('tmpcoder_update_plugin')) {
    
    function tmpcoder_update_plugin( $plugin_path ) {
        if ( ! current_user_can( 'install_plugins' ) ) {
            return;
        }

        if ( ! function_exists( 'plugins_api' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        }
        if ( ! class_exists( 'WP_Upgrader' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        }

        // Use AJAX upgrader skin instead of plugin installer skin.
        // ref: function wp_ajax_install_plugin().
        $upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );

        $upgrade = $upgrader->upgrade( $plugin_path );

        if ( false === $upgrade ) {
            return false;
        } else {
            return true;
        }
    }
}

if ( ! function_exists( 'tmpcoder_wizard_normalize_prebuilt_demo_row' ) ) {
    /**
     * Reduce remote prebuilt demo payload to safe fields for the setup wizard UI.
     *
     * @param array|object $demo Raw demo entry from TMPCODER_Remote_Api::get_prebuilt_demos().
     * @return array
     */
    function tmpcoder_wizard_normalize_prebuilt_demo_row( $demo ) {
        $d            = is_array( $demo ) ? $demo : (array) $demo;
        $slug         = isset( $d['theme-demo-slug'] ) ? sanitize_key( $d['theme-demo-slug'] ) : '';
        $requires_pro = ! empty( $d['is_upgrade_pro'] ) || ! empty( $d['is_pro'] );
        $is_pro_user  = function_exists( 'tmpcoder_is_availble' ) && tmpcoder_is_availble();

        $row = array(
            'name'             => isset( $d['name'] ) ? sanitize_text_field( wp_strip_all_tags( $d['name'] ) ) : '',
            'image'            => isset( $d['image'] ) ? esc_url_raw( $d['image'] ) : '',
            'preview_url'      => isset( $d['preview-url'] ) ? esc_url_raw( $d['preview-url'] ) : '',
            'slug'             => $slug,
            'category'         => isset( $d['tags'] ) ? sanitize_text_field( $d['tags'] ) : '',
            'locked'           => ( $requires_pro && ! $is_pro_user ),
            'is_new'           => ! empty( $d['new'] ),
            'show_pro_badge'   => ! empty( $d['is_pro'] ) || ! empty( $d['is_upgrade_pro'] ),
            'is_favorite'      => ! empty( $d['is_favorite'] ) || ! empty( $d['favorite'] ) || ! empty( $d['is-favorite'] ),
        );

        $row['search_blob'] = strtolower( $row['name'] . ' ' . $row['category'] . ' ' . $row['slug'] );

        return $row;
    }
}