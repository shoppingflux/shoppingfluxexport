<?php
/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_4_6_0($object)
{

	// Used to add the shop ID in the feed name (when generating the feed.xml file)
	// The option is not enabled for existing install
    Configuration::updateGlobalValue('SHOPPING_FLUX_XML_SHOP_ID', false);

    // Remove the previous PS_SHOPPINGFLUX_CRON_TIME configuration key replaced by SHOPPING_FLUX_CRON_TIME
    // No need to save the previous value of the key as it's not a critical process and will be generated
    // automatically at the next run of cron
    Configuration::deleteByName('PS_SHOPPINGFLUX_CRON_TIME');

    return true;
}
