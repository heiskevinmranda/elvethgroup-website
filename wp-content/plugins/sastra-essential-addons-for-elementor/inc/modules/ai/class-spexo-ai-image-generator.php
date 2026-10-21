<?php
/**
 * Spexo AI Image Generator
 * 
 * Handles AI image generation for Elementor fields
 *
 * @package Spexo_Addons
 * @since 1.0.28
 */

namespace Spexo_Addons\AI;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Class Spexo_AI_Image_Generator
 */
class Spexo_AI_Image_Generator {

    /**
     * Instance
     *
     * @var Spexo_AI_Image_Generator|null The single instance of the class.
     */
    private static $instance = null;

    /**
     * Curated labels for known image-capable models (used when API list is empty).
     *
     * @return array<string, string> Model id => human-readable label.
     */
    public static function get_curated_image_model_labels() {
        return [
            'dall-e-3' => 'DALL·E 3',
            'dall-e-2' => 'DALL·E 2',
            'gpt-image-1' => 'GPT Image 1',
            'gpt-image-1-mini' => 'GPT Image 1 Mini',
            'gpt-image-1.5' => 'GPT Image 1.5',
        ];
    }

    /**
     * Whether a model id from /v1/models is treated as an image-generation candidate.
     *
     * @param string $model_id Model id.
     * @return bool
     */
    public static function is_image_model_api_candidate( $model_id ) {
        if ( ! is_string( $model_id ) || $model_id === '' ) {
            return false;
        }
        if ( strpos( $model_id, 'dall-e-' ) === 0 ) {
            return true;
        }
        if ( strpos( $model_id, 'gpt-image-' ) === 0 ) {
            return true;
        }
        return false;
    }

    /**
     * Merge curated defaults with API-listed image models.
     *
     * @param array<string, string> $cached_models Key => label from OpenAI /v1/models.
     * @return array<string, string>
     */
    public static function merge_image_models_for_settings( $cached_models ) {
        $curated = self::get_curated_image_model_labels();
        $from_api = [];
        if ( is_array( $cached_models ) ) {
            foreach ( $cached_models as $id => $label ) {
                if ( self::is_image_model_api_candidate( (string) $id ) ) {
                    $from_api[ (string) $id ] = is_string( $label ) ? $label : (string) $id;
                }
            }
        }

        $merged = array_merge( $curated, $from_api );
        ksort( $merged );

        /**
         * Filter merged image model list for settings / UI.
         *
         * @param array<string, string> $merged Model id => label.
         */
        return apply_filters( 'spexo_ai_image_models_for_settings', $merged );
    }

    /**
     * Internal family for validation and API payload branching.
     *
     * @param string $model Model id.
     * @return string 'dall-e-3'|'dall-e-2'|'gpt-image'|''
     */
    public static function get_model_family( $model ) {
        $model = is_string( $model ) ? $model : '';
        if ( $model === 'dall-e-3' ) {
            return 'dall-e-3';
        }
        if ( $model === 'dall-e-2' ) {
            return 'dall-e-2';
        }
        if ( $model !== '' && strpos( $model, 'gpt-image-' ) === 0 ) {
            return 'gpt-image';
        }
        return '';
    }

    /**
     * Whether this model id is supported by our images/generations integration.
     *
     * @param string $model Model id.
     * @return bool
     */
    public static function is_allowed_image_model( $model ) {
        return self::get_model_family( $model ) !== '';
    }

    /**
     * Sanitize model from requests: allow only supported ids, else saved default or dall-e-3.
     *
     * @param string               $requested_model Model from client.
     * @param array<string, mixed> $options         spexo_ai_options.
     * @return string
     */
    public static function resolve_request_image_model( $requested_model, $options ) {
        $requested_model = sanitize_text_field( (string) $requested_model );
        $saved = isset( $options['openai_image_model'] ) ? sanitize_text_field( (string) $options['openai_image_model'] ) : 'dall-e-3';

        if ( self::is_allowed_image_model( $requested_model ) ) {
            return $requested_model;
        }
        if ( self::is_allowed_image_model( $saved ) ) {
            return $saved;
        }
        return 'dall-e-3';
    }

