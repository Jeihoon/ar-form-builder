<?php
/**
 * Uninstall AR Form Builder
 *
 * @package AR_Form_Builder
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete all plugin options.
delete_option( 'amin_form_builder_forms' );
delete_option( 'amin_form_builder_recaptcha' );
delete_option( 'amin_form_builder_destinations' );

// Optionally, drop the custom table for form entries.
global $wpdb;
$table_name = $wpdb->prefix . 'amin_form_entries';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
