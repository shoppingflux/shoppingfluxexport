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
    private static $logRotateHours = 24;

    private function __construct()
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

        $sf = new ShoppingFluxExport();
        
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
     * Rotates the logs
     */
    private function rotateLogFile($fileName)
    {
        if (self::$logRotateHours) {
            // file age
            if (! file_exists($fileName)) {
                return;
            }
            $now = time();
            $dateGeneration = filemtime($fileName);
            $age = ($now - $dateGeneration);
            if ($age > (self::$logRotateHours * 3600)) {
                $filePieces = explode('.', $fileName);
                $extension = $filePieces['1'];
                if (file_exists($filePieces['0'] . '_last_' . self::$logRotateHours . 'hours' . $extension)) {
                    unlink($filePieces['0'] . '_500' . $extension);
                    // Rename current log file
                    rename($fileName, $filePieces['0'] . '_last_' . self::$logRotateHours . 'hours' . $extension);
                } else {
                    rename($fileName, $filePieces['0'] . '_last_' . self::$logRotateHours . 'hours' . $extension);
                }
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
