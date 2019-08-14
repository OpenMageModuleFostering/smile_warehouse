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

$installer->getConnection()->addColumn(
    $installer->getTable('core_website'),
    'stock_id',
    'SMALLINT(4) UNSIGNED NOT NULL DEFAULT \'' . Mage_CatalogInventory_Model_Stock::DEFAULT_STOCK_ID . '\''
);
$installer->getConnection()->addConstraint(
    'FK_CORE_WEBSITE_STOCK',
    $installer->getTable('core_website'),
    'stock_id',
    $installer->getTable('cataloginventory_stock'),
    'stock_id'
);

$installer->endSetup();