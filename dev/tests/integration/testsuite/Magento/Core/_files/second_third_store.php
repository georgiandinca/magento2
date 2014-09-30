<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/** @var \Magento\Store\Model\Website $website */
$website = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('Magento\Store\Model\Website');
$website->setName('Second Website')->setCode('secondwebsite')->save();

$websiteId = $website->getId();
$groupId = $website->getDefaultGroupId();

/** @var \Magento\Store\Model\Store $store */
$store = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('Magento\Store\Model\Store');
$store->setCode('secondstore')->setName('Second Store')->setSortOrder(10)->setIsActive(1);
$store->save();


/** @var \Magento\Store\Model\Website $website */
$website = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('Magento\Store\Model\Website');
$website->setName('Third Website')->setCode('thirdwebsite')->save();

$websiteId = $website->getId();
$groupId = $website->getDefaultGroupId();

/** @var \Magento\Store\Model\Store $store */
$store = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create('Magento\Store\Model\Store');
$store->setCode('thirdstore')->setName('Third Store')->setSortOrder(10)->setIsActive(1);
$store->save();

/* Refresh stores memory cache */
\Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get(
    'Magento\Framework\StoreManagerInterface'
)->reinitStores();
