<?php
/**
 * Language switcher widget for frontend
 */
namespace WPM\Includes\Widgets;
use WPM\Includes\Abstracts\WPM_Widget;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class WPM_Widget_Language_Switcher
 * @package WPM/Includes/Widgets
 * @category Class
 * @author   Valentyn Riaboshtan
 */
class WPM_Widget_Language_Switcher extends WPM_Widget {

	/**
	 * WPM_Widget_Language_Switcher constructor.
	 */
	public function __construct() {
		$this->widget_cssclass    = 'wpm widget_language_switcher';
		$this->widget_description = esc_html__( 'Display language switcher.', 'wp-multilang' );
		$this->widget_id          = 'wpm_language_switcher';
		$this->widget_name        = esc_html__( 'Language Switcher', 'wp-multilang' );
		$this->settings           = array(
			'title' => array(
				'type'  => 'text',
				'std'   => esc_html__( 'Languages', 'wp-multilang' ),
				'label' => esc_html__( 'Title', 'wp-multilang' ),
			),
			'show'  => array(
				'type'    => 'select',
				'std'     => 'both',
				'options' => array(
					'both'     => esc_html__( 'Both', 'wp-multilang' ),
					'flag'     => esc_html__( 'Flag', 'wp-multilang' ),
					'name' => esc_html__( 'Name', 'wp-multilang' ),
				),
				'label'   => esc_html__( 'Show', 'wp-multilang' ),
			),
			'type'  => array(
				'type'    => 'select',
				'std'     => 'list',
				'options' => array(
					'list'     => esc_html__( 'List', 'wp-multilang' ),
					'dropdown' => esc_html__( 'Dropdown', 'wp-multilang' ),
					'select'   => esc_html__( 'Select', 'wp-multilang' ),
				),
				'label'   => esc_html__( 'Switcher Type', 'wp-multilang' ),
			),
		);
		parent::__construct();
	}

	/**
	 * Display language switcher
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		ob_start();

		$this->widget_start( $args, $instance );

		wpm_language_switcher( $instance['type'], $instance['show'] );

		$this->widget_end( $args );

		$content_escaped = ob_get_clean();

		//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all html inside this variable already escaped above in wpm_language_switcher() function
		echo $content_escaped;

	}
}
