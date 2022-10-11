<?php
/**
 * Content Recovery DB class.
 *
 * @since 1.0.0
 * @package ubc-h5p-content-recovery
 */

namespace UBC\H5P\ContentRecovery;

/**
 * Class to initiate Content RecoveryDB functionalities
 */
class ContentRecoveryDB {

	/**
	 * Add trashed column to h5p content table.
	 *
	 * @return void
	 */
	public static function add_table_column() {

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;
		$table_name = $wpdb->prefix . 'h5p_contents';

		\maybe_add_column( $table_name, 'trashed', "ALTER TABLE {$table_name} ADD trashed INT DEFAULT 0" );
	}

	/**
	 * Trash an existing H5P content.
	 *
	 * @param int $content_id ID of the h5p content.
	 *
	 * @return FALSE return false on failer, true on success.
	 */
	public static function do_trash( $content_id ) {

		if ( self::is_trashed( $content_id ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;
		$table_name = $wpdb->prefix . 'h5p_contents';

		// Check if content exist and has not been trashed.
		$results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}h5p_contents WHERE id = %d AND trashed = %d", $content_id, 0 ) );

		if ( null === $results ) {
			return false;
		}

		// Do trash the content.
		$results = $wpdb->update(
			$table_name,
			array( 'trashed' => 1 ),
			array( 'id' => $content_id ),
		);

		return false !== $results;
	}

	/**
	 * Undo trash for an existing H5P content.
	 *
	 * @param int $content_id ID of the h5p content.
	 *
	 * @return FALSE return false on failer, true on success.
	 */
	public static function undo_trash( $content_id ) {

		if ( ! self::is_trashed( $content_id ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;
		$table_name = $wpdb->prefix . 'h5p_contents';

		// Check if content exist and has been trashed.
		$results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}h5p_contents WHERE id = %d AND trashed = %d", $content_id, 1 ) );

		if ( null === $results ) {
			return false;
		}

		// Do trash the content.
		$results = $wpdb->update(
			$table_name,
			array( 'trashed' => 0 ),
			array( 'id' => $content_id ),
		);

		return false !== $results;
	}

	/**
	 * Check if a h5p content is trashed
	 *
	 * @param int $content_id ID of the h5p content.
	 *
	 * @return FALSE return false on failer, true on success.
	 */
	public static function is_trashed( $content_id ) {

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;
		$table_name = $wpdb->prefix . 'h5p_contents';

		// Check if content exist and has been trashed.
		$results = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}h5p_contents WHERE id = %d AND trashed = %d", $content_id, 1 ) );

		return null !== $results;
	}

	/**
	 * Delete H5P content based on content ID
	 *
	 * @param int $content_id the ID of the h5p content to delete.
	 * @return void
	 */
	public static function do_delete( $content_id ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;
		$plugin  = \H5P_Plugin::get_instance();
		$content = $plugin->get_content( $content_id );

		if ( is_string( $content ) ) {
			return;
		}

		$tags = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.name
					FROM {$wpdb->prefix}h5p_contents_tags ct
					JOIN {$wpdb->prefix}h5p_tags t ON ct.tag_id = t.id
					WHERE ct.content_id = %d",
				$content_id
			)
		);

		$content['tags'] = '';
		foreach ( $tags as $tag ) {
			$content['tags'] .= ( '' !== $content['tags'] ? ', ' : '' ) . $tag->name;
		}

		$storage = $plugin->get_h5p_instance( 'storage' );
		$storage->deletePackage( $content );
	}
}
