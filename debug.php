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
define('_PS_MODE_DEV_', true);
include(dirname(__FILE__) . '/../../config/config.inc.php');
include(dirname(__FILE__) . '/../../init.php');

include_once(dirname(__FILE__) . '/sfpayment.php');

ini_set("memory_limit", "2048M");
ini_set('display_errors', 'on');

$sf = Module::getInstanceByName('shoppingfluxexport');

if ((Tools::getValue('token') == '' || Tools::getValue('token') != $sf->getTokenValue()) && ! (isset($_GET['test_homepage']) || isset($_GET['test_curl']))) {
    die("Invalid Token");
}

function getDebugForm($sf)
{
    $output = '<fieldset id="debug_content">';
    $output .= '<legend>' . $sf->l('Debug') . '</legend>';
    $output .= getLogsConfiguration($sf);
    $output .= '</fieldset>';
    return $output;
}

function getRecablage($sf)
{
    $output = '<fieldset id="recablage">';
    $output .= '<legend>' . $sf->l('Recablage du montant du bloc paiement dans les commandes') . '</legend>';
    $output .= '<p>Si le montant dans le bloc paiement ne correspond pas au montant total de la commande, ajoutez la ligne suivante au début de la fonction <b>hookbackOfficeTop</b> : </p>';
    $output .= '<p>SfDebugger::recablageOrderPayment();</p>';
    $output .= '</fieldset>';
    return $output;
}

function getConfigForm($sf)
{
    $outOfStock = "n/a";
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
    $urlBase = Tools::getCurrentUrlProtocolPrefix() . $_SERVER['HTTP_HOST'];
    
    $idOrder = Tools::getValue('IdOrder');
    $output = '<fieldset id="debug_content">';
    $output .= '<legend>' . $sf->l('Configurations') . '</legend>';
    
    $output .= '<label>SHOPPING_FLUX_FDG</label>' . Configuration::get('SHOPPING_FLUX_FDG');
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>FDG allow out of stock</label>' . $outOfStock;
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>SHOPPING_FLUX_CRON_TIME (global)</label>' . Configuration::get('SHOPPING_FLUX_CRON_TIME');
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
    
    $output .= '<label>SHOPPING_FLUX_TOKEN</label>' . $sf->getTokenValue();
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>getAllTokensOfShop(true, true)</label>';
    $allTokens = $sf->getAllTokensOfShop(true, true);
    $output .= '<pre>' . print_r($allTokens, true) . '</pre>';
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>URL test homepage</label>' . $urlBase . '/modules/shoppingfluxexport/debug.php?test_homepage=1';
    $output .= '<p style="clear: both"></p>';
    
    $output .= '<label>URL test CURL</label>' . $urlBase . '/modules/shoppingfluxexport/debug.php?test_curl=1';
    $output .= '<p style="clear: both"></p>';
    
    $output .= '</fieldset>';
    return $output;
}

function getReplayOrdersForm($sf)
{
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
        $exploded = explode('+', (string) $orderXml->OrderDate);
        $output .= '<tr>
                            <td style="padding: 10px; text-align:center;">' . (string) $orderXml->IdOrder . '</td>
                            <td style="padding: 10px; text-align:center;">' . str_replace('T', ' ', $exploded[0]) . '</td>
                            <td style="padding: 10px; text-align:center;">' . (string) $orderXml->Marketplace . '</td>
                            <td style="padding: 10px; text-align:center;">' . (float) ($orderXml->TotalAmount) . ' ' . (string) $orderXml->Currency . '</td>
                            <td style="padding: 10px; text-align:center;"><a href="' . $link->getAdminLink("AdminOrders") . '&id_order=' . $idOrderPs . '&vieworder" target="_blank">' . $idOrderPs . '</a></td>
                            <td style="padding: 10px; text-align:center;"><a href="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '&IdOrder=' . (string) $orderXml->IdOrder . '">' . $sf->l('Replay') . '</a></td>
                        </tr>';
    }
    $output .= '</tbody>
                </table>
            </fieldset>';
    
    $output .= '<fieldset id="debug_content">';
    $output .= '<form method="post" action="' . Tools::safeOutput($_SERVER['REQUEST_URI']) . '">';
    $output .= '<legend>' . $sf->l('Replay specific order by XML (put only one order XML tag)') . '</legend>';
    $output .= '<textarea rows="15" cols="150" name="replayXml"></textarea>';
    $output .= '<br><input type="submit" value="' . $sf->l('Send');
    $output .= '" name="" class="button"/></p>';
    $output .= '</form>';
    $output .= '</fieldset>';
    
    return $output;
}

/**
 * Form to activate logs
 */
