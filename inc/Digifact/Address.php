<?php

namespace Digifact;

use Exception;

/**
 * Class Address
 *
 * Represents an address with various components including postal code, city, state, and country.
 */
class Address
{
    /** @var string Address line */
    public $address;

    /** @var string Postal code */
    public $postalCode;

    /** @var string City */
    public $municipality;

    /** @var string State/Department */
    public $department;

    /** @var string Country code (ISO 3166-1) */
    public $country;

    /**
     * @var array List of valid ISO 3166-1 country codes
     */
    const VALID_COUNTRIES = [
        'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AN',
        'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX',
        'AZ', 'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH',
        'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BR', 'BS',
        'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD',
        'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
        'CO', 'CR', 'CU', 'CV', 'CX', 'CY', 'CZ', 'DE',
        'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE', 'EG',
        'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM',
        'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF', 'GG',
        'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR',
        'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM', 'HN',
        'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN',
        'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM', 'JO',
        'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP',
        'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC', 'LI',
        'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA',
        'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML',
        'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT',
        'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA', 'NC',
        'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR',
        'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG', 'PH',
        'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW',
        'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW', 'SA',
        'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ',
        'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'ST', 'SV',
        'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ',
        'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV',
        'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ',
        'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU', 'WF',
        'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW', 'BQ', 'CW',
        'SS', 'SX'
    ];

    /**
     * Address constructor.
     *
     * @param string $address The address line
     * @param int $postalCode The postal code
     * @param string $city The city
     * @param string $state The state/department
     * @param string $countryCode The country code (ISO 3166-1)
     *
     * @throws Exception if any of the parameters are invalid
     */
    public function __construct(
        string $address,
        string $postalCode,
        string $municipality,
        string $department,
        string $countryCode
    ) {
        $this->setAddress($address);
        $this->setPostalCode($postalCode);
        $this->setMunicipality($municipality);
        $this->setDepartment($department);
        $this->setCountryCode($countryCode);
    }

    /**
     * set the address line.
     *
     * @param string $address
     * @throws Exception if the address is empty
     */
    private function setAddress(string $address): void
    {
        if (empty($address)) {
            throw new Exception('Address is required');
        }
        $this->address = $address;

    }

    /**
     * set the postal code.
     *
     * @param string $postalCode
     * @throws Exception if the postal code is not numeric
     */
    private function setPostalCode(string $postalCode): void
    {
        if (empty($postalCode)) {
            throw new Exception('Postal code must be numeric');
        }

        $this->postalCode = $postalCode;
    }

    /**
     * set the city.
     *
     * @param string $city
     * @throws Exception if the city is empty
     */
    private function setMunicipality(string $municipality): void
    {
        if (empty($municipality)) {
            throw new Exception('Municipality is required');
        }

        $this->municipality = $municipality;
    }

    /**
     * set the state/department.
     *
     * @param string $state
     * @throws Exception if the state/department is empty
     */
    private function setDepartment(string $department): void
    {
        if (empty($department)) {
            throw new Exception('State/Department is required');
        }

        $this->department = $department;
    }

    /**
     * set the country code.
     *
     * @param string $countryCode
     * @throws Exception if the country code is not valid
     */
    private function setCountryCode(string $countryCode): void
    {
        if (!in_array($countryCode, self::VALID_COUNTRIES)) {
            throw new Exception('Invalid country code, must be ISO 3166-1');
        }

        $this->country = $countryCode;
    }
} // END class Address
