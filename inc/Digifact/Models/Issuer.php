<?php

namespace Digifact\Models;

use Exception;
use Digifact\Models\Nit;
use Digifact\Models\Address;

/**
 * Class Issuer
 *
 * Represents the issuer of a document with various details including tax ID,
 * name, commercial name, and address.
 *
 * @package Digifact
 */
class Issuer
{
    /** @var Digifact\Nit Tax ID of the issuer */
    public $issuerNit;

    /** @var string Name of the issuer */
    public $issuerName;

    /** @var string issuer's email */
    public $email;

    /** @var int Establishment code */
    public $establishmentCode;

    /** @var string Commercial name of the issuer */
    public $commercialName;

    /** @var string VAT affiliation type */
    public $vatAffiliation;

    /** @var Address address of the issuer */
    public $address;

    const VALID_VAT_AFFILIATIONS = [
        'GEN',
        'EXE',
        'PEQ',
        'PEE',
        'AGR',
        'AGE'
    ];

    /**
     * Issuer constructor.
     *
     * @param string $nit Tax ID of the issuer
     * @param string $issuerName Name of the issuer
     * @param string $commercialName Commercial name of the issuer
     * @param Address $address Address of the issuer
     * @param int $establishmentCode Establishment code (default: 1)
     * @param string $vatAffiliation VAT affiliation type (default: GEN)
     *
     * @throws Exception if any of the parameters are invalid
     */
    public function __construct(
        $nit,
        $issuerName,
        $email,
        $commercialName,
        Address $address,
        $establishmentCode = 1,
        $vatAffiliation = 'GEN'
    ) {
        $this->setIssuerNit($nit);
        $this->setIssuerName($issuerName);
        $this->setIssuerEmail($email);
        $this->setEstablishmentCode($establishmentCode);
        $this->setCommercialName($commercialName);
        $this->setVatAffiliation($vatAffiliation);
        $this->setAddress($address);
    }

    /**
     * Sets the issuer's NIT.
     *
     * @param string $nit Tax ID of the issuer
     *
     * @throws Exception if the NIT is invalid
     */
    private function setIssuerNit(string $nit)
    {
        if (!empty($nit)) {
            $this->issuerNit = (new Nit($nit));
        } else {
            throw new Exception('Issuer NIT is required');
        }
    }

    /**
     * Sets the issuer's name.
     *
     * @param string $issuerName Name of the issuer
     *
     * @throws Exception if the issuer name is empty
     */
    private function setIssuerName(string $issuerName)
    {
        if (!empty($issuerName)) {
            $this->issuerName = $issuerName;
        } else {
            throw new Exception('Issuer name is required');
        }
    }

    /**
     * Sets the issuer's email.
     *
     * @param string $email The issuer's email
     *
     * @throws Exception if the email is invalid
     */
    private function setIssuerEmail(string $email): void
    {
        if (empty($email)) {
            throw new Exception('Issuer email is required');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Issuer email is not valid');
        }
        $this->email = $email;
    }

    /**
     * Sets the establishment code.
     *
     * @param int $establishmentCode Establishment code
     *
     * @throws Exception if the establishment code is not numeric
     */
    private function setEstablishmentCode(int $establishmentCode)
    {
        if (is_numeric($establishmentCode)) {
            $this->establishmentCode = $establishmentCode;
        } else {
            throw new Exception('Establishment code must be numeric');
        }
    }

    /**
     * Sets the commercial name of the issuer.
     *
     * @param string $commercialName Commercial name of the issuer
     *
     * @throws Exception if the commercial name is empty
     */
    private function setCommercialName(string $commercialName)
    {
        if (!empty($commercialName)) {
            $this->commercialName = $commercialName;
        } else {
            throw new Exception('Commercial name is required');
        }
    }

    /**
     * Sets the VAT affiliation type.
     *
     * @param string $vatAffiliation VAT affiliation type
     *
     * @throws Exception if the VAT affiliation type is invalid
     */
    private function setVatAffiliation(string $vatAffiliation)
    {
        if (in_array($vatAffiliation, self::VALID_VAT_AFFILIATIONS)) {
            $this->vatAffiliation = $vatAffiliation;
        } else {
            throw new Exception('Invalid VAT affiliation type');
        }
    }

    /**
     * Sets the address of the issuer.
     *
     * @param Address $address Address of the issuer
     *
     * @throws Exception if the address is not valid
     */
    private function setAddress(Address $address)
    {
        if ($address instanceof Address) {
            $this->address = $address;
        } else {
            throw new Exception('Invalid address type');
        }
    }
} // END class Issuer
