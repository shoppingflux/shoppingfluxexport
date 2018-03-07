<?php
/**
 * 2017-2018 ShoppingFlux
 *
 * @author    ShoppingFlux <support@shopping-flux.com>
 * @copyright 2017-2018 ShoppingFlux
 */
define('SF_LOG_CRON', 1);
define('SF_LOG_ORDERS', 2);
define('SF_LOG_WEBSERVICE', 3);
define('SF_LOG_DEBUG', 4);

class SfLogger
{

    /**
     * Singleton
     */
    private static $instance = null;

    /**
     * Log export CRON
     */
    private static $debug = true;

    /**
     * Log orders creations
     */
    private static $debugOrders = true;

    /**
     * Log rotatetime
     */
    private static $logRotateMegaBites = 100;

    protected function __construct()
    {
        if ((int) Configuration::get('SHOPPING_FLUX_ORDERS_DEBUG') || Configuration::get('SHOPPING_FLUX_ORDERS_DEBUG') == 'true') {
            self::$debugOrders = true;
        }
        if ((int) Configuration::get('SHOPPING_FLUX_DEBUG') || Configuration::get('SHOPPING_FLUX_DEBUG') == 'true') {
            self::$debug = true;
        }
    }

    /**
     * Singleton
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new SfLogger();
        }
        
        return self::$instance;
    }

    /**
     * Do a log to a file
     */
    public function log($level, $toLog, $doEcho = false)
    {
        // Check if display is needed
        $logLine = chr(10) . date('d/m/Y h:i:s A') . ' - ' . $toLog;
        if ($doEcho) {
            echo $logLine . '<br />';
        }

        $sf = Module::getInstanceByName('shoppingfluxexport');
        
        // Compute the output file and if we will do a log
        $outputFile = _PS_MODULE_DIR_ . 'shoppingfluxexport/logs/';
        $doLog = false;
        $outputMode = 'a';
        switch ($level) {
            case SF_LOG_CRON:
                $outputFile .= 'cronexport_' . $sf->getTokenValue() . '.txt';
                if (self::$debug) {
                    $doLog = true;
                }
                break;
            case SF_LOG_ORDERS:
                $outputFile .= 'orders_debug_' . $sf->getTokenValue() . '.txt';
                if (self::$debugOrders) {
                    $doLog = true;
                }
                break;
            case SF_LOG_WEBSERVICE:
                $outputFile .= 'callWebService_' . $sf->getTokenValue() . '.txt';
                $doLog = true;
                break;
            case SF_LOG_DEBUG:
                $outputFile .= 'orders_debug_errors_on_' . $sf->getTokenValue() . '.txt';
                $doLog = true;
                break;
            default:
                return;
                break;
        }
        
        // Rotate logs and write to file
        $this->rotateLogFile($outputFile);
        $fp = fopen($outputFile, $outputMode);
        fwrite($fp, $logLine);
        fclose($fp);
    }

    /**
     * Rotates the logs based on log size
     * @param  string $fileName The file name to rotate if needed
     */
    protected function rotateLogFile($fileName)
    {
        if (self::$logRotateMegaBites) {

            if (! file_exists($fileName)) {
                return;
            }

            $fileSizeMbs = filesize($fileName) / 1024 / 1024;
            if ($fileSizeMbs >= (self::$logRotateMegaBites)) {
                $filePieces = explode('.', $fileName);
                $extension = $filePieces['1'];
                $rotatedLogFile = $filePieces['0'] . '_last_' . self::$logRotateMegaBites . 'MBs.' . $extension;
                if (file_exists($rotatedLogFile)) {
                    unlink($rotatedLogFile);
                }
                // Rename current log file
                rename($fileName, $rotatedLogFile);
            }
        }
    }

    /**
     * empty log file
     */
    public function emptyLogCron()
    {
        if ($this->debug) {
            $outputFile = _PS_MODULE_DIR_ . 'shoppingfluxexport/logs/cronexport_' . Configuration::get('SHOPPING_FLUX_TOKEN') . '.txt';
            unlink($outputFile);
        }
    }
}
