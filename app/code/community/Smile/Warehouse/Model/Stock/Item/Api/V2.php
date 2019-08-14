<?php
/**
 * Stock item - API V2 model
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Model_Stock_Item_Api_V2 extends Smile_Warehouse_Model_Stock_Item_Api
{
    /**
     * Update product warehouse
     *
     * @param string $productId product id
     * @param string $stockCode stock code
     * @param object $data      data
     *
     * @return bool
     */
    public function update($productId, $stockCode, $data)
    {
        $product = Mage::getModel('catalog/product');

        if ($newId = $product->getIdBySku($productId)) {
            $productId = $newId;
        }

        //load stock
        $stock = Mage::getModel('smile_warehouse/stock')->loadByCode($stockCode);

        if (!$stock->getStockId()) {
            $this->_fault('stock_not_exists');
        }

        $product->load($productId);

        if (!$product->getId()) {
            $this->_fault('not_exists');
        }

        //get store id which work with called stock
        $productStockWebsites = array_intersect($stock->getAssignedWebsites(), $product->getWebsiteIds());
        if (empty($productStockWebsites)) {
            $this->_fault('stock_item_not_exists');
        }
        $websiteId = array_shift($productStockWebsites);
        $websiteStores = Mage::app()->getWebsite($websiteId)->getStoreIds();
        $storeId = array_shift($websiteStores);

        if (is_null($storeId)) {
            $this->_fault('store_not_exists');
        }

        //reload product to reassign stock item by store
        $product->setStoreId($storeId)->load($productId);

        if (!$stockData = $product->getStockData()) {
            $stockData = array();
        }

        $warehouseStockFields = Mage::helper('smile_warehouse')->getWarehouseStockFields();

        foreach ($warehouseStockFields as $warehouseStockField) {
            if (isset($data->$warehouseStockField)) {
                $stockData[$warehouseStockField] = $data->$warehouseStockField;
            }
        }

        $product->setStockData($stockData);

        try {
            $product->save();
        } catch (Mage_Core_Exception $e) {
            $this->_fault('not_updated', $e->getMessage());
        } catch (Exception $e){
            $this->_fault('not_updated', $e->getMessage());
        }

        return true;
    }
}