<?php
/**
 * Warehouse module setup
 *
 * @category Smile
 * @package  Smile_Warehouse
 * @author   Smile <solution.magento@smile.fr>
 * @license  http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
/* @var $installer Smile_Warehouse_Model_Mysql4_Setup */

$warehouse = Mage::getModel('cataloginventory/stock');
$warehouse->load(Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID);

$warehouse->setStockCode('Main');
$warehouse->save();