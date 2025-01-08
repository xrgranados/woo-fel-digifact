<?php

namespace Digifact;

use DateTime;
use Exception;
use SimpleXMLElement;
use Digifact\Models\Nit;

/**
 * Class AnulationData
 *
 * Represents the data required for invoice anulation.
 *
 * @package App\Library\Digifact\Models
 */
class AnulationData
{
    /** @var string Document number to anulate */
    public $documentNumberToAnulate;

    /** @var string Issuer NIT */
    public $issuerNIT;

    /** @var string Receiver NIT */
    public $receiverNIT;

    /** @var string Issue date to anulate */
    public $issueDateToAnulate;

    /** @var string Anulation date time */
    public $anulationDateTime;

    /** @var string Anulation reason */
    public $anulationReason;

    /** @var string Regex for NIT validation */
    private $nitRegex = '/^(\d+)\-?([\dkK])$/';

    /** @var string Date format */
    private $dateFormat = 'Y-m-d\TH:i:s';

    /**
     * AnulationData constructor.
     *
     * @param string $documentNumberToAnulate
     * @param string $issuerNIT
     * @param string $receiverID
     * @param string $issueDateToAnulate
     * @param string $anulationReason
     * @param string|bool $anulationDateTime
     * @throws Exception
     */
    public function __construct(
        string $documentNumberToAnulate,
        string $issuerNIT,
        string $receiverNIT,
        string $issueDateToAnulate,
        string $anulationReason,
        $anulationDateTime = false
    ) {
        if (empty($documentNumberToAnulate)) {
            throw new Exception('Document number to anulate is required.');
        }
        $this->documentNumberToAnulate = $documentNumberToAnulate;

        if (empty($anulationReason)) {
            throw new Exception('Anulation reason is required.');
        }
        $this->anulationReason = $anulationReason;

        if (!preg_match($this->nitRegex, $issuerNIT)) {
            throw new Exception('Invalid issuer NIT format.');
        }
        $this->issuerNIT = intval($issuerNIT);

        $this->receiverNIT = (new Nit($receiverNIT))->getNit();

        if (!$this->isValidDateFormat($issueDateToAnulate)) {
            throw new Exception('Invalid issue date format, expected "Y-m-d\TH:i:s".');
        }
        $this->issueDateToAnulate = $issueDateToAnulate;

        if ($anulationDateTime) {
            if (!$this->isValidDateFormat($anulationDateTime)) {
                throw new Exception('Invalid anulation date format, expected "Y-m-d\TH:i:s".');
            }
            $this->anulationDateTime = $anulationDateTime;
        } else {
            $this->anulationDateTime = date($this->dateFormat);
        }
    }

    /**
     * Validate the date format.
     *
     * @param string $date
     * @return bool
     */
    private function isValidDateFormat(string $date): bool
    {
        return DateTime::createFromFormat($this->dateFormat, $date) !== false;
    }

    /**
     * Generate the XML representation of the anulation data.
     *
     * @return string
     */
    public function toXML(): string
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><dte:GTAnulacionDocumento xmlns:dte="http://www.sat.gob.gt/dte/fel/0.1.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" Version="0.1"></dte:GTAnulacionDocumento>');
        $sat = $xml->addChild('dte:SAT');
        $anulationDTE = $sat->addChild('dte:AnulacionDTE');
        $anulationDTE->addAttribute('ID', 'DatosCertificados');
        $generalData = $anulationDTE->addChild('dte:DatosGenerales');
        $generalData->addAttribute('ID', 'DatosAnulacion');
        $generalData->addAttribute('NumeroDocumentoAAnular', $this->documentNumberToAnulate);
        $generalData->addAttribute('NITEmisor', $this->issuerNIT);
        $generalData->addAttribute('IDReceptor', $this->receiverNIT);
        $generalData->addAttribute('FechaEmisionDocumentoAnular', $this->issueDateToAnulate);
        $generalData->addAttribute('FechaHoraAnulacion', $this->anulationDateTime);
        $generalData->addAttribute('MotivoAnulacion', $this->anulationReason);

        return $xml->asXML();
    }
} // END class AnulationData
