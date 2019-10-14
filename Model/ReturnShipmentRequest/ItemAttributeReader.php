<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Model\ReturnShipmentRequest;

use Dhl\ShippingCore\Model\Attribute\Backend\ExportDescription;
use Dhl\ShippingCore\Model\Attribute\Backend\TariffNumber;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Model\Order\Item;

/**
 * Class ItemAttributeReader
 *
 * Read product attributes from order items.
 *
 * @package Dhl\PaketReturns\Model
 * @author  Christoph Aßmann <christoph.assmann@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class ItemAttributeReader
{
    /**
     * Obtain the actual product added to cart, i.e. the chosen configuration, and return value of given attribute.
     *
     * @param Item $orderItem
     * @param string $attributeCode
     * @return string
     */
    private function readAttribute(Item $orderItem, string $attributeCode): string
    {
        // load the product to read the attribute from. if configurable item is passed in, load from simple item.
        if ($orderItem->getProductType() === Configurable::TYPE_CODE) {
            $childItem = current($orderItem->getChildrenItems());
            $product = $childItem->getProduct();
        } else {
            $product = $orderItem->getProduct();
        }

        if (!$product) {
            return '';
        }

        if ($product->hasData($attributeCode)) {
            // attribute value found in simple
            return (string) $product->getData($attributeCode);
        }

        // as a last resort, fall back to the configurable (if exists) or return empty value
        return (string) ($orderItem->getProduct() ? $orderItem->getProduct()->getData($attributeCode) : '');
    }

    /**
     * Read HS code from product.
     *
     * @param Item $orderItem
     * @return string
     */
    public function getHsCode(Item $orderItem): string
    {
        return $this->readAttribute($orderItem, TariffNumber::CODE);
    }

    /**
     * Read export description from product.
     *
     * @param Item $orderItem
     * @return string
     */
    public function getExportDescription(Item $orderItem): string
    {
        return $this->readAttribute($orderItem, ExportDescription::CODE);
    }

    /**
     * Read country of manufacture from product.
     *
     * @param Item $orderItem
     * @return string
     */
    public function getCountryOfManufacture(Item $orderItem): string
    {
        return $this->readAttribute($orderItem, 'country_of_manufacture');
    }
}
