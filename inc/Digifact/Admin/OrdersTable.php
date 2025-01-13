<?php

namespace Digifact\Admin;

use Digifact\Models\Invoice;

/**
 * Class OrdersTable
 * @package Digifact\Admin
 */
class OrdersTable
{
    /** @var array digifact settings from the database */
    private $digifactSettings;

    /** @var int count per page */
    private $perPage = 15;

    /** @var string The slug for the plugin */
    private $slug = 'digifact-orders';

    /**
     * OrdersTable constructor.
     */
    public function __construct()
    {
        $this->digifactSettings = get_option('digifact_settings');

        $this->perPage = $_GET['per_page'] ?? 10;
    }

    /**
     * Renders the filters form.
     *
     * @return void
     */
    private function renderFilters()
    {
        ?>
        <div class="df-filters mt-4">
            <form id="df-orders-filters" method="get">
                <input type="hidden" name="page" value="<?php echo $this->slug; ?>">
                <select name="status" id="status">
                    <?php echo _renderHtml('option', ['value' => ''], [__('Todos los estados', 'fel-digifact')]); ?>
                    <?php
                    $statuses = wc_get_order_statuses();
                    foreach ($statuses as $status => $name) {
                        $attrs = ['value' => $status];
                        if (isset($_GET['status']) && $_GET['status'] === $status) {
                            $attrs['selected'] = 'selected';
                        }
                        echo _renderHtml('option', $attrs, [$name]);
                    }
                    ?>
                </select>
                <input type="text" name="order_id" placeholder="ID de la orden" value="<?php echo esc_attr(_old('order_id')); ?>">
                <button class="button" type="submit">
                    <?php _e('Filtrar', 'fel-digifact'); ?>
                    <i class="fas fa-search mt-0-25 ml-0-5"></i>
                </button>
                <?php
                echo _renderHtml('a', [
                    'href' => esc_url(admin_url("admin.php?page={$this->slug}&paged=1")),
                    'class' => 'button button-secondary px-1',
                    'title' => __('Limpiar filtros', 'fel-digifact'),
                ], [
                    __('Limpiar filtros', 'fel-digifact'),
                    '<i class="fas fa-trash mt-0-25 ml-0-5"></i>',
                ]);
                ?>
            </form>
        </div>
    <?php
    }

    /**
     * Get filtered orders.
     *
     * @param string $order_id Order ID.
     * @param string $status Status.
     * @return array
     */
    private function getFilteredOrders($order_id, $status)
    {
        $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 0;
        $offset = ($paged - 1) * $this->perPage;
        $args = [
            'limit' => $this->perPage,
            'offset' => $offset > 0 ? $offset : 0,
            'orderby' => 'date',
            'order' => 'DESC',
            'return' => 'objects',
            'p' => '',
            'status' => '',
            'billing_email' => '',
        ];

        if (!empty($order_id)) {
            $args['p'] = intval($order_id);
        }

        // Filtro por estado
        if (!empty($status)) {
            $args['status'] = [str_replace('wc-', '', $status)];
        }

        return [
            'orders' => wc_get_orders($args),
            'total' => wc_get_orders([
                'limit' => -1,
                'return' => 'ids',
                'p' => $args['p'] ?? '',
                'status' => $args['status'] ?? '',
                'billing_email' => $args['billing_email'] ?? '',
            ]),
            'per_page' => $this->perPage,
            'current_page' => $paged
        ];
    }

