<select class="wpm-language-switcher wpm-switcher-<?php echo esc_attr( $type ); ?>" onchange="location = this.value;" title="<?php echo esc_html( __( 'Language Switcher', 'wp-multilang' ) ); ?>">
	<?php foreach ( $languages as $code => $language ) { ?>
		<option value="<?php echo esc_url( wpm_translate_current_url( $code ) ); ?>"<?php if ( $code === $lang ) { ?> selected="selected"<?php } ?> data-lang="<?php echo esc_attr( $code ); ?>">
			<?php echo esc_attr( $language['name'] ); ?>
		</option>
	<?php } ?>
</select>
