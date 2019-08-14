<?php
/**
 * RSS block
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Block_Rss_Catalog_NotifyStock extends Mage_Rss_Block_Catalog_NotifyStock
{
    /**
     * Render rss xml
     *
     * @return string
     */
    protected function _toHtml()
    {
        $newurl = Mage::getUrl('rss/catalog/notifystock');
        $title = Mage::helper('rss')->__('Low Stock Products');

        $rssObj = Mage::getModel('rss/rss');
        $data = array(
            'title'       => $title,
            'description' => $title,
            'link'        => $newurl,
            'charset'     => 'UTF-8',
        );
        $rssObj->_addHeader($data);

        $_configManageStock = (int)Mage::getStoreConfigFlag(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_MANAGE_STOCK);
        $stockItemWhere = "({{table}}.low_stock_date is not null) "
            . " and ({{table}}.low_stock_date>'0000-00-00') "
            . " and IF({{table}}.use_config_manage_stock=1," . $_configManageStock . ",{{table}}.manage_stock) = 1"
            . " and {{table}}.stock_id <> " . Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID;

        $product = Mage::getModel('catalog/product');
        $collection = $product->getCollection()
            ->addAttributeToSelect('name', true)
            ->addAttributeToSelect('name', true)
            ->joinTable(
                'cataloginventory/stock_item',
                'product_id=entity_id',
                array(
                    'qty'=>'qty',
                    'notify_stock_qty' => 'notify_stock_qty',
                    'use_config' => 'use_config_notify_stock_qty',
                    'low_stock_date' => 'low_stock_date',
                    'stock_id' => 'stock_id'
                ),
                $stockItemWhere,
                'inner'
            )
            ->joinTable('cataloginventory/stock', 'stock_id=stock_id', array('stock_code'=>'stock_code'), null, 'inner')
            ->setOrder('low_stock_date');

        $_globalNotifyStockQty = (float) Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_NOTIFY_STOCK_QTY);

        Mage::dispatchEvent('rss_catalog_notify_stock_collection_select', array('collection' => $collection));

        /*
         * Using resource iterator to load the data one by one instead of loading all at the same time.
         * Loading all data at the same time can cause the big memory allocation.
         */
        Mage::getSingleton('core/resource_iterator')
            ->walk(
                $collection->getSelect(),
                array(array($this, 'addNotifyItemXmlCallback')),
                array('rssObj' => $rssObj, 'product' => $product, 'globalQty' => $_globalNotifyStockQty)
            );

        return $rssObj->createRssXml();
    }

    /**
     * Create xml-node with item info
     *
     * @param array $args arguments
     *
     * @return void
     */
    public function addNotifyItemXmlCallback($args)
    {
        $product = $args['product'];
        $product->setData($args['row']);
        $url = Mage::helper('adminhtml')->getUrl(
            'adminhtml/catalog_product/edit/',
            array('id'=>$product->getId(),'_secure' => true,'_nosecret' => true)
        );
        $description = Mage::helper('rss')->__(
            '%s has reached a quantity of %s in "%s" warehouse',
            $product->getName(),
            (1 * $product->getQty()),
            Mage::helper('rss')->escapeHtml($product->getStockCode())
        );
        $rssObj = $args['rssObj'];
        $data = array(
            'title'       => $product->getName(),
            'link'        => $url,
            'description' => $description,
        );
        $rssObj->_addEntry($data);
    }
}