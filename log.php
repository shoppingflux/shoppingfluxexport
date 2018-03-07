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

ini_set("memory_limit", "2048M");
ini_set('display_errors', 'on');

$sf = Module::getInstanceByName('shoppingfluxexport');

if (Tools::getValue('token') == '' || Tools::getValue('token') != $f->getTokenValue()) {
    die("<?xml version='1.0' encoding='utf-8'?><error>Invalid Token</error>");
}

// Check if file exists
$outputFileCronexport = _PS_MODULE_DIR_ . 'shoppingfluxexport/logs/cronexport_';
$outputFileCronexport .= $f->getTokenValue().'.txt';

if (!file_exists($outputFileCronexport)) {
    p('cronexport file does not exists');
} else {
    echo '========================= PRODUCT CRON GENERATION DETAILS =========================';
    $text = Tools::file_get_contents($outputFileCronexport);
    p($text);
    p('');
}

$outputFilecallWebService = _PS_MODULE_DIR_ . 'shoppingfluxexport/logs/callWebService_';
$outputFilecallWebService .= $f->getTokenValue().'.txt';

if (!file_exists($outputFilecallWebService)) {
    p('callWebService file does not exists');
} else {
    echo '========================= CALL TO WEBSERVICE DETAILS =========================';
    $text = Tools::file_get_contents($outputFilecallWebService);
    p($text);
}
