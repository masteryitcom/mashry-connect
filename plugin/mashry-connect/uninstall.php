<?php
/**
 * Uninstall mashry Connect
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

// Delete options
delete_option('mashry_node_server_url');
delete_option('mashry_connect_installed');
delete_option('mashry_connect_version');

// Drop database table
global $wpdb;
$table_name = $wpdb->prefix . 'mashry_migration_tracking';
$wpdb->query("DROP TABLE IF EXISTS $table_name");