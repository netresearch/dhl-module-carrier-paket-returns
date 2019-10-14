<?php
/**
 * See LICENSE.md for license details.
 */
declare(strict_types=1);

namespace Dhl\PaketReturns\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\BlockInterface;

/**
 * Array configuration field with receiver country and receiver country name.
 * The receiver country name dropdown is rendered per row using a separate form field.
 * @see Country
 *
 * @package Dhl\PaketReturns\Block
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @link    http://www.netresearch.de/
 */
class ReceiverId extends AbstractFieldArray
{
    /**
     * @var Country|BlockInterface
     */
    private $templateRenderer;

    /**
     * Create renderer used for displaying the receiver country select element.
     *
     * @return Country|BlockInterface
     *
     * @throws LocalizedException
     */
    private function getTemplateRenderer()
    {
        if (!$this->templateRenderer) {
            $this->templateRenderer = $this->getLayout()->createBlock(
                Country::class,
                '',
                [
                    'data' => [
                        'is_render_to_js_template' => true,
                    ]
                ]
            );
        }

        return $this->templateRenderer;
    }

    /**
     * Prepare existing row data object.
     *
     * @param DataObject $row
     *
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row)
    {
        $hash = $this->getTemplateRenderer()->calcOptionHash(
            $row->getData('iso')
        );

        $row->setData(
            'option_extra_attrs',
            [
                'option_' . $hash => 'selected="selected"',
            ]
        );
    }

    /**
     * Prepare to render.
     *
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn('iso', [
            'label'    => __('Country'),
            'renderer' => $this->getTemplateRenderer(),
        ]);

        $this->addColumn('receiver_id', [
            'label' => __('Receiver ID'),
            'style' => 'width: 80px',
            'class' => 'validate-length maximum-length-30',
        ]);

        // Hide "Add after" button
        $this->_addAfter = false;
    }

    /**
     * Append invisible inherit elements.
     *
     * On non-default scope, the combined field's individual inputs get enabled by the
     * FormElementDependenceController although "Use Default" is checked for the overall field.
     * The workaround is to add a hidden fake "Use Default" input to each of the fields contained in the field array.
     *
     * @see FormElementDependenceController.trackChange
     * @link https://github.com/magento/magento2/blob/2.2.0/lib/web/mage/adminhtml/form.js#L474
     *
     * @param string $columnName
     *
     * @return string
     * @throws \Exception
     */
    public function renderCellTemplate($columnName): string
    {
        $cellTemplate = parent::renderCellTemplate($columnName);

        if ($this->getData('element') && $this->getData('element')->getData('inherit')) {
            $htmlId = $this->_getCellInputElementId('<%- _id %>', $columnName);
            $inherit = '<input type="hidden" id="' . $htmlId . '_inherit" checked="checked" disabled="disabled" />';
            $cellTemplate.= $inherit;
        }

        return $cellTemplate;
    }
}
