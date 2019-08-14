<?php
/**
 * Stock resource model
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Model_Mysql4_Stock extends Mage_CatalogInventory_Model_Mysql4_Stock
{
    /**
     * Retrieve variable data by code
     *
     * @param string $code code
     *
     * @return array
     */
    public function getStockByCode($code)
    {
        $select = $this->_getReadAdapter()->select()
            ->from($this->getMainTable())
            ->where($this->getMainTable().'.stock_code = ?', $code);

        return $this->_getReadAdapter()->fetchRow($select);
    }

    /**
     * Load warehouse website id
     *
     * @param Smile_Warehouse_Model_Stock $object    object
     * @param int                         $websiteId website id
     *
     * @return void
     */
    public function loadByWebsiteId($object, $websiteId)
    {
        if (
            $data = $this->getReadConnection()->fetchRow(
                $this->getReadConnection()->select()
                ->from(array('site' => $this->getTable('core/website')), "website_id")
                ->join(array('stock' => $this->getMainTable()), "site.stock_id = stock.stock_id", array("stock.*"))
                ->where('website_id = ?', $websiteId)
                ->limit(1)
            )
        ) {
            $object->addData($data);
        }
    }

    /**
     * Get websites ids that asiigned to stock
     *
     * @param int $stockId stock id
     *
     * @return array
     */
    public function getAssignedWebsites($stockId)
    {
        return $this->getReadConnection()->fetchCol(
            $this->getReadConnection()->select()
            ->from(array('site' => $this->getTable('core/website')), "website_id")
            ->where('stock_id = ?', $stockId)
        );
    }

    /**
     * Set items out of stock basing on their quantities and config settings
     *
     */
    public function updateSetOutOfStock()
    {
        $this->_initConfig();
        $this->_getWriteAdapter()->update(
            $this->getTable('cataloginventory/stock_item'),
            array('is_in_stock' => 0, 'stock_status_changed_automatically' => 1),
            sprintf(
                'is_in_stock = 1
                AND (use_config_manage_stock = 1 AND 1 = %d OR use_config_manage_stock = 0 AND manage_stock = 1)
                AND (use_config_backorders = 1 AND %d = %d OR use_config_backorders = 0 AND backorders = %d)
                AND (use_config_min_qty = 1 AND qty <= %d OR use_config_min_qty = 0 AND qty <= min_qty)
                AND product_id IN (SELECT entity_id FROM %s WHERE type_id IN (%s))',
                $this->_isConfigManageStock,
                Mage_CatalogInventory_Model_Stock::BACKORDERS_NO, $this->_isConfigBackorders, Mage_CatalogInventory_Model_Stock::BACKORDERS_NO,
                $this->_configMinQty,
                $this->getTable('catalog/product'), $this->_getWriteAdapter()->quote($this->_configTypeIds)
            )
        );
    }

    /**
     * Set items in stock basing on their quantities and config settings
     *
     * @return void
     */
    public function updateSetInStock()
    {
        $this->_initConfig();
        $this->_getWriteAdapter()->update(
            $this->getTable('cataloginventory/stock_item'),
            array('is_in_stock' => 1),
            sprintf(
                'is_in_stock = 0
                AND stock_status_changed_automatically = 1
                AND (use_config_manage_stock = 1 AND 1 = %d OR use_config_manage_stock = 0 AND manage_stock = 1)
                AND (use_config_min_qty = 1 AND qty > %d OR use_config_min_qty = 0 AND qty > min_qty)
                AND product_id IN (SELECT entity_id FROM %s WHERE type_id IN (%s))',
                $this->_isConfigManageStock,
                $this->_configMinQty,
                $this->getTable('catalog/product'), $this->_getWriteAdapter()->quote($this->_configTypeIds)
            )
        );
    }

    /**
     * Update items low stock date basing on their quantities and config settings
     *
     * @return void
     */
    public function updateLowStockDate()
    {
        $nowUTC = Mage::app()->getLocale()->date(null, null, null, false)->toString(Varien_Date::DATETIME_INTERNAL_FORMAT);
        $this->_initConfig();
        $this->_getWriteAdapter()->update(
            $this->getTable('cataloginventory/stock_item'),
            array('low_stock_date' => new Zend_Db_Expr(
                sprintf(
                    'CASE
                    WHEN (use_config_notify_stock_qty = 1 AND qty < %d) OR (use_config_notify_stock_qty = 0 AND qty < notify_stock_qty)
                    THEN %s ELSE NULL
                    END',
                    $this->_configNotifyStockQty,
                    $this->_getWriteAdapter()->quote($nowUTC)
                )
            )),
            sprintf(
                '(use_config_manage_stock = 1 AND 1 = %d OR use_config_manage_stock = 0 AND manage_stock = 1) AND product_id IN (SELECT entity_id FROM %s WHERE type_id IN (%s))',
                $this->_isConfigManageStock,
                $this->getTable('catalog/product'), $this->_getWriteAdapter()->quote($this->_configTypeIds)
            )
        );
    }

    /**
     * add join to select only in stock products
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Link_Product_Collection $collection product collection
     *
     * @return Mage_CatalogInventory_Model_Mysql4_Stock
     */
    public function setInStockFilterToCollection($collection)
    {
        $manageStock = Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
        $cond = array(
            '{{table}}.use_config_manage_stock = 0 AND {{table}}.manage_stock=1 AND {{table}}.is_in_stock=1',
            '{{table}}.use_config_manage_stock = 0 AND {{table}}.manage_stock=0',
        );

        if ($manageStock) {
            $cond[] = '{{table}}.use_config_manage_stock = 1 AND {{table}}.is_in_stock=1';
        } else {
            $cond[] = '{{table}}.use_config_manage_stock = 1';
        }

        //load stock
        $stock = Mage::getModel('cataloginventory/stock')->loadByStore($collection->getStoreId());

        $collection->joinField(
            'inventory_in_stock',
            'cataloginventory/stock_item',
            'is_in_stock',
            'product_id=entity_id',
            '(('.join(') OR (', $cond) . ')) AND {{table}}.stock_id=' . $stock->getId()
        );
        return $this;
    }
}
