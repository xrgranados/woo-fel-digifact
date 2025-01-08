<?php

namespace Digifact;

use DateTime;
use Exception;

/**
 * Class GeneralData
 *
 * Provides data for DTE (Electronic Tax Document) invoicing.
 */
class GeneralData
{
    // Document constants
    const INVOICE_DOCUMENT = 'FACT';
    const EXCHANGE_INVOICE_DOCUMENT = 'FCAM';
    const SMALL_TAXPAYER_INVOICE_DOCUMENT = 'FPEQ';
    const EXCHANGE_INVOICE_SMALL_TAXPAYER_DOCUMENT = 'FCAP';
    const SPECIAL_INVOICE_DOCUMENT = 'FESP';
    const CREDIT_NOTE_DOCUMENT = 'NABN';
    const REDEMPTION_DOCUMENT = 'RDON';
    const RECEIPT_DOCUMENT = 'RECI';
    const DEBIT_NOTE_DOCUMENT = 'NDEB';
    // const CREDIT_NOTE_DOCUMENT = 'NCRE';

    // Signature and digest URLs
    const SIGNATURE_SHA256_URL = 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256';
    const DIGEST_SHA256_URL = 'http://www.w3.org/2001/04/xmlenc#sha256';

    /** @var array List of accepted DTE document types */
    private const TYPES_DTE = [
        self::INVOICE_DOCUMENT,
        self::EXCHANGE_INVOICE_DOCUMENT,
        self::SMALL_TAXPAYER_INVOICE_DOCUMENT,
        self::EXCHANGE_INVOICE_SMALL_TAXPAYER_DOCUMENT,
        self::SPECIAL_INVOICE_DOCUMENT,
        self::CREDIT_NOTE_DOCUMENT,
        self::REDEMPTION_DOCUMENT,
        self::RECEIPT_DOCUMENT,
        self::DEBIT_NOTE_DOCUMENT,
        self::CREDIT_NOTE_DOCUMENT,
    ];

    /** @var array Accepted currencies according to ISO 4217 */
    private const ACCEPTED_CURRENCIES = [
        'GTQ',
        'USD',
        'VES',
        'CRC',
        'SVC',
        'NIO',
        'DKK',
        'NOK',
        'SEK',
        'CAD',
        'HKD',
        'TWD',
        'PTE',
        'EUR',
        'CHF',
        'HNL',
        'GBP',
        'ARS',
        'DOP',
        'COP',
        'MXN',
        'BRL',
        'MYR',
        'INR',
        'PKR',
        'KPW',
        'JPY'
    ];

    /** @var string The currency for the invoice */
    public $currency;

    /** @var string The date and time of broadcast */
    public $issueDateTime;

    /** @var string The type of DTE document */
    public $type;

    /** @var string The internal reference for the invoice */
    public $internalReference;

    /** @var string Date format for issueDateTime */
    private $dateFormat = 'Y-m-d\TH:i:s';

    /**
     * GeneralData constructor.
     *
     * @param string $internalReference Internal reference for the invoice
     * @param string $type Type of DTE document
     * @param string $currency Currency for the invoice
     * @param string|null $issueDateTime Date and time of issuance
     *
     * @throws Exception
     */
    public function __construct(
        $internalReference,
        string $type = self::INVOICE_DOCUMENT,
        string $currency = 'GTQ',
        $issueDateTime = null
    ) {
        $this->setInternalReference($internalReference);
        $this->setTypeDTE($type);
        $this->setCurrency($currency);
        $this->setIssueDateTime($issueDateTime);
    }

    private function setTypeDTE($type)
    {
        if (!in_array($type, self::TYPES_DTE)) {
            throw new Exception('Tipo DTE no valido no valida');
        }

        $this->type = $type;
    }

    private function setCurrency($currency)
    {
        if (!in_array($currency, self::ACCEPTED_CURRENCIES)) {
            throw new Exception('Moneda no valida');
        }

        $this->currency = $currency;
    }

    private function setInternalReference($internalReference)
    {
        if (empty($internalReference)) {
            throw new Exception('Parameter "internalReference" is required');
        }

        $this->internalReference = $internalReference;
    }

    private function setIssueDateTime($issueDateTime)
    {
        if (empty($issueDateTime)) {
            // Default to current date and time if not provided
            $this->issueDateTime = wp_date($this->dateFormat);
        } else {
            if (DateTime::createFromFormat($this->dateFormat, $issueDateTime) !== false) {
                $this->issueDateTime = $issueDateTime;
            } else {
                throw new Exception('Invalid date format, required "Y-m-d\TH:i:s"');
            }
        }
    }
} // END class GeneralData
