<?php
/**
 * WP Multilang Gemini
 * @since 	2.4.34
 */
namespace WPM\Includes\Admin;

use WPM\Includes\Admin\Settings\WPM_Settings_AI_Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WPM_Gemini {

	public const API_BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/';

	public function __construct() {
		add_action( 'wpm_render_gemini_settings', [ $this, 'render_settings' ], 10, 1 );
		add_filter( 'wpm_filter_autotranslate_localize_data', [ $this, 'filter_localize_data' ] );
	}

	public function filter_localize_data( $params ) {
		$params['wpm_gemini_integration'] = get_option( 'wpm_gemini_integration', '0' );
		$params['ai_settings']['wpm_gemini_integration'] = $params['wpm_gemini_integration'];

		if ( isset( $params['ai_settings']['api_provider'] ) && $params['ai_settings']['api_provider'] === 'gemini' ) {
			if ( ! empty( $params['ai_settings']['gemini_model'] ) ) {
				$params['ai_settings']['model'] = $params['ai_settings']['gemini_model'];
			}
		}

		return $params;
	}

	/**
	 * Render Gemini settings panel
	 * @param array $ai_settings
	 */
	public function render_settings( $ai_settings ) {
		$secret_key             = isset( $ai_settings['gemini_secret_key'] ) && ! empty( $ai_settings['gemini_secret_key'] ) ? $ai_settings['gemini_secret_key'] : ( isset( $ai_settings['api_keys']['gemini'] ) ? $ai_settings['api_keys']['gemini'] : '' );
		$prompt                 = ! empty( $ai_settings['gemini_prompt'] ) ? $ai_settings['gemini_prompt'] : ( defined( 'WPM_GEMINI_PROMPT' ) ? WPM_GEMINI_PROMPT : WPM_OPENAI_PROMPT );
		$models                 = ! empty( $ai_settings['api_available_models']['gemini'] ) ? $ai_settings['api_available_models']['gemini'] : [];
		$selected_model         = ! empty( $ai_settings['gemini_model'] ) ? $ai_settings['gemini_model'] : ( ! empty( $ai_settings['model'] ) && isset( $ai_settings['api_provider'] ) && $ai_settings['api_provider'] === 'gemini' ? $ai_settings['model'] : '' );
		$wpm_gemini_integration = get_option( 'wpm_gemini_integration', '0' );

		$hide_child = 'wpm-hide';
		if ( $wpm_gemini_integration === '1' ) {
			$hide_child = '';
		}

		$hide_models_class = '';
		if ( empty( $models ) ) {
			$hide_models_class = 'wpm-hide';
		}
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label class="wpm-label-cursor" for="wpm_gemini_integration"><?php echo esc_html__( 'Gemini Integration', 'wp-multilang' ); ?></label>
			</th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<label for="wpm_gemini_integration">
						<input name="wpm_gemini_integration" id="wpm_gemini_integration" type="checkbox" value="1" <?php checked( $wpm_gemini_integration, '1' ); ?>>
					</label>
				</fieldset>
			</td>
		</tr>

		<tr valign="top" class="wpm-hide-gemini-wrapper wpm-gemini-children <?php echo esc_attr( $hide_child ); ?>">
			<th scope="row" class="titledesc wpm-pl-20">
				<label for="wpm-gemini-secretkey"><?php echo esc_html__( 'API Key', 'wp-multilang' ); ?></label>
			</th>
			<td class="wpm-pl-20">
				<input class="regular-text" type="password" id="wpm-gemini-secretkey" name="wpm_gemini_secretkey" value="<?php echo esc_attr( $secret_key ); ?>">
				<button type="button" id="wpm-validate-gemini-key" class="button"><?php echo esc_html__( 'Validate API Key', 'wp-multilang' ); ?></button>
				<span class="description wpm-pl-10"><a href="https://aistudio.google.com/app/apikey" target="_blank"><?php echo esc_html__( 'Get API Key.', 'wp-multilang' ); ?></a></span>
				<div id="wpm-gemini-secret-key-error"><?php echo esc_html__( 'API key cannot be blank', 'wp-multilang' ); ?></div>
				<div class="wpm-gemini-api-success-note"></div>
				<div class="wpm-gemini-api-error-note"></div>
			</td>
		</tr>

		<tr valign="top" id="wpm-hide-gemini-models-wrapper" class="wpm-hide-gemini-wrapper wpm-gemini-children <?php echo esc_attr( $hide_models_class ) . ' ' . esc_attr( $hide_child ); ?>">
			<th scope="row" class="titledesc wpm-pl-20">
				<label for="wpm-gemini-models"><?php echo esc_html__( 'Translation Models', 'wp-multilang' ); ?></label>
			</th>
			<td class="wpm-pl-20">
				<select name="wpm_gemini_models" id="wpm-gemini-models">
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

		<tr valign="top" class="wpm-hide-gemini-wrapper wpm-gemini-children <?php echo esc_attr( $hide_child ); ?>">
			<th scope="row" class="titledesc wpm-pl-20">
				<label for="wpm-gemini-prompt"><?php echo esc_html__( 'Prompt', 'wp-multilang' ); ?></label>
			</th>
			<td class="wpm-pl-20">
				<textarea class="regular-text" rows="5" id="wpm-gemini-prompt" name="wpm_gemini_prompt"><?php echo esc_html( $prompt ); ?></textarea>
				<p class="description"><?php echo esc_html__( 'Please ensure the prompt contains the placeholders ', 'wp-multilang' ); ?> <code>{{source_language}}</code> <?php echo esc_html__( 'and ', 'wp-multilang' ); ?> <code>{{target_language}}</code>, <?php echo esc_html__( 'which will be dynamically replaced during translation.', 'wp-multilang' ); ?></p>
				<div id="wpm-gemini-prompt-error"></div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Validate Gemini API secret key & fetch available models
	 * @return array
	 * @throws \Exception
	 */
	public static function validate_secret_key() {
		if ( empty( $_POST['secret_key'] ) ) {
			throw new \InvalidArgumentException( esc_html__( 'Please enter a valid API key for Gemini.', 'wp-multilang' ) );
		}

		$api_key   = sanitize_text_field( wp_unslash( $_POST['secret_key'] ) );
		$provider  = 'gemini';
		$end_point = self::API_BASE_URL . 'models?key=' . rawurlencode( $api_key );

		$response = wp_remote_get( $end_point, [
			'headers'   => [
				'Content-Type'   => 'application/json',
				'x-goog-api-key' => $api_key,
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
		if ( isset( $decoded_body['models'] ) && is_array( $decoded_body['models'] ) ) {
			foreach ( $decoded_body['models'] as $m ) {
				if ( isset( $m['name'] ) && isset( $m['supportedGenerationMethods'] ) && is_array( $m['supportedGenerationMethods'] ) ) {
					if ( in_array( 'generateContent', $m['supportedGenerationMethods'], true ) ) {
						$model_name = str_replace( 'models/', '', $m['name'] );
						// Include Gemini models only (ignore text-embedding, aqa, etc.)
						if ( stripos( $model_name, 'gemini' ) !== false ) {
							$models[] = $model_name;
						}
					}
				}
			}
		}

		if ( empty( $models ) ) {
			$models = [
				'gemini-2.5-flash',
				'gemini-2.0-flash',
				'gemini-1.5-flash',
				'gemini-1.5-pro',
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
	 * API request to translate content using Gemini
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
		if ( ! empty( $settings['gemini_secret_key'] ) ) {
			$api_key = $settings['gemini_secret_key'];
		} elseif ( ! empty( $settings['api_keys']['gemini'] ) ) {
			$api_key = $settings['api_keys']['gemini'];
		}

		if ( empty( $api_key ) ) {
			throw new \Exception( esc_html__( 'Gemini API Key is missing.', 'wp-multilang' ) );
		}

		$model = ! empty( $settings['gemini_model'] ) ? $settings['gemini_model'] : ( ! empty( $settings['model'] ) ? $settings['model'] : 'gemini-2.5-flash' );
		$model = ltrim( str_replace( 'models/', '', $model ), '/' );

		$prompt = ! empty( $settings['gemini_prompt'] ) ? $settings['gemini_prompt'] : ( ! empty( $settings['api_prompt'] ) ? $settings['api_prompt'] : WPM_GEMINI_PROMPT );
		$prompt = str_replace( [ '{{source_language}}', '{{target_language}}' ], [ $source, $target ], $prompt );

		if ( strpos( $string, '|||WPM_SEP|||' ) !== false ) {
			$prompt .= ' Important: The text contains "|||WPM_SEP|||" separator tokens dividing distinct sections. Preserve all "|||WPM_SEP|||" tokens exactly in place without altering, translating, or removing them.';
		}

		$endpoint = self::API_BASE_URL . 'models/' . rawurlencode( $model ) . ':generateContent?key=' . rawurlencode( $api_key );

		$body = [
			'systemInstruction' => [
				'parts' => [
					[ 'text' => $prompt ],
				],
			],
			'contents'          => [
				[
					'role'  => 'user',
					'parts' => [
						[ 'text' => $string ],
					],
				],
			],
			'generationConfig'  => [
				'temperature' => 0.2,
			],
			'safetySettings'    => [
				[
					'category'  => 'HARM_CATEGORY_HARASSMENT',
					'threshold' => 'BLOCK_ONLY_HIGH',
				],
				[
					'category'  => 'HARM_CATEGORY_HATE_SPEECH',
					'threshold' => 'BLOCK_ONLY_HIGH',
				],
				[
					'category'  => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
					'threshold' => 'BLOCK_ONLY_HIGH',
				],
				[
					'category'  => 'HARM_CATEGORY_DANGEROUS_CONTENT',
					'threshold' => 'BLOCK_ONLY_HIGH',
				],
			],
		];

		$response = wp_remote_post( $endpoint, [
			'headers' => [
				'Content-Type'   => 'application/json',
				'x-goog-api-key' => $api_key,
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

		if ( isset( $data['candidates'][0]['finishReason'] ) && $data['candidates'][0]['finishReason'] === 'SAFETY' ) {
			throw new \Exception( esc_html__( 'Gemini blocked translation due to safety policy.', 'wp-multilang' ) );
		}

		if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
			$translated_text = trim( $data['candidates'][0]['content']['parts'][0]['text'] );

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
