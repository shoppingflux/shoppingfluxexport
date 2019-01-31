<?php
/**
 * 2007-2019 PrestaShop SA and Contributors
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
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class SfDebugger
{

    /**
     * Singleton
     */
    private static $instance = null;

    /**
     * Log PHP errors and warnings
     */
    private static $debugOrdersErrors = false;

    protected function __construct()
    {
        if ($this->isDebugEnabled()) {
            self::$debugOrdersErrors = true;
        }
    }

    public function isDebugEnabled()
    {
        return ((int) Configuration::get('SHOPPING_FLUX_DEBUG_ERRORS')
            || Configuration::get('SHOPPING_FLUX_DEBUG_ERRORS') == 'true') ? true : false;
    }

    /**
     * Singleton
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new SfDebugger();
        }
        
        return self::$instance;
    }

    /**
     * Set errors to on
     */
    public function startDebug()
    {
        if (self::$debugOrdersErrors) {
            ob_start();
            @ini_set('display_errors', 'on');
            @error_reporting(E_ALL | E_STRICT);
        }
    }

    /**
     * Sets errors to off and log the output
     */
    public function endDebug($doEchoLog = false)
    {
        if (self::$debugOrdersErrors) {
            $output = ob_get_contents();
            ob_end_clean();
            @ini_set('display_errors', 'off');
            SfLogger::getInstance()->log(SF_LOG_DEBUG, $output);
            if ($doEchoLog) {
                echo $output;
            }
        }
    }
    
    /**
     * Reset amount in table order_payment
     */
    public static function recablageOrderPayment()
    {
        $sql = 'SELECT `id_order`
            FROM `' . _DB_PREFIX_ . 'message`
            WHERE `message` LIKE "%Numéro de commande%"
            ORDER BY `id_order` DESC
            LIMIT 10
        ';
        $results = Db::getInstance()->executeS($sql);
        
        foreach ($results as $result) {
            $id_order = $result['id_order'];
            $order = new Order($id_order);
            
            $updatePriceSQL = "UPDATE `" . _DB_PREFIX_ . "order_payment`
                SET `amount` = '" . $order->total_paid . "'
                WHERE `order_reference` = '$order->reference'
            ";
            Db::getInstance()->execute($updatePriceSQL);
        }
    }
}
