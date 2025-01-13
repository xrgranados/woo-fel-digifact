<?php

namespace Digifact\Admin;

use Digifact\Models\Invoice;

/**
 * Class AdminDashboard
 *
 * Represents the DigiFact admin dashboard.
 *
 * @package Digifact\Admin
 * @author Rafael Granados <xr.grandoso@gmail.com>
 */
class AdminDashboard
{
    /** @var Invoice invoice model instance */
    private $invoiceModel;

    /**
     * AdminMenu constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'registerMenu']);

        $this->invoiceModel = new Invoice();
    }

    /**
     * Add the menu to the admin page.
     *
     * @return void
     */
    public function registerMenu()
    {
        $icon = 'data:image/svg+xml;base64,' . base64_encode('<svg version="1.2" xmlns="http://www.w3.org/2000/svg" width="24" height="24"><style>.s0{fill:#a7aaad}.s0:hover{fill:#72aee6}</style><g id="layer1"><path id="path1054" class="s0" d="M0 7.1v9.4c0 2.3 1.9 4.1 4.2 4.1h6c4.4 0 8-3.4 8-7.7V7.1C18.2 4.8 16.3 3 14 3H4.2C1.9 3 0 4.8 0 7.1zm12.7 1.2v4.2c0 1.6-1.3 2.8-2.9 2.8H5.5v-7z"/><path id="path1056" fill-rule="evenodd" class="s0" d="M21.5 19.8c-1.2 0-2.1-.9-2.1-2s.9-2 2.1-2c1.2 0 2.1.9 2.1 2s-.9 2-2.1 2z"/></g></svg>');

        // main menu Digifact
        add_menu_page(
            'Digifact',
            'Digifact',
            'manage_options',
            'digifact',
            [$this, 'renderDashboardPage'],
            $icon,
            30
        );
    }

    /**
     * Renders the dashboard page.
     *
     * @return void
     */
    public function renderDashboardPage()
    {
        $result = $this->getStats();
        ?>
        <div class="wrap">
            <div id="digifact-settings-container" class="bg-white shadow rounded p-4 grid grid-cols-12">
                <div class="col-span-3">
                    <?php
                    echo _renderHtml('img', [
                        'src' => plugins_url('assets/img/digifact-logo.png', dirname(__DIR__, 2)),
                        'alt' => 'DigiFact',
                        'class' => 'img-fluid border-none w-36 h-36 mx-auto',
                    ], [], false);
                    ?>
                </div>

                <div class="col-span-9">
                    <h2 class="text-2xl font-bold mb-4"><?php _e('Dashboard', 'fel-digifact'); ?></h2>

                    <hr>

                    <p class="text-lg my-2"><?php _e('Bienvenido a la página de administración de WooFel Digifact, esta página te permite ver las estadísticas de facturas emitidas y anuladas en el rango de fechas seleccionadas.', 'woo-fel-digifact'); ?></p>

                    <form class="flex flex-row space-x-4 items-center my-6" action="">
                        <div class="flex flex-row space-x-4 items-center">

                            <input type="hidden" name="page" value="digifact">
                            <div>
                                <label for="date_start" class="text-sm font-medium text-gray-400">Fecha inicio</label>
                                <input type="date" name="date_start" id="date_start" class="form-input" value="<?php echo $result[0]->date_start; ?>">
                            </div>
                            <div>
                                <label for="date_end" class="text-sm font-medium text-gray-400">Fecha fin</label>
                                <input type="date" name="date_end" id="date_end" class="form-input" value="<?php echo $result[0]->date_end; ?>">
                            </div>
                            <div>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-0-25 px-1 rounded">Filtrar</button>
                            </div>
                        </div>
                    </form>

                    <div id="stats" class="grid gird-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 my-6">
                        <div class="bg-gray-400 p-6 rounded-lg">
                            <div class="flex flex-row space-x-4 items-center">
                                <div id="stats-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-white">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>

                                </div>
                                <div>
                                    <p class="text-teal-300 text-sm font-medium uppercase leading-4">Facturado</p>
                                    <p class="text-white font-bold text-2xl inline-flex items-center space-x-2">
                                        <span><?php echo wc_price($result[0]->total_amount); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-400 p-6 rounded-lg">
                            <div class="flex flex-row space-x-4 items-center">
                                <div id="stats-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-10 h-10 text-white">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>

                                </div>
                                <div>
                                    <p class="text-teal-300 text-sm font-medium uppercase leading-4">Facturas emitidas</p>
                                    <p class="text-white font-bold text-2xl inline-flex items-center space-x-2">
                                        <span><?php echo $result[0]->total_invoices; ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-400 p-6 rounded-lg">
                            <div class="flex flex-row space-x-4 items-center">
                                <div id="stats-1">
                                    <i class="fas fa-times text-white text-4xl"></i>
                                </div>
                                <div>
                                    <p class="text-teal-300 text-sm font-medium uppercase leading-4">Anuladas</p>
                                    <p class="text-white font-bold text-2xl inline-flex items-center space-x-2">
                                        <span><?php echo $result[0]->total_void ?? 0; ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Get the stats for the dashboard.
     *
     * @return array
     */
    private function getStats()
    {
        // add filter range date created
        $filter = [
            'date_start' => date('Y-m-d', strtotime('first day of this month')),
            'date_end' => date('Y-m-d', strtotime('last day of this month')),
        ];

        if (!empty($_GET['date_start'])) {
            $filter['date_start'] = sanitize_text_field($_GET['date_start']);
        }

        if (!empty($_GET['date_end'])) {
            $filter['date_end'] = sanitize_text_field($_GET['date_end']);
        }

        $result = $this->invoiceModel->getStats($filter);

        $result[0]->date_start = $filter['date_start'];
        $result[0]->date_end = $filter['date_end'];

        return $result;
    }
} // End AdminMenu Class
