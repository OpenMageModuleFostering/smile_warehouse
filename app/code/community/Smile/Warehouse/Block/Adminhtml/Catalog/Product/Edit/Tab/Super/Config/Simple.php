<?php
/**
 * Quick simple product creation
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Block_Adminhtml_Catalog_Product_Edit_Tab_Super_Config_Simple extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Super_Config_Simple
{
    /**
     * Remove quantity field
     *
     * @return void
     */
    protected function _prepareForm()
    {
        parent::_prepareForm();
        if (!$this->_getProduct()->getId() || !$this->_getProduct()->getStoreId()) {
            $this->getForm()->getElement('simple_product')
                    ->removeField('simple_product_inventory_qty')
                    ->removeField('simple_product_inventory_is_in_stock');
        }
    }
}