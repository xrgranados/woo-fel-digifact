<?php

namespace Digifact\Models;

/**
 * Invoice class.
 *
 * Represents a DigiFact invoice.
 *
 * @package Digifact\Models
 * @author Rafael Granados <xr.grandoso@gmail.com>
 */
class Invoice
{
    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_CERTIFIED = 'certified';
    const STATUS_VOIDED = 'voided';
    const STATUS_ERROR = 'error';

    /**
     * Statuses for invoice statuses.
     * @var array $STATUSES
     */
    const STATUSES = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_CERTIFIED => 'Certificado',
        self::STATUS_VOIDED => 'Anulado',
        self::STATUS_ERROR => 'Error',
    ];

    /**
     * Colors for invoice statuses.
     * @var array $STATUS_COLORS
     */
    const STATUS_COLORS = [
        self::STATUS_PENDING => 'warning',
        self::STATUS_CERTIFIED => 'success',
        self::STATUS_VOIDED => 'dark',
        self::STATUS_ERROR => 'danger',
    ];

    /** @var \wpdb The WordPress database object. */
    private $wpdb;

    /** @var string The table name. */
    protected $tableName;

    /**
     * Constructor
     *
     * Creates a new instance of the Invoices class.`
     */
    public function __construct()
    {
        global $wpdb;

        $this->wpdb = $wpdb;
        $this->tableName = $wpdb->prefix . 'digifact_invoices';
    }

    /**
     * Get all DigiFact invoice statuses.
     *
     * @return array
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_CERTIFIED,
            self::STATUS_VOIDED,
            self::STATUS_ERROR,
        ];
    }

    /**
     * Get all DigiFact invoice statuses.
     *
     * @return array
     */
    public static function getLabelsStatuses()
    {
        return [
            'pending' => __('Pendiente', 'fel-digifact'),
            'certified' => __('Certificado', 'fel-digifact'),
            'voided' => __('Anulado', 'fel-digifact'),
            'error' => __('Error', 'fel-digifact'),
        ];
    }

    /**
     * Get all DigiFact invoice stats.
     *
     * @return array
     */
    public function getStats($filter = [])
    {
        // make query to get stats, total invoices, total amount, total canceled invoices
        $query = "SELECT COUNT(*) as total_invoices,
            SUM(amount) as total_amount,
            SUM(IF(status = 'voided', 1, 0)) as total_void
            FROM $this->tableName";

        // add filter range date created
        if (!empty($filter['date_start'])) {
            $query .= " WHERE date(certificated_at) >= '{$filter['date_start']}'";
        }

        if (!empty($filter['date_end'])) {
            $query .= " AND date(certificated_at) <= '{$filter['date_end']}'";
        }

        $stats = $this->wpdb->get_results($query);

        return $stats;
    }

    /**
     * Get all DigiFact invoices.
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAllInvoices($page = 1, $perPage = 15, $filter = [])
    {
        try {
            $offset = ($page - 1) * $perPage;
            $wheres = [];
            $params = [];

            if (!empty($filter['customer_nit'])) {
                // Eliminar guiones para hacer una búsqueda más flexible
                $nit_clean = str_replace('-', '', $filter['customer_nit']);
                $wheres[] = "REPLACE(customer_nit, '-', '') LIKE %s";
                $params[] = '%' . $nit_clean . '%';
            }

            if (!empty($filter['order_id'])) {
                $wheres[] = "order_id LIKE %s";
                $params[] = '%' . $filter['order_id'] . '%';
            }

            if (!empty($filter['invoice_number'])) {
                $wheres[] = "invoice_number LIKE %s";
                $params[] = '%' . $filter['invoice_number'] . '%';
            }

            if (!empty($filter['date_start'])) {
                $wheres[] = "date(certificated_at) >= %s";
                $params[] = $filter['date_start'];
            }

            if (!empty($filter['date_end'])) {
                $wheres[] = "date(certificated_at) <= %s";
                $params[] = $filter['date_end'];
            }

            // Construcción de la consulta con manejo de condiciones
            $where_clause = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';

            $query = $this->wpdb->prepare(
                "SELECT * FROM $this->tableName
            {$where_clause}
            ORDER BY id DESC
            LIMIT %d OFFSET %d",
                array_merge($params, [$perPage, $offset])
            );

            $invoices = $this->wpdb->get_results($query);
            return $invoices;
        } catch (\Exception $e) {
            // Opcional: loguear el error
            error_log('Error al obtener facturas: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all DigiFact invoices count.
     *
     * @param array $filter
     * @return int
     */
    public function getAllInvoicesCount(array $filter = [])
    {
        try {
            $wheres = [];
            $params = [];

            if (!empty($filter['customer_nit'])) {
                // Eliminar guiones para hacer una búsqueda más flexible
                $nit_clean = str_replace('-', '', $filter['customer_nit']);
                $wheres[] = "REPLACE(customer_nit, '-', '') LIKE %s";
                $params[] = '%' . $nit_clean . '%';
            }

            if (!empty($filter['order_id'])) {
                $wheres[] = "order_id LIKE %s";
                $params[] = '%' . $filter['order_id'] . '%';
            }

            if (!empty($filter['invoice_number'])) {
                $wheres[] = "invoice_number LIKE %s";
                $params[] = '%' . $filter['invoice_number'] . '%';
            }

            if (!empty($filter['date_start'])) {
                $wheres[] = "date(certificated_at) >= %s";
                $params[] = $filter['date_start'];
            }

            if (!empty($filter['date_end'])) {
                $wheres[] = "date(certificated_at) <= %s";
                $params[] = $filter['date_end'];
            }

            // Construcción de la consulta base
            $query = "SELECT COUNT(*) FROM {$this->tableName}";

            // Añadir cláusula WHERE si hay filtros
            if (!empty($wheres)) {
                $query .= " WHERE " . implode(' AND ', $wheres);
            }

            // Preparar la consulta con los parámetros
            if (!empty($params)) {
                $query = $this->wpdb->prepare($query, $params);
            }

            $count = $this->wpdb->get_var($query);
            return $count;
        } catch (\Exception $e) {
            // Opcional: loguear el error
            error_log('Error al obtener facturas: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get a DigiFact invoice by ID.
     *
     * @param int $id
     * @return bool|object
     */
    public function getById($id)
    {
        $result = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $this->tableName WHERE id = %d",
            $id
        ));

        return $result[0] ?? null;
    }

    /**
     * Get a DigiFact invoice by order ID.
     *
     * @param int $orderId
     * @return object|null
     */
    public function getByOrderId($orderId)
    {
        $result = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $this->tableName WHERE order_id = %d ORDER BY id DESC",
            $orderId
         ));

        return $result[0] ?? null;
    }

    /**
     * Get a DigiFact invoice by order ID.
     *
     * @param int $orderId
     * @return object|null
     */
    public function getByInvoiceNumber($invoiceNumber)
    {
        $result = $this->wpdb->get_results($this->wpdb->prepare(
            "SELECT * FROM $this->tableName WHERE invoice_number = %s",
            $invoiceNumber
        ));

        return $result[0] ?? null;
    }

     /**
      * Create a DigiFact invoice.
      *
      * @param array $data
      * @return array
      */
    public function create(array $data)
    {
        return $this->wpdb->insert($this->tableName, $data);
    }

    /**
     * Update a DigiFact invoice.
     *
     * @param array $data
     * @return array
     */
    public function update(array $data)
    {
        return $this->wpdb->update(
            $this->tableName,
            $data,
            ['id' => $data['id']]
        );
    }

    /**
     * Delete a DigiFact invoice.
     *
     * @param int $id
     * @return array
     */
    public function delete($id)
    {
        return $this->wpdb->delete($this->tableName, ['id' => $id]);
    }
} // End Invoice class
