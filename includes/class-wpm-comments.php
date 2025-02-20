<?php

namespace WPM\Includes;
use WPM\Includes\Abstracts\WPM_Object;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class WPM_Comments
 *
 * @package  WPM/Classes
 * @author   Valentyn Riaboshtan
 */
class WPM_Comments extends WPM_Object {

	/**
	 * Object name
	 *
	 * @var string
	 */
	public $object_type = 'comment';

	/**
	 *Table name for meta
	 *
	 * @var string
	 */
	public $object_table = 'commentmeta';

	/**
	 * WPM_Comments constructor.
	 */
	public function __construct() {
		add_action( 'parse_comment_query', array( $this, 'filter_comments_by_language' ) );
		add_filter( 'get_comments_number', array( $this, 'fix_comment_count' ), 10, 2 );
		add_filter( "get_{$this->object_type}_metadata", array( $this, 'get_meta_field' ), 5, 3 );
		add_filter( "update_{$this->object_type}_metadata", array( $this, 'update_meta_field' ), 99, 5 );
		add_filter( "add_{$this->object_type}_metadata", array( $this, 'add_meta_field' ), 99, 5 );
		add_action( "delete_{$this->object_type}_metadata", array( $this, 'delete_meta_field' ), 99, 3 );
		add_filter( "wp_update_{$this->object_type}_data", array( $this, 'save_translated_comment' ), 10, 3 );
		add_filter( "get_{$this->object_type}", array( $this, 'translate_comment' ) );
	}

	/**
	 * Separate comments py languages
	 *
	 * @param $query object WP_Comment_Query
	 *
	 * @return object WP_Comment_Query
	 */
	public function filter_comments_by_language( $query ) {

		if ( defined( 'DOING_CRON' ) || ( is_admin() && ! is_front_ajax() ) ) {
			return $query;
		}

		$lang = get_query_var( 'lang' );

		if ( ! $lang ) {
			$lang = wpm_get_user_language();
		}

		if ( 'all' !== $lang ) {
			$meta_query = array(
				array(
					'relation' => 'OR',
					array(
						'key'     => '_languages',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_languages',
						'value'   => serialize( $lang ),
						'compare' => 'LIKE',
					),
				),
			);

			if ( isset( $query->query_vars['meta_query'] ) ) {
				$meta_query = wp_parse_args( $query->query_vars['meta_query'], $meta_query );
			}

			//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			$query->query_vars['meta_query'] = $meta_query;
		}

		return $query;
	}

	public function fix_comment_count( $count, $post_id ) {

		if ( defined( 'DOING_CRON' ) || ( is_admin() && ! is_front_ajax() ) ) {
			return $count;
		}

		$lang = get_query_var( 'lang' );

		if ( ! $lang ) {
			$lang = wpm_get_user_language();
		}

		$count_array = wp_cache_get( $post_id, 'wpm_comment_count' );

		if ( isset( $count_array[ $lang ] ) ) {
			return $count_array[ $lang ];
		} else {
			$count_array = array();
		}

		global $wpdb;

		$meta_query = array(
			array(
				'relation' => 'OR',
				array(
					'key'     => '_languages',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_languages',
					'value'   => serialize( $lang ),
					'compare' => 'LIKE',
				),
			),
		);

		$meta_sql = get_meta_sql( $meta_query, 'comment', $wpdb->comments, 'comment_ID' );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->comments} {$meta_sql['join']} WHERE comment_post_ID = %d AND comment_approved = '1' {$meta_sql['where']};", $post_id ) );

		$count_array[ $lang ] = $count;
		wp_cache_add( $post_id, $count_array, 'wpm_comment_count' );

		return $count;
	}
	
	/**
	 * Translate Comment data and store it in a comment meta table
	 * @param 	$commentdata 	array
	 * @return 	$commentdata 	array
	 * @since 	2.4.17
	 * */
	public function save_translated_comment( $data, $old_data, $commentarr ){
		
		if ( is_admin() ) {
			
			if ( is_array( $data ) && isset( $data['comment_content'] ) && is_array( $old_data ) && isset( $old_data['comment_content'] ) ) {
				$current_language 	=	wpm_get_language();
				$default_language 	=	wpm_get_default_language();
				$languages 			=	wpm_get_languages();

				// Comment has not been edited in default language, then store old comment only
				$updated_comment 	=	$data['comment_content'];
				if ( $current_language !==  $default_language) {
					$data['comment_content'] = $old_data['comment_content'];
				}

				if ( ! empty( $languages ) && is_array( $languages ) && array_key_exists( $current_language, $languages ) ) {

					$comment_id 	=	$data['comment_ID'];
					$comment_meta 	=	get_comment_meta( $comment_id, '_wpm_translated_comments', true );

					if ( empty( $comment_meta ) ) {
						$comment_meta =	array();
					}

					if ( empty( $comment_meta ) && $current_language !== $default_language ) {
						$comment_meta[$default_language] 	=	$old_data['comment_content'];			
					}

					$comment_meta[$current_language]		=	$updated_comment;
					update_comment_meta( $comment_id, '_wpm_translated_comments', $comment_meta );

				}
			}

		}
		return $data; 
	}

	/**
	 * Translate comment content
	 * @param 	$_comment 	WP_Comment 
	 * @return 	$_comment 	WP_Comment
	 * @since 	2.4.17
	 * */
	public function translate_comment( $_comment ) {
		
		if ( is_object( $_comment ) && isset( $_comment->comment_ID ) ) {

			$current_language 	=	wpm_get_language();
			$comment_id 		=	$_comment->comment_ID;

			$comment_meta 		=	get_comment_meta( $comment_id, '_wpm_translated_comments', true );
			if ( is_array( $comment_meta ) && isset( $comment_meta[$current_language] ) ) {
				$_comment->comment_content	=	$comment_meta[$current_language];
			}
		}

		return $_comment;
	}
}