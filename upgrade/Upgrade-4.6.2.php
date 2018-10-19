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

function upgrade_module_4_6_2($object)
{
    // Set the default state for market place expedited order's has shipped
    if (version_compare(_PS_VERSION_, '1.5', '>') && Shop::isFeatureActive()) {
        foreach (Shop::getShops() as $shop) {
            Configuration::updateValue('SHOPPING_FLUX_STATE_MP_EXP', Configuration::get('PS_OS_SHIPPING'), false, null, $shop['id_shop']);
        }
    } else {
        Configuration::updateValue('SHOPPING_FLUX_STATE_MP_EXP', Configuration::get('PS_OS_SHIPPING'));
    }

    // uniformise carrier matching by using lowercase only
    $object->migrateToNewCarrierMatching();

    return true;
}
