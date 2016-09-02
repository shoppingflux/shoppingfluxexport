<?php
/**
* 2007-2015 PrestaShop
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
* @author    PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2015 PrestaShop SA
* @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*/

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/../../init.php');

include_once(dirname(__FILE__).'/sfpayment.php');
include(dirname(__FILE__).'/shoppingfluxexport.php');

ini_set("memory_limit", "2048M");
ini_set('display_errors', 'off');

$f = new ShoppingFluxExport();

if (Tools::getValue('token') == '' || Tools::getValue('token') != Configuration::get('SHOPPING_FLUX_TOKEN')) {
    die("<?xml version='1.0' encoding='utf-8'?><error>Invalid Token</error>");
}

$current = Tools::getValue('current');

if (!Tools::getIsset('current') && !Tools::getIsset('total') && !Tools::getIsset('passes')) {
    if (Configuration::get('PS_SHOPPINGFLUX_LAST_URL') != '0' && Configuration::get('PS_SHOPPINGFLUX_LAST_URL') != '') {
        $f->logDebug('CRON CALL - last cron not finished,
            redirecting to : '.'http://'.Tools::getHttpHost().Configuration::get('PS_SHOPPINGFLUX_LAST_URL'));
        Tools::redirect('http://'.Tools::getHttpHost().Configuration::get('PS_SHOPPINGFLUX_LAST_URL'));
    } else {
        $frequency_in_hours = 6;
        $last_executed = Configuration::get('PS_SHOPPINGFLUX_CRON_TIME');
        $today = date('Y-m-d H:i:s');
        // Convert to unix timestamp
        $timestamp_last_exec = strtotime($last_executed);
        $timestamp_today = strtotime($today);
        $hours = ($timestamp_today - $timestamp_last_exec)/(60*60);

        if ($hours >= $frequency_in_hours) {
            $f->logDebug('CRON CALL - begining of treament');
        } else {
            $f->logDebug('');
            $f->logDebug('CRON CALL - Not initiated. Cron call has already been called at '.$last_executed);
            $f->logDebug('NEXT CRON CALL - Can be initiated at
                '.date('Y-m-d H:i:s', strtotime($last_executed . ' + '.$frequency_in_hours.' hour')));
            $f->logDebug('');
        }
    }
} else {
    Configuration::updateValue('PS_SHOPPINGFLUX_LAST_URL', $_SERVER['REQUEST_URI']);
    $f->logDebug('CRON CALL - cron in progress, middle of treament');
}

if (empty($current)) {
    $f->initFeed();
} else {
    $f->writeFeed(Tools::getValue('total'), Tools::getValue('current'), Tools::getValue('lang'));
}
