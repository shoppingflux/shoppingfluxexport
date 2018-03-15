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
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');

include_once(dirname(__FILE__) . '/sfpayment.php');

ini_set("memory_limit", "2048M");
ini_set('display_errors', 'off');

$id_shop = Context::getContext()->shop->id;

$sf = Module::getInstanceByName('shoppingfluxexport');
if (!$sf || !$sf->active) {
    die("<?xml version='1.0' encoding='utf-8'?><error>Module inactive</error>");
}

$token = Tools::getValue('token');
if (version_compare(_PS_VERSION_, '1.5', '>') && Shop::isFeatureActive()) {
    $tokenInConfig = $sf->getTokenValue($id_shop);

    $allTokens_raw = $sf->getAllTokensOfShop();
    $allTokens = array();
    foreach ($allTokens_raw as $allTokens_subraw) {
        $allTokens[$allTokens_subraw['token']] = $allTokens_subraw['token'];
    }
} else {
    $tokenInConfig = $sf->getTokenValue();
    $allTokens[$tokenInConfig] = $tokenInConfig;
}

if ($token == '' || ($token != $tokenInConfig && ! in_array($token, $allTokens))) {
    die("<?xml version='1.0' encoding='utf-8'?><error>Invalid Token</error>");
}

$current = Tools::getValue('current');
$lang = Tools::getValue('lang');

// Do not allow feed generation if less than 2 hours before last generation
$frequency_in_hours = 2;
$today = date('Y-m-d H:i:s');

$keyCronTime = 'SHOPPING_FLUX_CRON_TIME';
if (!empty($lang)) {

    // When a lang is provided, we will have a separate frequency
    if (!Language::getIdByIso($lang)) {
        // The lang is not valid
        $logMessage = 'Invalid lang: '.$lang;
        SfLogger::getInstance()->log(SF_LOG_CRON, $logMessage);
        die("<?xml version='1.0' encoding='utf-8'?><error>Invalid lang</error>");
    }

    // Separate the configuration key by lang, such as : SHOPPING_FLUX_CRON_TIME_FR
    $keyCronTime = $keyCronTime.'_'.Tools::strtoupper($lang);
}
$last_executed = Configuration::get($keyCronTime, null, null, $id_shop);

if (empty($last_executed) || ($last_executed == '0')) {
    $last_executed = 0;
}
// Convert to unix timestamp
$timestamp_last_exec = strtotime($last_executed);
$timestamp_today = strtotime($today);
$hours = ($timestamp_today - $timestamp_last_exec) / (60 * 60);

if (empty($current)) {
    if ($hours >= $frequency_in_hours) {
        SfLogger::getInstance()->log(SF_LOG_CRON, 'CRON CALL - begining of treament');
        $sf->initFeed($lang);
    } else {
        $logMessage = 'CRON CALL - Not initiated SHOULD NOT HAVE BEEN CALLED. ';
        $logMessage .= 'Cron call has already been called at ' . $last_executed;
        SfLogger::getInstance()->log(SF_LOG_CRON, $logMessage);
        
        $logMessage = 'NEXT CRON CALL - Can be initiated at ';
        $logMessage .= date('Y-m-d H:i:s', strtotime($last_executed . ' + ' . $frequency_in_hours . ' hour'));
        SfLogger::getInstance()->log(SF_LOG_CRON, $logMessage);
    }
} else {
    $sf->writeFeed(Tools::getValue('total'), Tools::getValue('current'), $lang);
}
