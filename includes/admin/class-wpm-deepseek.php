<?php
/**
 * WP Multilang DeepSeek
 * @since 	2.4.34
 */
namespace WPM\Includes\Admin;

use WPM\Includes\Admin\Settings\WPM_Settings_AI_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPM_DeepSeek {

	public const API_BASE_URL = 'https://api.deepseek.com/';

	public function __construct() {
		add_action( 'wpm_render_deepseek_settings', [ $this, 'render_settings' ], 10, 1 );
		add_filter( 'wpm_filter_autotranslate_localize_data', [ $this, 'filter_localize_data' ] );
	}

	public function filter_localize_data( $params ) {
		$params['wpm_deepseek_integration'] = get_option( 'wpm_deepseek_integration', '0' );
		$params['ai_settings']['wpm_deepseek_integration'] = $params['wpm_deepseek_integration'];

		if ( isset( $params['ai_settings']['api_provider'] ) && $params['ai_settings']['api_provider'] === 'deepseek' ) {
			if ( ! empty( $params['ai_settings']['deepseek_model'] ) ) {
				$params['ai_settings']['model'] = $params['ai_settings']['deepseek_model'];
			}
		}

		return $params;
	}

	/**
	 * Render DeepSeek settings panel
	 * @param array $ai_settings
	 */
	public function render_settings( $ai_settings ) {
		$secret_key               = isset( $ai_settings['deepseek_secret_key'] ) && ! empty( $ai_settings['deepseek_secret_key'] ) ? $ai_settings['deepseek_secret_key'] : ( isset( $ai_settings['api_keys']['deepseek'] ) ? $ai_settings['api_keys']['deepseek'] : '' );
		$prompt                   = ! empty( $ai_settings['deepseek_prompt'] ) ? $ai_settings['deepseek_prompt'] : ( defined( 'WPM_DEEPSEEK_PROMPT' ) ? WPM_DEEPSEEK_PROMPT : WPM_OPENAI_PROMPT );
		$models                   = ! empty( $ai_settings['api_available_models']['deepseek'] ) ? $ai_settings['api_available_models']['deepseek'] : [];
		$selected_model           = ! empty( $ai_settings['deepseek_model'] ) ? $ai_settings['deepseek_model'] : ( ! empty( $ai_settings['model'] ) && isset( $ai_settings['api_provider'] ) && $ai_settings['api_provider'] === 'deepseek' ? $ai_settings['model'] : 'deepseek-chat' );
		$wpm_deepseek_integration = get_option( 'wpm_deepseek_integration', '0' );

		$hide_child = 'wpm-hide';
		if ( $wpm_deepseek_integration === '1' ) {
			$hide_child = '';
		}

		$hide_models_class = '';
		if ( empty( $models ) ) {
			$hide_models_class = 'wpm-hide';
		}
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label class="wpm-label-cursor" for="wpm_deepseek_integration"><?php echo esc_html__( 'DeepSeek Integration', 'wp-multilang' ); ?></label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<label for="wpm_deepseek_integration">
						<input name="wpm_deepseek_integration" id="wpm_deepseek_integration" type="checkbox" value="1" <?php checked( $wpm_deepseek_integration, '1' ); ?>>
					</label>
				</fieldset>
			</td>
		</tr>

		<tr valign="top" class="wpm-hide-deepseek-wrapper wpm-deepseek-children <?php echo esc_attr( $hide_child ); ?>">
			<th scope="row" class="titledesc wpm-pl-20">
				<label for="wpm-deepseek-secretkey"><?php echo esc_html__( 'API Key', 'wp-multilang' ); ?></label>
			</th>
			<td class="wpm-pl-20">
				<input class="regular-text" type="password" id="wpm-deepseek-secretkey" name="wpm_deepseek_secretkey" value="<?php echo esc_attr( $secret_key ); ?>">
				<button type="button" id="wpm-validate-deepseek-key" class="button"><?php echo esc_html__( 'Validate API Key', 'wp-multilang' ); ?></button>
				<span class="description wpm-pl-10"><a href="https://platform.deepseek.com/" target="_blank"><?php echo esc_html__( 'Get API Key.', 'wp-multilang' ); ?></a></span>
				<div id="wpm-deepseek-secret-key-error"><?php echo esc_html__( 'API key cannot be blank', 'wp-multilang' ); ?></div>
				<div class="wpm-deepseek-api-success-note"></div>
				<div class="wpm-deepseek-api-error-note"></div>
			</td>
		</tr>

		<tr valign="top" id="wpm-hide-deepseek-models-wrapper" class="wpm-hide-deepseek-wrapper wpm-deepseek-children <?php echo esc_attr( $hide_models_class ) . ' ' . esc_attr( $hide_child ); ?>">
			<th scope="row" class="titledesc wpm-pl-20">
				<label for="wpm-deepseek-models"><?php echo esc_html__( 'Translation Models', 'wp-multilang' ); ?></label>
			</th>
			<td class="wpm-pl-20">
				<select name="wpm_deepseek_models" id="wpm-deepseek-models">
					<?php
					if ( ! empty( $models ) && is_array( $models ) ) {
						foreach ( $models as $model ) {
							$selected = ( $model === $selected_model ) ? 'selected' : '';
							?>
							<option value="<?php echo esc_attr( $model ); ?>" <?php echo esc_attr( $selected ); ?>><?php echo esc_html( $model ); ?></option>
							<?php
						}
					}
					?>
				</select>
			</td>
		</tr>

		<tr valign="top" class="wpm-hide-deepseek-wrapper wpm-deepseek-children <?php echo esc_attr( $hide_child ); ?>">
			<th scope="row" class="titledesc wpm-pl-20">
				<label for="wpm-deepseek-prompt"><?php echo esc_html__( 'Prompt', 'wp-multilang' ); ?></label>
			</th>
			<td class="wpm-pl-20">
				<textarea class="regular-text" rows="5" id="wpm-deepseek-prompt" name="wpm_deepseek_prompt"><?php echo esc_html( $prompt ); ?></textarea>
				<p class="description"><?php echo esc_html__( 'Please ensure the prompt contains the placeholders ', 'wp-multilang' ); ?> <code>{{source_language}}</code> <?php echo esc_html__( 'and ', 'wp-multilang' ); ?> <code>{{target_language}}</code>, <?php echo esc_html__( 'which will be dynamically replaced during translation.', 'wp-multilang' ); ?></p>
				<div id="wpm-deepseek-prompt-error"></div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Validate DeepSeek API secret key & fetch available models
	 * @return array
	 * @throws \Exception
	 */
	public static function validate_secret_key() {
		if ( empty( $_POST['secret_key'] ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Please enter a valid API key for DeepSeek.', 'wp-multilang' ) );
		}

		$api_key   = sanitize_text_field( wp_unslash( $_POST['secret_key'] ) );
		$provider  = 'deepseek';
		$end_point = self::API_BASE_URL . 'models';

		$response = wp_remote_get( $end_point, [
			'headers'   => [
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			],
			'timeout'   => 20,
			'sslverify' => true,
		] );

		if ( is_wp_error( $response ) ) {
			throw new \Exception( esc_html__( 'API Request WP Error: ', 'wp-multilang' ) . esc_html( $response->get_error_message() ) );
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		$decoded_body  = json_decode( $response_body, true );

		if ( $response_code !== 200 ) {
			$error_message = '(Code: ' . $response_code . '): ';
			$error_details = isset( $decoded_body['error']['message'] ) ? $decoded_body['error']['message'] : $response_body;
			if ( strlen( $error_details ) > 500 ) {
				$error_details = substr( $error_details, 0, 500 ) . '... (truncated)';
			}
			$error_message .= $error_details;
			throw new \Exception( esc_html( $error_message ) );
		}

		$models = [];
		if ( isset( $decoded_body['data'] ) && is_array( $decoded_body['data'] ) ) {
			foreach ( $decoded_body['data'] as $m ) {
				if ( isset( $m['id'] ) && is_string( $m['id'] ) ) {
					$models[] = $m['id'];
				}
			}
		}

		if ( empty( $models ) ) {
			$models = [
				'deepseek-chat',
				'deepseek-reasoner',
			];
		}

		$models = array_values( array_unique( array_filter( $models ) ) );
		sort( $models );

		return [
			'message'  => esc_html__( 'Key validated successfully.', 'wp-multilang' ),
			'models'   => $models,
			'api_key'  => $api_key,
			'provider' => $provider,
		];
	}

	/**
	 * API request to translate content using DeepSeek
	 *
	 * @param string $string
	 * @param string $source
	 * @param string $target
	 * @param array  $settings
	 * @return string
	 * @throws \Exception
	 */
	public static function translate_content( $string, $source, $target, $settings ) {
		$api_key = '';
		if ( ! empty( $settings['deepseek_secret_key'] ) ) {
			$api_key = $settings['deepseek_secret_key'];
		} elseif ( ! empty( $settings['api_keys']['deepseek'] ) ) {
			$api_key = $settings['api_keys']['deepseek'];
		}

		if ( empty( $api_key ) ) {
			throw new \Exception( esc_html__( 'DeepSeek API Key is missing.', 'wp-multilang' ) );
		}

		$model = ! empty( $settings['deepseek_model'] ) ? $settings['deepseek_model'] : ( ! empty( $settings['model'] ) ? $settings['model'] : 'deepseek-chat' );

		$prompt = ! empty( $settings['deepseek_prompt'] ) ? $settings['deepseek_prompt'] : ( ! empty( $settings['api_prompt'] ) ? $settings['api_prompt'] : WPM_DEEPSEEK_PROMPT );
		$prompt = str_replace( [ '{{source_language}}', '{{target_language}}' ], [ $source, $target ], $prompt );

		if ( strpos( $string, '|||WPM_SEP|||' ) !== false ) {
			$prompt .= ' Important: The text contains "|||WPM_SEP|||" separator tokens dividing distinct sections. Preserve all "|||WPM_SEP|||" tokens exactly in place without altering, translating, or removing them.';
		}

		$endpoint = self::API_BASE_URL . 'chat/completions';

		$body = [
			'model'       => $model,
			'messages'    => [
				[
					'role'    => 'system',
					'content' => $prompt,
				],
				[
					'role'    => 'user',
					'content' => $string,
				],
			],
			'temperature' => 0.3,
			'stream'      => false,
		];

		$response = wp_remote_post( $endpoint, [
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		] );
		if ( is_wp_error( $response ) ) {
			throw new \Exception( esc_html( $response->get_error_message() ) );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$data        = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status_code !== 200 ) {
			$error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : ( 'HTTP ' . $status_code );
			throw new \Exception( esc_html( $error_message ) );
		}

		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			$translated_text = trim( $data['choices'][0]['message']['content'] );

			// Strip accidental markdown code blocks if original text didn't contain them
			if ( preg_match( '/^```[a-z]*\s*\n?(.*?)\n?```$/is', $translated_text, $matches ) && strpos( $string, '```' ) === false ) {
				$translated_text = trim( $matches[1] );
			}

			return $translated_text;
		}

		return $string;
	}

	/**
	 * Check if API quota/connectivity is active
	 * @return array
	 */
	public static function check_quota() {
		$api_settings        = WPM_Settings_AI_Integration::get_openai_settings();
		$api_resp['status']  = false;
		$api_resp['message'] = '';

		try {
			self::translate_content( 'Hi', 'en', 'es', $api_settings );
			$api_resp['status'] = true;
		} catch ( \Throwable $e ) {
			$api_resp['message'] = $e->getMessage();
		}

		return $api_resp;
	}
}
