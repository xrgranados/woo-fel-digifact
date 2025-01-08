<?php

namespace Digifact;

use Exception;

/**
 * Class Tax
 *
 * Represents the taxes applied to an item or product.
 */
class Tax
{
    /** @var string Short name of the tax */
    public $shortName;

    /** @var int Taxable unit code */
    public $taxableUnitCode;

    /** @var float Taxable amount */
    public $taxableAmount;

    /** @var float Tax amount */
    public $taxAmount;

    /**
     * const Types of taxes accepted by SAT
     */
    private const TAX_TYPES = [
        'IVA',
        'PETROLEO',
        'TURISMO HOSPEDAJE',
        'TURISMO PASAJES',
        'TIMBRE DE PRENSA',
        'BOMBEROS',
        'TASA MUNICIPAL',
        'BEBIDAS ALCOHOLICAS',
        'TABACO',
        'CEMENTO',
        'BEBIDAS NO ALCOHOLICAS',
        'TARIFA PORTUARIA',
    ];

    /** Tax rate constant */
    private const TAX_RATE = 0.12;

    /**
     * Tax constructor.
     *
     * @param string $shortName Short name of the tax
     * @param int $taxableUnitCode Taxable unit code
     * @param float $totalAmount Total amount on which the tax is applied
     *
     * @throws Exception if any of the parameters are invalid
     */
    public function __construct(string $shortName, int $taxableUnitCode, float $totalAmount)
    {
        $this->setShortName($shortName);
        $this->setTaxableUnitCode($taxableUnitCode);
        $this->setTaxableAmount($totalAmount);
        $this->calculateTaxAmount();
    }

    /**
     * Set the short name of the tax.
     *
     * @param string $shortName
     * @throws Exception if the short name is invalid
     */
    private function setShortName(string $shortName): void
    {
        if (in_array($shortName, self::TAX_TYPES)) {
            $this->shortName = $shortName;
        } else {
            throw new Exception('Invalid tax short name');
        }
    }

    /**
     * Set the taxable unit code.
     *
     * @param int $taxableUnitCode
     * @throws Exception if the taxable unit code is invalid
     */
    private function setTaxableUnitCode(int $taxableUnitCode): void
    {
        if (is_numeric($taxableUnitCode)) {
            $this->taxableUnitCode = $taxableUnitCode;
        } else {
            throw new Exception('Taxable unit code is required');
        }
    }

    /**
     * Set the taxable amount.
     *
     * @param float $totalAmount
     * @throws Exception if the total amount is invalid
     */
    private function setTaxableAmount(float $totalAmount): void
    {
        if (is_numeric($totalAmount)) {
            $this->taxableAmount = round($totalAmount / (1 + self::TAX_RATE), 2);
        } else {
            throw new Exception('Total amount is required');
        }
    }

    /**
     * Calculate the tax amount based on the taxable amount.
     */
    private function calculateTaxAmount(): void
    {
        $taxAmount = $this->taxableAmount * self::TAX_RATE;
        $this->taxAmount = round($taxAmount, 2);
    }
} // END class Tax
