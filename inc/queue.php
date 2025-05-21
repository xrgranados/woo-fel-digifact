<?php
if (! defined('ABSPATH'))
    exit; // Exit if accessed directly

/**
 * Enqueues library styles for the admin area.
 */
function digifact_admin_assets()
{
    if (!is_admin()) {
        return;
    }

    // load font awesome
    wp_enqueue_style(
        'font-awesome',
        'https://use.fontawesome.com/releases/v5.0.13/css/all.css',
        array(),
        '5.0.13'
    );

    wp_enqueue_style(
        'jquery-ui-css',
        plugins_url('assets/jquery-ui-1.14.0/jquery-ui.min.css', dirname(__FILE__)),
        array(),
        '1.14.0'
    );

    wp_register_script(
        'jquery-ui',
        plugins_url('assets/jquery-ui-1.14.0/jquery-ui.min.js', dirname(__FILE__)),
        array('jquery'),
        '1.14.0',
        true
    );

    wp_enqueue_script('jquery-ui');

    wp_enqueue_script(
        'digifact-utils',
        plugins_url('assets/js/utils.js', dirname(__FILE__)),
        array('jquery'),
        '1.0.0',
        true
    );

    wp_enqueue_script(
        'digifact-script',
        plugins_url('assets/js/admin.js', dirname(__FILE__)),
        array('jquery'),
        '1.0.0',
        true
    );

    wp_enqueue_style(
        'digifact-style',
        plugins_url('assets/css/styles.css', dirname(__FILE__)),
        array(),
        '1.0.0'
    );

    if (! isset($_GET['page'])) {
        return;
    }

    if ('digifact' !== substr($_GET['page'], 0, 8)) {
        return;
    }

    wp_enqueue_style(
        'digifact-orders-table',
        plugins_url('assets/css/orders-table.css', dirname(__FILE__)),
        array(),
        '1.0.0'
    );

    wp_enqueue_style(
        'tailwindcss',
        'https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css',
        array(),
        '1.0.0'
    );
}

add_action('admin_enqueue_scripts', 'digifact_admin_assets');
