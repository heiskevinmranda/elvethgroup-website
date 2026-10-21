<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once TMPCODER_PLUGIN_DIR . 'inc/header-footer-helper/tmpcoder-plugin-advanced-hooks-loader.php';

/**
 * Human-readable display conditions (include/exclude) for list view.
 *
 * @param int $post_id Template post ID.
 * @return array{ 'include' => string[], 'exclude' => string[] }
 */
function tmpcoder_get_template_conditions_summary( $post_id ) {
	$out = array( 'include' => array(), 'exclude' => array() );
	if ( ! class_exists( 'TMPCODER_Target_Rules_Fields' ) ) {
		return $out;
	}
	$post_id = (int) $post_id;

	foreach ( array( 'include' => 'tmpcoder_target_include_locations', 'exclude' => 'tmpcoder_target_exclude_locations' ) as $key => $meta_key ) {
		$locations = get_post_meta( $post_id, $meta_key, true );
		if ( empty( $locations ) || ! is_array( $locations ) ) {
			continue;
		}
		$labels = array();
		$rule   = isset( $locations['rule'] ) ? $locations['rule'] : array();
		$idx    = array_search( 'specifics', $rule, true );
		if ( false !== $idx ) {
			unset( $rule[ $idx ] );
		}
		foreach ( $rule as $loc_key ) {
			if ( (string) $loc_key === '' ) {
				continue;
			}
			$label = TMPCODER_Target_Rules_Fields::get_location_by_key( $loc_key );
			if ( $label ) {
				$labels[] = $label;
			}
		}
		if ( isset( $locations['specific'] ) && is_array( $locations['specific'] ) ) {
			foreach ( $locations['specific'] as $loc_key ) {
				$label = TMPCODER_Target_Rules_Fields::get_location_by_key( $loc_key );
				if ( $label ) {
					$labels[] = $label;
				}
			}
		}
		$out[ $key ] = array_filter( $labels );
	}
	return $out;
}

/**
** TMPCODER_Templates_Loop setup
*/
class TMPCODER_Templates_Loop {

