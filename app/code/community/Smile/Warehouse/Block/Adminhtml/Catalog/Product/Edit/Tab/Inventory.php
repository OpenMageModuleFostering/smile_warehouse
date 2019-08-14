<?php
/**
 * Product inventory data
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Block_Adminhtml_Catalog_Product_Edit_Tab_Inventory extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Inventory
{
    /**
     * Stock items collection
     *
     * @var Mage_CatalogInventory_Model_Mysql4_Stock_Item_Collection|array
     */
    protected $_stockItems;

    /**
     * Set custom template for the block
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTemplate('smile/warehouse/catalog/product/tab/inventory.phtml');
    }

    /**
     * Get stock qty info for all warehouses
     *
     * @return Mage_CatalogInventory_Model_Mysql4_Stock_Item_Collection|array
     */
    public function getStockItems()
    {
        if ($this->getProduct()->getId() && is_null($this->_stockItems)) {
            $stockCollection = Mage::getResourceModel('cataloginventory/stock_item_collection');
            $warehouseCollection = Mage::getModel('cataloginventory/stock')->getCollection();

            $stockCollection->addProductsFilter(array($this->getProduct()->getId()));

            $websiteIds = $this->getProduct()->getWebsiteIds();
            $stockIds = array();
            foreach ($websiteIds as $website) {
                if (!isset($stockIds[Mage::app()->getWebsite($website)->getStockId()])) {
                    $stockIds[Mage::app()->getWebsite($website)->getStockId()] = Mage::app()->getWebsite($website)->getStockId();
                }
            }
            if ($stockIds) {
                $stockCollection->addFieldToFilter('stock_id', array('in' => $stockIds));
            } else {
                $this->_stockItems = array();
                return $this->_stockItems;
            }

            $warehouses = array();
            foreach ($stockCollection as $item) {
                $warehouses[$item->getStockId()] = $item->getStockId();
            }

            if ($warehouses) {
                $warehouseCollection->addFieldToFilter('stock_id', array('in' => $warehouses));
            }

            foreach ($stockCollection as $item) {
                $item->setStockCode($warehouseCollection->getItemByColumnValue('stock_id', $item->getStockId())->getStockCode());
            }
            $this->_stockItems = $stockCollection;
        }

        return $this->_stockItems;
    }

    /**
     * Check is inventory tab available
     *
     * @return bool
     */
    public function isInventoryTabAvailable()
    {
        if ($storeId = $this->getProduct()->getStoreId()) {
            $websiteIds = $this->getProduct()->getWebsiteIds();
            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();
            if (!in_array($websiteId, $websiteIds)) {
                return false;
            }
        }

        return true;
    }
}