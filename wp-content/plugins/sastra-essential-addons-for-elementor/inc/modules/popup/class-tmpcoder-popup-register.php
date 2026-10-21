<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Register Popup Document Type
 */
function tmpcoder_register_popup_document() {
	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		return;
	}

	require_once TMPCODER_PLUGIN_DIR . 'inc/modules/popup/class-tmpcoder-popup-document.php';

	\Elementor\Plugin::instance()->documents->register_document_type( 'tmpcoder-popup', 'TMPCODER_Popup_Document' );
}
add_action( 'elementor/documents/register', 'tmpcoder_register_popup_document' );




