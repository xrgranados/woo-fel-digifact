<?php

namespace Digifact\Models;

use Exception;

/**
 * NIT Classs
 *
 * Handles the validation and management of Guatemalan Tax Identification Number (NIT).
 *
 * @package Digifact
 **/
class Nit
{
    /**
     * Regular expression pattern for validating NIT.
     */
    const NIT_REGEX = '/^(\d+)\-?([\dkK])$/';

    /** @var string The NIT number */
    public $nit;

    /**
     * Nit constructor.
     *
     * @param string $nit The NIT number to be validated and stored.
     *
     * @throws Exception If the provided NIT is not valid.
     */
    function __construct(string $nit)
    {
        if (!$this->validateNit($nit)) {
            new Exception('NIT is not valid');
        }

        // Remove any dashes from the NIT
        $this->nit = str_replace('-', '', $nit);
    }

    /**
     * Validates the provided NIT number.
     *
     * @param string $nit The NIT number to be validated.
     *
     * @return bool True if the NIT is valid, false otherwise.
     */
    function validateNit($nit): bool
    {
        $pattern = '/^(\d+)\-?([\dkK])$/';

        if (preg_match($pattern, $nit, $nd)) {
            $nd[2] = (strtolower($nd[2]) === 'k') ? 10 : intval($nd[2]);
            $add = 0;
            $length = strlen($nd[1]);
            for ($i = 0; $i < $length; $i++) {
                $add += (((($i - $length) * -1) + 1) * $nd[1][$i]);
            }
            return ((11 - ($add % 11)) % 11) === $nd[2];
        } else {
            return false;
        }
    }

    /**
     * Returns the stored NIT number.
     *
     * @return string The NIT number.
     */
    public function getNit(): string
    {
        return $this->nit;
    }
} // END class NIT
