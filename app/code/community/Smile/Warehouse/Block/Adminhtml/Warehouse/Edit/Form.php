<?php
/**
 * Warehouse edit form
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Block_Adminhtml_Warehouse_Edit_Form extends Mage_Adminhtml_Block_Widget_Form
{
    /**
     * Prepare form
     *
     * @return Smile_Warehouse_Block_Adminhtml_Warehouse_Edit_Form
     */
    protected function _prepareForm()
    {
        $form = new Varien_Data_Form(array('id' => 'edit_form', 'action' => $this->getData('action'), 'method' => 'post'));

        $warehouse = Mage::registry('current_warehouse');

        if ($warehouse->getStockId()) {
            $form->addField('stock_id', 'hidden', array('name' => 'id', 'value'=> $warehouse->getStockId()));
        }

        $fieldset = $form->addFieldset('base_fieldset', array('legend' => Mage::helper('smile_warehouse')->__('Warehouse')));

        $fieldset->addField(
            'stock_code',
            'text',
            array(
                'name'      =>'stock_code',
                'label'     => Mage::helper('smile_warehouse')->__('Warehouse Code'),
                'required'  => true,
                'value'     => $warehouse->getStockCode()
            )
        );

        $fieldset->addField(
            'stock_name',
            'text',
            array(
                'name'      =>'stock_name',
                'label'     => Mage::helper('smile_warehouse')->__('Warehouse Name'),
                'required'  => true,
                'value'     => $warehouse->getStockName()
            )
        );

        $fieldset->addField(
            'form_key',
            'hidden',
            array(
                'name'  => 'form_key',
                'value' => Mage::getSingleton('core/session')->getFormKey(),
            )
        );

        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}