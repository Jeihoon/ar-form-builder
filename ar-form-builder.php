<?php
/**
 * Plugin Name: AR Form Builder
 * Plugin URI: https://formsplugin.mypixellab.com/
 * Description: AR Form Builder is a powerful and user-friendly WordPress plugin that lets you create fully customized forms using an intuitive drag-and-drop interface—no coding required. Whether you’re building a contact form, registration form, or feedback form, AR Form Builder gives you complete control over layout, fields, and design. With support for reCAPTCHA, file uploads, conditional logic (upcoming), and database entry storage, it’s the perfect solution for anyone who needs a flexible and reliable form system that works seamlessly with any theme.
 * Version: 1.0.0
 * Author: Amin Rahnama
 * Author URI: https://mypixellab.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ar-form-builder
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Plugin initialization code here.
function ar_form_builder_init() {
    // Load text domain
    load_plugin_textdomain( 'ar-form-builder', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Include necessary files
    // include_once plugin_dir_path( __FILE__ ) . 'includes/class-ar-form-builder.php';

    // Init the form builder
    // $form_builder = new AR_Form_Builder();
}
add_action( 'plugins_loaded', 'ar_form_builder_init' );
// Include Admin and Frontend Classes
require_once plugin_dir_path( __FILE__ ) . 'admin/class-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'frontend/class-frontend.php';

class AR_Form_Builder {

    public function __construct() {
        // Plugin activation hook
        register_activation_hook( __FILE__, array( $this, 'install' ) );

        // Initialize admin functionality in the dashboard
        if ( is_admin() ) {
            new AR_Form_Builder_Admin();
        }

        // Initialize front-end functionality for public pages
        new AR_Form_Builder_Frontend();
    }

    public static function install() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'amin_form_entries';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id varchar(255) NOT NULL,
            submission longtext NOT NULL,
            submission_date datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }
}

new AR_Form_Builder();
