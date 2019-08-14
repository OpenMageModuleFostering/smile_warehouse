<?php
/**
 * Adminhtml low stock products report grid block
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Block_Adminhtml_Report_Product_Lowstock_Grid
    extends Mage_Adminhtml_Block_Report_Product_Lowstock_Grid
{
    /**
     * Prepare collection
     *
     * @return Smile_Warehouse_Block_Adminhtml_Report_Product_Lowstock_Grid
     */
    protected function _prepareCollection()
    {
        parent::_prepareCollection();
        $collection = $this->getCollection();
        if (!$collection->getStoreId()) {
            // Add websites to collection
            $collection->addWebsiteNamesToResult();

            // Add stocks qty to product collection
            Mage::getModel('cataloginventory/stock')->addStocksQtyToCollection($collection);
        }

        return $this;
    }

    /**
     * Add qty column
     *
     * @return Smile_Warehouse_Block_Adminhtml_Report_Product_Lowstock_Grid
     */
    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        $groupId = (int) $this->getRequest()->getParam('group', 0);
        $websiteId = (int) $this->getRequest()->getParam('website', 0);

        if (!$storeId && !$groupId && !$websiteId) {
            $this->addColumn(
                'qty',
                array(
                    'header'    => Mage::helper('reports')->__('Stock Qty'),
                    'width'     => '215px',
                    'sortable'  => false,
                    'filter'    => false,
                    'index'     =>'qty',
                    'renderer'  => 'smile_warehouse/adminhtml_widget_grid_column_renderer_implode',
                    'separator' => (!$this->_isExport) ? '<br/>' : '; ',
                )
            );
        }
        return $this;
    }
}