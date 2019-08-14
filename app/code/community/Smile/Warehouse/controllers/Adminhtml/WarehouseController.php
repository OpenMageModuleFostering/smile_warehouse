<?php
/**
 * Warehouse controller
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Adminhtml_WarehouseController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Init actions
     *
     * @return Smile_Warehouse_Adminhtml_WarehouseController
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->loadLayout()
            ->_setActiveMenu('system/warehouse')
            ->_addBreadcrumb(Mage::helper('smile_warehouse')->__('System'), Mage::helper('smile_warehouse')->__('System'))
            ->_addBreadcrumb(Mage::helper('smile_warehouse')->__('Manage Warehouses'), Mage::helper('smile_warehouse')->__('Manage Warehouses'));

        return $this;
    }

    /**
     * Warehouses list
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_title($this->__('System'))
             ->_title($this->__('Warehouses'));

        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('smile_warehouse/adminhtml_warehouse'))
            ->renderLayout();
    }

    /**
     * Render grid
     *
     * @return void
     */
    public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Init warehouse
     *
     * @return Smile_Warehouse_Model_Stock
     */
    protected function _initWarehouse($idFieldName = 'id')
    {
        $id = $this->getRequest()->getParam($idFieldName);
        $warehouse = Mage::getModel('cataloginventory/stock');

        if (!empty($id)) {
            $warehouse->load($id);
        }

        Mage::register('current_warehouse', $warehouse);
        return $warehouse;
    }

    /**
     * Create new warehouse
     *
     * @return void
     */
    public function newAction()
    {
        // The same form is used to create and edit
        $this->_forward('edit');
    }

    /**
     * Create/Edit warehouse
     *
     * @return void
     */
    public function editAction()
    {
        $warehouse = $this->_initWarehouse('id');

        if ($warehouse->getStockId() == Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('smile_warehouse')->__('The Default Warehouse is not available for edit'));
            $this->_redirect('*/*/');
            return;
        }

        $this->_title($this->__('System'))
             ->_title($this->__('Warehouses'))
             ->_title($warehouse->getStockId() ? $warehouse->getStockName() : Mage::helper('smile_warehouse')->__('New Warehouse'));

        $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
        if (!empty($data)) {
            $warehouse->addData($data);
        }

        $this->_initAction()
            ->_addContent($this->getLayout()->createBlock('smile_warehouse/adminhtml_warehouse_edit'))
            ->renderLayout();
    }

    /**
     * Save action
     *
     * @return void
     */
    public function saveAction()
    {
        $redirectBack = $this->getRequest()->getParam('back', false);
        if ($data = $this->getRequest()->getPost()) {

            $warehouse = $this->_initWarehouse('id');
            $id = $this->getRequest()->getParam('id');

            if (!$warehouse->getStockId() && $id) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('smile_warehouse')->__('Warehouse with ID %s doesn\'t exist.', $id));
                $this->_redirect('*/*/');
                return;
            }

            if ($warehouse->getStockId() == Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID) {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('smile_warehouse')->__('The Default Warehouse is not available for edit'));
                $this->_redirect('*/*/');
                return;
            }

            // save model
            try {
                if (!empty($data)) {
                    $warehouse->addData($data);
                    Mage::getSingleton('adminhtml/session')->setFormData($data);
                }

                $warehouse->validate()->save();
                Mage::getSingleton('adminhtml/session')->setFormData(false);
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('smile_warehouse')->__('The warehouse has been saved.'));
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $redirectBack = true;
            } catch (Exception $e) {
                $this->_getSession()->addError(Mage::helper('smile_warehouse')->__('Unable to save the warehouse.'));
                $redirectBack = true;
                Mage::logException($e);
            }
            if ($redirectBack) {
                $this->_redirect('*/*/edit', array('id' => $warehouse->getStockId()));
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Delete action
     *
     * @return void
     */
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                // init model and delete
                $warehouse = Mage::getModel('cataloginventory/stock')->load($id);

                if ($warehouse->getStockId() == Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID) {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('smile_warehouse')->__('The Default Warehouse is not available for edit'));
                    $this->_redirect('*/*/');
                    return;
                }

                $websiteIds = $warehouse->getAssignedWebsites();
                if (!empty($websiteIds)) {
                    Mage::getSingleton('adminhtml/session')->addError(Mage::helper('smile_warehouse')->__('The warehouse can\'t be deleted because it is assigned to a website.'));
                    $this->_redirect('*/*/');
                    return;
                }

                $warehouse->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('smile_warehouse')->__('The warehouse has been deleted.'));
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(Mage::helper('smile_warehouse')->__('An error occurred while deleting warehouse data.'));
                Mage::logException($e);
                // save data in session
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                // redirect to edit form
                $this->_redirect('*/*/edit', array('id' => $id));
                return;
            }
        }

        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('smile_warehouse')->__('Unable to find a warehouse to delete.'));
        $this->_redirect('*/*/');
    }

    /**
     * Delete warehouses using grid massaction
     *
     * @return void
     */
    public function massDeleteAction()
    {
        $ids = $this->getRequest()->getParam('warehouse');

        if (!is_array($ids)) {
            $this->_getSession()->addError($this->__('Please select warehouse(s).'));
        } else {
            try {
                $i = 0;
                foreach ($ids as $id) {
                    $model = Mage::getModel('cataloginventory/stock')->load($id);
                    $websiteIds = $model->getAssignedWebsites();

                    //prevent from removing default warehouse
                    if ($model->getStockId() != Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID && empty($websiteIds)) {
                        $model->delete();
                        $i++;
                    }
                }

                if ($i > 0) {
                    $this->_getSession()->addSuccess(
                        $this->__('Total of %d record(s) have been deleted.', $i)
                    );
                }

                if ($i < count($ids)) {
                    $this->_getSession()->addError(
                        $this->__('Total of %d record(s) haven\'t been deleted because they are assigned to website(s).', count($ids) - $i)
                    );
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError(Mage::helper('smile_warehouse')->__('An error occurred while mass deleting warehouses.'));
                Mage::logException($e);
                return;
            }
        }

        $this->_redirect('*/*/');
    }
}
