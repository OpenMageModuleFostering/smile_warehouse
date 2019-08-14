<?php
/**
 * Product Low Stock Report Collection
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Model_Mysql4_Reports_Product_Lowstock_Collection
    extends Mage_Reports_Model_Mysql4_Product_Lowstock_Collection
{
    /**
     * Join catalog inventory stock item table for further stock_item values filters
     *
     * @return Mage_Reports_Model_Mysql4_Product_Collection
     */
    public function joinInventoryItem($fields = array())
    {
        if (!$this->_inventoryItemJoined) {

            if ($this->getStoreId()) {
                $stock = Mage::getModel('cataloginventory/stock')->loadByStore($this->getStoreId());
                if ($stock->getStockId()) {
                    $stockId = $stock->getStockId();
                } else {
                    $stockId = Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID;
                }

                $joinCondition = '`e`.`%s`=`%s`.`product_id` AND `%s`.stock_id=' . $stockId;

            } else {
                $this->getSelect()
                    ->join(array('cpw'=>$this->getTable('catalog/product_website')), '`e`.`entity_id`=`cpw`.`product_id`')
                    ->join(array('w'=>$this->getTable('core/website')), '`w`.`website_id`=`cpw`.`website_id`')
                    ->group($this->getEntity()->getEntityIdField());

                $joinCondition = '`e`.`%s`=`%s`.`product_id` AND `lowstock_inventory_item`.`stock_id`=`w`.`stock_id`';
            }

            $this->getSelect()->join(
                array($this->_getInventoryItemTableAlias() => $this->_getInventoryItemTable()),
                sprintf(
                    $joinCondition,
                    $this->getEntity()->getEntityIdField(),
                    $this->_getInventoryItemTableAlias(),
                    $this->_getInventoryItemTableAlias()
                ),
                array()
            );
            $this->_inventoryItemJoined = true;
        }

        parent::joinInventoryItem($fields);

        return $this;
    }

    /**
     * Add Use Manage Stock Condition to collection
     * Fix core bug related with incorrect field that used in SQL query
     *
     * @param int|null $storeId
     * @return Mage_Reports_Model_Mysql4_Product_Collection
     */
    public function useManageStockFilter($storeId = null)
    {
        $this->joinInventoryItem();
        $this->getSelect()->where(
            sprintf(
                'IF(%s,%d,%s)=1',
                $this->_getInventoryItemField('use_config_manage_stock'),
                (int) Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK, $storeId),
                $this->_getInventoryItemField('manage_stock')
            )
        );
        return $this;
    }
}