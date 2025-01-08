<?php

namespace Digifact;

use Exception;
use Digifact\Issuer;
use Digifact\Address;
use Digifact\GeneralData;

/**
 * Class DigiFact
 *
 * This class handles the DigiFact functionality for the WooCommerce plugin.
 *
 * @package WooFelDigiFact
 */
class DigifactLib
{
    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_CERTIFIED = 'certified';
    const STATUS_VOIDED = 'voided';
    const STATUS_ERROR = 'error';

    // anulation types
    const ANULATION_TOSIGN = 'ANULAR_FEL_TOSIGN';
    const ANULATION_FEL = 'ANULAR_FEL_TOSIGN';

    /** @var string the username used for authentication */
    protected $username;

    /** @var string the password used for authentication */
    protected $password;

    /** @var array the headers used for authentication */
    protected $headers = [];

    /** @var string the token used for API requests */
    protected $token;

    /** @var string the base URL for the API */
    public $apiVersion;

    /** @var string the base URL for the API */
    private $baseUrl;

    /** @var string the NIT of emitting company */
    private $nit;

    /** @var array the products to be invoiced */
    private $products = [];

    /** @var array the order details */
    private $order;

    /** @var Issuer issuer details */
    private $issuer;

    /** @var Receiver receiver details */
    private $receiver;

    /** @var GeneralData general data for the DTE */
    private $generalData;

    /** @var InvoiceGenerator DTE generator for the invoice */
    private $dte;

    /** @var Phrase[] phrases for the DTE */
    private $phrases;

    /** @var string xml of the DTE certificated */
    private $xml;

    /** @var string html of the DTE certificated */
    private $html;

    /** @var string pdf of the DTE certificated */
    private $pdf;

    /** @var string authorization number of the certificate */
    private $authorization;

    /** @var string the serial number of the certificate */
    private $serialNumber;

    /** @var string the invoice number of the certificate */
    private $invoiceNumber;

    /** @var array anulation types */
    private $anulationTypes = [self::ANULATION_TOSIGN, self::ANULATION_FEL];

    /** @var AnulationDoc anulation document */
    private $anulationDoc;

    /**
     * Initializes the DigiFactInvoce class.
     *
     * @return void
     */
    public function __construct(string $nit, string $username, string $password)
    {
        $nit = str_replace('-', '', $nit);
        $this->nit = str_pad($nit, 12, '0', STR_PAD_LEFT);
        $this->username = "GT.{$this->nit}.{$username}";
        $this->password = $password;
        $this->apiVersion = 'gt.com.fel.api.v3/api/';
        $this->baseUrl = 'https://felgtaws.digifact.com.gt/' . $this->apiVersion;
    }

    /**
     * Get an authentication token.
     *
     * @param array $credentials The credentials used for authentication.
     * @return string The authentication token.
     */
    private function getToken()
    {
        $settings = get_option('fel-settings');
        $userAuth = $settings[$this->username] ?? [];
        $this->token = $userAuth['token'] ?? null;


        // If there is no token in cache or it is about to expire (e.g., in less than 1 hour),
        // request a new token
        if (!$this->token || $this->isTokenExpiringSoon($this->token)) {
            $response = $this->authenticate();

            $decodedExp = $this->getTokenExpiration($this->token);
            $expiration = date('Y-m-d H:i:s', $decodedExp);

            $data = [
                "{$this->username}" => [
                    'token' => $this->token,
                    'token_expiration' => $response['expira_en'] ?: $expiration
                ]
            ];

            update_option('fel-settings', $data);
        }

        return $this->token;
    }

    /**
     * Get the expiration timestamp of a token.
     *
     * @param string $token The token to check.
     * @return int The expiration timestamp.
     */
    private function getTokenExpiration($token = null)
    {
        $payload = explode('.', $token)[1];
        $decoded = base64_decode($payload);
        $expiration = json_decode($decoded)->exp;
        return $expiration;
    }

    /**
     * Check if the token is expiring soon.
     *
     * @param string $token The token to check.
     * @return bool True if the token is expiring soon, false otherwise.
     */
    private function isTokenExpiringSoon(string $token)
    {
        if (!$token) {
            return false;
        }

        // Get the current time in seconds
        $current_time = current_time('timestamp');

        $expiration = $this->getTokenExpiration($token);

        // Consider the token to be expiring soon if less than 1 hour remains
        return ($current_time + HOUR_IN_SECONDS) >= $expiration;
    }

    /**
     * Authenticate with the DigiFact API.
     *
     * @return array The response from the authentication request.
     */
    private function authenticate()
    {
        // Authenticate with the DigiFact API
        $response = wp_remote_post("{$this->baseUrl}login/get_token", [
            'body' => [
                'username' => $this->username,
                'password' => $this->password
            ],
        ]);

        //
        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        // Decode
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // store
        $this->token = $data['Token'] ?? null;

        return $data;
    }