    /**
     * Display orders table
     *
     * @return void
     */
    public function display()
    {
        $order_id = isset($_GET['order_id']) ? sanitize_text_field($_GET['order_id']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

        $result = $this->getFilteredOrders($order_id, $status);
        $orders = $result['orders'];
        $total_orders = count($result['total']);
        $total_pages = ceil($total_orders / $result['per_page']);
        $statusColors = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'on-hold' => 'bg-yellow-100 text-yellow-800',
            'processing' => 'bg-blue-100 text-blue-800',
            'completed' => 'bg-green-100 text-green-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'refunded' => 'bg-red-100 text-red-800',
            'failed' => 'bg-red-100 text-red-800',
        ];
    ?>
        <div class="wrap">
            <div class="df-container">
                <h2 class="text-2xl font-bold mb-4">
                    <?php _e('Órdenes WooCommerce', 'fel-digifact'); ?>
                </h2>
                <hr>

                <?php $this->renderFilters(); ?>

                <table class="min-w-full bg-white border border-gray-300 strip-even:bg-gray-200 strip-odd:bg-gray-200 text-sm mt-4 orders">
                    <colgroup>
                        <col style="width: 20%;">
                        <col style="width: 20%;">
                        <col>
                        <col>
                        <col style="width: 15%;">
                    </colgroup>
                    <caption class="py-0-5 border-b text-left st">
                        <?php _e('Lista de órdenes', 'fel-digifact'); ?>
                    </caption>
                    <thead class="bg-gray-800 text-white">
                        <tr class="border-b">
                            <th scope="col" class="px-0-5 py-0-5 border-b text-left">
                                <?php _e('Orden', 'fel-digifact'); ?>
                            </th>
                            <th scope="col" class="px-0-5 py-0-5 border-b text-left">
                                <?php _e('Estado', 'fel-digifact'); ?>
                            </th>
                            <th scope="col" class="px-0-5 py-0-5 border-b text-left">
                                <?php _e('Fecha', 'fel-digifact'); ?>
                            </th>
                            <th scope="col" class="px-0-5 py-0-5 border-b text-left">
                                <?php _e('Total', 'fel-digifact'); ?>
                            </th>
                            <th scope="col" class="px-0-5 py-0-5 border-b text-left">
                                <?php _e('Número de Factura', 'fel-digifact'); ?>
                            </th>
                            <th scope="col" class="px-0-5 py-0-5 border-b text-center">
                                <?php _e('Acciones', 'fel-digifact'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)) : ?>
                            <tr>
                                <td colspan="5" class="px-4 py-0-5 border-b text-center">
                                    <h3 class="text-center text-2xl font-bold mb-4">
                                        <?php _e('No hay órdenes por mostrar', 'fel-digifact'); ?>
                                    </h3>
                                    <?php
                                    echo _renderHtml('img', [
                                        'src' => plugins_url('assets/img/content.png', dirname(__DIR__, 2)),
                                        'class' => 'mt-2 border-none m-auto mb-4',
                                        'width' => '100px',
                                    ]);
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php
                        foreach ($orders as $order) :
                            $invoice = (new Invoice())->getByOrderId($order->get_id());

                            if ($invoice && $invoice->status === Invoice::STATUS_CERTIFIED) {
                                $nitEface = str_replace('-', '', $this->digifactSettings['digifact_nit']);
                                $viewInvoiceUrl = esc_url("https://felgtaws.digifact.com.gt/guest/api/FEL?DATA={$nitEface}|{$invoice->authorization}|GUESTUSERQR");
                            }

