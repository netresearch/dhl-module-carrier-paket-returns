<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace Dhl\PaketReturns\Block\Adminhtml\System\Config\Form\Field;

use Dhl\PaketReturns\Model\Adminhtml\System\Config\Source\Procedure;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * The procedures dropdown.
 */
class Procedures extends Select
{
    /**
     * @var Procedure
     */
    private $source;

    public function __construct(
        Context $context,
        Procedure $source,
        array $data = []
    ) {
        $this->source = $source;

        parent::__construct($context, $data);
    }

    /**
     * @param string $value
     *
     * @return self
     */
    public function setInputName(string $value): self
    {
        return $this->setData('name', $value);
    }

    /**
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
            $this->addOption('0', __('Select Procedure'));

            foreach ($this->source->toOptionArray() as $data) {
                $this->addOption($data['value'], $this->escapeHtml($data['label']));
            }
        }

        return parent::_toHtml();
    }
}