function getLogsConfiguration($sf)
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
    

    $sf_order_debug = '';
    if ((int) Configuration::get('SHOPPING_FLUX_DEBUG_ERRORS') || Configuration::get('SHOPPING_FLUX_DEBUG_ERRORS') == 'true') {
        $sf_order_debug = ' checked="checked" ';
    }
    $html .= '<p style="clear: both"></p>';
    $html .= '<label>' . $sf->l('Enable order logs with PHP errors and warnings');
    $html .= '</label>
        <input type="checkbox" name="SHOPPING_FLUX_DEBUG_ERRORS" ' . $sf_order_debug . '>
        ' . $sf->l('Enable order logs with PHP errors and warnings.');
    
    
    $html .= '<p style="margin-top:20px"><label>&nbsp;</label><input type="submit" value="' . $sf->l('Update');
    $html .= '" name="rec_config_debug" class="button"/></p>';
    $html .= '</form>';
    $html .= '<p style="clear: both"></p>';
    return $html;
}

/**
 * Main menu for accessing the logs
 */
function getLogsContent($sf) {
    $html = '<fieldset>
                <legend>Logs</legend>';
    $allTokens = $sf->getAllTokensOfShop(true, false, true);
    foreach ($allTokens as $currentToken) {
        $html .= getLogsContentOfToken($sf, $currentToken);
    }
    $html .= '</fieldset>';
    return $html;
}


/**
 * Reads the logs and displays them gently into HTML format
 */
function getLogsParsed($sf) {
    $fileName = dirname(__FILE__) . Tools::getValue('log');
    $content = file_get_contents($fileName);
    
    $contentExploded = explode('<?xml', $content);
    ?>
    <script type="text/javascript">
    function copyTextToClipboard(text) {
      var textArea = document.createElement("textarea");
    
      // Place in top-left corner of screen regardless of scroll position.
      textArea.style.position = 'fixed';
      textArea.style.top = 0;
      textArea.style.left = 0;
    
      // Ensure it has a small width and height. Setting to 1px / 1em
      // doesn't work as this gives a negative w/h on some browsers.
      textArea.style.width = '2em';
      textArea.style.height = '2em';
    
      // We don't need padding, reducing the size if it does flash render.
      textArea.style.padding = 0;
    
      // Clean up any borders.
      textArea.style.border = 'none';
      textArea.style.outline = 'none';
      textArea.style.boxShadow = 'none';
    
      // Avoid flash of white box if rendered for any reason.
      textArea.style.background = 'transparent';
      textArea.value = text;
    
      document.body.appendChild(textArea);
    
      textArea.select();
    
      try {
        var successful = document.execCommand('copy');
        var msg = successful ? 'successful' : 'unsuccessful';
      } catch (err) {
        console.log('Oops, unable to copy');
      }
    
      document.body.removeChild(textArea);
    }
    </script>
    <div class="logs">
    <?php 

    foreach ($contentExploded as $currentSegment) {
        $currentSegment = str_replace(' version="1.0" encoding="utf-8"?>', '', $currentSegment);
        $currentSegment = str_replace(' version="1.0" encoding="UTF-8"?>', '', $currentSegment);
        if (strpos($currentSegment, '>')) {
            // Has xml
            $xmlContent = substr($currentSegment, 0, strrpos($currentSegment, '>') + 1);
            $textContent = substr($currentSegment, strrpos($currentSegment, '>') + 1, strlen($currentSegment));
        } else {
            // Has no XML
            $xmlContent = '';
            $textContent = $currentSegment;
        }
        // First echo XML
        if ($xmlContent) {
            makeXmlTree($xmlContent);
        }
        
        // Then echo text, removing first line break
        $textContent = nl2br($textContent);
        $textContent = substr($textContent, 6, strlen($textContent));
        echo $textContent;
    }

    if ($xmlContent) {
    ?>
    <script type="text/javascript">
    $(document).ready(function() {

        $('.textarea_xml').each(function(){
            var tree = $.parseXML($(this).val());
            traverse($(this).next().find('li'), tree.firstChild)
            // this – is an —
            $('<b>–<\/b>').prependTo($(this).next().find('li')).click(function() {
                var sign = $(this).text()
                if (sign == "–")
                    $(this).text('+').next().next().children().hide()
                else
                    $(this).text('–').next().next().children().show()
            });
        });
        
        function traverse(node, tree) {
            var children = $(tree).children();
            var appended = node.append('<label>'+tree.nodeName+'</label>');
            node.children()[0].onclick = function() { 
                copyTextToClipboard(tree.outerHTML);
            };
            
            if (children.length) {
                var ul = $("<ul>").appendTo(node)
                children.each(function() {
                    var li = $('<li>').appendTo(ul)
                    traverse(li, this)
                })
            } else {
                $('<ul><li><span>' + $(tree).text() + '<\/span><\/li><\/ul>').appendTo(node)
            }
        }
    });
    </script>
    <?php
    }
    ?>
    </div><?php
}

/**
 * Builds an ergonomic XML tree from a XML String
 */
function makeXmlTree($xmlContent) {
    $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>' . $xmlContent;
    $randed = rand(0, 10000);
    ?>
    <textarea class="textarea_xml" style="display: none;"><?php echo $xmlContent; ?></textarea>
	<ul class='treeView_xml'>
		<li></li>
	</ul>
	
    <?php 
}

/**
 * Get the fieldset for the logs of a specific token
 */
