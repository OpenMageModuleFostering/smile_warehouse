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

$installer->startSetup();

$installer->getConnection()->addColumn($installer->getTable('cataloginventory/stock_item'), 'stock_location', 'VARCHAR(255) NOT NULL DEFAULT \'\'');

$installer->endSetup();