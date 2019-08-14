<?php
/**
 * Warehouse grid
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Block_Adminhtml_Warehouse_Grid extends Mage_Adminhtml_Block_Widget_Grid
{
    /**
     * Set defaults
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('warehouseGrid');
        $this->setDefaultSort('stock_id');
        $this->setDefaultDir('desc');
        $this->setSaveParametersInSession(true);
    }

    /**
     * Instantiate and prepare collection
     *
     * @return Smile_Warehouse_Block_Adminhtml_Warehouse_Collection
     */
    protected function _prepareCollection()
    {
        $collection = Mage::getModel('cataloginventory/stock')->getCollection()->addNonDefaultWarehouseCondition();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Define grid columns
     *
     * @return Smile_Warehouse_Block_Adminhtml_Warehouse_Collection
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'stock_id',
            array(
                'header'=> Mage::helper('smile_warehouse')->__('ID'),
                'width' => 1,
                'type'  => 'number',
                'index' => 'stock_id',
            )
        );

        $this->addColumn(
            'code',
            array(
                'header' => Mage::helper('smile_warehouse')->__('Code'),
                'type'   => 'text',
                'width'  => '100px',
                'index'  => 'stock_code',
            )
        );

        $this->addColumn(
            'name',
            array(
                'header' => Mage::helper('smile_warehouse')->__('Name'),
                'type'   => 'text',
                'index'  => 'stock_name',
            )
        );

        return parent::_prepareColumns();
    }

    /**
     * Prepare mass action options for this grid
     *
     * @return Smile_Warehouse_Block_Adminhtml_Warehouse_Collection
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('stock_id');
        $this->getMassactionBlock()->setFormFieldName('warehouse');

        $this->getMassactionBlock()->addItem(
            'delete',
            array(
                'label'    => Mage::helper('smile_warehouse')->__('Delete'),
                'url'      => $this->getUrl('*/*/massDelete'),
                'confirm'  => Mage::helper('smile_warehouse')->__('Are you sure you want to delete these warehouses?')
            )
        );

        return $this;
    }

    /**
     * Grid row URL getter
     *
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getStockId()));
    }
}
