<?php

namespace Digifact\Admin;

use \Exception;
use Digifact\DigifactLib;
use Digifact\Models\Invoice;

/**
 * Class ProcessBilling
 *
 * @package Digifact\Admin
 * @author Rafael Granados <xr.granados@gmail.com>
 */
class ProcessBilling
{
    /** @var array digifact settings from the database */
    private $digifactSettings;

    /**
     * ProcessBilling constructor.
     */
    public function __construct()
    {
        $this->digifactSettings = get_option('digifact_settings');
        // add button to order edit page
        add_filter(
            'woocommerce_admin_order_actions_end',
            array($this, 'addProcessBillingButton'),
            10,
            2
        );

        add_action('admin_footer', [$this, 'addModal']);
        add_action('wp_ajax_process_generate_invoice', [$this, 'processAjax']);
    }

    /**
     * Adds a button to the order edit page to generate an invoice.
     *
     * @param WC_Order $order The order object.
     * @return void
     */
    public function addProcessBillingButton($order)
    {
        $order_id = $order->get_id();

        if (!$order_id) {
            return;
        }

        $invoice = (new Invoice())->getByOrderId($order_id);

        if ($invoice && $invoice->status === Invoice::STATUS_CERTIFIED) {
            $nitEface = str_replace('-', '', $this->digifactSettings['digifact_nit']);
            $viewInvoiceUrl = esc_url("https://felgtaws.digifact.com.gt/guest/api/FEL?DATA={$nitEface}|{$invoice->authorization}|GUESTUSERQR");

            echo _link(
                $viewInvoiceUrl,
                '',
                [
                    'class' => 'button dashicons dashicons-visibility',
                    'class' => 'button fas fa-eye text-center',
                    'target' => '_blank',
                    'title' => __('Ver factura', 'fel-digifact'),
                ]
            );
            return;
        }

        // Verificar si la función `get_billing_nit` existe
        $customer_nit = function_exists('get_billing_nit')
            ? get_billing_nit($order_id)
            : get_post_meta($order_id, '_billing_nit', true);
        $customer_email = $order->get_billing_email() ?: $this->digifactSettings['digifact_email'];

        echo _link('#', '', [
            'class' => 'button generate_invoice dashicons dashicons-text-page',
            'class' => 'button generate_invoice fas fa-file text-center',
            'data-nit' => $customer_nit,
            'data-order-id' => $order_id,
            'data-email' => $customer_email,
            'title' => __('Generar factura', 'fel-digifact'),
        ]);
    }

    /**
     * Add the modal to the admin footer
     *
     * @return void
     */
    public function addModal()
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        // if page is orders list, page=wc-order
        // if (!isset($_GET['page'])) {
        //     return;
        // }

        ?>
        <div id="dialog-generate-invoice" title="<?php _e('Generar Factura Electrónica', 'fel-digifact'); ?>" style="display: none;">
            <div class="modal-content w-full">
                <div class="modal-header">
                    <h2>
                        <?php _e('Generar Factura Electrónica para la orden: ', 'fel-digifact'); ?>
                        #<span id="order-id--label"></span>
                    </h2>
                </div>

                <hr class="modal-line">

                <p><?php _e('Por favor, confirme o ingrese los datos del cliente:', 'fel-digifact'); ?></p>
                <form id="generate-invoice-form">
                    <input type="hidden" id="order-id" name="order_id">
                    <div class="form-group">
                        <label for="customer-nit"><?php _e('NIT del Cliente:', 'fel-digifact'); ?></label>
                        <input type="text" id="customer-nit" name="customer_nit" required pattern="^[0-9]+(-?[0-9kK])?$">
                    </div>
                    <div class="form-group">
                        <label for="customer-email"><?php _e('Correo del Cliente:', 'fel-digifact'); ?></label>
                        <input type="email" id="customer-email" name="customer_email" required>
                    </div>
                    <span id="spinner"></span>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Process Ajax
     *
     * Process request to generate invoice
     *
     * @return void
     */
    public function processAjax()
    {
        $order_id = intval($_POST['order_id']);
        $customer_nit = sanitize_text_field($_POST['customer_nit']);

        if (!$order_id || empty($customer_nit)) {
            wp_send_json_error(__('Datos incompletos para generar la factura.', 'fel-digifact'));
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(__('No tienes permisos para realizar esta acción', 'fel-digifact'));
        }

        try {
            $response = $this->runBillingProcess($order_id, $customer_nit);

            $storeInvoice = $this->createInvoice($order_id, Invoice::STATUS_CERTIFIED, $response);

            if (!$storeInvoice) {
                throw new Exception('No se pudo guardar la factura en la base de datos');
            }

            wp_send_json_success(
                ['message' => __('Factura generada correctamente.', 'fel-digifact')],
            );
        } catch (Exception $e) {
            wp_send_json_error(
                __('Error al generar la factura: ', 'fel-digifact') . $e->getMessage(),
                400
            );
        }
    }

