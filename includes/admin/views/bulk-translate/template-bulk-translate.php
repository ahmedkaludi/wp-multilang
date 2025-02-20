<?php
/**
 * Admin View: Bulk Translate
 *
 * @package wpm
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="get" id="wpm-bulk-translate-form">
	<table style="display: none">
		<tbody id="wpm-bulk-translate">
			<tr id="wpm-translate-tr" class="inline-edit-row">
				<td colspan="10">
					<div class="inline-edit-wrapper">
						<span class="inline-edit-legend"><?php esc_html_e( 'Bulk translate', 'wp-multilang' ); ?></span>
						<div class="wpm-bulk-translate-fields-wrapper">
							<fieldset>
								<div class="inline-edit-col">
									<label>
										<span><?php echo esc_html__( 'File Format', 'wp-multilang' ); ?></span>
										<select name="wpm_bt_file_format">
											<option value="XLIFF_2-1"><?php echo esc_html__( 'XLIFF 2.1', 'wp-multilang' ); ?></option>
											<option value="XLIFF_2-0"><?php echo esc_html__( 'XLIFF 2.0', 'wp-multilang' ); ?></option>
											<option value="XLIFF_1-2"><?php echo esc_html__( 'XLIFF 1.2', 'wp-multilang' ); ?></option>
										</select>
										<span class="description"><?php echo esc_html__( 'Select file format to export selected content into a file', 'wp-multilang' ); ?></span>
									</label>
								</div>
							</fieldset>
						</div> <!-- wpm-bulk-translate-fields-wrapper div end  -->
						<p class="submit wpm-bulk-translate-save">
							<?php wp_nonce_field( 'wpm_bulk_translate', '_wpm_bulk_translate_nonce' ); ?>
							<button type="button" class="button button-secondary cancel"><?php esc_html_e( 'Cancel', 'wp-multilang' ); ?></button>
							<?php submit_button( __( 'Export', 'wp-multilang' ), 'primary', '', false ); ?>
						</p>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</form>