<?php

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('dkl_rental_version');
delete_option('dkl_rental_settings');

// Remove database tables
global $wpdb;

$tables = array(
    $wpdb->prefix . 'dkl_customers',
    $wpdb->prefix . 'dkl_rentals',
    $wpdb->prefix . 'dkl_staff'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Remove upload directories (optional - commented out to preserve user data)
/*
$upload_dir = wp_upload_dir();
$dkl_dir = $upload_dir['basedir'] . '/dkl-rental';

if (file_exists($dkl_dir)) {
    // Remove directory and all contents
    function dkl_remove_directory($dir) {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                if (is_dir("$dir/$file")) {
                    dkl_remove_directory("$dir/$file");
                } else {
                    unlink("$dir/$file");
                }
            }
            rmdir($dir);
        }
    }
    dkl_remove_directory($dkl_dir);
}
*/

// Remove scheduled events
wp_clear_scheduled_hook('dkl_rental_cleanup');

// Remove custom user roles
remove_role('dkl_customer');