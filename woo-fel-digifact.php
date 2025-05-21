<?php
/*
Plugin Name: Woo Fel DigiFact
Description: A plugin to integrate DigiFact with WooCommerce.
Version: 1.1.0
Author: Rafael Granados
Author URI: https://github.com/xrgranados
Plugin URI: https://rgranados.notion.site/Woo-Fel-Digifact-1fa62456ebc0807db1e5fb2996158979?pvs=4
License: GPLv2 or later
*/

// Prevent direct access to the file
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Ensure that WooCommerce is active before proceeding
if (! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    exit; // Exit if WooCommerce is not active
}

// Include necessary files for the DigiFact functionality
require_once('inc/utils.php'); // Include additional helper functions
require_once('inc/functions.php'); // Include additional helper functions
require_once('inc/queue.php'); // Include the queue handling functionality

if (!function_exists('simplexml_load_string')) {
    // Show an error message if the SimpleXML extension is not installed
    add_action('admin_notices', function () {
        echo '<div class="error"><p>El plugin requiere la extensión SimpleXML de PHP</p></div>';
    });
}

/**
 * Autoload classes
 */
spl_autoload_register(function ($class) {
    // Base namespace del plugin
    $namespaces = [
        'Digifact\\' => 'inc/Digifact/',
        'Digifact\\Admin\\' => 'inc/Digifact/Admin/',
        'Digifact\\Models\\' => 'inc/Digifact/Models/'
    ];

    foreach ($namespaces as $namespace => $path) {
        if (strpos($class, $namespace) === 0) {
            $relative_class = substr($class, strlen($namespace));
            $file = plugin_dir_path(__FILE__) . $path . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

register_activation_hook(__FILE__, 'create_digifact_invoices_table');

/**
 * Creates the DigiFact table in the database upon plugin activation.
 *
 * This function is registered to run on plugin activation and creates the table
 * used to store DigiFact details.
 *
 * @return void
 */
function create_digifact_invoices_table()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'digifact_invoices';
    $charset_collate = $wpdb->get_charset_collate();

    $status = 'certified' ;

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        status varchar(50) DEFAULT '{$status}' NOT NULL,
        invoice_number varchar(255) NOT NULL,
        authorization varchar(255) NOT NULL,
        amount decimal(10,2) NOT NULL,
        customer_nit varchar(50),
        customer_name varchar(255),
        certificated_at datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        annulated_at datetime DEFAULT NULL,
        annulation_reason varchar(255) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY order_id (order_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
