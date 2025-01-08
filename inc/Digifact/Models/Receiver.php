<?php

namespace Digifact\Models;

use Exception;
use Digifact\Models\Nit;
use Digifact\Models\Address;

/**
 * Class Receiver
 *
 * Represents a receiver with a name, ID, email, and address.
 *
 * @package Digifact
 */
class Receiver
{
    /** @var string Receiver's name */
    public $name;

    /** @var Digifact\Nit Receiver's ID (can be numeric or "CF" for consumers) */
    public $receiverNit;

    /** @var string Receiver's email */
    public $email;

    /** @var Address Receiver's address */
    public $address;

    /**
     * Receiver constructor.
     *
     * @param string $name The receiver's name
     * @param string $nit The receiver's ID (can be numeric or "CF" for consumers)
     * @param string $email The receiver's email
     * @param Address $address The receiver's address
     *
     * @throws Exception if any of the parameters are invalid
     */
    public function __construct(
        string $name,
        string $nit,
        string $email,
        Address $address
    ) {
        $this->setName($name);
        $this->setNit($nit);
        $this->setEmail($email);
        $this->setAddress($address);
    }

    /**
     * Sets the receiver's name.
     *
     * @param string $name The receiver's name
     *
     * @throws Exception if the name is empty
     */
    private function setName(string $name): void
    {
        if (empty($name)) {
            throw new Exception('Receiver name is required');
        }
        $this->name = $name;
    }

    /**
     * Sets the receiver's ID.
     *
     * @param string|int $nit The receiver's ID (can be numeric or "CF" for consumers)
     *
     * @throws Exception if the ID is invalid
     */
    private function setNit($nit): void
    {
        if (empty($nit)) {
            throw new Exception('Receiver NIT is required');
        }

        $this->receiverNit = (new Nit($nit));
    }

    /**
     * Sets the receiver's email.
     *
     * @param string $email The receiver's email
     *
     * @throws Exception if the email is invalid
     */
    private function setEmail(string $email): void
    {
        if (empty($email)) {
            throw new Exception('Receiver email is required');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Receiver email is not valid');
        }
        $this->email = $email;
    }

    /**
     * Sets the receiver's address.
     *
     * @param Address $address The receiver's address
     *
     * @throws Exception if the address is invalid
     */
    private function setAddress(Address $address): void
    {
        if (empty($address)) {
            throw new Exception('Receiver address is required');
        }
        $this->address = $address;
    }
} // END class Receiver