                            $billingNit = function_exists('get_billing_nit')
                                ? get_billing_nit($order->get_id())
                                : get_post_meta($order->get_id(), '_billing_nit', true);
                            $billingEmail = $order->get_billing_email() ?: $this->digifactSettings['digifact_email'];
                        ?>
                            <tr class="odd:bg-white even:bg-gray-100">
                                <td class="px-0-25 py-0-5 border-b">
                                    #<?php echo $order->get_id(); ?>
                                    <?php echo $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(); ?>
                                </td>
                                <td class="px-0-25 py-0-5 border-b">
                                    <span class="<?php echo $statusColors[$order->get_status()]; ?> px-2 py-0-5 rounded-full text-xs">
                                        <?php echo wc_get_order_status_name($order->get_status()); ?>
                                    </span>
                                </td>
                                <td class="px-0-25 py-0-5 border-b">
                                    <?php echo $order->get_date_created()->format('d-m-Y H:i:s'); ?>
                                </td>
                                <td class="px-0-25 py-0-5 border-b text-right">
                                    <?php echo $order->get_currency(); ?>
                                    <?php echo $order->get_total(); ?>
                                </td>
                                <td class="px-0-25 py-0-5 border-b text-center">
                                    <?php
                                    if ($invoice && $invoice->status === Invoice::STATUS_CERTIFIED) {
                                        echo "<code>{$invoice->invoice_number}</code>";
                                    } else {
                                        echo "&mdash;";
                                    }
                                    ?>
                                </td>
                                <td class="px-0-25 py-0-5 border-b text-center">
                                    <?php
                                    if ($invoice && $invoice->status === Invoice::STATUS_CERTIFIED) {
                                        $nitEface = str_replace('-', '', $this->digifactSettings['digifact_nit']);
                                        $viewInvoiceUrl = esc_url("https://felgtaws.digifact.com.gt/guest/api/FEL?DATA={$nitEface}|{$invoice->authorization}|GUESTUSERQR");

                                        echo _link(
                                            $viewInvoiceUrl,
                                            '',
                                            [
                                                'class' => 'button button-link fas fa-eye text-center',
                                                'target' => '_blank',
                                                'title' => __('Ver factura', 'fel-digifact'),
                                            ]
                                        );
                                    } else {
                                        echo _button('<i class="fas fa-file"></i>', [
                                            'class' => 'button button-link generate_invoice',
                                            'data-order-id' => $order->get_id(),
                                            'data-nit' => $billingNit,
                                            'data-email' => $billingEmail,
                                            'title' => 'Generar factura',
                                        ]);
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php echo $this->renderPagination($total_pages); ?>

            </div>
        </div>
        <?php
    }

    /**
     * Renders the pagination.
     *
     * @param int $total_pages The total number of pages.
     * @return void
     */
    private function renderPagination($total_pages)
    {
        $params = [];
        if (isset($_GET['order_id'])) {
            $params['order_id'] = $_GET['order_id'];
        }

        if (isset($_GET['customer_email'])) {
            $params['customer_email'] = $_GET['customer_email'];
        }

        if (isset($_GET['status'])) {
            $params['status'] = $_GET['status'];
        }

        $paged = $_GET['paged'] ?? 1;

        $params['page'] = $this->slug;

        $query = http_build_query($params);
        if ($total_pages > 1) {
        ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $prev_page = $paged - 1;
                    if ($paged > 1) {
                    ?>
                        <span class="page-numbers">
                            <?php
                            echo _renderHtml('a', [
                                'href' => esc_url(admin_url("admin.php?{$query}&paged=1")),
                                'title' => __('Ver órdenes recientes', 'fel-digifact'),
                            ], [
                                '<i class="dashicons dashicons-controls-skipback"></i>',
                            ]);
                            ?>
                        </span>
                        <span class="page-numbers">
                            <?php
                            echo _renderHtml('a', [
                                'href' => esc_url(admin_url("admin.php?{$query}&paged={$prev_page}")),
                            ], [
                                '<i class="dashicons dashicons-arrow-left-alt2"></i>',
                            ]);
                            ?>
                        </span>
                    <?php
                    }
                    ?>

                    <span class="page-numbers">
                        Pagina <?php echo $paged ?> de <?php echo $total_pages ?>
                    </span>

                    <?php
                    // Show next page if there are more pages
                    if ($paged < $total_pages) {
                        $next_page = $paged + 1;
                    ?>
                        <span class="page-numbers">
                            <?php
                            echo _renderHtml('a', [
                                'href' => esc_url(admin_url("admin.php?{$query}&paged={$next_page}")),
                            ], [
                                '<i class="dashicons dashicons-arrow-right-alt2"></i>',
                            ]);
                            ?>
                        </span>
                        <span class="page-numbers">
                            <?php
                            echo _renderHtml('a', [
                                'href' => esc_url(admin_url("admin.php?{$query}&paged={$total_pages}")),
                                'title' => __('Ver últimas órdenes', 'fel-digifact'),
                            ], [
                                '<i class="dashicons dashicons-controls-skipforward"></i>',
                            ]);
                            ?>
                        </span>
                    <?php
                    }
                    ?>
                </div>
            </div>
        <?php
        }
    }
} // End OrdersTable Class