	/**
	** Loop Through Custom Templates
	*/
	public static function render_theme_builder_templates( $template ) {
		$args = array(
			'post_type'      => array( TMPCODER_THEME_ADVANCED_HOOKS_POST_TYPE ),
			'post_status'    => array( 'publish' ),
			'posts_per_page' => -1,
			'meta_key'       => 'tmpcoder_template_type',
			'meta_value'     => $template,
			'meta_compare'   => '=',
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		$user_templates = get_posts( $args );

		echo '<ul class="tmpcoder-'. esc_attr($template) .'-templates-list tmpcoder-my-templates-list" data-pro="'. esc_attr(tmpcoder_is_availble()) .'">';

			if ( ! empty( $user_templates ) ) {
				foreach ( $user_templates as $user_template ) {

					$slug = $user_template->post_name;

					if ( !str_contains( $slug, 'user-' ) ) {
						continue;
					}

					$include_locations = get_post_meta( $user_template->ID, 'tmpcoder_target_include_locations', true );
					$conditions        = isset( $include_locations['rule'] ) ? wp_json_encode( array_values( $include_locations['rule'] ) ) : '';
					$specific_conditions = isset( $include_locations['specific'] ) ? wp_json_encode( $include_locations['specific'] ) : '';


					$edit_url = str_replace( 'edit', 'elementor', get_edit_post_link( $user_template->ID ) );
					$show_on_canvas = get_post_meta(tmpcoder_get_template_id($slug), 'tmpcoder_'. $template .'_show_on_canvas', true);

					$conditions_summary = tmpcoder_get_template_conditions_summary( $user_template->ID );
					$is_active          = ! empty( $conditions_summary['include'] );
					$li_class           = 'tmpcoder-template-item' . ( $is_active ? ' tmpcoder-template-active' : '' );

					echo '<li class="' . esc_attr( $li_class ) . '">';
					echo '<div class="tmpcoder-template-card-inner">';

					// Left column: title + condition notes + inactive note.
					echo '<div class="tmpcoder-template-card-text">';
					echo '<div class="tmpcoder-template-title-row">';
					echo '<h3 class="tmpcoder-title">' . esc_html( $user_template->post_title ) . '</h3>';
					if ( ! $is_active ) {
						echo '<span class="tmpcoder-template-draft-tag">' . esc_html__( 'Draft', 'sastra-essential-addons-for-elementor' ) . '</span>';
					}
					echo '</div>'; // .tmpcoder-template-title-row

					if ( ! empty( $conditions_summary['include'] ) || ! empty( $conditions_summary['exclude'] ) ) {
						$include_labels = $conditions_summary['include'];
						$exclude_labels = $conditions_summary['exclude'];

						if ( ! empty( $include_labels ) ) {
							echo '<p class="tmpcoder-conditions-note">' . esc_html__( 'Active Conditions:', 'sastra-essential-addons-for-elementor' ) . ' <strong>' . esc_html( implode( ', ', $include_labels ) ) . '</strong></p>';
						}

						if ( ! empty( $exclude_labels ) ) {
							echo '<p class="tmpcoder-conditions-note">' . esc_html__( 'Condition Excluded:', 'sastra-essential-addons-for-elementor' ) . ' <strong>' . esc_html( implode( ', ', $exclude_labels ) ) . '</strong></p>';
						}
					}

					if ( ! $is_active ) {
						echo '<p class="tmpcoder-conditions-note tmpcoder-inactive-label">' . esc_html__( 'Inactive Template', 'sastra-essential-addons-for-elementor' ) . '</p>';
					}

					echo '</div>'; // .tmpcoder-template-card-text

					// Right column: existing action buttons (position and structure unchanged).
					echo '<div class="tmpcoder-action-buttons">';
					// Manage Conditions
					echo '<span data-id="' . esc_attr( $user_template->ID ) . '" id="current-layout-' . esc_attr( $user_template->ID ) . '" class="tmpcoder-template-conditions button button-primary" data-conditions="' . esc_attr( $conditions ) . '" data-specific="' . esc_attr( $specific_conditions ) . '" data-slug="' . esc_attr( $slug ) . '" data-show-on-canvas="' . esc_attr( $show_on_canvas ) . '">' . esc_html__( 'Manage Conditions', 'sastra-essential-addons-for-elementor' ) . '</span>';
					// Edit
					echo '<a href="' . esc_url( $edit_url ) . '" class="tmpcoder-edit-template button button-primary">' . esc_html__( 'Edit Template', 'sastra-essential-addons-for-elementor' ) . '</a>';
					// Delete
					$one_time_nonce = wp_create_nonce( 'delete_post-' . $slug );
					echo '<span class="tmpcoder-delete-template button button-primary"  data-nonce="' . esc_attr( $one_time_nonce ) . '" data-slug="' . esc_attr( $slug ) . '" data-warning="' . esc_html__( 'Are you sure you want to delete this template?', 'sastra-essential-addons-for-elementor' ) . '"><span class="dashicons dashicons-trash"></span></span>';
					echo '</div>'; // .tmpcoder-action-buttons

					echo '</div>'; // .tmpcoder-template-card-inner
					echo '</li>';
				}
			} else {
				echo '<li class="tmpcoder-no-templates">You currently don\'t have any templates!</li>';
			}

		echo '</ul>';

		// Restore original Post Data
		wp_reset_postdata();

	}

	/**
	** Loop Through My Templates
	*/
	public static function render_elementor_saved_templates() {

		// WP_Query arguments
		$args = array (
			'post_type' => array( 'elementor_library' ),
			'post_status' => array( 'publish' ),
			'meta_key' => '_elementor_template_type',
			'meta_value' => ['page', 'section', 'container'],
			'numberposts' => -1
		);

		// The Query
		$user_templates = get_posts( $args );

		// My Templates List
		echo '<ul class="tmpcoder-my-templates-list">';

		// The Loop
		if ( ! empty( $user_templates ) ) {
			foreach ( $user_templates as $user_template ) {
				// Edit URL
				$edit_url = str_replace( 'edit', 'elementor', get_edit_post_link( $user_template->ID ) );

				// List
				echo '<li>';
					echo '<h3 class="tmpcoder-title">'. esc_html($user_template->post_title) .'</h3>';
					
					echo '<span class="tmpcoder-action-buttons">';
						echo '<a href="'. esc_url($edit_url) .'" class="tmpcoder-edit-template button button-primary">'. esc_html__( 'Edit Template', 'sastra-essential-addons-for-elementor' ) .'</a>';

						// Delete
						$one_time_nonce = wp_create_nonce( 'delete_post-' . $user_template->post_name );

						echo '<span class="tmpcoder-delete-template button button-primary" data-nonce="'.esc_attr($one_time_nonce).'" data-slug="'. esc_attr($user_template->post_name) .'" data-warning="'. esc_html__( 'Are you sure you want to delete this template?', 'sastra-essential-addons-for-elementor' ) .'"><span class="dashicons dashicons-trash"></span></span>';
					echo '</span>';
				echo '</li>';
			}
		} else {
			echo '<li class="tmpcoder-no-templates">You don\'t have any templates yet!</li>';
		}
		
		echo '</ul>';

		// Restore original Post Data
		wp_reset_postdata();
	}

	/**
	** Render Conditions Popup
	*
	* @param bool   $canvas            Whether canvas mode.
	* @param int|false $template_id    Optional template post ID.
	* @param string|null $force_layout_type Optional. When set (e.g. 'type_popup' on Popup Builder page), used as active_tab instead of $_GET['layout_type'].
	*/
	public static function render_conditions_popup( $canvas = false, $template_id = false, $force_layout_type = null ) {

		// Active Tab
		if ( null !== $force_layout_type && '' !== $force_layout_type ) {
			$active_tab = $force_layout_type;
		} else {
			$active_tab = isset( $_GET['layout_type'] ) ? sanitize_text_field( wp_unslash( $_GET['layout_type'] ) ) : 'type_header';// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$post_id = tmpcoder_get_post_id_by_meta_key_and_meta_value('tmpcoder_template_type', $active_tab);

		if ($template_id) {
			$post_id = $template_id;
		}

	?>

    <div class="tmpcoder-condition-popup-wrap tmpcoder-admin-popup-wrap">
        <div class="tmpcoder-condition-popup tmpcoder-admin-popup">
            <header>
                <h2><?php esc_html_e( 'Where Would You Like to See Your Template Presented?', 'sastra-essential-addons-for-elementor' ); ?></h2>
               
                    <?php esc_html_e( 'Define the rules that establish how and where your Template appears on your website.', 'sastra-essential-addons-for-elementor' ); ?><br>
            </header>
            <div class="popup-loader-html">
                <?php
                tmpcoder_render_common_loader(
                    array(
                        'class'       => 'tmpcoder-template-conditions-loader',
                        'type'        => 'template-conditions',
                        'title'       => '',
                        'description' => '',
                    )
                );
                ?>
            </div>
            <span class="close-popup dashicons dashicons-no-alt"></span>
            <table class="tmpcoder-options-table widefat">
				<tbody>
		            <?php TMPCODER_Target_Rules_Fields::get_instance()->admin_styles(); ?>
		            <tr class="bsf-target-rules-row tmpcoder-options-row">
						<td class="bsf-target-rules-row-heading tmpcoder-options-row-heading">
							<label><?php esc_html_e( 'Display On', 'sastra-essential-addons-for-elementor' ); ?></label>
							<i class="bsf-target-rules-heading-help dashicons dashicons-editor-help">
								<span class="tooltip"><?php esc_attr_e( 'Add locations for where this template should appear.', 'sastra-essential-addons-for-elementor' ); ?></span>
							</i>
						</td>
						<td class="bsf-target-rules-row-content tmpcoder-options-row-content">

							<?php
							$include_locations = get_post_meta( $post_id, 'tmpcoder_target_include_locations', true );

							TMPCODER_Target_Rules_Fields::target_rule_settings_field(
								'bsf-target-rules-location',
								[
									'title'          => __( 'Display Rules', 'sastra-essential-addons-for-elementor' ),
									'value'          => '[{"type":"basic-global","specific":null}]',
									'tags'           => 'site,enable,target,pages',
									'rule_type'      => 'display',
									'add_rule_label' => __( 'Add Display Rule', 'sastra-essential-addons-for-elementor' ),
								],
								$include_locations
							);
							?>
						</td>
					</tr>
				</tbody>
			</table>

            <?php
           	// Pro Notice
			if ( ! tmpcoder_is_availble() ) {
				echo '<span class="tmpcoder-popup-pro-notice"><br>Conditions are fully suppoted in the <strong><a href="'. esc_url(TMPCODER_PURCHASE_PRO_URL.'?ref=tmpcoder-plugin-backend-conditions-upgrade-pro#purchasepro') .'" target="_blank"><img src="'.esc_url( TMPCODER_ADDONS_ASSETS_URL . 'images/premium-icon-purple.svg' ).'" style="width: 16px; height: 16px;     vertical-align: sub;margin-right: 4px;" /><span>Pro versions</span>.</a></strong></span>';
			}

            ?>
            
            <!-- Action Buttons -->
            <span class="tmpcoder-save-conditions"><?php esc_html_e( 'Save Conditions', 'sastra-essential-addons-for-elementor' ); ?></span>
        </div>
    </div>

	<?php

	}


	/**
	** Render Create Template Popup
	*/
	public static function render_create_template_popup() {
	?>

    <!-- Custom Template Popup -->
    <div class="tmpcoder-user-template-popup-wrap tmpcoder-admin-popup-wrap">
        <div class="tmpcoder-user-template-popup tmpcoder-admin-popup">
        	<header>
	            <h2><?php esc_html_e( 'Templates are instrumental in boosting your efficiency at work!', 'sastra-essential-addons-for-elementor' ); ?></h2>
	            <p><?php esc_html_e( 'Utilize templates to generate various components of your website, allowing you to effortlessly reuse them whenever necessary with just one click.', 'sastra-essential-addons-for-elementor' ); ?></p>
			</header>

            <input type="text" name="user_template_title" class="tmpcoder-user-template-title" placeholder="<?php esc_html_e( 'Enter Template Title', 'sastra-essential-addons-for-elementor' ); ?>">
            <input type="hidden" name="user_template_type" class="user-template-type">
            <span class="tmpcoder-create-template"><?php esc_html_e( 'Create Template', 'sastra-essential-addons-for-elementor' ); ?></span>
            <span class="close-popup dashicons dashicons-no-alt"></span>
        </div>
    </div>

	<?php
	}

	/**
	** Render Create Template Popup
	*/
	public static function render_delete_template_confirm_popup() {
		?>
		<div class="tmpcoder-delete-template-confirm-popup-wrap tmpcoder-admin-popup-wrap">
            <div class="tmpcoder-delete-template-popup tmpcoder-admin-popup">
                <div id="tmpcoder-delete-template-confirm-popup">
					<header>
						<h2><?php esc_html_e( 'Are you sure you want to delete this template?', 'sastra-essential-addons-for-elementor' ); ?></h2>
						<p><?php echo wp_kses_post(__( 'This template and its settings will be <strong>permanently removed</strong> from your site. You <strong>won’t be able to recover it</strong> later.', 'sastra-essential-addons-for-elementor' )); ?></p>
					</header>
                    <div class="popup-action">
						<a class="button button-primary tmpcoder-delete-template-confirm-button"><?php esc_html_e('Delete Template', 'sastra-essential-addons-for-elementor') ?></a>
                        <a class="button button-secondary tmpcoder-delete-template-confirm-popup-close"><?php esc_html_e('Cancel', 'sastra-essential-addons-for-elementor') ?></a>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}
	

	/**
	** Check if Library Template Exists
	*/
	public static function template_exists( $slug ) {
		$result = false;
		$tmpcoder_templates = get_posts( ['post_type' => 'tmpcoder_templates', 'posts_per_page' => '-1'] );

		foreach ( $tmpcoder_templates as $post ) {

			if ( $slug === $post->post_name ) {
				$result = true;
			}
		}

		return $result;
	}

}