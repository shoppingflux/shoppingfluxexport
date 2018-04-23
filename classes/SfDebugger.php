<?php

/**
 * 2017-2018 ShoppingFlux
 *
 * @author    ShoppingFlux <support@shopping-flux.com>
 * @copyright 2017-2018 ShoppingFlux
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
        return ((int) Configuration::get('SHOPPING_FLUX_DEBUG_ERRORS') || Configuration::get('SHOPPING_FLUX_DEBUG_ERRORS') == 'true') ? true : false;
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
