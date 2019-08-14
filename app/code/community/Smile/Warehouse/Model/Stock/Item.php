<?php
/**
 * Stock item
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Model_Stock_Item extends Mage_CatalogInventory_Model_Stock_Item
{
    /**
     * Retrieve stock identifier
     *
     * @return int
     */
    public function getStockId()
    {
        $stockId = $this->getData('stock_id');
        if (is_null($stockId)) {
            $stockId = Mage::getModel('cataloginventory/stock')->loadByStore($this->getStoreId())->getStockId();
            $this->setData('stock_id', $stockId);
        }

        return $stockId;
    }

    /**
     * Adding stock data to product
     *
     * @param Mage_Catalog_Model_Product $product product
     *
     * @return Smile_Warehouse_Model_Stock_Item
     */
    public function assignProduct(Mage_Catalog_Model_Product $product)
    {
        $this->setStoreId($product->getStoreId());
        parent::assignProduct($product);

        return $this;
    }
}