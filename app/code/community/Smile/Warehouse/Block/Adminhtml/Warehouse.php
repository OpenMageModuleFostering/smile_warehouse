<?php
/**
 * Warehouse manage page
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Block_Adminhtml_Warehouse extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    /**
     * Initialize warehouses manage page
     *
     * @return void
     */
    public function __construct()
    {
        $this->_controller = 'adminhtml_warehouse';
        $this->_blockGroup = 'smile_warehouse';
        $this->_headerText = Mage::helper('smile_warehouse')->__('Manage Warehouses');
        $this->_addButtonLabel = Mage::helper('smile_warehouse')->__('Add New Warehouse');
        parent::__construct();
    }
}
