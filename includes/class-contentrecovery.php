<?php
/**
 * Content recovery class.
 *
 * @since 1.0.0
 * @package ubc-h5p-content-recovery
 */

namespace UBC\H5P\ContentRecovery;

/**
 * Class to initiate Content Recovery functionalities
 */
class ContentRecovery {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		add_action( 'load-h5p-content_page_h5p_new', array( $this, 'enqueue_add_new_content_script' ), 10 );
		add_action( 'wp_ajax_ubc_h5p_trash_content', array( $this, 'do_trash' ) );
		add_action( 'wp_ajax_ubc_h5p_delete_content', array( $this, 'do_delete' ) );

		add_action( 'toplevel_page_h5p', array( $this, 'enqueue_listing_view_script' ), 99 );
		add_filter( 'h5p_content_taxonomy_context_query', array( $this, 'query_data_context_query' ), 10, 2 );

		add_filter( 'h5p_add_field_to_query_response', array( $this, 'add_additional_field_to_query_response' ) );
		add_filter( 'h5p_embed_access', array( $this, 'can_h5p_content_embed' ), 10, 2 );
	}
	/**
	 * Load assets for h5p new content page.
	 *
	 * @return void
	 */
	public function enqueue_add_new_content_script() {
		if ( ! ( isset( $_GET['page'] ) && 'h5p_new' === $_GET['page'] && isset( $_GET['id'] ) ) ) {
			return;
		}

		wp_enqueue_script(
			'ubc-h5p-content-recovery-js',
			H5P_CONTENT_RECOVERY_PLUGIN_URL . 'dist/content-edit-view.js',
			array(),
			filemtime( H5P_CONTENT_RECOVERY_PLUGIN_DIR . 'dist/content-edit-view.js' ),
			true
		);

		wp_localize_script(
			'ubc-h5p-content-recovery-js',
			'ubc_h5p_content_recovery_admin',
			array(
				'security_nonce' => wp_create_nonce( 'security' ),
				'data'           => array(
					'is_content_trashed' => (bool) ContentRecoveryDB::is_trashed( absint( $_GET['id'] ) ),
				),
			)
		);

		wp_register_style(
			'ubc-h5p-content-recovery-css',
			H5P_CONTENT_RECOVERY_PLUGIN_URL . 'dist/content-edit-view.css',
			array(),
			filemtime( H5P_CONTENT_RECOVERY_PLUGIN_DIR . 'dist/content-edit-view.css' ),
		);
		wp_enqueue_style( 'ubc-h5p-content-recovery-css' );
	}//end enqueue_add_new_content_script()

	/**
	 * Ajax handler to trash/untrash an H5P content.
	 *
	 * @return void
	 */
	public function do_trash() {

		check_ajax_referer( 'security', 'nonce' );

		$content_id   = isset( $_POST['content_id'] ) ? absint( $_POST['content_id'] ) : null;
		$trash_action = isset( $_POST['trash_action'] ) ? sanitize_textarea_field( wp_unslash( $_POST['trash_action'] ) ) : null;

		if ( empty( $content_id ) ) {
			wp_send_json(
				array(
					'valid'   => false,
					'message' => __( 'System error, please contact platform administrator.', 'ubc-h5p-addon-content-recovery' ),
				)
			);
		}

		$result = 'trash' === $trash_action ? ContentRecoveryDB::do_trash( $content_id ) : ContentRecoveryDB::undo_trash( $content_id );

		wp_send_json(
			array(
				'valid'   => $result,
				'message' => $result ?
				'trash' === $trash_action ? __( 'The content has been successfully trashed.', 'ubc-h5p-addon-content-recovery' ) : __( 'The content has been successfully untrashed.', 'ubc-h5p-addon-content-recovery' )
				: __( 'System error, please contact platform administrator.', 'ubc-h5p-addon-content-recovery' ),
			)
		);
	}

	/**
	 * Ajax requet handler to delete a H5P content based on content ID
	 *
	 * @return void
	 */
	public function do_delete() {
		check_ajax_referer( 'security', 'nonce' );

		$content_id   = isset( $_POST['content_id'] ) ? absint( $_POST['content_id'] ) : null;

		if ( empty( $content_id ) ) {
			wp_send_json(
				array(
					'valid'   => false,
					'message' => __( 'System error, please contact platform administrator.', 'ubc-h5p-addon-content-recovery' ),
				)
			);
		}

		$result = ContentRecoveryDB::do_delete( $content_id );
	}

	/**
	 * Enqueue necessary Javascript for listing view.
	 *
	 * @return void
	 */
	public function enqueue_listing_view_script() {
		if ( ! \UBC\H5P\Taxonomy\ContentTaxonomy\Helper::is_h5p_list_view_page() ) {
			return;
		}

		wp_enqueue_script(
			'ubc-h5p-content-recovery-listing-view-js',
			H5P_CONTENT_RECOVERY_PLUGIN_URL . 'dist/listing-view.js',
			array(),
			filemtime( H5P_CONTENT_RECOVERY_PLUGIN_DIR . 'dist/listing-view.js' ),
			true
		);

		wp_localize_script(
			'ubc-h5p-content-recovery-listing-view-js',
			'ubc_h5p_content_recovery_admin',
			array(
				'security_nonce' => wp_create_nonce( 'security' ),
			)
		);

		wp_register_style(
			'ubc-h5p-content-recovery-listing-view-css',
			H5P_CONTENT_RECOVERY_PLUGIN_URL . '/dist/listing-view.css',
			array(),
			filemtime( H5P_CONTENT_RECOVERY_PLUGIN_DIR . 'dist/listing-view.css' )
		);
		wp_enqueue_style( 'ubc-h5p-content-recovery-listing-view-css' );
	}//end enqueue_listing_view_script()

	/**
	 * Filter the context query section of the main content listing/filtering query.
	 *
	 * @param string $query context part of the main listing query.
	 * @param string $context current context from the request.
	 * @return string
	 */
	public function query_data_context_query( $query, $context ) {

		// phpcs:ignore
		$trash = isset( $_POST['trash'] ) ? sanitize_text_field( wp_unslash( $_POST['trash'] ) ) : '0';

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		return $query . ' AND trashed = ' . $trash;
	}

	/**
	 * Return trashed status from database query.
	 *
	 * @param array $fields list of extra fields added to the query.
	 * @return array
	 */
	public function add_additional_field_to_query_response( $fields ) {
		if ( in_array( 'trashed', $fields, true ) ) {
			return;
		}

		array_push( $fields, 'trashed' );
		return $fields;
	}

	/**
	 * Block embed if the H5P content is trashed.
	 *
	 * @param Bool $embed_allowed Is embed enabled gloablly.
	 * @param Int  $id Id of the current embeded H5P content.
	 *
	 * @return Bool False if the content is trashed, true if the content is not trashed.
	 */
	public function can_h5p_content_embed( $embed_allowed, $id ) {
		if ( ! $embed_allowed ) {
			return $embed_allowed;
		}

		return ! ContentRecoveryDB::is_trashed( $id );
	}

}

new ContentRecovery();
