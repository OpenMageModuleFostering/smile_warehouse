<?php
/**
 * Stock collection
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Model_Mysql4_Stock_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    /**
     * Init model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('smile_warehouse/stock');
    }

    /**
     * Remove default warehouse
     *
     * @return Smile_Warehouse_Model_Mysql4_Stock_Collection
     */
    public function addNonDefaultWarehouseCondition()
    {
        $this->getSelect()->where('stock_id != ?', Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID);
        return $this;
    }

    /**
     * Convert items array to array for select options
     *
     * @return  array
     */
    public function toOptionArray()
    {
        return $this->_toOptionHash('stock_id', 'stock_name');
    }
}