<?php

// Inicializar la página de facturas
add_action('init', function () {
    new Digifact\Admin\InvoicesPage();
    new Digifact\Admin\SettingsPage();
    new Digifact\Admin\ProcessBilling();
});