    /**
     * Options for Elementor script: model rows + control definitions by family.
     *
     * @param string $saved_image_model Default from settings (ensure it appears in the list).
     * @return array<string, mixed>
     */
    public static function get_editor_script_image_config( $saved_image_model ) {
        $labels = self::get_curated_image_model_labels();
        if ( $saved_image_model && self::is_allowed_image_model( $saved_image_model ) && ! isset( $labels[ $saved_image_model ] ) ) {
            $labels[ $saved_image_model ] = $saved_image_model;
        }
        ksort( $labels );

        $image_models = [];
        foreach ( $labels as $id => $label ) {
            $image_models[] = [
                'value' => $id,
                'label' => $label,
            ];
        }

        $model_controls = [
            'dall-e-3' => [
                'qualities' => [
                    [ 'value' => 'hd', 'label' => 'HD (default)' ],
                    [ 'value' => 'standard', 'label' => 'Standard' ],
                ],
                'sizes' => [
                    [ 'value' => '1024x1024', 'label' => '1024×1024 (square)' ],
                    [ 'value' => '1024x1792', 'label' => '1024×1792 (portrait)' ],
                    [ 'value' => '1792x1024', 'label' => '1792×1024 (landscape)' ],
                ],
                'supports_background' => false,
            ],
            'dall-e-2' => [
                'qualities' => [
                    [ 'value' => 'standard', 'label' => 'Default' ],
                ],
                'sizes' => [
                    [ 'value' => '256x256', 'label' => '256×256' ],
                    [ 'value' => '512x512', 'label' => '512×512' ],
                    [ 'value' => '1024x1024', 'label' => '1024×1024' ],
                ],
                'supports_background' => false,
            ],
            'gpt-image' => [
                'qualities' => [
                    [ 'value' => 'high', 'label' => 'High (default)' ],
                    [ 'value' => 'medium', 'label' => 'Medium' ],
                    [ 'value' => 'low', 'label' => 'Low' ],
                    [ 'value' => 'auto', 'label' => 'Auto' ],
                ],
                'sizes' => [
                    [ 'value' => 'auto', 'label' => 'Auto (default)' ],
                    [ 'value' => '1024x1024', 'label' => '1024×1024 (square)' ],
                    [ 'value' => '1536x1024', 'label' => '1536×1024 (landscape)' ],
                    [ 'value' => '1024x1536', 'label' => '1024×1536 (portrait)' ],
                ],
                'supports_background' => true,
            ],
        ];

        return [
            'image_models' => $image_models,
            'model_controls' => $model_controls,
        ];
    }

