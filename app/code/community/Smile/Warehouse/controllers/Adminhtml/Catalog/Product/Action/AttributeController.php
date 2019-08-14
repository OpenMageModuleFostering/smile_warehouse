<?php
require_once 'app/code/core/Mage/Adminhtml/controllers/Catalog/Product/Action/AttributeController.php';
/**
 * Product attribute controller
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Adminhtml_Catalog_Product_Action_AttributeController extends Mage_Adminhtml_Catalog_Product_Action_AttributeController
{
    /**
     * Save action
     *
     * @return void
     */
    public function saveAction()
    {
        if (!$this->_validateProducts()) {
            return;
        }

        /* Collect Data */
        $inventoryData      = $this->getRequest()->getParam('inventory', array());
        $attributesData     = $this->getRequest()->getParam('attributes', array());
        $websiteRemoveData  = $this->getRequest()->getParam('remove_website_ids', array());
        $websiteAddData     = $this->getRequest()->getParam('add_website_ids', array());

        /* Prepare inventory data item options (use config settings) */
        foreach (Mage::helper('cataloginventory')->getConfigItemOptions() as $option) {
            if (isset($inventoryData[$option]) && !isset($inventoryData['use_config_' . $option])) {
                $inventoryData['use_config_' . $option] = 0;
            }
        }

        try {
            if ($attributesData) {
                $dateFormat = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
                $storeId    = $this->_getHelper()->getSelectedStoreId();

                foreach ($attributesData as $attributeCode => $value) {
                    $attribute = Mage::getSingleton('eav/config')
                        ->getAttribute('catalog_product', $attributeCode);
                    if (!$attribute->getAttributeId()) {
                        unset($attributesData[$attributeCode]);
                        continue;
                    }
                    if ($attribute->getBackendType() == 'datetime') {
                        if (!empty($value)) {
                            $filterInput    = new Zend_Filter_LocalizedToNormalized(array(
                                'date_format' => $dateFormat
                            ));
                            $filterInternal = new Zend_Filter_NormalizedToLocalized(array(
                                'date_format' => Varien_Date::DATE_INTERNAL_FORMAT
                            ));
                            $value = $filterInternal->filter($filterInput->filter($value));
                        } else {
                            $value = null;
                        }
                        $attributesData[$attributeCode] = $value;
                    } else if ($attribute->getFrontendInput() == 'multiselect') {
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                        $attributesData[$attributeCode] = $value;
                    }
                }

                Mage::getSingleton('catalog/product_action')
                    ->updateAttributes($this->_getHelper()->getProductIds(), $attributesData, $storeId);
            }

            if ($websiteAddData || $websiteRemoveData) {
                /* @var $actionModel Mage_Catalog_Model_Product_Action */
                $actionModel = Mage::getSingleton('catalog/product_action');
                $productIds  = $this->_getHelper()->getProductIds();

                if ($websiteRemoveData) {
                    $actionModel->updateWebsites($productIds, $websiteRemoveData, 'remove');
                }
                if ($websiteAddData) {
                    $actionModel->updateWebsites($productIds, $websiteAddData, 'add');
                }

                /**
                 * @deprecated since 1.3.2.2
                 */
                Mage::dispatchEvent('catalog_product_to_website_change', array('products' => $productIds));

                $this->_getSession()->addNotice(
                    $this->__('Please refresh "Catalog URL Rewrites" and "Product Attributes" in System -> <a href="%s">Index Management</a>', $this->getUrl('adminhtml/process/list'))
                );
            }

            $disallowedItemQty = 0;

            if ($inventoryData) {
                $stockItem = Mage::getModel('cataloginventory/stock_item');
                $storeId    = $this->_getHelper()->getSelectedStoreId();

                //prevent from edit warehouse fields for default stock
                if ($storeId == Mage_Core_Model_App::ADMIN_STORE_ID) {
                    $warehouseStockFields = Mage::helper('smile_warehouse')->getWarehouseStockFields();
                    foreach ($warehouseStockFields as $warehouseStockField) {
                        if (isset($inventoryData[$warehouseStockField])) {
                            unset($inventoryData[$warehouseStockField]);
                        }
                    }
                }

                foreach ($this->_getHelper()->getProductIds() as $productId) {
                    $stockItem->setData(array());
                    $stockItem->setStoreId($storeId);
                    $stockItem->loadByProduct($productId)
                        ->setProductId($productId);

                    if (!Mage::helper('smile_warehouse')->canUpdateInventory($stockItem)) {
                        $disallowedItemQty++;
                        continue;
                    }
                    if ($stockItem->getItemId()) {
                        $stockDataChanged = false;
                        foreach ($inventoryData as $k => $v) {
                            $stockItem->setDataUsingMethod($k, $v);
                            if ($stockItem->dataHasChangedFor($k)) {
                                $stockDataChanged = true;
                            }
                        }
                        if ($stockDataChanged) {
                            $stockItem->save();
                        }
                    }
                }
            }

            $this->_getSession()->addSuccess(
                $this->__('Total of %d record(s) were updated', count($this->_getHelper()->getProductIds()))
            );

            if ($disallowedItemQty > 0) {
                $this->_getSession()->addError(
                    $this->__('%d record(s) aren\'t allowed for update their inventory', $disallowedItemQty)
                );
            }
        }
        catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        catch (Exception $e) {
            $this->_getSession()->addException($e, $this->__('An error occurred while updating the product(s) attributes.'));
        }

        $this->_redirect('*/catalog_product/', array('store'=>$this->_getHelper()->getSelectedStoreId()));
    }
}