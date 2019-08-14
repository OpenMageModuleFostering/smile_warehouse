<?php
/**
 * Stock model
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Model_Stock extends Mage_CatalogInventory_Model_Stock
{

    /**
     * Standard model initialization
     *
     * @param string $resourceModel resource model
     *
     * @return void
     */
    protected function _init($resourceModel)
    {
        $this->_setResourceModel($resourceModel, 'smile_warehouse/stock_collection');
    }

    /**
     * Retrieve stock identifier
     *
     * @return int
     */
    public function getId()
    {
        $stockId = $this->getData('stock_id');
        if (is_null($stockId) && ($storeId = $this->getData('store_id'))) {
            $this->loadByStore($storeId);
            $stockId = $this->getStockId();
        }

        return $stockId;
    }

    /**
     * Add stock item objects to products
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $productCollection product collection
     *
     * @return Smile_Warehouse_Model_Stock
     */
    public function addItemsToProducts($productCollection)
    {
        $this->setStoreId($productCollection->getStoreId());
        return parent::addItemsToProducts($productCollection);
    }

    /**
     * Validate stock information
     *
     * @return Smile_Warehouse_Model_Stock
     */
    public function validate()
    {
        if ($this->getStockCode()) {
            $stock = $this->getResource()->getStockByCode($this->getStockCode());
            if (!empty($stock) && $stock['stock_id'] != $this->getStockId()) {
                Mage::throwException(Mage::helper('smile_warehouse')->__('Warehouse code must be unique.'));
            }
        }

        return $this;
    }

    /**
     * Get warehouse by store id
     *
     * @param mixed $store store
     *
     * @return Smile_Warehouse_Model_Stock
     */
    public function loadByStore($store)
    {
        $websiteId = Mage::app()->getStore($store)->getWebsiteId();
        $this->getResource()->loadByWebsiteId($this, $websiteId);

        return $this;
    }

    /**
     * Get assigned websites
     *
     * @return array
     */
    public function getAssignedWebsites()
    {
        return $this->getResource()->getAssignedWebsites($this->getStockId());
    }

    /**
     * Get back to stock (when order is canceled or whatever else)
     *
     * @param int     $productId product id
     * @param numeric $qty       quantity
     *
     * @return Mage_CatalogInventory_Model_Stock
     */
    public function backItemQty($productId, $qty)
    {
        $stockItem = Mage::getModel('cataloginventory/stock_item')
            ->setStockId($this->getId())
            ->loadByProduct($productId);

        if ($stockItem->getId() && Mage::helper('cataloginventory')->isQty($stockItem->getTypeId())) {
            $stockItem->addQty($qty);
            if ($stockItem->getCanBackInStock() && $stockItem->getQty() > $stockItem->getMinQty()) {
                $stockItem->setIsInStock(true)
                    ->setStockStatusChangedAutomaticallyFlag(true);
            }
            $stockItem->save();
        }
        return $this;
    }

    /**
     * Adds filtering for collection to return only in stock products
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection $collection product collection
     *
     * @return Mage_CatalogInventory_Model_Stock $this
     */
    public function addInStockFilterToCollection($collection)
    {
        if (!$this->getId()) {
            $this->loadByStore($collection->getStoreId());
        }

        parent::addInStockFilterToCollection();
        return $this;
    }

    /**
     * Get warehouse stock by stock code
     *
     * @param string $stockCode stock code
     *
     * @return Smile_Warehouse_Model_Stock
     */
    public function loadByCode($stockCode)
    {
        $data = $this->getResource()->getStockByCode($stockCode);
        if (!empty($data)) {
            $this->addData($data);
        }
        return $this;
    }

    /**
     * Add qty in all warehouses to each product in the collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $productCollection product collection
     *
     * @return Smile_Warehouse_Model_Stock
     */
    public function addStocksQtyToCollection($productCollection)
    {
        if ($productCollection) {
            $stockCollection = Mage::getResourceModel('cataloginventory/stock_item_collection');
            $warehouseCollection = Mage::getModel('cataloginventory/stock')->getCollection();

            $stockCollection->addProductsFilter($productCollection);

            $warehouses = array();
            foreach ($stockCollection as $item) {
                $warehouses[$item->getStockId()] = $item->getStockId();
            }

            if ($warehouses) {
                $warehouseCollection->addFieldToFilter('stock_id', array('in' => $warehouses));
            }

            $warehouseCodesAssoc = array();
            foreach ($warehouseCollection as $warehouse) {
                $warehouseCodesAssoc[$warehouse->getStockId()] = $warehouse->getStockCode();
            }

            $productWarehousesAssoc = array();
            foreach ($productCollection as $product) {
                foreach ($product->getWebsites() as $websiteId) {
                    $productWarehousesAssoc[$product->getId()][Mage::app()->getWebsite($websiteId)->getStockId()] = Mage::app()->getWebsite($websiteId)->getStockId();
                }
            }

            $productStocksQtyAssoc = array();
            foreach ($stockCollection as $stockItem) {
                if (isset($productWarehousesAssoc[$stockItem->getProductId()])
                    && isset($productWarehousesAssoc[$stockItem->getProductId()][$stockItem->getStockId()])) {
                    if (!isset($productStocksQtyAssoc[$stockItem->getProductId()])) {
                        $productStocksQtyAssoc[$stockItem->getProductId()] = array(
                            $stockItem->getStockId() => $warehouseCodesAssoc[$stockItem->getStockId()] . ': ' . floatval($stockItem->getQty())
                        );
                    } else {
                        $productStocksQtyAssoc[$stockItem->getProductId()][$stockItem->getStockId()] = $warehouseCodesAssoc[$stockItem->getStockId()] . ': ' . floatval($stockItem->getQty());
                    }
                }
            }

            foreach ($productCollection as $product) {
                if (isset($productStocksQtyAssoc[$product->getId()])) {
                    $product->setData('qty', $productStocksQtyAssoc[$product->getId()]);
                }
            }
        }

        return $this;
    }
}