    /**
     * Run the billing process for the given order.
     *
     * @param int $order_id The ID of the order.
     * @param string $customer_nit The customer's NIT.
     * @return array The DigiFact response.
     * @throws Exception If an error occurs during the billing process.
     */
    private function runBillingProcess($order_id, $customer_nit)
    {
        $clientNit = $customer_nit;
        $clientEmail = $_POST['customer_email'];
        $digifactLib = $this->getDigifactLib();

        // Obtener la orden de WooCommerce
        $order = wc_get_order($order_id);

        // Validar que la orden exista
        if (!$order) {
            throw new Exception('La orden no existe.');
        }

        try {
            $receiverSharedInfo = (object) $digifactLib->getSharedInfoNIT(
                $clientNit
            );

            update_post_meta($order_id, '_billing_nit', $customer_nit);
        } catch (Exception $e) {
            throw new Exception('No se pudo obtener datos del cliente: ' . $e->getMessage());
        }

        $issuer = [
            'nit' => $this->digifactSettings['digifact_nit'],
            'name' => $this->digifactSettings['digifact_name'],
            'email' => $this->digifactSettings['digifact_email'],
            'tradename' => $this->digifactSettings['digifact_trade_name'],
            'address' => $this->digifactSettings['digifact_address'],
            'establishment_code' => 1,
            'iva_affiliation' => 'GEN',
            'address' => [
                'address' => $this->digifactSettings['digifact_address'],
                'postal_code' => '01010',
                'municipality' => 'Guatemala',
                'department' => 'Guatemala',
                'country' => 'GT'
            ]
        ];

        $receiver = [
            'name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'nit' => $customer_nit,
            'email' => $order->get_billing_email() ?: $clientEmail,
            'address' => [
                'address' => $order->get_billing_address_1(),
                'postal_code' => $order->get_billing_postcode() ?: '01010',
                'country' => $order->get_billing_country(),
                'department' => $order->get_billing_state(),
                'municipality' => $order->get_billing_city(),
            ]
        ];

        if ($receiverSharedInfo) {
            $receiver['name'] = DigifactLib::getTaxPayerName($receiverSharedInfo->NOMBRE);
            $receiver['address']['address'] = $order->get_billing_address_1() ?: $receiverSharedInfo->Direccion;
            $receiver['address']['country'] = $order->get_billing_country() ?: $receiverSharedInfo->PAIS;
            $receiver['address']['department'] = $order->get_billing_state() ?: $receiverSharedInfo->DEPARTAMENTO;
            $receiver['address']['municipality'] = $order->get_billing_city() ?: $receiverSharedInfo->MUNICIPIO;
        };

        try {
            $response = $digifactLib->setIssuer($issuer)
                ->setReceiver($receiver)
                ->setPhrases('1', '1')
                ->setProducts($order->get_items())
                ->generateDTE()
                ->certificateDTE();

            return $response;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
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
     * Create an invoice in the database.
     *
     * @param int $order_id The ID of the order.
     * @param string $status The status of the invoice.
     * @param object $response The DigiFact response.
     * @param double $amount The amount of the invoice.
     * @return void
     */
    private function createInvoice(
        $order_id,
        $status = Invoice::STATUS_CERTIFIED,
        $response
    ) {
        $data = [
            'order_id' => $order_id,
            'status' => $status,
            'invoice_number' => $response->NUMERO,
            'authorization' => $response->Autorizacion,
            'amount' => $response->grandTotal,
            'customer_nit' => $response->NIT_COMPRADOR,
            'customer_name' => $response->NOMBRE_COMPRADOR,
            'certificated_at' => _formatDate(
                $response->Fecha_de_certificacion,
                'Y-m-d H:i:s'
            ),
        ];

        $created = (new Invoice())->create($data);

        return $created;
    }
} // End ProcessBilling class
