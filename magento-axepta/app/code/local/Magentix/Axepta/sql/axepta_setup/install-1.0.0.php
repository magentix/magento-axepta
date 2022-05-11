<?php
/**
 * Copyright (C) 2022 Magentix SARL
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$installer->getConnection()->addColumn(
    $this->getTable('sales/order'), Magentix_Axepta_Helper_Data::ORDER_AXEPTA_KEY, 'VARCHAR(100) NULL'
);
$installer->getConnection()->addIndex(
    $this->getTable('sales/order'),
    $installer->getConnection()->getIndexName(
        $this->getTable('sales/order'),
        Magentix_Axepta_Helper_Data::ORDER_AXEPTA_KEY,
        Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
    ),
    Magentix_Axepta_Helper_Data::ORDER_AXEPTA_KEY,
    Varien_Db_Adapter_Interface::INDEX_TYPE_UNIQUE
);

$installer->endSetup();