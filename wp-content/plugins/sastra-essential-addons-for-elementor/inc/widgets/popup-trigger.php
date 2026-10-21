<?php
namespace TMPCODER\Widgets;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Widget_Base;
use Elementor\Icons_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TMPCODER_Popup_Trigger extends Widget_Base {
	
	public function get_name() {
		return 'tmpcoder-popup-trigger';
	}

	public function get_title() {
		return esc_html__( 'Popup Trigger', 'sastra-essential-addons-for-elementor' );
	}

	public function get_icon() {
		return 'tmpcoder-icon eicon-button';
	}

	public function get_categories() {
		return tmpcoder_show_theme_buider_widget_on('type_header') ? [ 'tmpcoder-header-builder-widgets'] : ['tmpcoder-widgets-category'];
	}

	public function get_keywords() {
		return [ 'popup', 'trigger', 'button', 'action', 'close' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! \Elementor\Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}

	public function add_control_popup_trigger_show_again_delay() {
		$this->add_control(
			'popup_trigger_show_again_delay',
			[
				'label'   => esc_html__( 'Show Again Delay', 'sastra-essential-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '0',
				'options' => [
					'0' => esc_html__( 'No Delay', 'sastra-essential-addons-for-elementor' ),
					'60000' => esc_html__( '1 Minute', 'sastra-essential-addons-for-elementor' ),
					'180000' => esc_html__( '3 Minute', 'sastra-essential-addons-for-elementor' ),
					'300000' => esc_html__( '5 Minute', 'sastra-essential-addons-for-elementor' ),
					'pro-60' => esc_html__( '10 Minute (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-180' => esc_html__( '30 Minute (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-360' => esc_html__( '1 Hour (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-1080' => esc_html__( '3 Hour (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-2160' => esc_html__( '6 Hour (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-4320' => esc_html__( '12 Hour (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-8640' => esc_html__( '1 Day (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-25920' => esc_html__( '3 Days (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-43200' => esc_html__( '5 Days (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-60480' => esc_html__( '7 Days (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-864000' => esc_html__( '10 Days (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-1296000' => esc_html__( '15 Days (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-1728000' => esc_html__( '20 Days (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-262800' => esc_html__( '1 Month (Pro)', 'sastra-essential-addons-for-elementor' ),
				],
				'description' => esc_html__( 'This option determines when to show popup again to a visitor after it is closed.', 'sastra-essential-addons-for-elementor' ),
				'separator' => 'before',
				'condition' => [
					'popup_trigger_type!' => 'close-permanently'
				]
			]
		);
	}

	protected function register_controls() {

		// Tab: Content ==============
		// Section: Settings ---------
		$this->start_controls_section(
			'section_popup_trigger',
			[
				'label' => esc_html__( 'Settings', 'sastra-essential-addons-for-elementor' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		tmpcoder_library_buttons( $this, Controls_Manager::RAW_HTML );

		$this->add_control(
			'countdown_editor_notice',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw' => '<strong>'. esc_html__( 'Please Note:', 'sastra-essential-addons-for-elementor' ) .'</strong> '. esc_html__( 'this widget only works if it is placed inside a Popup. To create a Popup, please navigate to the WordPress', 'sastra-essential-addons-for-elementor' ) .' <a href="'. esc_url( admin_url('admin.php?page=spexo-popup-builder') ) .'">'. esc_html__( 'Dashboard > Spexo Addons > Popup Builder.', 'sastra-essential-addons-for-elementor' ) .'</a>',
				'separator' => 'after',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->add_control(
			'popup_trigger_type',
			[
				'label'   => esc_html__( 'Button Action', 'sastra-essential-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'close',
				'options' => [
					'close' => esc_html__( 'Close Popup', 'sastra-essential-addons-for-elementor' ),
					'close-permanently' => esc_html__( 'Close Permanently', 'sastra-essential-addons-for-elementor' ),
					'back' => esc_html__( 'Go Back to Referrer', 'sastra-essential-addons-for-elementor' ),
				]
			]
		);

		$this->add_control_popup_trigger_show_again_delay();

		// Upgrade to Pro Notice
		tmpcoder_upgrade_pro_notice( $this, Controls_Manager::RAW_HTML, 'popup', 'popup_trigger_show_again_delay', [
			'pro-60',
			'pro-180',
			'pro-360',
			'pro-1080',
			'pro-2160',
			'pro-4320',
			'pro-8640',
			'pro-25920',
			'pro-43200',
			'pro-60480',
			'pro-864000',
			'pro-1296000',
			'pro-1728000',
			'pro-262800'
		] );

		$this->add_control(
			'popup_trigger_redirect',
			[
				'label' => esc_html__( 'Redirect to URL when Closed', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'condition' => [
					'popup_trigger_type!' => 'back'
				]
			]
		);

		$this->add_control(
			'popup_trigger_redirect_url',
			[
				'type' => Controls_Manager::URL,
				'dynamic' => [
					'active' => true,
				],
				'condition' => [
					'popup_trigger_redirect' => 'yes',
					'popup_trigger_type!' => 'back'
				]
			]
		);

		$this->add_control(
			'popup_trigger_text',
			[
				'label' => esc_html__( 'Button Text', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => esc_html__( 'Close Popup', 'sastra-essential-addons-for-elementor' ),
				'separator' => 'before'
			]
		);

		$this->add_control(
			'popup_trigger_extra_icon_pos',
			[
				'label' => esc_html__( 'Icon Position', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'none' => esc_html__( 'None', 'sastra-essential-addons-for-elementor' ),
					'before' => esc_html__( 'Before Element', 'sastra-essential-addons-for-elementor' ),
					'after' => esc_html__( 'After Element', 'sastra-essential-addons-for-elementor' ),
				],
				'default' => 'none',
			]
		);

		$this->add_control(
			'popup_trigger_extra_icon',
			[
				'label' => esc_html__( 'Select Icon', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::ICONS,
				'skin' => 'inline',
				'label_block' => false,
				'default' => [
					'value' => 'fas fa-times',
					'library' => 'fa-solid',
				],
				'condition' => [
					'popup_trigger_extra_icon_pos!' => 'none'
				]
			]
		);

		$this->add_responsive_control(
			'button_width',
			[
				'label' => esc_html__( 'Width', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'%' => [
						'min' => 0,
						'max' => 100,
					],
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'size_units' => [ '%', 'px' ],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button' => 'width: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before',
				'frontend_available' => true,
			]
		);

		$this->add_responsive_control(
            'popup_trigger_align',
            [
                'label' => esc_html__( 'Button Align', 'sastra-essential-addons-for-elementor' ),
                'type' => Controls_Manager::CHOOSE,
                'label_block' => false,
                'default' => 'center',
                'options' => [
                    'left' => [
                        'title' => esc_html__( 'Left', 'sastra-essential-addons-for-elementor' ),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__( 'Center', 'sastra-essential-addons-for-elementor' ),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__( 'Right', 'sastra-essential-addons-for-elementor' ),
                        'icon' => 'eicon-h-align-right',
                    ]
                ],
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}}',
				],
				'separator' => 'before'
            ]
        );

		$this->end_controls_section(); // End Controls Section

		// Section: Help & Docs
		if(function_exists('tmpcoder_add_section_help_docs')) {
			tmpcoder_add_section_help_docs( $this, Controls_Manager::RAW_HTML, '' );
		}

		// Tab: Styles ===============
		// Section: General ----------
		$this->start_controls_section(
			'section_popup_trigger_styles',
			[
				'label' => esc_html__( 'General', 'sastra-essential-addons-for-elementor' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_popup_trigger_style' );

		$this->start_controls_tab(
			'tab_popup_trigger_normal',
			[
				'label' => esc_html__( 'Normal', 'sastra-essential-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'popup_trigger_color',
			[
				'label'  => esc_html__( 'Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button' => 'color: {{VALUE}}',
					'{{WRAPPER}} .tmpcoder-popup-trigger-button svg' => 'fill: {{VALUE}}'
				],
			]
		);

		$this->add_control(
			'popup_trigger_bg_color',
			[
				'label'  => esc_html__( 'Background Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#5729D9',
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button' => 'background-color: {{VALUE}}',
				]
			]
		);

		$this->add_control(
			'popup_trigger_border_color',
			[
				'label'  => esc_html__( 'Border Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'popup_trigger_box_shadow',
				'selector' => '{{WRAPPER}} .tmpcoder-popup-trigger-button',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_popup_trigger_hover',
			[
				'label' => esc_html__( 'Hover', 'sastra-essential-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'popup_trigger_color_hr',
			[
				'label'  => esc_html__( 'Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'popup_trigger_bg_color_hr',
			[
				'label'  => esc_html__( 'Background Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#4A45D2',
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button:hover' => 'background-color: {{VALUE}}',
				]
			]
		);

		$this->add_control(
			'popup_trigger_border_color_hr',
			[
				'label'  => esc_html__( 'Border Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button:hover' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'popup_trigger_box_shadow_hr',
				'selector' => '{{WRAPPER}} .tmpcoder-popup-trigger-button:hover',
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'popup_trigger_divider',
			[
				'type' => Controls_Manager::DIVIDER,
				'style' => 'thick',
			]
		);
		$this->add_control(
			'popup_trigger_transition_duration',
			[
				'label' => esc_html__( 'Transition Duration', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 0.1,
				'min' => 0,
				'max' => 5,
				'step' => 0.1,
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button' => 'transition-duration: {{VALUE}}s',
				],
				'separator' => 'after',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'popup_trigger_typography',
				'selector' => '{{WRAPPER}} .tmpcoder-popup-trigger-button'
			]
		);

		$this->add_control(
			'popup_trigger_border_type',
			[
				'label' => esc_html__( 'Border Type', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SELECT,
				'options' => [
					'none' => esc_html__( 'None', 'sastra-essential-addons-for-elementor' ),
					'solid' => esc_html__( 'Solid', 'sastra-essential-addons-for-elementor' ),
					'double' => esc_html__( 'Double', 'sastra-essential-addons-for-elementor' ),
					'dotted' => esc_html__( 'Dotted', 'sastra-essential-addons-for-elementor' ),
					'dashed' => esc_html__( 'Dashed', 'sastra-essential-addons-for-elementor' ),
					'groove' => esc_html__( 'Groove', 'sastra-essential-addons-for-elementor' ),
				],
				'default' => 'none',
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button' => 'border-style: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'popup_trigger_border_width',
			[
				'label' => esc_html__( 'Border Width', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px' ],
				'default' => [
					'top' => 1,
					'right' => 1,
					'bottom' => 1,
					'left' => 1,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'popup_trigger_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			'popup_trigger_svg_icon_size',
			[
				'label' => esc_html__( 'SVG Icon Size', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 50,
					],
				],				
				'default' => [
					'unit' => 'px',
					'size' => 10,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before'
			]
		);

		$this->add_control(
			'popup_trigger_icon_spacing',
			[
				'label' => esc_html__( 'Extra Icon Spacing', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 25,
					],
				],				
				'default' => [
					'unit' => 'px',
					'size' => 10,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button .tmpcoder-extra-icon-left' => 'padding-right: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .tmpcoder-popup-trigger-button .tmpcoder-extra-icon-right' => 'padding-left: {{SIZE}}{{UNIT}};',
				],
				'separator' => 'before'
			]
		);

		$this->add_responsive_control(
			'popup_trigger_padding',
			[
				'label' => esc_html__( 'Padding', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', ],
				'default' => [
					'top' => 6,
					'right' => 15,
					'bottom' => 6,
					'left' => 15,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'popup_trigger_margin',
			[
				'label' => esc_html__( 'Margin', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', ],
				'default' => [
					'top' => 0,
					'right' => 0,
					'bottom' => 0,
					'left' => 0,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'popup_trigger_radius',
			[
				'label' => esc_html__( 'Border Radius', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => 3,
					'right' => 3,
					'bottom' => 3,
					'left' => 3,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-trigger-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();
	}


	protected function render() {
		// Get Settings
		$settings = $this->get_settings();

		// Get Icon HTML
		ob_start();
		Icons_Manager::render_icon( $settings['popup_trigger_extra_icon'], [ 'aria-hidden' => 'true' ] );
		$icon_html = ob_get_clean();

		$popup_show_delay = $settings['popup_trigger_show_again_delay'];

		if ( 'close-permanently' === $settings['popup_trigger_type'] ) {
			$popup_show_delay = 10000000000000;
		}

		$redirect_url = '';
		if ( ! empty( $settings['popup_trigger_redirect_url']['url'] ) ) {
			$redirect_url = esc_url( $settings['popup_trigger_redirect_url']['url'] );
		}

		echo '<div class="tmpcoder-popup-trigger-button" data-trigger="'. esc_attr( $settings['popup_trigger_type'] ) .'" data-show-delay="'. esc_attr( $popup_show_delay ) .'" data-redirect="'. esc_attr( $settings['popup_trigger_redirect'] ) .'" data-redirect-url="'. esc_attr( $redirect_url ) .'">';

			// Icon: Before
			if ( 'before' === $settings['popup_trigger_extra_icon_pos'] && ! empty( $settings['popup_trigger_extra_icon']['value'] ) ) {
				echo '<span class="tmpcoder-extra-icon-left">'. $icon_html .'</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '<span>'. esc_html( $settings['popup_trigger_text'] ) .'</span>';

			// Icon: After
			if ( 'after' === $settings['popup_trigger_extra_icon_pos'] && ! empty( $settings['popup_trigger_extra_icon']['value'] ) ) {
				echo '<span class="tmpcoder-extra-icon-right">'. $icon_html .'</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		echo '</div>';

	}
	
}
