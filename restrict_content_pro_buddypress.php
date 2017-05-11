<?php
/**
 * Plugin Name: Restrict Content Pro - BuddyPress
 * Plugin URI:  https://iwitnessdesign.com/downloads/restrict-content-pro-buddypress/
 * Description: Extends Restrict Content Pro to integrate with BuddyPress
 * Version:     1.1
 * Author:      Tanner Moushey
 * Author URI:  https://iwitnessdesign.com/
 * License:     GPLv2+
 * Text Domain: rcpbp
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 Tanner Moushey (email : tanner@iwitnessdesign.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Useful global constants
define( 'RCPBP_VERSION', '1.1' );
define( 'RCPBP_URL',     plugin_dir_url( __FILE__ ) );
define( 'RCPBP_PATH',    dirname( __FILE__ ) . '/' );

// EDD Licensing constants
define( 'RCPBP_STORE_URL', 'https://iwitnessdesign.com' );
define( 'RCPBP_ITEM_NAME', 'Restrict Content Pro - BuddyPress' );
define( 'RCPBP_PLUGIN_LICENSE_PAGE', 'rcpbp-settings' );

include( RCPBP_PATH . '/includes/setup.php' );

/**
 * Default initialization for the plugin:
 * - Registers the default textdomain.
 */
function rcpbp_init() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'rcpbp' );
	load_textdomain( 'rcpbp', WP_LANG_DIR . '/rcpbp/rcpbp-' . $locale . '.mo' );
	load_plugin_textdomain( 'rcpbp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'rcpbp_init' );

/**
 * Activate the plugin
 */
function rcpbp_activate() {
	do_action( 'rcpbp_activate' );

	// First load the init scripts in case any rewrite functionality is being loaded
	rcpbp_init();

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'rcpbp_activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function rcpbp_deactivate() {
	do_action( 'rcpbp_deactivate' );
}
register_deactivation_hook( __FILE__, 'rcpbp_deactivate' );

/**
 * Initialize Updater
 */
function rcpbp_plugin_updater() {

	// load our custom updater
	if( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
		include( RCPBP_PATH . '/includes/updater.php' );
	}

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'rcpbp_license_key' ) );

	// setup the updater
	new EDD_SL_Plugin_Updater( RCPBP_STORE_URL, __FILE__, array(
			'version'   => RCPBP_VERSION,    // current version number
			'license'   => $license_key,     // license key (used get_option above to retrieve from DB)
			'item_name' => urlencode( RCPBP_ITEM_NAME ), // the name of our product in EDD
			'author'    => 'Tanner Moushey'  // author of this plugin
		)
	);

}
add_action( 'admin_init', 'rcpbp_plugin_updater' );

/**
 * check the amount of groups and prevent the user from creating if max is reached
 */
function rcpbp_check_group_numbers() {

	global $wpdb;
	global $rcp_levels_db;

	//get the groups created by this user
	$created_groups = $wpdb->get_results( $wpdb->prepare( "SELECT name FROM wp_bp_groups WHERE creator_id = %d", get_current_user_id() ) );

	//count the number of groups created by this user
	$created_groups_count = count($created_groups);

	//get the current user subscription level
	$user_sub_id = rcp_get_subscription_id(get_current_user_id());

	//get the current users subscription level limit
	$sub_level_limit = $rcp_levels_db->get_meta( $user_sub_id,'rcpbp_group_limit', $single = true );

	//if the user has reached their max amount of groups allowed to create
	if ($created_groups_count >= $sub_level_limit) {

		//get the groups page post id
		$bp_pages = get_option( 'bp-pages' );
		$groups_page_id = $bp_pages['groups'];

		//go back to the groups page
		wp_redirect(get_permalink($groups_page_id));
		bp_core_add_message(esc_html('You have reached the max amount of groups allowed to create.'),'warning');
	}

}

add_action( 'groups_action_sort_creation_steps', 'rcpbp_check_group_numbers', 10 );

