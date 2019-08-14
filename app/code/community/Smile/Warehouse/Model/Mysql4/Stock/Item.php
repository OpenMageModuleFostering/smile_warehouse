<?php
/**
 * Stock item resource model
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Model_Mysql4_Stock_Item extends Mage_CatalogInventory_Model_Mysql4_Stock_Item
{
    /**
     * Add join for catalog in stock field to product collection
     *
     * @param Mage_Catalog_Model_Entity_Product_Collection $productCollection product collection
     *
     * @return Mage_CatalogInventory_Model_Mysql4_Stock_Item
     */
    public function addCatalogInventoryToProductCollection($productCollection)
    {
        $stockId = Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID;
        $stock = Mage::getModel('cataloginventory/stock')->loadByStore($productCollection->getStoreId());
        if ($stock->getStockId()) {
            $stockId = $stock->getStockId();
        }
        $isStockManagedInConfig = (int) Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
        $inventoryTable = $this->getTable('cataloginventory/stock_item');
        $productCollection->joinTable(
            'cataloginventory/stock_item',
            'product_id=entity_id',
            array(
                'is_saleable' => new Zend_Db_Expr(
                    "(
                        IF(
                            IF(
                                $inventoryTable.use_config_manage_stock,
                                 $isStockManagedInConfig,
                                $inventoryTable.manage_stock
                            ),
                            $inventoryTable.is_in_stock,
                            1
                        )
                     )"
                ),
                'inventory_in_stock' => 'is_in_stock'
            ),
            '{{table}}.stock_id=' . $stockId, 'left'
        );
        return $this;
    }
}