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

$installer->getConnection()->addColumn($installer->getTable('cataloginventory_stock'), 'stock_code', "varchar(20) NOT NULL DEFAULT ''");
$installer->getConnection()->addKey($installer->getTable('cataloginventory_stock'), 'stock_code_unique_index', 'stock_code', 'unique');

$installer->endSetup();