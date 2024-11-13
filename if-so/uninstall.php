<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://if-so.com
 * @since      1.0.0
 *
 * @package    IfSo
 */

require_once ('services/plugin-settings-service/plugin-settings-service.class.php');

use IfSo\Services;

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function ifso_delete_plugin() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/ifso-constants.php';
    require_once IFSO_PLUGIN_BASE_DIR . 'services/license-service/license-service.class.php';
    require_once IFSO_PLUGIN_BASE_DIR . 'services/license-service/geo-license-service.class.php';

	global $wpdb;

	$posts = get_posts( array(
		'numberposts' => -1,
		'post_type' => 'ifso_triggers',
		'post_status' => 'any' ) );

	foreach ( $posts as $post ) {
		wp_delete_post( $post->ID, true );
		delete_post_meta($post->ID, 'ifso_trigger_default');
		delete_post_meta($post->ID, 'ifso_trigger_rules');
		delete_post_meta($post->ID, 'ifso_trigger_version');
	}

	delete_option('ifso_groups_data');  //Remove "groups" data

	Services\LicenseService\LicenseService::get_instance()->clear_license();
    Services\GeoLicenseService\GeoLicenseService::get_instance()->clear_license();

    $tables_created_by_ifso = ["{$wpdb->prefix}ifso_local_user","{$wpdb->prefix}ifso_daily_sessions"];
    foreach($tables_created_by_ifso as $table){
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

}

function ifso_is_remove_checked() {
	$settings_service = Services\PluginSettingsService\PluginSettingsService::get_instance();
	$to_remove = $settings_service->removePluginDataOption->get();

	return $to_remove;
}


if ( ifso_is_remove_checked() ) {
	 ifso_delete_plugin();
}