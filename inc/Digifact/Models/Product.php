<?php

namespace Digifact\Models;

use Exception;

/**
 * Class Product
 *
 * Represents a product with its details and associated taxes.
 */
class Product
{
    // constants for asset or service
    const TYPE_ASSET = 'B';
    const TYPE_SERVICE = 'S';

    /** @var float Quantity of the product */
    public $quantity;

    /** @var string Unit of measurement */
    public $unitOfMeasurement;

    /** @var string Description of the product */
    public $description;

    /** @var float Unit price of the product */
    public $unitPrice;

    /** @var float Total price of the product */
    public $price;

    /** @var float Discount applied to the product */
    public $discount;

    /** @var string Whether the item is a asset or service ('B' for assets, 'S' for services) */
    public $assetOrService;

    /** @var array Taxes applied to the product */
    public $taxes;

    /**
     * Product constructor.
     *
     * @param float $quantity Quantity of the product
     * @param string $unitOfMeasurement Unit of measurement
     * @param string $description Description of the product
     * @param float $unitPrice Unit price of the product
     * @param float $discount Discount applied to the product
     * @param string $assetOrService Whether the item is a asset or service ('B' for goods, 'S' for services)
     * @param array $taxes Taxes applied to the product
     *
     * @throws Exception if any of the parameters are invalid
     */
    public function __construct(
        float $quantity,
        string $unitOfMeasurement,
        string $description,
        float $unitPrice,
        float $discount,
        string $assetOrService = self::TYPE_SERVICE,
        array $taxes = []
    ) {
        $this->setQuantity($quantity);
        $this->setUnitOfMeasurement($unitOfMeasurement);
        $this->setDescription($description);
        $this->setUnitPrice($unitPrice);
        $this->setDiscount($discount);
        $this->setAssetOrService($assetOrService);
        $this->setTaxes($taxes);

        $this->calculatePrice();
    }

    /**
     * Set the quantity of the product.
     *
     * @param float $quantity
     * @throws \Exception if the quantity is invalid
     */
    private function setQuantity(float $quantity): void
    {
        if ($quantity > 0) {
            $this->quantity = $quantity;
        } else {
            throw new Exception('Quantity is required');
        }
    }

    /**
     * Set the unit of measurement.
     *
     * @param string $unitOfMeasurement
     * @throws Exception if the unit of measurement is invalid
     */
    private function setUnitOfMeasurement(string $unitOfMeasurement): void
    {
        if (!empty($unitOfMeasurement)) {
            $this->unitOfMeasurement = $unitOfMeasurement;
        } else {
            throw new Exception('Unit of measurement is required');
        }
    }

    /**
     * Set the description of the product.
     *
     * @param string $description
     * @throws Exception if the description is invalid
     */
    private function setDescription(string $description): void
    {
        if (!empty($description)) {
            $this->description = $description;
        } else {
            throw new Exception('Description is required');
        }
    }

    /**
     * Set the unit price of the product.
     *
     * @param float $unitPrice
     * @throws Exception if the unit price is invalid
     */
    private function setUnitPrice(float $unitPrice): void
    {
        if ($unitPrice >= 0) {
            $this->unitPrice = round($unitPrice, 2);
        } else {
            throw new Exception('Unit price is required');
        }
    }

    /**
     * Set the discount applied to the product.
     *
     * @param float $discount
     * @throws Exception if the discount is invalid
     */
    private function setDiscount(float $discount): void
    {
        if ($discount >= 0) {
            $this->discount = round($discount, 2);
        } else {
            throw new Exception('Discount is required');
        }
    }

    /**
     * Set whether the item is a asset or service.
     *
     * @param string $assetOrService
     * @throws Exception if the asset or service type is invalid
     */
    private function setAssetOrService(string $assetOrService): void
    {
        if (in_array($assetOrService, ['B', 'S'])) {
            $this->assetOrService = $assetOrService;
        } else {
            throw new Exception('Asset or service must be "B" or "S"');
        }
    }

    /**
     * Set the taxes applied to the product.
     *
     * @param array $taxes
     * @throws \Exception if the taxes are invalid
     */
    private function setTaxes(array $taxes): void
    {
        if (is_array($taxes)) {
            $this->taxes = $taxes;
        } else {
            throw new Exception('Taxes must be an array');
        }
    }

    /**
     * Calculate the total price of the product after applying the discount.
     */
    private function calculatePrice(): void
    {
        $price = ($this->unitPrice * $this->quantity) - $this->discount;
        $this->price = round($price, 2);
    }
} // END class Product
