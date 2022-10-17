<?php
/**
 * Plugin Name:       UBC H5P addon - Content Recovery
 * Plugin URI:
 * Description:       Allow H5P content to have the same trash system as WordPress posts have.
 * Version:           1.0.2
 * Author:            Kelvin Xu
 * Author URI:         https://ctlt.ubc.ca/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ubc-h5p
 *
 * @package ubc_h5p
 */

namespace UBC\H5P\ContentRecovery;

define( 'H5P_CONTENT_RECOVERY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'H5P_CONTENT_RECOVERY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

require_once 'includes/class-contentrecoverydb.php';

/**
 * Init plugin functionality.
 */
function init() {

	if ( ! class_exists( '\UBC\H5P\Taxonomy\ContentTaxonomy\ContentTaxonomy' ) ) {
		return;
	}

	require_once 'includes/class-contentrecovery.php';
}

/* --------------------------------------------------------------------------------------------------------------------------------------------------- */
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
register_activation_hook( __FILE__, __NAMESPACE__ . '\\ContentRecoveryDB::add_table_column' );