    /**
     * Fetches shared information for a given NIT (Tax Identification Number) using the Digifact API.
     *
     * @param string $nit The NIT to query.
     * @return mixed The shared information object if successful, false otherwise.
     * @throws \Error If there is an error in the API response.
     */
    public function getSharedInfoNIT(string $nit)
    {
        // Check if the NIT is empty
        if (empty($nit)) {
            return FALSE;
        }

        // Remove any hyphens from the NIT
        $nit = str_replace('-', '', $nit);

        try {
            $this->headers['Authorization'] = $this->getToken();

            $params = http_build_query([
                'NIT'      => $this->nit,
                'USERNAME' => $this->username,
                'DATA1'    => 'SHARED_GETINFONITCOM',
                'DATA2'    => "NIT|{$nit}",
            ]);

            // Make the API request to get the shared information
            $response = wp_remote_get("{$this->baseUrl}SHAREDINFO?{$params}", [
                'headers' => [
                    'Authorization' => $this->headers['Authorization'],
                ]
            ]);

            if (is_wp_error($response)) {
                throw new Exception("Error en la solicitud: " . $response->get_error_message());
            }

            $responseData = json_decode(wp_remote_retrieve_body($response), true);

            // Check if the response contains the required data
            if ($responseData['REQUEST_DATA'][0]['Respuesta'] != 1) {
                throw new Exception("FEL INFONIT: {$responseData['REQUEST_DATA'][0]['Mensaje']}");
            }

            return $responseData['RESPONSE'][0];
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Retrieves and formats the taxpayer's name.
     *
     * This method takes a string containing the taxpayer's name, which may have
     * parts separated by double commas. It reverses the order of these parts,
     * replaces commas with spaces, and converts ampersands to their HTML entity.
     *
     * @param string|null $name The taxpayer's name as a string, or null.
     *                          If null, an empty string will be returned.
     * @return string The formatted taxpayer's name.
     */
    public static function getTaxPayerName($name = null): string
    {
        $strName = '';
        $strArray = explode(',,', $name);
        $strArray = array_reverse($strArray);
        foreach ($strArray as $str) {
            $strName .= str_replace(',', ' ', $str) . ' ';
        }

        $strName = str_replace('&', '&amp;', $strName);
        return trim($strName);
    }

    /**
     * Set the issuer details.
     *
     * @param array $issuer The issuer details.
     * @return Digifact
     */
    public function setIssuer(array $issuer)
    {
        // verify if the issuer is not empty
        if (empty($issuer)) {
            throw new \Exception('Issuer is empty');
        }

        $addressIssuer = new Address(
            $issuer['address']['address'],
            $issuer['address']['postal_code'],
            $issuer['address']['municipality'],
            $issuer['address']['department'],
            $issuer['address']['country'],
        );

        $this->issuer = new Issuer(
            $issuer['nit'],
            $issuer['name'],
            $issuer['email'],
            $issuer['tradename'],
            $addressIssuer,
            $issuer['establishment_code'],
            $issuer['iva_affiliation'],
        );

        return $this;
    }

    /**
     * Set the receiver details.
     *
     * @param array $receiver The receiver details.
     * @return Digifact
     */
    public function setReceiver(array $receiver)
    {
        // verify if the receiver is not empty
        if (empty($receiver)) {
            throw new \Exception('Receiver is empty');
        }

        $addressReceiver = new Address(
            $receiver['address']['address'],
            $receiver['address']['postal_code'],
            $receiver['address']['municipality'],
            $receiver['address']['department'],
            $receiver['address']['country'],
        );

        $this->receiver = new Receiver(
            $receiver['name'],
            $receiver['nit'],
            $receiver['email'],
            $addressReceiver,
        );

        return $this;
    }

    /**
     * set order details.
     *
     * @param array $order
     * @return $this
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * set products to generate invoice.
     *
     * @param array $products
     * @return $this
     * @throws Exception
     */
    public function setProducts(array $items)
    {
        if (empty($items)) {
            throw new Exception('No hay productos para generar factura');
        }

        foreach ($items as $item) {
            // Extract product data
            $quantity = $item->get_quantity(); // Quantity
            $unitPrice = $item->get_total() / $quantity; // Unit price
            $description = $item->get_name(); // Product name or description
            $discount = 0; // Discount applied
            $taxes = [new Tax('IVA', 1, $unitPrice)];
            $isService = Product::TYPE_ASSET; // Active or service
            $unitOfMeasurement = '1'; // Default unit of measurement (adjust if necessary)

            // Create an instance of the product
            $productInstance = new Product(
                $quantity,             // Quantity
                $unitOfMeasurement,    // Unit of measurement
                $description,          // Description
                $unitPrice,            // Unit price
                $discount,             // Discount
                $isService,            // Active or service
                $taxes                 // Taxes
            );

            // Add to the list of products
            array_push($this->products, $productInstance);
        }

        return $this;
    }

    /**
     * Set the Phrases for the invoice.
     *
     * @param string $phraseType Type of phrase (default: '1')
     * @param string $scenarioCode Scenario code (default: '1')
     *
     * @return Digifact
     */
    public function setPhrases($phraseType = '1', $scenarioCode = '1')
    {
        $this->phrases[] = new Phrase($phraseType, $scenarioCode);
        return $this;
    }

    /**
     * Generate the DTE (Electronic Tax Document).
     *
     * @return Digifact
     */
    public function generateDTE()
    {
        // make sure the reference is unique without booking prefix
        $reference = time();
        $this->generalData = new GeneralData($reference);

        $this->dte = new InvoiceGenerator(
            $this->generalData,
            $this->issuer,
            $this->receiver,
            $this->phrases,
            $this->products,
        );

        return $this;
    }

    /**
     * Sends the invoice to the Digifact API and receives the certificate.
     *
     * @return array
     */
    public function certificateDTE()
    {
        // Add the authorization header with the token
        $this->headers['Authorization'] = $this->getToken();

        $params = http_build_query([
            'NIT'      => $this->nit,
            'TIPO'     => 'CERTIFICATE_DTE_XML_TOSIGN',
            'FORMAT'   => 'XML',
        ]);

        $endpointUrl = "{$this->baseUrl}FelRequest?{$params}";

        // Get the XML body of the DTE
        $body = $this->dte->getXML();

        // Make the POST request with wp_remote_post
        $response = wp_remote_post($endpointUrl, [
            'headers' => $this->headers,
            'body'    => $body,
            'method'  => 'POST',
            'timeout' => 45,
        ]);

        // Check if there was an error in the request
        if (is_wp_error($response)) {
            throw new Exception('Error al conectarse con Digifact: ' . $response->get_error_message());
        }

        // Decode the response JSON
        $responseBody = wp_remote_retrieve_body($response);
        $responseObj = json_decode($responseBody);

        // Validate the response content
        if (isset($responseObj->Codigo) && $responseObj->Codigo == 1) {
            $this->xml = $responseObj->ResponseDATA1;
            $this->html = $responseObj->ResponseDATA2;
            $this->pdf = $responseObj->ResponseDATA3;
            $this->authorization = $responseObj->Autorizacion;
            $this->serialNumber = $responseObj->Serie;
            $this->invoiceNumber = $responseObj->NUMERO;
            $responseObj->grandTotal = $this->dte->grandTotal;

            return $responseObj;
        } else {
            // Handle errors in the response
            if (isset($responseObj->ResponseDATA1)) {
                throw new Exception("FEL DTE: {$responseObj->ResponseDATA1}");
            } elseif (isset($responseObj->Mensaje)) {
                throw new Exception("FEL DTE: {$responseObj->Mensaje}");
            } else {
                throw new Exception('Ha ocurrido un error inesperado conectándose a Digifact.');
            }
        }
    }

    /**
     * Generate an anulation request.
     *
     * @param string $invoiceNumber Invoice number to be annulled
     * @param string $receiverNit NIT of the receiver
     * @param string $issueDateToAnulate Issue date of the document to be annulled in format 'Y-m-d\TH:i:s'
     * @param string $reason Reason for the anulation
     * @return $this
     * @throws \Exception if any of the provided parameters are invalid
     */
    public function anulation(
        $invoiceNumber,
        $receiverNit,
        $issueDateToAnulate,
        $reason
    ) {
        try {
            $date = new \DateTime($issueDateToAnulate);
            $formattedDate = $date->format('Y-m-d\TH:i:s');
        } catch (Exception $e) {
            throw new Exception('Invalid date format provided for issueDateToAnulate.');
        }
        $this->anulationDoc = new AnulationData(
            $invoiceNumber,
            $this->nit,
            $receiverNit,
            $formattedDate,
            $reason
        );

        return $this;
    }

    /**
     * Sends an anulation request to Digifact.
     *
     * @param string $anulationType Type of anulation to be sent (default: 'tosign')
     * @return Digifact
     * @throws Exception if the anulation type is invalid
     */
    public function certificateAnulation($anulationType = self::ANULATION_TOSIGN)
    {
        if (!in_array($anulationType, $this->anulationTypes)) {
            throw new Exception('Tipo de anulación no válido');
        }

        // Add the authorization header with the token
        $this->headers['Authorization'] = $this->getToken();

        // Get the XML body of the DTE
        $endpointUrl = "{$this->baseUrl}FelRequest?NIT={$this->nit}"
            . "&TIPO={$anulationType}"
            . '&FORMAT=XML';

        $body = $this->anulationDoc->toXML();

        $response = wp_remote_post($endpointUrl, [
            'headers' => $this->headers,
            'body'    => $body,
            'method'  => 'POST',
            'timeout' => 45,
        ]);

        // Check if there was an error in the request
        if (is_wp_error($response)) {
            throw new Exception('Error al conectarse con Digifact: ' . $response->get_error_message());
        }

        // Decode the response JSON
        $responseBody = wp_remote_retrieve_body($response);
        $responseObj = json_decode($responseBody);

        return $responseObj;
    }
} // End DigiFactInvoice class
