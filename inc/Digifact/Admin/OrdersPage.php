<?php

namespace Digifact\Admin;

use Digifact\Admin\OrdersTable;

/**
 * Class OrdersPage
 * @package Digifact\Admin
 */
class OrdersPage
{
    /**
     * OrdersPage constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    /**
     * Add the menu to the admin page.
     *
     * @return void
     */
    public function addMenu()
    {
        add_submenu_page(
            'digifact',
            'Órdenes WooCommerce',
            'Órdenes WooCommerce',
            'manage_options',
            'digifact-orders',
            [$this, 'renderOrdersPage'],
        );
    }

    /**
     * Make function for render
     *
     * @return void
     */
    public function renderOrdersPage() {
        // Make instance for OrdersTable
        $table = new OrdersTable();
        // Call function for display
        $table->display();
    }
} // End OrdersPage Class
