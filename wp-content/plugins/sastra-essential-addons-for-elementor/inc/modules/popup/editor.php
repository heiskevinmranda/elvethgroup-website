<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Elementor Instance
$elementor_plugin = \Elementor\Plugin::$instance;

?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
		<title><?php echo esc_html( wp_get_document_title() ); ?></title>
	<?php endif; ?>
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

	<div class="tmpcoder-template-popup">
		<div class="tmpcoder-template-popup-inner">

			<!-- Popup Overlay & Close Button -->
			<div class="tmpcoder-popup-overlay"></div>

			<!-- Template Container -->
			<div class="tmpcoder-popup-container">

				<!-- Popup Close Button -->
				<?php
				if ( $elementor_plugin->experiments->is_feature_active( 'e_font_icon_svg' ) ) {
					echo '<div class="tmpcoder-popup-close-btn"><i class="fa fa-times"></i></div>';
				} else {
					echo '<div class="tmpcoder-popup-close-btn"><i class="eicon-close"></i></div>';
				}
				?>

				<div class="tmpcoder-popup-container-inner">
					<?php $elementor_plugin->modules_manager->get_modules( 'page-templates' )->print_content(); ?>
				</div>

			</div>

		</div>
	</div>

	<?php wp_footer(); ?>

</body>
</html>
