<?php

namespace Digifact\Admin;

use Exception;
use Digifact\DigifactLib;
use Digifact\Models\Invoice;

/**
 * Class InvoicesPage
 * @package Digifact\Admin
 * @author Rafael Granados <xr.grandoso@gmail.com>
 */
class InvoicesPage
{
    /** @var Invoice invoice model instance */
    private $invoiceModel;

    /** @var int count per page */
    private $perPage = 10;

    /** @var array DigiFact settings */
    private $digifactSettings;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'addMenu'));
        add_action('admin_footer', [$this, 'addVoidModal']);
        add_action('wp_ajax_process_void_invoice', [$this, 'processVoidAjax']);

        $this->digifactSettings = get_option('digifact_settings');
        $this->invoiceModel = new Invoice();
        $this->perPage = $_GET['per_page'] ?? 10;
    }

    /**
     * Add the menu to the admin page.
     *
     * @return void
     */
    public function addMenu()
    {
        add_submenu_page(
            'woocommerce',
            'Facturas Emitidas',
            'Facturas Emitidas',
            'manage_woocommerce',
            'digifact-invoices',
            [$this, 'renderInvoicesPage'],
        );
    }

    /**
     * Add the modal to void an invoice.
     *
     * @return void
     */
    public function addVoidModal()
    {
        // verify if the page is invoices list, page=digifact-invoices
        if (!isset($_GET['page'])) {
            return;
        }

        if ($_GET['page'] !== 'digifact-invoices') {
            return;
        }
        ?>
        <div id="digifact-void-modal" role="dialog" title="Anular Factura" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>
                        <?php _e('¿Estás seguro de anular la factura?', 'fel-digifact'); ?>
                    </h2>
                </div>

                <hr class="modal-line">

                <div class="modal-body">
                    <p>
                        <?php _e('Esta acción no se puede deshacer. ¿Estás seguro de querer anular la factura?', 'fel-digifact'); ?>
                    </p>
                    <form id="void-form" method="post">
                        <input type="hidden" name="action" value="digifact_void">
                        <input id="invoice-id" type="hidden" name="invoice_id" value="">
                        <div class="text-left mb-0-5">
                            <label for="reason">
                                <?php _e('Razón de anulación', 'fel-digifact'); ?>:
                            </label>
                        </div>
                        <textarea class="form-control w-full" id="reason" name="reason" required></textarea>
                        <span id="spinner"></span>
                    </form>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Process the void invoice action.
     *
     * @uses $this->runVoidProcess
     * @return void
     */
    public function processVoidAjax()
    {
        $invoice_id = intval($_POST['invoice_id']);
        $reason = sanitize_text_field($_POST['reason']);

        if (!$invoice_id) {
            wp_send_json_error(['message' => __('Datos incompletos para anular la factura.', 'fel-digifact')], 400);
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(['message' => __('No tienes permisos para realizar esta acción', 'fel-digifact')], 400);
        }

        $invoice = (new Invoice())->getById($invoice_id);

        if (!$invoice or $invoice->status !== Invoice::STATUS_CERTIFIED) {
            wp_send_json_error(['message' => __('No está disponible la factura para anularla.', 'fel-digifact')]);
        }

        try {
            $response = $this->runVoidProcess($invoice, $reason);

            if (!$response) {
                throw new Exception('No se logró finalizar la solicitud');
            }

            if ($response->Codigo != 1) {
                throw new Exception('No se pudo anular la factura: ' . $response->Mensaje);
            }

            $updated = (new Invoice())->update([
                'status' => Invoice::STATUS_VOIDED,
                'annulation_reason' => $reason,
                'annulated_at' => date('Y-m-d H:i:s'),
                'id' => $invoice->id
            ]);

            if (!$updated) {
                throw new Exception('No se pudo actualizar la factura en la base de datos');
            }

            wp_send_json_success(['message' => __('Factura anulada con éxito.', 'fel-digifact')]);
        } catch (Exception $e) {

            wp_send_json_error(
                ['message' => __('Error al anular la factura: ', 'fel-digifact') . $e->getMessage()],
                400
            );
        }
    }

    /**
     * Run the void process.
     *
     * @param int $order_id The order ID.
     * @param string $customer_nit The customer NIT.
     * @param string $reason The reason for the void.
     *
     * @return object|null
     */
    private function runVoidProcess($invoice, $reason)
    {
        // Run the DigiFact void process
        try {
            $digifactLib = $this->getDigifactLib();
            $response = $digifactLib->anulation(
                $invoice->authorization,
                $invoice->customer_nit,
                $invoice->certificated_at,
                $reason
            )->certificateAnulation();

            return $response;
        } catch (Exception $e) {
            throw new Exception('No se pudo obtener datos del cliente: ' . $e->getMessage());
        }
    }

    /**
     * Returns the DigiFact instance
     *
     * @return DigifactLib
     */
    private function getDigifactLib()
    {
        $nit = $this->digifactSettings['digifact_nit'];
        $user = $this->digifactSettings['digifact_user'];
        $password = $this->digifactSettings['digifact_password'];

        return new DigifactLib(
            $nit,
            $user,
            $password
        );
    }


    /**
     * Renders the filter form.
     *
     * @return void
     */
    public function renderFilterForm()
    {
    ?>
        <form method="get">
            <input type="hidden" name="page" value="digifact-invoices">
            <input type="hidden" name="action" value="filter">

            <div class="w-max-500 mb-3">
                <div class="form-group">
                    <label for="order_id"><?php _e('Orden', 'fel-digifact'); ?></label>
                    <input type="text" name="order_id" value="<?php echo esc_attr(_old('order_id')); ?>">
                </div>

                <div class="form-group">
                    <label for="customer_nit"><?php _e('NIT', 'fel-digifact'); ?></label>
                    <input type="text" name="customer_nit" value="<?php echo esc_attr(_old('customer_nit')); ?>">
                </div>
                <p>
                    <small class="form-text text-muted">
                        <?php _e('* Ingrese el NIT del cliente o el número de orden para filtrar.', 'fel-digifact'); ?>
                    </small>
                </p>
                <button class="button button-primary py-0-25 mr-0-5 px-1" type="submit">
                    <?php _e('Buscar', 'fel-digifact'); ?>
                    <i class="dashicons dashicons-search mt-0-25"></i>
                </button>

                <a href="<?php echo esc_url(admin_url("admin.php?page=digifact-invoices&paged=1")); ?>" class="button button-secondary py-0-25 px-1">
                    <?php _e('Limpiar filtros', 'fel-digifact'); ?>
                    <i class="dashicons dashicons-trash mt-0-25"></i>
                </a>
            </div>
        </form>
    <?php
    }

    /**
     * Renders the invoices page.
     *
     * @return void
     */
    public function renderInvoicesPage()
    {
        $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $customerNit = isset($_GET['customer_nit']) ? $_GET['customer_nit'] : null;
        $orderId = isset($_GET['order_id']) ? $_GET['order_id'] : null;
        $filter = [
            'customer_nit' => $customerNit,
            'order_id' => $orderId
        ];

        $invoices = $this->invoiceModel->getAllInvoices($paged, $this->perPage, $filter);
    ?>
        <div class="wrap">
            <div id="digifact-invoices-container" class="df-container">
                <h1><?php _e('Facturas Emitidas', 'fel-digifact'); ?></h1>
                <hr>
                <?php $this->renderFilterForm(); ?>
                <table id="digifact-invoices-table" class="wp-list-table widefat fixed striped table-view-list">
                    <thead>
                        <tr>
                            <th><?php _e('Nombre cliente', 'fel-digifact'); ?></th>
                            <th><?php _e('Nit cliente', 'fel-digifact'); ?></th>
                            <th><?php _e('Número de orden', 'fel-digifact'); ?></th>
                            <th><?php _e('Número de Factura', 'fel-digifact'); ?></th>
                            <th><?php _e('Autorización', 'fel-digifact'); ?></th>
                            <th><?php _e('Fecha de Certificación', 'fel-digifact'); ?></th>
                            <th><?php _e('Estado', 'fel-digifact'); ?></th>
                            <th><?php _e('Acciones', 'fel-digifact'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($invoices)): ?>

                            <?php
                            foreach ($invoices as $invoice) {
                                $this->renderRow($invoice);
                            }
                            ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <?php _e('No hay facturas registradas.', 'fel-digifact'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php $this->renderPagination($invoices); ?>
            </div>
        </div>
    <?php
    }

    /**
     * Renders a single invoice row.
     *
     * @param object $invoice The invoice object.
     * @return void
     */
    private function renderRow($invoice)
    {
        $nitEface = str_replace('-', '', $this->digifactSettings['digifact_nit']);
        $viewInvoiceUrl = esc_url("https://felgtaws.digifact.com.gt/guest/api/FEL?DATA={$nitEface}|{$invoice->authorization}|GUESTUSERQR");
    ?>
        <tr>
            <td><?php echo esc_html($invoice->customer_name); ?></td>
            <td><?php echo esc_html($invoice->customer_nit); ?></td>
            <td><?php echo esc_html($invoice->order_id); ?></td>
            <td><?php echo esc_html($invoice->invoice_number); ?></td>
            <td><?php echo esc_html($invoice->authorization); ?></td>
            <td><?php echo esc_html(_formatDate($invoice->certificated_at, 'd-m-Y H:i:s')); ?></td>
            <td><?php echo $this->renderStatusLabel($invoice); ?></td>
            <td>
                <?php
                echo $this->renderActionButton([
                    'class' => 'button button-link dashicons dashicons-visibility mr-0-25',
                    'href' => $viewInvoiceUrl,
                    'target' => '_blank',
                    'title' => __('Ver factura', 'fel-digifact'),
                ]);

                if ($invoice->status === 'certified') {
                    echo $this->renderActionButton([
                        'class' => 'button button-link void-invoice dashicons dashicons-no-alt button-link-delete mr-0-25',
                        'data-invoice-id' => $invoice->id,
                        'title' => __('Anular factura', 'fel-digifact'),
                    ]);
                }
                ?>
            </td>
        </tr>
        <?php
    }

    /**
     * Renders an html element for an action button.
     *
     * @param object $invoice The invoice object.
     * @return void
     */
    private function renderActionButton($attributes, $content = '')
    {
        $attributes = wp_parse_args($attributes, [
            'class' => 'button button-link dashicons dashicons-no-alt',
        ]);

        return sprintf('<a %s>%s</a>', _renderAttributes($attributes), $content);
    }

    /**
     * Renders the status label for an invoice.
     *
     * @param Digifact\Models\Invoice $invoice The invoice to render the status label for.
     * @return string The rendered status label.
     */
    private function renderStatusLabel($invoice)
    {
        $labelsStatuses = $this->invoiceModel::STATUSES;
        $statuses = $this->invoiceModel->getStatuses();

        $statusColor = $this->invoiceModel::STATUS_COLORS[$invoice->status] ?? '';
        $statusLabel = $labelsStatuses[$invoice->status] ?? '';

        return "<span class=\"badge badge-{$statusColor}\">{$statusLabel}</span>";
    }

    /**
     * Renders the pagination.
     *
     * @uses $this->perPage
     * @uses $this->invoiceModel
     * @return void
     */
    private function renderPagination()
    {
        $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
        $customerNit = isset($_GET['customer_nit']) ? $_GET['customer_nit'] : null;
        $orderId = isset($_GET['order_id']) ? $_GET['order_id'] : null;
        $filter = [
            'customer_nit' => $customerNit,
            'order_id' => $orderId
        ];
        $totalInvoices = $this->invoiceModel->getAllInvoicesCount($filter);
        $totalPages = ceil($totalInvoices / $this->perPage);

        if ($totalPages > 1) {
        ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $prev_page = $paged - 1;
                    if ($paged > 1) {
                    ?>
                        <span class="page-numbers">
                            <a href="<?php echo esc_url(admin_url("admin.php?page=digifact-invoices&paged=1")); ?>">
                                <i class="dashicons dashicons-controls-skipback"></i>
                            </a>
                        </span>
                        <span class="page-numbers">
                            <a href="<?php echo esc_url(admin_url("admin.php?page=digifact-invoices&paged={$prev_page}")); ?>">
                                <i class="dashicons dashicons-arrow-left-alt2"></i>
                            </a>
                        </span>
                    <?php
                    }
                    ?>

                    <span class="page-numbers">
                        Pagina <?php echo $paged ?> de <?php echo $totalPages ?>
                    </span>

                    <?php
                    // Mostrar la siguiente página si hay más páginas
                    if ($paged < $totalPages) {
                        $next_page = $paged + 1;
                    ?>
                        <span class="page-numbers">
                            <a href="<?php echo esc_url(admin_url("admin.php?page=digifact-invoices&paged={$next_page}")); ?>">
                                <i class="dashicons dashicons-arrow-right-alt2"></i>
                            </a>
                        </span>
                        <span class="page-numbers">
                            <a href="<?php echo esc_url(admin_url("admin.php?page=digifact-invoices&paged={$totalPages}")); ?>">
                                <i class="dashicons dashicons-controls-skipforward"></i>
                            </a>
                        </span>
                    <?php
                    }
                    ?>
                </div>
            </div>
<?php
        }
        return $totalPages;
    }
} // End InvoicesPage class
