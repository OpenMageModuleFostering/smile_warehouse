<?php
/**
 * Warehouse observer
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Model_Observer extends Mage_CatalogInventory_Model_Observer
{
    /**
     * Add warehouses dropdown on website create
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function addWarehouseField(Varien_Event_Observer $observer)
    {
        if ($observer->getBlock() && $observer->getBlock() instanceof Mage_Adminhtml_Block_System_Store_Edit_Form) {
            if (Mage::registry('store_type') == 'website') {
                $observer->getBlock()
                    ->getForm()
                    ->getElement('website_fieldset')
                    ->addField(
                        'website_warehouse',
                        'select',
                        array(
                            'name' => 'website[stock_id]',
                            'label' => Mage::helper('smile_warehouse')->__('Warehouse'),
                            'value' => Mage::registry('store_data')->getStockId(),
                            'values' => Mage::getModel('cataloginventory/stock')->getCollection()->toOptionArray(),
                            'required' => true,
                            'disabled' => Mage::registry('store_data')->isReadOnly()
                        ),
                        'website_code'
                    );
            }
        }
        return $this;
    }

    /**
     * Saving product inventory data. Product qty calculated dynamically.
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function saveInventoryData($observer)
    {
        $product = $observer->getEvent()->getProduct();

        if (Mage::helper('smile_warehouse')->canUpdateInventory($product->getStockItem())) {
            parent::saveInventoryData($observer);
            $this->_updateStockItems($product);
        } else {
            if (!$product->getIsMassupdate()) {
                Mage::getSingleton('adminhtml/session')->addError(
                    Mage::helper('smile_warehouse')->__('You are not allowed to change inventory data.')
                );
            }
        }

        return $this;
    }

    /**
     * Update stock items
     *
     * @param Mage_Catalog_Model_Product $product product
     *
     * @return void
     */
    protected function _updateStockItems($product)
    {
        $websiteCollection = Mage::getModel('core/website')->getCollection()->addIdFilter($product->getWebsiteIds());
        $stockIds = array(Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID);
        foreach ($websiteCollection as $website) {
            if (!in_array($website->getStockId(), $stockIds)) {
                array_push($stockIds, $website->getStockId());
            }
        }

        foreach ($stockIds as $stockId) {
            if (!Mage::helper('smile_warehouse')->isStockItemExists($product, $stockId)) {
                $this->_createStockItem($product, $stockId);
            }
        }
    }

    /**
     * Create stock item for product by stock
     *
     * @param Mage_Catalog_Model_Product $product product
     * @param int                        $stockId stock id
     *
     * @return void
     */
    protected function _createStockItem($product, $stockId)
    {
        //cover duplicate functionality
        if ($product->getIsDuplicate()) {
            $sourceStock = Mage::getModel('cataloginventory/stock_item')
                ->setStockId($stockId)
                ->loadByProduct($product->getOriginalId());
        }

        if (isset($sourceStock) && $sourceStock->getItemId()) {
            $item = clone $sourceStock;
            $item->unsItemId();
        } else {
            //create new stock item
            $item = Mage::getModel('cataloginventory/stock_item')
                ->setManageStock($this->_getStockItemConfig('manage_stock'))
                ->setUseConfigManageStock(1)
                ->setMinQty($this->_getStockItemConfig('min_qty'))
                ->setUseConfigMinQty(1)
                ->setMinSaleQty($this->_getStockItemConfig('min_sale_qty'))
                ->setUseConfigMinSaleQty(1)
                ->setMaxSaleQty($this->_getStockItemConfig('max_sale_qty'))
                ->setUseConfigMaxSaleQty(1)
                ->setIsQtyDecimal($this->_getStockItemConfig('is_qty_decimal'))
                ->setBackorders($this->_getStockItemConfig('backorders'))
                ->setUseConfigBackorders(1)
                ->setEnableQtyIncrements($this->_getStockItemConfig('enable_qty_increments'))
                ->setUseConfigEnableQtyIncrements(1);
        }

        //set default values
        $item->setStoreLocation('')
            ->setStockId($stockId)
            ->setNotifyStockQty(1)
            ->setQty(0)
            ->setProduct($product)
            ->setProductId($product->getId());

        $item->save();
    }

    /**
     * Get defualt values related to stock item from config
     *
     * @param string $field field
     *
     * @return mixed
     */
    protected function _getStockItemConfig($field)
    {
        return Mage::getStoreConfig(Mage_CatalogInventory_Model_Stock_Item::XML_PATH_ITEM . $field);
    }

    /**
     * Copy product inventory data (used for product duplicate functionality)
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function copyInventoryData($observer)
    {
        // Disable native copy inventory functionality, because it's clear default stock item information
        return $this;
    }

    /**
     * Manage stock items when products websites are changed
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function updateProductsWebsites(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getEvent()->getProductIds();

        foreach ($productIds as $productId) {
            $product = Mage::getModel('catalog/product')->load($productId);
            $this->_updateStockItems($product);
        }
    }

    /**
     * Add information about producs stock status to collection
     * Used in for product collection after load
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function addStockStatusToCollection($observer)
    {
        $productCollection = $observer->getEvent()->getCollection();
        if ($productCollection->hasFlag('require_stock_items')) {
            Mage::getModel('cataloginventory/stock')->addItemsToProducts($productCollection);
        } else {
            $stockId = null;
            $stock = Mage::getModel('cataloginventory/stock')->loadByStore($productCollection->getStoreId());
            if ($stock->getStockId()) {
                $stockId = $stock->getStockId();
            }

            $websiteId = Mage::app()->getStore($productCollection->getStoreId())->getWebsiteId();

            Mage::getModel('cataloginventory/stock_status')->addStockStatusToProducts($productCollection, $websiteId, $stockId);
        }
        return $this;
    }


    /**
     * Subtract quote items qtys from stock items related with quote items products.
     *
     * Used before order placing to make order save/place transaction smaller
     * Also called after every successful order placement to ensure subtraction of inventory
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function subtractQuoteInventory(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();

        // Maybe we've already processed this quote in some event during order placement
        // e.g. call in event 'sales_model_service_quote_submit_before' and later in 'checkout_submit_all_after'
        if ($quote->getInventoryProcessed()) {
            return;
        }
        $items = $this->_getProductsQty($quote->getAllItems());

        /**
         * Remember items
         */
        $this->_itemsForReindex = Mage::getSingleton('cataloginventory/stock')
                ->setStoreId($quote->getStoreId())
                ->registerProductsSale($items);

        $quote->setInventoryProcessed(true);
        return $this;
    }

    /**
     * Revert quote items inventory data (cover not success order place case)
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return void
     */
    public function revertQuoteInventory($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        $items = $this->_getProductsQty($quote->getAllItems());
        Mage::getSingleton('cataloginventory/stock')->setStoreId($quote->getStoreId())->revertProductsSale($items);

        // Clear flag, so if order placement retried again with success - it will be processed
        $quote->setInventoryProcessed(false);
    }

    /**
     * Return creditmemo items qty to stock
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return void
     */
    public function refundOrderInventory($observer)
    {
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $items = array();
        foreach ($creditmemo->getAllItems() as $item) {
            $return = false;
            if ($item->hasBackToStock()) {
                if ($item->getBackToStock() && $item->getQty()) {
                    $return = true;
                }
            } elseif (Mage::helper('cataloginventory')->isAutoReturnEnabled()) {
                $return = true;
            }
            if ($return) {
                if (isset($items[$item->getProductId()])) {
                    $items[$item->getProductId()]['qty'] += $item->getQty();
                } else {
                    $items[$item->getProductId()] = array(
                        'qty' => $item->getQty(),
                        'item'=> null,
                    );
                }
            }
        }
        Mage::getSingleton('cataloginventory/stock')->setStoreId($creditmemo->getStoreId())->revertProductsSale($items);
    }

    /**
     * Cancel order item
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function cancelOrderItem($observer)
    {
        $item = $observer->getEvent()->getItem();

        $children = $item->getChildrenItems();
        $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();

        if ($item->getId() && ($productId = $item->getProductId()) && empty($children) && $qty) {
            Mage::getSingleton('cataloginventory/stock')->setStoreId($item->getStoreId())->backItemQty($productId, $qty);
        }

        return $this;
    }

    /**
     * Manage products stocks when website stock is changed
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function reassignStock(Varien_Event_Observer $observer)
    {
        $website = $observer->getEvent()->getWebsite();

        if ($website->dataHasChangedFor('stock_id')) {
            $stockId = $website->getData('stock_id');

            $productsCollection = Mage::getModel('catalog/product')->getCollection()->addWebsiteFilter($website)
                    ->joinTable(
                        'cataloginventory/stock_item',
                        'product_id=entity_id',
                        array('stock_item_id' => 'item_id'),
                        '{{table}}.stock_id=' . $stockId,
                        'left'
                    );

            foreach ($productsCollection as $product) {
                if (!$product->getStockItemId()) {
                    $this->_createStockItem($product, $stockId);
                }
            }
        }

        return $this;
    }

    /**
     * Import product by stock code
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function importProductByStockCode(Varien_Event_Observer $observer)
    {
        $product = $observer->getEvent()->getProduct();

        if ($product->getIsMassupdate()) {
            $stockData = $product->getStockData();
            if (isset($stockData['stock_code'])) {
                if ($product->getStoreId() == Mage_Catalog_Model_Abstract::DEFAULT_STORE_ID) {
                    $stock = Mage::getModel('cataloginventory/stock')->loadByCode($stockData['stock_code']);
                    if (!$stock->getId()) {
                        $message = Mage::helper('catalog')->__(
                            'Skipping import row, unknown warehouse specified for product with sku %s.',
                            $product->getSku()
                        );
                        Mage::throwException($message);
                    } else {
                        if ($product->getStockItem() && $product->getStockItem()->getStockId() != $stock->getId()) {
                            $product->getStockItem()->setStockId($stock->getId());
                            $product->getStockItem()->loadByProduct($product);
                        }
                    }
                } else {
                    $stock = Mage::getModel('cataloginventory/stock')->loadByStore($product->getStoreId());
                    if ($stock->getStockCode() != $stockData['stock_code']) {
                        $message = Mage::helper('catalog')->__(
                            'Skipping import row, store and stock code mismatch for product with sku %s.',
                            $product->getSku()
                        );
                        Mage::throwException($message);
                    }
                }
            }

            $stockId = 0;
            if (!$product->getId()) {
                if (isset($stock) && $stock) {
                    $stockId = $stock->getId();
                } else {
                    $stockId = Mage::getModel('cataloginventory/stock')->loadByStore($product->getStoreId())->getStockId();
                }
            } else if ($product->getStockItem()) {
                $stockId = $product->getStockItem()->getStockId();
            }

            if (!Mage::helper('smile_warehouse')->canUpdateInventory($stockId)) {
                $message = Mage::helper('smile_warehouse')->__(
                    'Skip import row, stock item for product with sku "%s" is not allowed in your current permission scope.',
                    $product->getSku()
                );
                Mage::throwException($message);
            }
        }

        return $this;
    }

    /**
     * Set flag that product grid block loading
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function setProductGridFlag(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        /* @var $block Mage_Core_Block_Abstract */

        if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Grid) {
            Mage::register('product_grid_block_loading', true);
        }

        return $this;
    }

    /**
     * Remove flag that product grid block loading
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function unsetProductGridFlag(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        /* @var $block Mage_Core_Block_Abstract */

        if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Grid) {
            Mage::unregister('product_grid_block_loading');
        }

        return $this;
    }

    /**
     * Fix product collection stock items join on product grid
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function fixProductCollectionStockJoin(Varien_Event_Observer $observer)
    {
        if (Mage::registry('product_grid_block_loading')) {
            $collection = $observer->getEvent()->getCollection();
            /* @var $collection Mage_Catalog_Model_Resource_Eav_Mysql4_Product_Collection */

            if ($collection->getStoreId()) {
                $fromPart = $collection->getSelect()->getPart(Zend_Db_Select::FROM);
                foreach ($fromPart as &$from) {
                    if ($from['tableName'] == $collection->getTable('cataloginventory/stock_item') && isset($from['joinCondition'])) {
                        $from['joinCondition'] = str_replace(
                            'stock_id=1',
                            'stock_id=' . Mage::app()->getStore($collection->getStoreId())->getWebsite()->getStockId(),
                            $from['joinCondition']
                        );
                    }
                }
                $collection->getSelect()->setPart(Zend_Db_Select::FROM, $fromPart);
            }
        }

        return $this;
    }

    /**
     * Add all stocks qty column to product grid
     *
     * @param Varien_Event_Observer $observer observer
     *
     * @return Smile_Warehouse_Model_Observer
     */
    public function addAllStocksQtyColumn(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        /* @var $block Mage_Core_Block_Abstract */

        if ($block instanceof Mage_Adminhtml_Block_Catalog_Product_Grid
            && !$block->getCollection()->getStoreId()) {
            Mage::getModel('cataloginventory/stock')->addStocksQtyToCollection($block->getCollection());

            $block->addColumn(
                'qty',
                array(
                    'header'    => Mage::helper('catalog')->__('Qty'),
                    'width'     => '100px',
                    'index'     => 'qty',
                    'renderer'  => 'smile_warehouse/adminhtml_widget_grid_column_renderer_implode',
                    'separator' => '<br />',
                    'filter'    => false,
                    'sortable'  => false,
                )
            );
            $block->sortColumnsByOrder();
        }

        return $this;
    }
}