    /**
     * Get Instance
     *
     * @return Spexo_AI_Image_Generator
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        $options = get_option('spexo_ai_options', []);
        
        if (!isset($options['enable_ai_image_generation_button']) || $options['enable_ai_image_generation_button']) {
            // Enqueue Elementor editor scripts
            add_action('elementor/editor/before_enqueue_scripts', [$this, 'enqueue_elementor_scripts']);
            
            // AJAX handlers
            add_action('wp_ajax_spexo_ai_generate_image', [$this, 'handle_ai_generate_image']);
            add_action('wp_ajax_spexo_ai_image_check_limits', [$this, 'handle_ai_image_check_limits']);
        }
    }

    /**
     * Enqueue Elementor editor scripts
     */
    public function enqueue_elementor_scripts() {
        $options = get_option('spexo_ai_options', []);
        
        if (empty($options['openai_api_key'])) {
            return;
        }

        // Determine file extensions based on SCRIPT_DEBUG
        $css_ext = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.css' : '.min.css';
        $js_ext = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.js' : '.min.js';

        wp_enqueue_style(
            'spexo-ai-imagefield',
            TMPCODER_PLUGIN_URI . 'inc/modules/ai/assets/css/ai-imagefield' . $css_ext,
            [],
            TMPCODER_PLUGIN_VER
        );

        wp_enqueue_script(
            'spexo-ai-imagefield',
            TMPCODER_PLUGIN_URI . 'inc/modules/ai/assets/js/ai-imagefield' . $js_ext,
            ['jquery', 'elementor-editor'],
            TMPCODER_PLUGIN_VER,
            true
        );

        // Check Pro license status
        $is_pro = function_exists('tmpcoder_is_availble') ? tmpcoder_is_availble() : false;
        $saved_image_model = $options['openai_image_model'] ?? 'dall-e-3';
        $editor_config = self::get_editor_script_image_config( $saved_image_model );

        wp_localize_script('spexo-ai-imagefield', 'SpexoAiImageField', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'generate_action' => 'spexo_ai_generate_image',
            'generate_nonce' => wp_create_nonce('spexo_ai_generate_image_nonce'),
            'settings_url' => admin_url('admin.php?page=spexo-ai-settings'),
            'icon_url' => TMPCODER_PLUGIN_URI . 'inc/modules/ai/assets/images/ai-translator.svg',
            'image_model' => $saved_image_model,
            'is_pro' => $is_pro,
            'image_models' => $editor_config['image_models'],
            'model_controls' => $editor_config['model_controls'],
        ]);
    }

    /**
     * Handle AI image generation AJAX request
     */
    public function handle_ai_generate_image() {
        check_ajax_referer('spexo_ai_generate_image_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'sastra-essential-addons-for-elementor')], 403);
        }

        $options = get_option('spexo_ai_options', []);
        if (empty($options['openai_api_key'])) {
            wp_send_json_error([
                'message' => esc_html__('OpenAI API key is not configured.', 'sastra-essential-addons-for-elementor'),
                'needs_setup' => true
            ], 400);
        }

        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field(wp_unslash($_POST['prompt'])) : '';
        $quality = isset($_POST['quality']) ? sanitize_text_field(wp_unslash($_POST['quality'])) : 'hd';
        $size = isset($_POST['size']) ? sanitize_text_field(wp_unslash($_POST['size'])) : '1024x1024';
        $model = isset($_POST['model']) ? sanitize_text_field(wp_unslash($_POST['model'])) : 'dall-e-3';
        $background = isset($_POST['background']) ? sanitize_text_field(wp_unslash($_POST['background'])) : '';

        if (empty($prompt)) {
            wp_send_json_error(['message' => esc_html__('Prompt is required.', 'sastra-essential-addons-for-elementor')], 400);
        }

        $model = self::resolve_request_image_model($model, $options);

        $result = $this->generate_image_with_ai($prompt, $quality, $size, $model, $background);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()], 500);
        }

        // Create WordPress attachment from generated image
        $attachment_id = $this->create_attachment_from_generation_result($result, $prompt);

        if (is_wp_error($attachment_id)) {
            wp_send_json_error(['message' => $attachment_id->get_error_message()], 500);
        }

        wp_send_json_success([
            'attachment_id' => $attachment_id,
            'url' => wp_get_attachment_url($attachment_id),
            'usage' => $result['usage'] ?? []
        ]);
    }

    /**
     * Handle AI image check limits AJAX request
     */
    public function handle_ai_image_check_limits() {
        check_ajax_referer('spexo_ai_generate_image_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => esc_html__('Permission denied.', 'sastra-essential-addons-for-elementor')], 403);
        }

        $options = get_option('spexo_ai_options', []);
        $api_key = $options['openai_api_key'] ?? '';
        // Image generation only requires an API key; text model is unrelated.
        $api_key_valid = ! empty( $api_key );

        wp_send_json_success([
            'api_key_valid' => $api_key_valid
        ]);
    }

    /**
     * Generate image using OpenAI API
     */
    private function generate_image_with_ai($prompt, $quality = 'hd', $size = '1024x1024', $model = 'dall-e-3', $background = '') {
        $options = get_option('spexo_ai_options', []);
        $api_key = $options['openai_api_key'];

        $family = self::get_model_family( $model );
        if ( $family === '' ) {
            $model = 'dall-e-3';
            $family = 'dall-e-3';
        }

        // Validate parameters based on model
        $validated_params = $this->validate_image_params($model, $quality, $size, $background);

        $payload = [
            'model' => $model,
            'prompt' => $prompt,
            'n' => 1,
            'size' => $validated_params['size'],
        ];

        if ( $family === 'dall-e-3' ) {
            $payload['quality'] = $validated_params['quality'];
            $payload['style'] = 'natural';
        }

        if ( $family === 'gpt-image' ) {
            // Match prior behavior: only optional background; API uses its own defaults for quality tiers.
            if ( ! empty( $validated_params['background'] ) ) {
                $payload['background'] = $validated_params['background'];
            }
        }

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => wp_json_encode($payload),
            'timeout' => 120, // Longer timeout for image generation
            'method' => 'POST',
        ];

        $response = wp_remote_post('https://api.openai.com/v1/images/generations', $args);

        if (is_wp_error($response)) {
            return new \WP_Error('api_error', $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($response_body, true);

        if ($response_code !== 200) {
            $error_message = isset($decoded_body['error']['message']) 
                ? $decoded_body['error']['message'] 
                : esc_html__('Image generation failed.', 'sastra-essential-addons-for-elementor');
            return new \WP_Error('api_error', $error_message, ['status' => $response_code]);
        }

        if (!isset($decoded_body['data'][0]) || !is_array($decoded_body['data'][0])) {
            return new \WP_Error('api_error', esc_html__('Unexpected API response format.', 'sastra-essential-addons-for-elementor'));
        }

        $first = $decoded_body['data'][0];

        if (!empty($first['url'])) {
            return [
                'url' => $first['url'],
                'b64' => null,
                'usage' => $decoded_body['usage'] ?? []
            ];
        }

        if (!empty($first['b64_json'])) {
            return [
                'url' => null,
                'b64' => $first['b64_json'],
                'usage' => $decoded_body['usage'] ?? []
            ];
        }

        return new \WP_Error('api_error', esc_html__('Unexpected API response format.', 'sastra-essential-addons-for-elementor'));
    }

    /**
     * Validate image generation parameters based on model
     */
    private function validate_image_params($model, $quality, $size, $background) {
        $validated = [
            'quality' => $quality,
            'size' => $size,
            'background' => $background
        ];

        $family = self::get_model_family( $model );

        if ( $family === 'dall-e-3' ) {
            $valid_qualities = ['hd', 'standard'];
            if (!in_array($quality, $valid_qualities, true)) {
                $validated['quality'] = 'hd';
            }

            $valid_sizes = ['1024x1024', '1024x1792', '1792x1024'];
            if (!in_array($size, $valid_sizes, true)) {
                $validated['size'] = '1024x1024';
            }
        } elseif ( $family === 'dall-e-2' ) {
            $valid_sizes = ['256x256', '512x512', '1024x1024'];
            if (!in_array($size, $valid_sizes, true)) {
                $validated['size'] = '1024x1024';
            }
        } elseif ( $family === 'gpt-image' ) {
            $valid_qualities = ['high', 'medium', 'low', 'auto'];
            if (!in_array($quality, $valid_qualities, true)) {
                $validated['quality'] = 'high';
            }

            $valid_sizes = ['auto', '1024x1024', '1536x1024', '1024x1536'];
            if (!in_array($size, $valid_sizes, true)) {
                $validated['size'] = 'auto';
            }
        }

        return $validated;
    }

    /**
     * Create attachment from URL or base64 API payload.
     *
     * @param array<string, mixed> $result Result from generate_image_with_ai.
     * @param string                 $prompt Image prompt.
     * @return int|\WP_Error
     */
    private function create_attachment_from_generation_result( $result, $prompt ) {
        if ( ! empty( $result['url'] ) ) {
            return $this->create_attachment_from_url( $result['url'], $prompt );
        }
        if ( ! empty( $result['b64'] ) ) {
            $binary = base64_decode( (string) $result['b64'], true );
            if ( $binary === false || $binary === '' ) {
                return new \WP_Error( 'decode_error', esc_html__( 'Failed to decode image data.', 'sastra-essential-addons-for-elementor' ) );
            }
            return $this->create_attachment_from_binary( $binary, $prompt );
        }
        return new \WP_Error( 'api_error', esc_html__( 'No image data in API response.', 'sastra-essential-addons-for-elementor' ) );
    }

    /**
     * Create WordPress attachment from image URL
     */
    private function create_attachment_from_url($image_url, $prompt) {
        $sslverify = apply_filters( 'spexo_ai_image_download_sslverify', true );

        // Download the image
        $response = wp_remote_get($image_url, [
            'timeout' => 60,
            'sslverify' => $sslverify,
        ]);

        if (is_wp_error($response)) {
            return new \WP_Error('download_error', $response->get_error_message());
        }

        $image_data = wp_remote_retrieve_body($response);
        if (empty($image_data)) {
            return new \WP_Error('download_error', esc_html__('Failed to download image.', 'sastra-essential-addons-for-elementor'));
        }

        return $this->create_attachment_from_binary( $image_data, $prompt );
    }

    /**
     * Write binary image data to uploads and create attachment.
     *
     * @param string $image_data Raw image bytes.
     * @param string $prompt     Prompt for title / alt.
     * @return int|\WP_Error Attachment ID.
     */
    private function create_attachment_from_binary( $image_data, $prompt ) {
        // Generate filename
        $filename = 'ai-generated-' . uniqid() . '.png';
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;

        // Save the image
        $result = file_put_contents($file_path, $image_data);
        if ($result === false) {
            return new \WP_Error('save_error', esc_html__('Failed to save image.', 'sastra-essential-addons-for-elementor'));
        }

        // Prepare attachment data
        $attachment = [
            'post_mime_type' => 'image/png',
            'post_title' => sanitize_text_field($prompt),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        // Insert attachment
        $attachment_id = wp_insert_attachment($attachment, $file_path);

        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        if ( ! $attachment_id ) {
            return new \WP_Error( 'save_error', esc_html__( 'Failed to create attachment.', 'sastra-essential-addons-for-elementor' ) );
        }

        // Generate attachment metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $file_path);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        // Set alt text based on prompt
        $alt_text = $this->generate_alt_text_from_prompt($prompt);
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);

        return $attachment_id;
    }

    /**
     * Generate alt text from prompt
     */
    private function generate_alt_text_from_prompt($prompt) {
        // Clean up the prompt to create a reasonable alt text
        $alt_text = sanitize_text_field($prompt);
        $alt_text = mb_substr($alt_text, 0, 125); // Limit to 125 characters
        
        return $alt_text;
    }
}
