<?php
/**
 * Warehouse edit page
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Block_Adminhtml_Warehouse_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_warehouse';
        $this->_blockGroup = 'smile_warehouse';
        $this->_mode = 'edit';

        parent::__construct();
    }

    /**
     * Get warehouse model
     *
     * @return Smile_Warehouse_Model_Stock
     */
    public function getWarehouse()
    {
        return Mage::registry('current_warehouse');
    }

    /**
     * Get header text
     *
     * @return string
     */
    public function  getHeaderText()
    {
        return $this->getWarehouse()->getStockId()
                ? $this->htmlEscape($this->getWarehouse()->getStockName())
                : Mage::helper('smile_warehouse')->__('New Warehouse');
    }

    /**
     * Get form action URL
     *
     * @return string
     */
    public function getFormActionUrl()
    {
        return $this->getUrl('*/*/save');
    }
}