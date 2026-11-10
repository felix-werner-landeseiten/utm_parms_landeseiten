<?php
/**
 * Removes all plugin data when the plugin is deleted from the Plugins screen.
 *
 * @package UTM_Parameter_Forwarder
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'utmpf_settings' );
delete_site_transient( 'utmpf_release_info' );
