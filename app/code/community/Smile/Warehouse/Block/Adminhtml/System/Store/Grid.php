<?php
/**
 * Store grid
 * Add stock data
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Block_Adminhtml_System_Store_Grid extends Mage_Adminhtml_Block_System_Store_Grid
{
    /**
     * Join stock table to collection
     *
     * @return Smile_Warehouse_Block_Adminhtml_System_Store_Grid
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('core/website')
            ->getCollection()
            ->joinGroupAndStore();

        $collection->getSelect()->join(
            array('stock_table' => $collection->getTable('cataloginventory/stock')),
            'main_table.stock_id=stock_table.stock_id',
            array('stock_name')
        );

        $this->setCollection($collection);
        Mage_Adminhtml_Block_Widget_Grid::_prepareCollection();

        return $this;
    }

    /**
     * Prepare columns. Add stock name
     *
     * @return Smile_Warehouse_Block_Adminhtml_System_Store_Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumnAfter(
            'stock_name',
            array(
                'header'        => Mage::helper('smile_warehouse')->__('Warehouse'),
                'index'         => 'stock_name',
                'filter_index'  => 'stock_table.stock_name'
            ),
            'store_title'
        );
        return parent::_prepareColumns();
    }
}