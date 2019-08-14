<?php
require_once 'app/code/core/Mage/Adminhtml/controllers/Catalog/ProductController.php';
/**
 * Product controller
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Adminhtml_Catalog_ProductController extends Mage_Adminhtml_Catalog_ProductController
{
    /**
     * Quick create simple product for configurable product action
     *
     * @return void
     */
    public function quickCreateAction()
    {
        $result = array();

        $storeId = $this->getRequest()->getParam('store', 0);

        /* @var $configurableProduct Mage_Catalog_Model_Product */
        $configurableProduct = Mage::getModel('catalog/product')
            ->setStoreId($storeId)
            ->load($this->getRequest()->getParam('product'));

        if (!$configurableProduct->isConfigurable()) {
            // If invalid parent product
            $this->_redirect('*/*/');
            return;
        }

        /* @var $product Mage_Catalog_Model_Product */

        $product = Mage::getModel('catalog/product')
            ->setStoreId($storeId)
            ->setTypeId(Mage_Catalog_Model_Product_Type::TYPE_SIMPLE)
            ->setAttributeSetId($configurableProduct->getAttributeSetId());


        foreach ($product->getTypeInstance()->getEditableAttributes() as $attribute) {
            if ($attribute->getIsUnique()
                || $attribute->getAttributeCode() == 'url_key'
                || $attribute->getFrontend()->getInputType() == 'gallery'
                || $attribute->getFrontend()->getInputType() == 'media_image'
                || !$attribute->getIsVisible()) {
                continue;
            }

            $product->setData(
                $attribute->getAttributeCode(),
                $configurableProduct->getData($attribute->getAttributeCode())
            );
        }

        $productData = $this->getRequest()->getParam('simple_product', array());

        //prevent from edit warehouse fields for default stock
        if (!$storeId && isset($productData['stock_data'])) {
            $warehouseStockFields = Mage::helper('smile_warehouse')->getWarehouseStockFields();
            foreach ($warehouseStockFields as $warehouseStockField) {
                if (isset($productData['stock_data'][$warehouseStockField])) {
                    unset($productData['stock_data'][$warehouseStockField]);
                }
            }
        }

        $product->addData($productData);
        $product->setWebsiteIds($configurableProduct->getWebsiteIds());

        $autogenerateOptions = array();
        $result['attributes'] = array();

        foreach ($configurableProduct->getTypeInstance()->getConfigurableAttributes() as $attribute) {
            $value = $product->getAttributeText($attribute->getProductAttribute()->getAttributeCode());
            $autogenerateOptions[] = $value;
            $result['attributes'][] = array(
                'label'         => $value,
                'value_index'   => $product->getData($attribute->getProductAttribute()->getAttributeCode()),
                'attribute_id'  => $attribute->getProductAttribute()->getId()
            );
        }

        if ($product->getNameAutogenerate()) {
            $product->setName($configurableProduct->getName() . '-' . implode('-', $autogenerateOptions));
        }

        if ($product->getSkuAutogenerate()) {
            $product->setSku($configurableProduct->getSku() . '-' . implode('-', $autogenerateOptions));
        }

        if (is_array($product->getPricing())) {
           $result['pricing'] = $product->getPricing();
           $additionalPrice = 0;
           foreach ($product->getPricing() as $pricing) {
               if (empty($pricing['value'])) {
                   continue;
               }

               if (!empty($pricing['is_percent'])) {
                   $pricing['value'] = ($pricing['value']/100)*$product->getPrice();
               }

               $additionalPrice += $pricing['value'];
           }

           $product->setPrice($product->getPrice() + $additionalPrice);
           $product->unsPricing();
        }

        try {
            /**
             * @todo implement full validation process with errors returning which are ignoring now
             */
//            if (is_array($errors = $product->validate())) {
//                $strErrors = array();
//                foreach($errors as $code=>$error) {
//                    $codeLabel = $product->getResource()->getAttribute($code)->getFrontend()->getLabel();
//                    $strErrors[] = ($error === true)? Mage::helper('catalog')->__('Value for "%s" is invalid.', $codeLabel) : Mage::helper('catalog')->__('Value for "%s" is invalid: %s', $codeLabel, $error);
//                }
//                Mage::throwException('data_invalid', implode("\n", $strErrors));
//            }

            $product->validate();
            $product->save();
            $result['product_id'] = $product->getId();
            $this->_getSession()->addSuccess(Mage::helper('catalog')->__('The product has been created.'));
            $this->_initLayoutMessages('adminhtml/session');
            $result['messages']  = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
        } catch (Mage_Core_Exception $e) {
            $result['error'] = array(
                'message' =>  $e->getMessage(),
                'fields'  => array(
                    'sku'  =>  $product->getSku()
                )
            );

        } catch (Exception $e) {
            Mage::logException($e);
            $result['error'] = array(
                'message'   =>  $this->__('An error occurred while saving the product. ') . $e->getMessage()
             );
        }

        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }
}