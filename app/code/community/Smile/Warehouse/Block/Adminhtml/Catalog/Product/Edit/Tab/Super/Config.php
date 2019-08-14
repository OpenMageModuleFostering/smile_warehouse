<?php
/**
 * Adminhtml catalog super product configurable tab
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Smile_Warehouse_Block_Adminhtml_Catalog_Product_Edit_Tab_Super_Config extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Super_Config
{
    /**
     * Retrieve Quick create product URL
     *
     * @return string
     */
    public function getQuickCreationUrl()
    {
        return $this->getUrl(
            '*/*/quickCreate',
            array(
                'product'  => $this->_getProduct()->getId(),
                'store'    => $this->getRequest()->getParam('store', 0)
            )
        );
    }
}