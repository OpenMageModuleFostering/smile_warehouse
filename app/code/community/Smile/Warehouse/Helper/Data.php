<?php
/**
 * Warehouse helper
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * List of warehouse stock fields
     *
     * @return array
     */
    public function getWarehouseStockFields()
    {
        return array('qty', 'stock_location', 'is_in_stock', 'notify_stock_qty', 'use_config_notify_stock_qty');
    }

    /**
     * Check if the warehouse stock edit allowed
     *
     * @return boolean
     */
    public function isStockAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('catalog/stock');
    }

    /**
     * Check, if current admin user allowed to change inventory data for the particular stock item
     *
     * @param Smile_Warehouse_Model_Stock_Item|int $stockItem stock item
     *
     * @return boolean
     */
    public function canUpdateInventory($stockItem)
    {
        $result = false;

        if ($stockItem instanceof Smile_Warehouse_Model_Stock_Item) {
            $stockId = $stockItem->getStockId();
        } else {
            $stockId = $stockItem;
        }

        $role = Mage::getSingleton('enterprise_admingws/role');
        if ($role->getIsAll()) {
            return true; // super admin is found
        }

        if ($this->isStockAllowed() && $stockId) {
            if ($stockId == Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID) {
                $result = true;
            } else {
                $assignedWebsites = Mage::getModel('smile_warehouse/stock')->load($stockId)->getAssignedWebsites();
                $websites = $role->getWebsiteIds();
                $diff = array_diff($assignedWebsites, $websites);
                if (empty($diff)) {
                    $result = true;
                }
            }
        } else if ($this->isStockAllowed() && !$stockId) {
            $result = true;
        }

        return $result;
    }

    /**
     * Check is stock item exists for product
     *
     * @param Mage_Catalog_Model_Product|int        $product product object or id
     * @param Mage_CatalogInventory_Model_Stock|int $stock   stock object or id
     *
     * @return bool
     */
    public function isStockItemExists($product, $stock)
    {
        if ($product instanceof Mage_Catalog_Model_Product) {
            $product = $product->getId();
        }

        if ($stock instanceof Mage_CatalogInventory_Model_Stock) {
            $stock = $stock->getStockId();
        }

        $stockItem = Mage::getModel('cataloginventory/stock_item')
            ->setStockId($stock)
            ->loadByProduct($product);

        return (bool)$stockItem->getItemId();
    }
}