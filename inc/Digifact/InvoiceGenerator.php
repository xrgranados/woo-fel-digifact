<?php

namespace Digifact;

use Exception;
use SimpleXMLElement;
use Digifact\Issuer;
use Digifact\Phrase;
use Digifact\Receiver;
use Digifact\GeneralData;

/**
 * Class to generate an XML invoice using the GTDocumento format.
 */
class InvoiceGenerator
{
    /** @var SimpleXMLElement The XML document. */
    private $xml;

    /** @var GeneralData General data of the invoice. */
    public $generalData;

    /** @var Issuer Issuer of the invoice. */
    public $issuer;

    /** @var Receiver Receiver of the invoice. */
    public $receiver;

    /** @var Phrase[] List of phrases associated with the invoice. */
    public $phrases;

    /** @var Product[] List of items (products) in the invoice. */
    public $items;

    /** @var float Grand total of the invoice. */
    public $grandTotal;

    /** @var array Total taxes applied to the invoice. */
    public $totalTaxes;

    /**
     * Constructor to initialize the XML document.
     *
     * @param GeneralData $generalData The general data of the invoice.
     * @param Issuer $issuer The issuer of the invoice.
     * @param Receiver $receiver The receiver of the invoice.
     * @param Phrase[] $phrases List of phrases associated with the invoice.
     * @param Product[] $items List of items (products) in the invoice.
     *
     * @throws Exception If any of the phrases or items are invalid.
     */
    public function __construct(
        GeneralData $generalData,
        Issuer $issuer,
        Receiver $receiver,
        array $phrases = [],
        array $items = []
    ) {
        $this->xml = new SimpleXMLElement(
            '<xml version= "1.0" encoding="utf-8" xmlns:dte="http://www.sat.gob.gt/dte/fel/0.2.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"/>',
            0,
            false,
            'dte',
            true
        );

        $this->generalData = $generalData;
        $this->issuer = $issuer;
        $this->receiver = $receiver;

        $this->setPhrases($phrases);
        $this->setProducts($items);
    }

    /**
     * Set the phrases associated with the invoice.
     *
     * @param Phrase[] $phrases List of phrases.
     *
     * @throws Exception If any phrase is invalid or the list is empty.
     */
    public function setPhrases($phrases)
    {
        if (empty($phrases)) {
            throw new Exception('Phrases cannot be empty.');
        }

        foreach ($phrases as $phrase) {
            if (!$phrase instanceof Phrase) {
                throw new Exception('Invalid Phrase object.');
            }
        }

        $this->phrases = $phrases;
    }

    /**
     * Set the products (items) in the invoice.
     *
     * @param Product[] $items List of items.
     *
     * @throws Exception If any item is invalid or the list is empty.
     */
    public function setProducts($items)
    {
        if (empty($items)) {
            throw new Exception('Items cannot be empty.');
        }

        foreach ($items as $item) {
            if (!$item instanceof Product) {
                throw new Exception('Invalid Product object.');
            }
        }

        $this->items = $items;
    }

    /**
     * Calculate the totals for the invoice.
     */
    private function calcTotals()
    {
        $this->grandTotal = 0;
        $this->totalTaxes = [];
        foreach ($this->items as $item) {
            $this->grandTotal += $item->price;
            foreach ($item->taxes as $tax) {
                if (!isset($this->totalTaxes[$tax->shortName])) {
                    $this->totalTaxes[$tax->shortName] = 0;
                }
                $this->totalTaxes[$tax->shortName] += $tax->taxAmount;
            }
        }
    }

    /**
     * Set the general data node.
     * @param array $param The general data parameters.
     */
    public function setGeneralDataNode()
    {
        $generalData = $this->xml->addChild('dte:DatosGenerales', '', 'dte');
        $generalData->addAttribute('CodigoMoneda', $this->generalData->currency);
        $generalData->addAttribute('FechaHoraEmision', $this->generalData->issueDateTime);
        $generalData->addAttribute('Tipo', $this->generalData->type);
    }

    /**
     * Set the issuer node.
     */
    public function setIssuerNode()
    {
        $issuer = $this->xml->addChild('dte:Emisor', '', 'dte');

        $issuer->addAttribute('AfiliacionIVA', $this->issuer->vatAffiliation);
        $issuer->addAttribute('CodigoEstablecimiento', $this->issuer->establishmentCode);
        $issuer->addAttribute('CorreoEmisor', $this->issuer->email);
        $issuer->addAttribute('NITEmisor', $this->issuer->issuerNit->nit);
        $issuer->addAttribute('NombreComercial', $this->issuer->commercialName);
        $issuer->addAttribute('NombreEmisor', $this->issuer->issuerName);

        $addressIssuer = $issuer->addChild('dte:DireccionEmisor', '', 'dte');
        $addressIssuer->addChild('dte:Direccion', $this->issuer->address->address, 'dte');
        $addressIssuer->addChild('dte:CodigoPostal', $this->issuer->address->postalCode, 'dte');
        $addressIssuer->addChild('dte:Municipio', $this->issuer->address->municipality, 'dte');
        $addressIssuer->addChild('dte:Departamento', $this->issuer->address->department, 'dte');
        $addressIssuer->addChild('dte:Pais', $this->issuer->address->country, 'dte');
    }

