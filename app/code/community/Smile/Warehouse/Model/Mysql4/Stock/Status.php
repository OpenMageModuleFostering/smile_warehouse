<?php
/**
 * Stock status per website resource model
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Model_Mysql4_Stock_Status extends Mage_CatalogInventory_Model_Mysql4_Stock_Status
{
    /**
     * Add stock status limitation to catalog product price index select object
     *
     * @param Varien_Db_Select    $select       select object
     * @param string|Zend_Db_Expr $entityField  entity field
     * @param string|Zend_Db_Expr $websiteField website field
     *
     * @return Mage_CatalogInventory_Model_Mysql4_Stock_Status
     */
    public function prepareCatalogProductIndexSelect(Varien_Db_Select $select, $entityField, $websiteField)
    {
        $stockIdField = '';
        foreach ($select->getPart(Zend_Db_Select::FROM) as $alias => $from) {
            if ($from['tableName'] == $this->getTable('core/website')) {
                $stockIdField = $alias . '.stock_id';
                break;
            }
        }
        if (!$stockIdField) {
            $select->join(
                array('ciss_cw' => $this->getTable('core/website')),
                $websiteField  . '=ciss_cw.website_id',
                array()
            );
            $stockIdField = 'ciss_cw.stock_id';
        }
        $select->join(
            array('ciss' => $this->getMainTable()),
            "ciss.product_id = {$entityField} AND ciss.website_id = {$websiteField} AND ciss.stock_id = {$stockIdField}",
            array()
        );
        $select->where('ciss.stock_status=?', Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK);

        return $this;
    }

    /**
     * Add only is in stock products filter to product collection
     *
     * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection $collection product collection
     *
     * @return Mage_CatalogInventory_Model_Stock_Status
     */
    public function addIsInStockFilterToCollection($collection)
    {
        $websiteId = Mage::app()->getStore($collection->getStoreId())->getWebsiteId();

        //load stock
        $stock = Mage::getModel('cataloginventory/stock')->loadByStore($collection->getStoreId());

        $collection->getSelect()
            ->join(
                array('stock_status_index' => $this->getMainTable()),
                'e.entity_id = stock_status_index.product_id AND stock_status_index.website_id = '.$websiteId.' AND stock_status_index.stock_id = '.$stock->getId(),
                array()
            )
            ->where('stock_status_index.stock_status=?', Mage_CatalogInventory_Model_Stock_Status::STATUS_IN_STOCK);

        return $this;
    }
}