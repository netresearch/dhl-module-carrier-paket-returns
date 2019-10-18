<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Block\Adminhtml\System\Config\Form\Field;

use Magento\Directory\Model\Config\Source\Country as CountrySource;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * The country names dropdown.
 *
 * @package Dhl\PaketReturns\Block
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    https://www.netresearch.de/
 */
class Country extends Select
{
    /**
     * @var CountrySource
     */
    private $source;

    /**
     * CountryNames constructor.
     *
     * @param Context $context
     * @param CountrySource $source
     * @param mixed[] $data
     */
    public function __construct(
        Context $context,
        CountrySource $source,
        array $data = []
    ) {
        $this->source = $source;

        parent::__construct($context, $data);
    }

    /**
     * This method is magically called by the Magento core
     * to set the select name attribute.
     *
     * @param string $value
     *
     * @return self
     */
    public function setInputName(string $value): self
    {
        return $this->setData('name', $value);
    }

    /**
     * This method is magically called by the Magento core
     * to set the select id attribute.
     *
     * @param string $value
     *
     * @return self
     */
    public function setInputId(string $value): self
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            foreach ($this->source->toOptionArray() as $data) {
                $this->addOption($data['value'], $this->escapeHtml($data['label']));
            }
        }

        return parent::_toHtml();
    }
}
