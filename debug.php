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
include (dirname(__FILE__) . '/../../config/config.inc.php');
include (dirname(__FILE__) . '/../../init.php');

include_once (dirname(__FILE__) . '/sfpayment.php');
include (dirname(__FILE__) . '/shoppingfluxexport.php');

ini_set("memory_limit", "2048M");
ini_set('display_errors', 'on');

$sf = new ShoppingFluxExport();

if (Tools::getValue('token') == '' || Tools::getValue('token') != Configuration::get('SHOPPING_FLUX_TOKEN')) {
    die("Invalid Token");
}

function getDebugForm($sf)
{
    $idOrder = Tools::getValue('IdOrder');
    $output = '<fieldset id="debug_content">';
    $output .= '<legend>' . $sf->l('Debug') . '</legend>';
    $output .= getLogContent($sf);
    
    $output .= '<p><label>' . $sf->l('Replay last received orders') . ' :</label><br />&nbsp;</p>';
    $output .= '<table width="100%" border=1>
                        <tbody>
                        <tr>
                            <th style="padding: 10px; text-align:center;">' . $sf->l('Id order Shopping Flux') . '</td>
                            <th style="padding: 10px; text-align:center;">' . $sf->l('Date') . '</td>
                            <th style="padding: 10px; text-align:center;">' . $sf->l('Market place') . '</td>
                            <th style="padding: 10px; text-align:center;">' . $sf->l('Total Amount') . '</td>
                            <th style="padding: 10px; text-align:center;">' . $sf->l('Id Order Prestashop') . '</td>
                            <th style="padding: 10px; text-align:center;">' . $sf->l('Actions') . '</td>
                        </tr>';
    
    $lastOrders = $sf->getLastOrdersTreated();
    $link = new Link();
    foreach ($lastOrders as $currentOrder) {
        $orderXml = @simplexml_load_string($currentOrder);
        $idOrderPs = $sf->getPrestashopOrderIdFromSfOrderId((string) $orderXml->IdOrder, (string) $orderXml->Marketplace);
        $output .= '<tr>
                            <td style="padding: 10px; text-align:center;">' . (string) $orderXml->IdOrder . '</td>
                            <td style="padding: 10px; text-align:center;">' . str_replace('T', ' ', explode('+', (string) $orderXml->OrderDate)[0]) . '</td>
                            <td style="padding: 10px; text-align:center;">' . (string) $orderXml->Marketplace . '</td>
                            <td style="padding: 10px; text-align:center;">' . (float) ($orderXml->TotalAmount) . ' ' . (string) $orderXml->Currency . '</td>
                            <td style="padding: 10px; text-align:center;"><a href="' . $link->getAdminLink("AdminOrders") . '&id_order=' . $idOrderPs . '&vieworder" target="_blank">' . $idOrderPs . '</a></td>
                            <td style="padding: 10px; text-align:center;"><a href="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '&IdOrder=' . (string) $orderXml->IdOrder . '">' . $sf->l('Replay') . '</a></td>
                        </tr>';
    }
    $output .= '</tbody>
                </table>
            </fieldset>';
    
    if ($idOrder) {
        $output .= '<div style="border: 1px solid #CCC; padding: 10px;">
                            <div><b>' . $sf->l('Execution Result :') . '</b></div><br>
                            <div>' . $sf->replayOrder((string) Tools::getValue('IdOrder')) . '</div>
                       </div>';
    }
    
    return $output;
}

/**
 * Form to activate logs
 */
function getLogContent($sf)
{
    $doLogDebug = (int) Configuration::get('SHOPPING_FLUX_DEBUG');
    $sf_basic_log = '';
    if ($doLogDebug) {
        $sf_basic_log = ' checked="checked" ';
    }
    
    $html = '<form method="post" action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '">';
    $html .= '<p style="clear: both"><label>' . $sf->l('Enable logs');
    $html .= ' :</label><span style="display: block; padding: 3px 0 0 0;">
         <input type="checkbox" name="SHOPPING_FLUX_DEBUG" ' . $sf_basic_log . '>
         ' . $sf->l('Enable basic module logs.') . '
         </span></p>';
    $html .= '<p style="clear: both"></p>';
    
    $doLogDebugOrders = (int) Configuration::get('SHOPPING_FLUX_ORDERS_DEBUG');
    $sf_order_log = '';
    if ($doLogDebugOrders) {
        $sf_order_log = ' checked="checked" ';
    }
    
    $html .= '<p style="clear: both"><label>' . $sf->l('Enable order logs');
    $html .= ' :</label><span style="display: block; padding: 3px 0 0 0;">
        <input type="checkbox" name="SHOPPING_FLUX_ORDERS_DEBUG" ' . $sf_order_log . '>
        ' . $sf->l('Enable order creation logs.') . '
        </span></p>';
    $html .= '<p style="margin-top:20px"><label>&nbsp;</label><input type="submit" value="' . $sf->l('Update');
    $html .= '" name="rec_config_debug" class="button"/></p>';
    $html .= '</form>';
    $html .= '<p style="clear: both"></p>';
    return $html;
}

/**
 * DEBUG TOOLS
 */

$rec_config_debug = Tools::getValue('rec_config_debug');
if (isset($rec_config_debug) && $rec_config_debug != null) {
    $doLogDebug = Tools::getValue('SHOPPING_FLUX_DEBUG', 'off');
    if ($doLogDebug == 'on') {
        Configuration::updateValue('SHOPPING_FLUX_DEBUG', '1');
    } else {
        Configuration::updateValue('SHOPPING_FLUX_DEBUG', '0');
    }
    
    $doLogDebugOrders = Tools::getValue('SHOPPING_FLUX_ORDERS_DEBUG', 'off');
    if ($doLogDebugOrders == 'on') {
        Configuration::updateValue('SHOPPING_FLUX_ORDERS_DEBUG', '1');
    } else {
        Configuration::updateValue('SHOPPING_FLUX_ORDERS_DEBUG', '0');
    }
}

?>
<html>
<head>
</head>
<body>
    <?php
    echo getDebugForm($sf);
    ?>
</body>
</html>