function getLogsContentOfToken($sf, $token) {
    $html = '<fieldset>
                <legend>' . $sf->l('Logs du token') . ' : ' . $token . '</legend>';
    $fileName = '/logs/cronexport_' . $token . '.txt';
    if (file_exists(dirname(__FILE__) . $fileName)) {
        $button = '<a target="_blank" href="./debug.php?token=' . Tools::getValue('token'). '&action=viewLog&log=' . $fileName . '">' . $sf->l('Voir') . '</a>';
        $html .= '<label>' . $sf->l('Logs de la génération du flux') . '</label>' . $button;
        $html .= '<p style="clear: both"></p>';
    }
    $fileName = '/logs/orders_debug_' . $token . '.txt';
    if (file_exists(dirname(__FILE__) . $fileName)) {
        $button = '<a target="_blank" href="./debug.php?token=' . Tools::getValue('token'). '&action=viewLog&log=' . $fileName . '">' . $sf->l('Voir') . '</a>';
        $html .= '<label>' . $sf->l('Logs de la création de commandes sur Prestashop') . '</label>' . $button;
        $html .= '<p style="clear: both"></p>';
    }
    $fileName = '/logs/orders_debug_errors_on_' . $token . '.txt';
    if (file_exists(dirname(__FILE__) . $fileName)) {
        $button = '<a target="_blank" href="./debug.php?token=' . Tools::getValue('token'). '&action=viewLog&log=' . $fileName . '">' . $sf->l('Voir') . '</a>';
        $html .= '<label>' . $sf->l('Logs de la création de commandes sur Prestashop avec erreurs activées') . '</label>' . $button;
        $html .= '<p style="clear: both"></p>';
    }
    $fileName = '/logs/callWebService_' . $token . '.txt';
    if (file_exists(dirname(__FILE__) . $fileName)) {
        $button = '<a target="_blank" href="./debug.php?token=' . Tools::getValue('token'). '&action=viewLog&log=' . $fileName . '">' . $sf->l('Voir') . '</a>';
        $html .= '<label>' . $sf->l('Logs des appels au webservice ShoppingFlux') . '</label>' . $button;
        $html .= '<p style="clear: both"></p>';
    }
    
    $html .= '</fieldset>';
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
    
    $doLogDebugOrders = Tools::getValue('SHOPPING_FLUX_DEBUG_ERRORS', 'off');
    if ($doLogDebugOrders == 'on') {
        Configuration::updateValue('SHOPPING_FLUX_DEBUG_ERRORS', '1');
    } else {
        Configuration::updateValue('SHOPPING_FLUX_DEBUG_ERRORS', '0');
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
<script type="text/javascript" src="//code.jquery.com/jquery-1.7.2.js"></script>

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

.logs ul, .logs li {
    list-style: none;
    margin: 0;
}


.logs span {
    font-size: 12px;
}

.logs label {
    background: #d86;
    color: #f4f4f4;
    border-radius: 6px;
    padding-right: 4px;
    margin-right: 6px;
    padding-left: 4px;
    cursor: copy;
    font-weight: normal;
    display: inline;
    width: auto;
    text-align: left;
    float: inherit;
}

.logs ul b {
    border: solid;
    border-radius: 6px;
    padding-right: 4px;
    border-width: 1px;
    margin-right: 3px;
    padding-left: 4px;
    cursor: pointer;
    color: darkgrey;
}
</style>
</head>
<body>
    <?php
    $action = Tools::getValue('action');
    switch ($action) {
        case 'viewLog' :
            echo getLogsParsed($sf);
            break;
        default :
            echo getDebugForm($sf);
            echo getLogsContent($sf);
            echo getConfigForm($sf);
            echo getRecablage($sf);
            echo getReplayOrdersForm($sf);
            $urlBase = Tools::getCurrentUrlProtocolPrefix() . $_SERVER['HTTP_HOST'];
            
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
                    $nextUrl = $urlBase . '/modules/shoppingfluxexport/utils.php?test_curl=1&index=' . $index;
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
                        $nextUrl = $urlBase . '/modules/shoppingfluxexport/utils.php?test_curl=1&index=' . $index;
                        logDebug('Call received, index = ' . $_GET['index']);
                        logDebug('Going to call : ' . $nextUrl);
                        $curl_response = curl_file_get_contents($nextUrl);
                    }
                }
            }
            
            $idOrder = Tools::getValue('IdOrder');
            $replayXml = Tools::getValue('replayXml');
            if ($idOrder) {
                ob_start();
                $sf->replayOrder((string) Tools::getValue('IdOrder'));
                $result = ob_get_contents();
                ob_end_clean();
                
                echo '<div style="border: 1px solid #CCC; padding: 10px;">
                                    <div><b>' . $sf->l('Execution Result of last order replay:') . '</b></div><br>
                                    <div>' . $result . '</div>
                               </div>';
            }
            if ($replayXml) {
                $replayOrder = @simplexml_load_string($replayXml);
                ?><div style="border: 1px solid #CCC; padding: 10px;">
                    <div>
                        <b><?php echo $sf->l('Execution Result of XML replay :'); ?></b>
                    </div>
                    <br>
                    <div><?php $sf->replayOrder(false, $replayOrder) ?></div>
                </div><?php
            }
            break;
    }
    ?>
</body>
</html>
