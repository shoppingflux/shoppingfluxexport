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

if ((Tools::getValue('token') == '' || Tools::getValue('token') != Configuration::get('SHOPPING_FLUX_TOKEN')) && ! (isset($_GET['test_homepage']) || isset($_GET['test_curl']))) {
    die("Invalid Token");
}

function getDebugForm($sf)
{
    $idOrder = Tools::getValue('IdOrder');
    $output = '<fieldset id="debug_content">';
    $output .= '<legend>' . $sf->l('Debug') . '</legend>';
    $output .= getLogContent($sf);
    $output .= '</fieldset>';
    return $output;
}

function getConfigForm($sf)
{
    if (Configuration::get('SHOPPING_FLUX_FDG')) {
        $product_id = Configuration::get('SHOPPING_FLUX_FDG');
        $sql = '
        SELECT `out_of_stock`
        FROM  `' . _DB_PREFIX_ . 'stock_available`
        WHERE  `id_product` = ' . $product_id . '
        GROUP BY `id_product` ';
        
        $productStock = Db::getInstance()->getRow($sql);
        
        if ($productStock['out_of_stock'] == 0) {
            $outOfStock = 'Refuser les commandes';
        } else {
            if ($productStock['out_of_stock'] == 1) {
                $outOfStock = 'Accepter les commandes';
            } else {
                if ($productStock['out_of_stock'] == 2) {
                    $outOfStock = 'Par défaut: Accepter les commandes tel que défini dans les préférences produits';
                }
            }
        }
    }
    $urlBase = Tools::getCurrentUrlProtocolPrefix() . $_SERVER['SERVER_NAME'] . $_SERVER['REWRITEBASE'];
    
    $idOrder = Tools::getValue('IdOrder');
    $output = '<fieldset id="debug_content">';
    $output .= '<legend>' . $sf->l('Configurations') . '</legend>';
    
    $output .= '<label>SHOPPING_FLUX_FDG</label>' . Configuration::get('SHOPPING_FLUX_FDG');
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>FDG allow out of stock</label>' . $outOfStock;
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>PS_SHOPPINGFLUX_CRON_TIME</label>' . Configuration::get('PS_SHOPPINGFLUX_CRON_TIME');
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>SHOPPING_FLUX_STATUS_SHIPPED</label>' . Configuration::get('SHOPPING_FLUX_STATUS_SHIPPED');
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>SHOPPING_FLUX_SHIPPED</label>' . Configuration::get('SHOPPING_FLUX_SHIPPED');
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>SHOPPING_FLUX_STATUS_CANCELED</label>' . Configuration::get('SHOPPING_FLUX_STATUS_CANCELED');
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>SHOPPING_FLUX_CANCELED</label>' . Configuration::get('SHOPPING_FLUX_CANCELED');
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>SHOPPING_FLUX_REF</label>' . Configuration::get('SHOPPING_FLUX_REF');
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>SHOPPING_FLUX_TOKEN</label>' . Configuration::get('SHOPPING_FLUX_TOKEN');
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>getAllTokensOfShop()</label>';
    $output .= '<pre>' . print_r($sf->getAllTokensOfShop(), true) . '</pre>';
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>URL test homepage</label>' . $urlBase . 'modules/shoppingfluxexport/debug.php?test_homepage=1';
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>URL test CURL</label>' . $urlBase . 'modules/shoppingfluxexport/debug.php?test_curl=1';
    $output .= '<p style="clear: both"></p>';
    
    $output .= '</fieldset>';
    return $output;
}

function getReplayOrdersForm($sf)
{
    $idOrder = Tools::getValue('IdOrder');
    $output = '<fieldset id="debug_content">';
    $output .= '<legend>' . $sf->l('Replay last received orders') . '</legend>';
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
    $sf_basic_log = '';
    if ((int) Configuration::get('SHOPPING_FLUX_DEBUG') || Configuration::get('SHOPPING_FLUX_DEBUG') == 'true') {
        $sf_basic_log = ' checked="checked" ';
    }
    
    $html = '<form method="post" action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '">';
    $html .= '<label>' . $sf->l('Enable logs');
    $html .= '</label>
         <input type="checkbox" name="SHOPPING_FLUX_DEBUG" ' . $sf_basic_log . '>
         ' . $sf->l('Enable basic module logs.') . '
         </span>';
    $sf_order_log = '';
    if ((int) Configuration::get('SHOPPING_FLUX_ORDERS_DEBUG') || Configuration::get('SHOPPING_FLUX_ORDERS_DEBUG') == 'true') {
        $sf_order_log = ' checked="checked" ';
    }
    $html .= '<p style="clear: both"></p>';
    $html .= '<label>' . $sf->l('Enable order logs');
    $html .= '</label>
        <input type="checkbox" name="SHOPPING_FLUX_ORDERS_DEBUG" ' . $sf_order_log . '>
        ' . $sf->l('Enable order creation logs.');
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

function curl_file_get_contents($url)
{
    $curl_post_data = array();
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 1);
    $curl_response = curl_exec($curl);
    curl_close($curl);
    return $curl_response;
}

function logDebug($toLog)
{
    $outputFile = dirname(__FILE__) . '/logs/testCurl.txt';
    $fp = fopen($outputFile, 'a');
    fwrite($fp, chr(10) . date('d/m/Y h:i:s A') . ' - ' . $toLog);
    fclose($fp);
}
?>
<html>
<head>
<style type="text/css">
label {
	font-weight: bold;
	display: inline-block;
	width: 50%;
	text-align: right;
	padding-right: 10px;
	float: left;
}

pre {
	float: left;
}
</style>
</head>
<body>
    <?php
    echo getDebugForm($sf);
    echo getConfigForm($sf);
    echo getReplayOrdersForm($sf);
    $urlBase = Tools::getCurrentUrlProtocolPrefix() . $_SERVER['SERVER_NAME'] . $_SERVER['REWRITEBASE'];
    if (isset($_GET['test_homepage']) && $_GET['test_homepage'] != '') {
        $curl_response = curl_file_get_contents($urlBase);
        ?>
        <fieldset id="debug_content">
		<legend>Open page via curl for <?php echo $urlBase; ?>, result :</legend>
		<?php echo $curl_response; ?>
	</fieldset>
	<?php
    }
    if (isset($_GET['test_curl']) && $_GET['test_curl'] != '') {
        $outputFile = dirname(__FILE__) . '/logs/testCurl.txt';
        // To test timeout
        sleep(2);
        if (! isset($_GET['index'])) {
            // First call
            logDebug('Starting first call');
            $fp = fopen($outputFile, 'w');
            fwrite($fp, '');
            fclose($fp);
            $index = 1;
            $nextUrl = $urlBase . 'modules/shoppingfluxexport/utils.php?test_curl=1&index=' . $index;
            logDebug('Going to call : ' . $nextUrl);
            $curl_response = curl_file_get_contents($nextUrl);
        } else {
            $index = $_GET['index'];
            $index ++;
            if ($index > 100) {
                // Ended
                logDebug('Successfully ended');
                die();
            } else {
                // Call next URL
                $nextUrl = $urlBase . 'modules/shoppingfluxexport/utils.php?test_curl=1&index=' . $index;
                logDebug('Call received, index = ' . $_GET['index']);
                logDebug('Going to call : ' . $nextUrl);
                $curl_response = curl_file_get_contents($nextUrl);
            }
        }
    }
    ?>
</body>
</html>
