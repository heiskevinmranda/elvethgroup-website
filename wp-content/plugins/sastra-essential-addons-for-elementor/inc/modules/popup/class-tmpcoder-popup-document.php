<?php

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class TMPCODER_Popup_Document extends \Elementor\Core\Base\Document {
	
	public function get_name() {
		return 'tmpcoder-popup';
	}

	public static function get_type() {
		return 'tmpcoder-popup';
	}
	
	public static function get_title() {
		return esc_html__( 'Spexo Popup', 'sastra-essential-addons-for-elementor' );
	}

	public function get_css_wrapper_selector() {
		if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return '.tmpcoder-template-popup';
		} else {
			return '#tmpcoder-popup-id-'. $this->get_main_id();
		}
	}

	public function add_control_popup_trigger() {
		$this->add_control(
			'popup_trigger',
			[
				'label'   => esc_html__( 'Open Popup', 'sastra-essential-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'load',
				'options' => [
					'load' => esc_html__( 'On Page Load', 'sastra-essential-addons-for-elementor' ),
					'pro-sc' => esc_html__( 'On Page Scroll (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-es' => esc_html__( 'On Scroll to Element (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-dt' => esc_html__( 'After Specific Date (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-ia'  => esc_html__( 'After User Inactivity (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-ex' => esc_html__( 'After User Exit Intent (Pro)', 'sastra-essential-addons-for-elementor' ),
					'pro-cs' => esc_html__( 'Custom Trigger (Button Click) (Pro)', 'sastra-essential-addons-for-elementor' ),
				],
				'render_type' => 'template'
			]
		);	
	}

	public function add_control_popup_show_again_delay() {
		$this->add_control(
			'popup_show_again_delay',
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
				'render_type' => 'template'
			]
		);
	}

	public function add_controls_group_popup_settings() {}

	protected function register_controls() {

		$this->start_controls_section(
			'popup_settings',
			[
				'label' => esc_html__( 'Settings', 'sastra-essential-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);

		$this->add_control_popup_trigger();

		// Upgrade to Pro Notice
		if ( ! tmpcoder_is_availble() ) {
			tmpcoder_upgrade_pro_notice( $this, Controls_Manager::RAW_HTML, 'popup', 'popup_trigger', [
				'pro-sc',
				'pro-es',
				'pro-dt',
				'pro-ia',
				'pro-ex',
				'pro-cs'
			] );
		}

		$this->add_control(
			'popup_load_delay',
			[
				'label' => esc_html__( 'Delay after Page Load (sec)', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 1,
				'min' => 0,
				'condition' => [
					'popup_trigger' => 'load',
				]
			]
		);

		$this->add_control(
			'popup_scroll_progress',
			[
				'label' => esc_html__( 'Scroll Progress (in %)', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 10,
				'min' => 1,
				'max' => 100,
				'condition' => [
					'popup_trigger' => 'scroll',
				]
			]
		);

		$this->add_control(
			'popup_element_scroll',
			[
				'label' => esc_html__( 'Element Selector', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => '',
				'condition' => [
					'popup_trigger' => 'element-scroll',
				]
			]
		);

		$this->add_control(
			'popup_specific_date',
			[
				'label' => esc_html__( 'Select Date', 'sastra-essential-addons-for-elementor' ),
				'label_block' => false,
				'type' => Controls_Manager::DATE_TIME,
				'default' => gmdate( 'Y-m-d H:i', strtotime( '+1 day' ) + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ),
				/* Translators: %s: Timezone string. */
				'description' => sprintf( __( 'Set according to your WordPress timezone: %s.', 'sastra-essential-addons-for-elementor' ), Elementor\Utils::get_timezone_string() ),
				'condition' => [
					'popup_trigger' => 'date',
				],
			]
		);

		$this->add_control(
			'popup_custom_trigger',
			[
				'label' => esc_html__( 'Element Selector', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::TEXT,
				'dynamic' => [
					'active' => true,
				],
				'default' => '',
				'render_type' => 'template',
				'condition' => [
					'popup_trigger' => 'custom',
				]
			]
		);

		$this->add_control(
			'popup_inactivity_time',
			[
				'label' => esc_html__( 'Inactivity Time (sec)', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 15,
				'min' => 1,
				'condition' => [
					'popup_trigger' => 'inactivity',
				]
			]
		);

		$this->add_control_popup_show_again_delay();

		// Upgrade to Pro Notice for show again delay	
		tmpcoder_upgrade_pro_notice( $this, Controls_Manager::RAW_HTML, 'popup', 'popup_show_again_delay', [
			'pro-60',
			'pro-180',
			'pro-360',
			'pro-1080',
			'pro-2160',
			'pro-4320',
			'pro-8640',
			'pro-25920',
		] );

		$this->add_controls_group_popup_settings();

		if ( ! tmpcoder_is_availble() ) {
			$this->add_control(
				'group_popup_settings_pro_notice',
				[
					'type' => Controls_Manager::RAW_HTML,
					'raw' => '<div class="elementor-panel-alert elementor-panel-alert-info">
						<p>' . esc_html__( 'Upgrade to Pro to unlock advanced popup settings like automatic closing, role-based display, device visibility, and more!', 'sastra-essential-addons-for-elementor' ) . '</p>
						<p><a href="' . esc_url( TMPCODER_PURCHASE_PRO_URL . '?ref=tmpcoder-popup-settings-upgrade-pro#purchasepro' ) . '" target="_blank" class="elementor-button elementor-button-default">' . esc_html__( 'Get Pro', 'sastra-essential-addons-for-elementor' ) . '</a></p>
					</div>',
					'content_classes' => 'tmpcoder-pro-notice',
				]
			);
		}

		$this->end_controls_section();

		$this->start_controls_section(
			'popup_layout',
			[
				'label' => esc_html__( 'Layout', 'sastra-essential-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);

		$this->add_control(
			'popup_display_as',
			[
				'label'   => esc_html__( 'Display As', 'sastra-essential-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'modal',
				'options' => [
					'modal' => esc_html__( 'Modal Popup', 'sastra-essential-addons-for-elementor' ),
					'notification' => esc_html__( 'Top Bar Banner', 'sastra-essential-addons-for-elementor' ),
				],
			]
		);

		$this->add_control(
			'popup_display_as_divider',
			[
				'type' => Controls_Manager::DIVIDER,
				'style' => 'thick',
			]
		);

		$this->add_responsive_control(
			'popup_width',
			[
				'label' => esc_html__( 'Width', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px','%'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1000,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],				
				'default' => [
					'unit' => 'px',
					'size' => 650,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-container' => 'width: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'popup_display_as!' => 'notification',
				]
			]
		);

		$this->add_control(
			'popup_height',
			[
				'label'   => esc_html__( 'Height', 'sastra-essential-addons-for-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto'=> esc_html__( 'Auto', 'sastra-essential-addons-for-elementor' ),
					'custom' => esc_html__( 'Custom', 'sastra-essential-addons-for-elementor' ),
				],
				'selectors_dictionary' => [
					'auto' => 'height: auto; z-index: 13;',
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-container-inner' => '{{VALUE}}',
				],
			]
		);

		$this->add_responsive_control(
			'popup_custom_height',
			[
				'label' => esc_html__( 'Custom Height', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px','vh'],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 1000,
					],
					'%' => [
						'min' => 0,
						'max' => 100,
					],
				],				
				'default' => [
					'unit' => 'px',
					'size' => 500,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-container-inner' => 'height: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'popup_height' => 'custom'
				]
			]
		);

		$this->add_responsive_control(
            'popup_align_hr',
            [
                'label' => esc_html__( 'Horizontal Align', 'sastra-essential-addons-for-elementor' ),
                'type' => Controls_Manager::CHOOSE,
                'label_block' => false,
                'default' => 'center',
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__( 'Left', 'sastra-essential-addons-for-elementor' ),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__( 'Center', 'sastra-essential-addons-for-elementor' ),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__( 'Right', 'sastra-essential-addons-for-elementor' ),
                        'icon' => 'eicon-h-align-right',
                    ]
                ],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-template-popup-inner' => 'justify-content: {{VALUE}}',
				],
				'separator' => 'before',
				'condition' => [
					'popup_display_as!' => 'notification',
				]
            ]
        );

		$this->add_responsive_control(
            'popup_align_vr',
            [
                'label' => esc_html__( 'Vertical Align', 'sastra-essential-addons-for-elementor' ),
                'type' => Controls_Manager::CHOOSE,
                'label_block' => false,
                'default' => 'center',
				'options' => [
					'flex-start' => [
						'title' => esc_html__( 'Top', 'sastra-essential-addons-for-elementor' ),
						'icon' => 'eicon-v-align-top',
					],
					'center' => [
						'title' => esc_html__( 'Middle', 'sastra-essential-addons-for-elementor' ),
						'icon' => 'eicon-v-align-middle',
					],
					'flex-end' => [
						'title' => esc_html__( 'Bottom', 'sastra-essential-addons-for-elementor' ),
						'icon' => 'eicon-v-align-bottom',
					],
                ],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-template-popup-inner' => 'align-items: {{VALUE}}',
				],
				'condition' => [
					'popup_display_as!' => 'notification',
				]
            ]
        );

		$this->add_responsive_control(
            'popup_content_align',
            [
                'label' => esc_html__( 'Content Align', 'sastra-essential-addons-for-elementor' ),
                'type' => Controls_Manager::CHOOSE,
                'label_block' => false,
                'default' => 'flex-start',
				'options' => [
					'flex-start' => [
						'title' => esc_html__( 'Top', 'sastra-essential-addons-for-elementor' ),
						'icon' => 'eicon-v-align-top',
					],
					'center' => [
						'title' => esc_html__( 'Middle', 'sastra-essential-addons-for-elementor' ),
						'icon' => 'eicon-v-align-middle',
					],
					'flex-end' => [
						'title' => esc_html__( 'Bottom', 'sastra-essential-addons-for-elementor' ),
						'icon' => 'eicon-v-align-bottom',
					],
                ],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-container-inner' => 'align-items: {{VALUE}}',
				],
				'condition' => [
					'popup_display_as!' => 'notification',
				]
            ]
        );

		$this->add_control(
			'popup_animation',
			[
				'label' => esc_html__( 'Entrance Animation', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::ANIMATION,
				'default' => 'fadeIn',
				'label_block' => true,
				'frontend_available' => true,
				'separator' => 'before',
			]
		);

		$this->add_control(
			'popup_animation_duration',
			[
				'label' => esc_html__( 'Animation Duration', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 1,
				'min' => 0,
				'step' => 0.1,
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-container' => 'animation-duration: {{SIZE}}s;',
				],
				'condition' => [
					'popup_animation!' => ['', 'none'],
				]
			]
		);

		$this->add_control(
			'popup_zindex',
			[
				'label' => esc_html__( 'Z Index', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 9999,
				'min' => 1,
				'selectors' => [
					'{{WRAPPER}}' => 'z-index: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'popup_disable_page_scroll',
			[
				'label' => esc_html__( 'Disable Page Scroll', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => true,
				'return_value' => true,
				'separator' => 'before',
				'condition' => [
					'popup_display_as!' => 'notification',
				]
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'popup_overlay',
			[
				'label' => esc_html__( 'Overlay', 'sastra-essential-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
				'condition' => [
					'popup_display_as!' => 'notification',
				]
			]
		);

		$this->add_control(
			'popup_overlay_display',
			[
				'label' => esc_html__( 'Show Overlay', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'selectors_dictionary' => [
					'' => 'display: none !important;',
					'yes' => 'display: block;'
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-overlay' => '{{VALUE}}',
				],
			]
		);

		$this->add_control(
			'popup_overlay_disable_close',
			[
				'label' => esc_html__( 'Prevent Closing on Overlay Click', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SWITCHER,
				'condition' => [
					'popup_overlay_display' => 'yes'
				]
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'popup_close_button',
			[
				'label' => esc_html__( 'Close Button', 'sastra-essential-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_SETTINGS,
			]
		);

		$this->add_control(
			'popup_close_button_display',
			[
				'label' => esc_html__( 'Show Close Button', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SWITCHER,
				'default' => 'yes',
				'selectors_dictionary' => [
					'' => 'display: none;',
					'yes' => 'display: block;'
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn' => '{{VALUE}}',
				],
			]
		);

		$this->add_control(
			'popup_close_button_display_delay',
			[
				'label' => esc_html__( 'Show Up Delay (sec)', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::NUMBER,
				'default' => 0,
				'min' => 0,
				'condition' => [
					'popup_close_button_display' => 'yes',
				]
			]
		);

		$this->add_responsive_control(
			'popup_close_button_position_vr',
			[
				'label' => esc_html__( 'Vertical Position', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => -100,
						'max' => 100,
					],
				],				
				'default' => [
					'unit' => 'px',
					'size' => 10,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn' => 'top: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'popup_close_button_display' => 'yes'
				]
			]
		);

		$this->add_responsive_control(
			'popup_close_button_position_hr',
			[
				'label' => esc_html__( 'Horizontal Position', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => -100,
						'max' => 100,
					],
				],				
				'default' => [
					'unit' => 'px',
					'size' => 10,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn' => 'right: {{SIZE}}{{UNIT}};',
				],
				'condition' => [
					'popup_close_button_display' => 'yes'
				]
			]
		);

		$this->end_controls_section();

		// Section: Pro Features
		if ( ! tmpcoder_is_availble() ) {
			$this->start_controls_section(
				'pro_features_section',
				[
					'label' => 'Pro Features <span class="dashicons dashicons-star-filled"></span>',
					'tab'   => Controls_Manager::TAB_SETTINGS,
				]
			);

			$this->add_control(
				'pro_features_list',
				[
					'type' => Controls_Manager::RAW_HTML,
					'raw' => '<ul>
						<li>Open Popup: On Page Scroll</li>
						<li>Open Popup: On Scroll to Element</li>
						<li>Open Popup: After Specific Date</li>
						<li>Open Popup: After User Inactivity</li>
						<li>Open Popup: After User Exit Intent</li>
						<li>Open Popup: Custom Trigger (Button Click or Selector)</li>
						<li>Show Again Delay: Set any time (hours, days, weeks) - This option determines when to show popup again to a visitor after it is closed.</li>
						<li>Stop showing after Specific Date</li>
						<li>Automatic Closing Delay</li>
						<li>Show Popup for Specific Roles</li>
						<li>Show according to URL Keyword - Popup will show up if URL(referral) contains chosen keyword</li>
						<li>Show/Hide Popup on any Device</li>
						<li>Prevent Popup closing on"ESC" key</li>
					</ul>
					<a href="' . esc_url( TMPCODER_PURCHASE_PRO_URL . '?ref=tmpcoder-popup-pro-features-upgrade-pro#purchasepro' ) . '" target="_blank">Get Pro version</a>',
					'content_classes' => 'tmpcoder-pro-features-list',
				]
			);

			$this->end_controls_section();
		}

		// Default Document Settings
		parent::register_controls();

		$this->start_controls_section(
			'popup_container_styles',
			[
				'label' => esc_html__( 'Popup', 'sastra-essential-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);
		
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'popup_container_bg',
				'label' => esc_html__( 'Background', 'sastra-essential-addons-for-elementor' ),
				'types' => [ 'classic', 'gradient' ],
				'fields_options' => [
					'color' => [
						'default' => '#ffffff',
					],
				],
				'selector' => '{{WRAPPER}} .tmpcoder-popup-container-inner'
			]
		);

		$this->add_responsive_control(
			'popup_container_padding',
			[
				'label' => esc_html__( 'Padding', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => 20,
					'right' => 20,
					'bottom' => 20,
					'left' => 20,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-container-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before'
			]
		);

		$this->add_control(
			'popup_container_radius',
			[
				'label' => esc_html__( 'Border Radius', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => 0,
					'right' => 0,
					'bottom' => 0,
					'left' => 0,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-container-inner' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'popup_container_border',
				'label' => esc_html__( 'Border', 'sastra-essential-addons-for-elementor' ),
				'placeholder' => '1px',
				'default' => '1px',
				'selector' => '{{WRAPPER}} .tmpcoder-popup-container-inner',
				'separator' => 'before'
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'popup_container_shadow',
				'selector' => '{{WRAPPER}} .tmpcoder-popup-container-inner'
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'popup_overlay_styles',
			[
				'label' => esc_html__( 'Overlay', 'sastra-essential-addons-for-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
				'condition' => [
					'popup_overlay_display' => 'yes'
				]
			]
		);
		
		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name' => 'popup_overlay_bg',
				'label' => esc_html__( 'Background', 'sastra-essential-addons-for-elementor' ),
				'types' => [ 'classic', 'gradient' ],
				'fields_options' => [
					'color' => [
						'default' => '#777777',
					],
				],
				'selector' => '{{WRAPPER}} .tmpcoder-popup-overlay'
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'popup_close_btn_styles',
			[
				'label' => esc_html__( 'Close Button', 'sastra-essential-addons-for-elementor' ),
				'tab' => Controls_Manager::TAB_STYLE,
				'show_label' => false,
			]
		);

		$this->start_controls_tabs( 'tabs_popup_close_btn_style' );

		$this->start_controls_tab(
			'tab_popup_close_btn_normal',
			[
				'label' => esc_html__( 'Normal', 'sastra-essential-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'popup_close_btn_color',
			[
				'label'  => esc_html__( 'Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#333333',
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'popup_close_btn_bg_color',
			[
				'label'  => esc_html__( 'Background Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn' => 'background-color: {{VALUE}}',
				]
			]
		);

		$this->add_control(
			'popup_close_btn_border_color',
			[
				'label'  => esc_html__( 'Border Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn' => 'border-color: {{VALUE}}',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name' => 'popup_close_btn_box_shadow',
				'selector' => '{{WRAPPER}} .tmpcoder-popup-close-btn',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_popup_close_btn_hover',
			[
				'label' => esc_html__( 'Hover', 'sastra-essential-addons-for-elementor' ),
			]
		);

		$this->add_control(
			'popup_close_btn_color_hr',
			[
				'label'  => esc_html__( 'Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#54595f',
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn:hover' => 'color: {{VALUE}}',
				],
			]
		);

		$this->add_control(
			'popup_close_btn_bg_color_hr',
			[
				'label'  => esc_html__( 'Background Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn:hover' => 'background-color: {{VALUE}}',
				]
			]
		);

		$this->add_control(
			'popup_close_btn_border_color_hr',
			[
				'label'  => esc_html__( 'Border Color', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn:hover' => 'border-color: {{VALUE}}',
				]
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_control(
			'popup_close_btn_size',
			[
				'label' => esc_html__( 'Size', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 10,
						'max' => 100,
					],
				],				
				'default' => [
					'unit' => 'px',
					'size' => 20,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .tmpcoder-popup-close-btn svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};'
				],
			]
		);

		$this->add_control(
			'popup_close_btn_box_size',
			[
				'label' => esc_html__( 'Box Size', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => ['px'],
				'range' => [
					'px' => [
						'min' => 10,
						'max' => 100,
					],
				],				
				'default' => [
					'unit' => 'px',
					'size' => 35,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn' => 'width: {{SIZE}}{{UNIT}};height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .tmpcoder-popup-close-btn i' => 'line-height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .tmpcoder-popup-close-btn svg' => 'line-height: {{SIZE}}{{UNIT}};'
				],
			]
		);

		$this->add_control(
			'popup_close_btn_border_type',
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
					'{{WRAPPER}} .tmpcoder-popup-close-btn' => 'border-style: {{VALUE}};',
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'popup_close_btn_border_width',
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
					'{{WRAPPER}} .tmpcoder-popup-close-btn' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'condition' => [
					'popup_close_btn_border_type!' => 'none',
				],
			]
		);

		$this->add_control(
			'popup_close_btn_radius',
			[
				'label' => esc_html__( 'Border Radius', 'sastra-essential-addons-for-elementor' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'default' => [
					'top' => 2,
					'right' => 2,
					'bottom' => 2,
					'left' => 2,
				],
				'selectors' => [
					'{{WRAPPER}} .tmpcoder-popup-close-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
				'separator' => 'before',
			]
		);

		$this->end_controls_section();

	}
	
}