    /**
     * Set the receiver node.
     */
    public function setReceiverNode()
    {
        $receiver = $this->xml->addChild('dte:Receptor', '', 'dte');

        $receiver->addAttribute('IDReceptor', $this->receiver->receiverNit->nit);
        $receiver->addAttribute('NombreReceptor', $this->receiver->name);
        $receiver->addAttribute('CorreoReceptor', $this->receiver->email);

        $addressReceiver = $receiver->addChild('dte:DireccionReceptor', '', 'dte');

        $addressReceiver->addChild('dte:Direccion', $this->receiver->address->address, 'dte');
        $addressReceiver->addChild('dte:CodigoPostal', $this->receiver->address->postalCode, 'dte');
        $addressReceiver->addChild('dte:Municipio', $this->receiver->address->municipality, 'dte');
        $addressReceiver->addChild('dte:Departamento', $this->receiver->address->department, 'dte');
        $addressReceiver->addChild('dte:Pais', $this->receiver->address->country, 'dte');
    }

    /**
     * Set the phrases associated with the invoice.
     *
     * @param Phrase[] $phrases List of phrases.
     *
     * @throws Exception If any phrase is invalid or the list is empty.
     */
    public function setPhrasesNode()
    {
        $phrases = $this->xml->addChild('dte:Frases', '', 'dte');
        foreach ($this->phrases as $phrase) {
            $phraseNode = $phrases->addChild('dte:Frase', '', 'dte');
            $phraseNode->addAttribute('CodigoEscenario', $phrase->scenarioCode);
            $phraseNode->addAttribute('TipoFrase', $phrase->phraseType);
        }
    }

    /**
     * Set the items node.
     * @param array $params The item parameters.
     */
    public function setItemsNode()
    {
        $items = $this->xml->addChild('dte:Items', '', 'dte');
        foreach ($this->items as $index => $item) {
            $itemNode = $items->addChild('Item');
            $itemNode->addAttribute('BienOServicio', $item->assetOrService);
            $itemNode->addAttribute('NumeroLinea', $index + 1);
            $itemNode->addChild('dte:Cantidad', $item->quantity, 'dte');
            $itemNode->addChild('dte:UnidadMedida', $item->unitOfMeasurement, 'dte');
            $itemNode->addChild('dte:Descripcion', $item->description, 'dte');
            $itemNode->addChild('dte:PrecioUnitario', $item->unitPrice, 'dte');
            $itemNode->addChild('dte:Precio', $item->price, 'dte');
            $itemNode->addChild('dte:Descuento', $item->discount, 'dte');

            $taxes = $itemNode->addChild('Impuestos', '', 'dte');
            foreach ($item->taxes as $tax) {
                $taxNode = $taxes->addChild('dte:Impuesto', '', 'dte');
                $taxNode->addChild('dte:NombreCorto', $tax->shortName, 'dte');
                $taxNode->addChild('dte:CodigoUnidadGravable', $tax->taxableUnitCode, 'dte');
                $taxNode->addChild('dte:MontoGravable', $tax->taxableAmount, 'dte');
                $taxNode->addChild('dte:MontoImpuesto', $tax->taxAmount, 'dte');
            }
            $itemNode->addChild('dte:Total', $item->price, 'dte');
        }
    }

    /**
     * Set the totals node.
     * @param array $params The total parameters.
     */
    public function setTotalsNode()
    {
        $totals = $this->xml->addChild('dte:Totales', '', 'dte');
        $totalTaxes = $totals->addChild('dte:TotalImpuestos', '', 'dte');
        foreach ($this->totalTaxes as $taxName => $taxAmount) {
            $totalTax = $totalTaxes->addChild('dte:TotalImpuesto', '', 'dte');
            $totalTax->addAttribute('NombreCorto', $taxName);
            $totalTax->addAttribute('TotalMontoImpuesto', $taxAmount);
        }
        $totals->addChild('dte:GranTotal', $this->grandTotal, 'dte');
    }

    /**
     * Get the XML as a string.
     * @return string The XML as a string.
     */
    public function getXML()
    {
        $this->calcTotals();

        $this->setGeneralDataNode();
        $this->setIssuerNode();
        $this->setReceiverNode();
        $this->setPhrasesNode();
        $this->setItemsNode();
        $this->setTotalsNode();


        $result = $this->xml->DatosGenerales->asXML();
        $result .= $this->xml->Emisor->asXML();
        $result .= $this->xml->Receptor->asXML();
        $result .= $this->xml->Frases->asXML();
        $result .= $this->xml->Items->asXML();
        $result .= $this->xml->Totales->asXML();

        // Construct the final XML document
        $element = '<?xml version="1.0" encoding="utf-8" standalone="no"?><dte:GTDocumento xmlns:dte="http://www.sat.gob.gt/dte/fel/0.2.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="0.1"><dte:SAT ClaseDocumento="dte"><dte:DTE ID="DatosCertificados"><dte:DatosEmision ID="DatosEmision">' . $result . '</dte:DatosEmision></dte:DTE></dte:SAT></dte:GTDocumento>';

        // Remove any redundant namespace declarations
        $element = str_replace(' xmlns:dte="dte"', '', $element);

        return $element;
    }
} // END class InvoiceGenerator
