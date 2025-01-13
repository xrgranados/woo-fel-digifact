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
    private $perPage;

    /** @var array DigiFact settings */
    private $digifactSettings;

    /** @var string The slug for the plugin */
    private $slug = 'digifact-invoices';

    /**
     * InvoiceAdmin constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', [$this, 'addMenu']);
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
            'digifact',
            'Facturas emitidas',
            'Facturas emitidas',
            'manage_options',
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

        if ($_GET['page'] !== $this->slug) {
            return;
        }
?>
        <div id="digifact-void-modal" role="dialog" title="<?php _e('¿Estás seguro de anular la factura?', 'fel-digifact'); ?>" style="display: none;">
            <div class="modal-content w-full">
                <div class="modal-body">
                    <p>
                        <?php _e('Esta acción no se puede deshacer. ¿Estás seguro de querer anular la factura?', 'fel-digifact'); ?>
                    </p>

                    <hr class="modal-line my-2">

                    <form id="void-form" method="post">
                        <input type="hidden" name="action" value="digifact_void">
                        <input id="invoice-id" type="hidden" name="invoice_id" value="">
                        <div class="text-left mb-0-5">
                            <label for="reason">
                                <?php _e('Razón de anulación', 'fel-digifact'); ?>:
                            </label>
                        </div>
                        <textarea class="form-control w-full px-0-5 py-0-25" id="reason" name="reason" required></textarea>
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
            wp_send_json_error(__('Datos incompletos para anular la factura.', 'fel-digifact'), 400);
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'fel-digifact'), 400);
        }

        $invoice = (new Invoice())->getById($invoice_id);

        if (!$invoice or $invoice->status !== Invoice::STATUS_CERTIFIED) {
            wp_send_json_error(__('No está disponible la factura para anularla.', 'fel-digifact'));
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

            wp_send_json_success(__('Factura anulada con éxito.', 'fel-digifact'));
        } catch (Exception $e) {

            wp_send_json_error(
                __('Error al anular la factura: ', 'fel-digifact') . ' ' . $e->getMessage(),
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
            <input type="hidden" name="page" value="<?php echo $this->slug; ?>">
            <input type="hidden" name="action" value="filter">

            <div class="w-max-500 mb-3">
                <div class="form-group flex flex-row space-x-4">
                    <label for="date_start" class="w-3/12"><?php _e('Fecha', 'fel-digifact'); ?></label>
                    <div class="w-4/12">
                        <input type="date" name="date_start" id="date_start" value="<?php echo esc_attr(_old('date_start')); ?>">
                    </div>
                    <div class="w-4/12">
                        <input type="date" name="date_end" id="date_end" value="<?php echo esc_attr(_old('date_end')); ?>">
                    </div>
                </div>

                <div class="form-group flex flex-row space-x-4">
                    <label for="order_id" class="w-3/12"><?php _e('Orden', 'fel-digifact'); ?></label>
                    <div class="w-4/12">
                        <input type="text" name="order_id" value="<?php echo esc_attr(_old('order_id')); ?>">
                    </div>
                </div>

                <div class="form-group flex flex-row space-x-4">
                    <label for="customer_nit" class="w-3/12"><?php _e('NIT cliente', 'fel-digifact'); ?></label>
                    <div class="w-4/12">
                        <input type="text" name="customer_nit" value="<?php echo esc_attr(_old('customer_nit')); ?>">
                    </div>
                </div>

                <div class="form-group flex flex-row space-x-4">
                    <label for="invoice_number" class="w-3/12"><?php _e('Número de Factura', 'fel-digifact'); ?></label>
                    <div class="w-4/12">
                        <input type="text" name="invoice_number" value="<?php echo esc_attr(_old('invoice_number')); ?>">
                    </div>
                </div>

                <div class="text-center">

                    <button class="button button-primary py-0-25 mr-0-5 px-1" type="submit">
                        <?php _e('Buscar', 'fel-digifact'); ?>
                        <i class="dashicons dashicons-search mt-0-25"></i>
                    </button>

                    <a href="<?php echo esc_url(admin_url("admin.php?page={$this->slug}&paged=1")); ?>" class="button button-secondary py-0-25 px-1">
                        <?php _e('Limpiar filtros', 'fel-digifact'); ?>
                        <i class="dashicons dashicons-trash mt-0-25"></i>
                    </a>
                </div>
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
        $invoiceNumber = isset($_GET['invoice_number']) ? $_GET['invoice_number'] : null;
        $date_start = isset($_GET['date_start']) ? $_GET['date_start'] : null;
        $date_end = isset($_GET['date_end']) ? $_GET['date_end'] : null;
        $filter = [
            'customer_nit' => $customerNit,
            'order_id' => $orderId,
            'invoice_number' => $invoiceNumber,
            'date_start' => $date_start,
            'date_end' => $date_end,
        ];

        $invoices = $this->invoiceModel->getAllInvoices($paged, $this->perPage, $filter);
    ?>
        <div class="wrap">
            <div id="digifact-invoices-container" class="df-container">
                <h2 class="text-2xl font-bold mb-4"><?php _e('Facturas Emitidas', 'fel-digifact'); ?></h2>

                <?php if (! $this->digifactSettings) : ?>
                    <div class="error">
                        <p>Por favor, configure el plugin antes de continuar</p>
                    </div>
                <?php endif; ?>

                <hr>
                <p class="text-lg mb-4">
                    <?php _e('Ingrese el NIT del cliente o el número de orden para filtrar.', 'fel-digifact'); ?>
                </p>
                <?php $this->renderFilterForm(); ?>
                <table id="digifact-invoices-table" class="min-w-full bg-white border border-gray-300 strip-even:bg-gray-200 strip-odd:bg-gray-200 text-sm mt-4">
                    <thead class="bg-gray-800 text-white">
                        <tr class="border-b">
                            <th scope="col" class="px-0-5 py-0-5 border-b text-left"><?php _e('Nombre cliente', 'fel-digifact'); ?></th>
                            <th scope="col" class="px-0-5 py-0-5 border-b text-left"><?php _e('Nit cliente', 'fel-digifact'); ?></th>
                            <th scope="col" class="px-0-5 py-0-5 border-b text-left"><?php _e('Número de orden', 'fel-digifact'); ?></th>
                            <th scope="col" class="px-0-5 py-0-5 border-b text-left"><?php _e('Número de Factura', 'fel-digifact'); ?></th>
                            <th scope="col" class="px-0-5 py-0-5 border-b text-left"><?php _e('Fecha de Certificación', 'fel-digifact'); ?></th>
                            <th scope="col" class="px-0-5 py-0-5 border-b text-left"><?php _e('Estado', 'fel-digifact'); ?></th>
                            <th scope="col" class="px-0-5 py-0-5 border-b w-1/12 text-center"><?php _e('Acciones', 'fel-digifact'); ?></th>
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
                                    <h3 class="text-center text-2xl font-bold mb-4">
                                        <?php _e('No hay facturas por mostrar', 'fel-digifact'); ?>
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
        <tr class="odd:bg-white even:bg-gray-100">
            <td class="px-0-25 py-0-5 border-b"><?php echo esc_html($invoice->customer_name); ?></td>
            <td class="px-0-25 py-0-5 border-b"><?php echo esc_html($invoice->customer_nit); ?></td>
            <td class="px-0-25 py-0-5 border-b">#<?php echo esc_html($invoice->order_id); ?></td>
            <td class="px-0-25 py-0-5 border-b">
                <code><?php echo esc_html($invoice->invoice_number); ?></code>
            </td>
            <td class="px-0-25 py-0-5 border-b"><?php echo esc_html(_formatDate($invoice->certificated_at, 'd-m-Y H:i:s')); ?></td>
            <td class="px-0-25 py-0-5 border-b"><?php echo $this->renderStatusLabel($invoice); ?></td>
            <td class="px-0-25 py-0-5 border-b text-center">
                <?php
                echo $this->renderActionButton([
                    'class' => 'button button-link fas fa-eye mr-0-25',
                    'href' => $viewInvoiceUrl,
                    'target' => '_blank',
                    'title' => __('Ver factura', 'fel-digifact'),
                ]);

                if ($invoice->status === 'certified') {
                    echo _renderHtml('button', [
                        'class' => 'button button-link void-invoice fas fa-times button-link-delete mr-0-25',
                        'type' => 'button',
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

        return "<span class=\"badge badge-{$statusColor} px-0-5 py-0-25 rounded-full text-xs\">{$statusLabel}</span>";
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
        $params = [];
        if (isset($_GET['customer_nit'])) {
            $params['customer_nit'] = $_GET['customer_nit'];
        }

        if (isset($_GET['order_id'])) {
            $params['order_id'] = $_GET['order_id'];
        }
        $paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;

        $params['page'] = $this->slug;

        $filter = [
            'customer_nit' => $params['customer_nit'] ?? null,
            'order_id' => $params['order_id'] ?? null
        ];
        $totalInvoices = $this->invoiceModel->getAllInvoicesCount($filter);
        $totalPages = ceil($totalInvoices / $this->perPage);

        $query = http_build_query($params);
        if ($totalPages > 1) {
        ?>
            <div class="tablenav">
                <div class="tablenav-pages">
                    <?php
                    $prev_page = $paged - 1;
                    if ($paged > 1) {
                    ?>
                        <span class="page-numbers">
                            <a href="<?php echo esc_url(admin_url("admin.php?{$query}&paged=1")); ?>">
                                <i class="dashicons dashicons-controls-skipback"></i>
                            </a>
                        </span>
                        <span class="page-numbers">
                            <a href="<?php echo esc_url(admin_url("admin.php?{$query}&paged={$prev_page}")); ?>">
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
                            <a href="<?php echo esc_url(admin_url("admin.php?{$query}&paged={$next_page}")); ?>">
                                <i class="dashicons dashicons-arrow-right-alt2"></i>
                            </a>
                        </span>
                        <span class="page-numbers">
                            <a href="<?php echo esc_url(admin_url("admin.php?{$query}&paged={$totalPages}")); ?>">
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
} // End InvoicesPage Class
