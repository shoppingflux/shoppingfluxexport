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
    private static $logRotateMegaBites = 20;

    /**
     * Maximal number of rotation of log files before being removed
     * @var integer
     */
    private static $maxRotateIteration = 10;

    protected function __construct()
    {
        if ((int) Configuration::get('SHOPPING_FLUX_ORDERS_DEBUG')
            || Configuration::get('SHOPPING_FLUX_ORDERS_DEBUG') == 'true') {
            self::$debugOrders = true;
        }
        if ((int) Configuration::get('SHOPPING_FLUX_DEBUG')
            || Configuration::get('SHOPPING_FLUX_DEBUG') == 'true') {
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
        $outputMode = 'a';
        switch ($level) {
            case SF_LOG_CRON:
                $outputFile .= 'cronexport_' . $sf->getTokenValue() . '.txt';
                break;
            case SF_LOG_ORDERS:
                $outputFile .= 'orders_debug_' . $sf->getTokenValue() . '.txt';
                break;
            case SF_LOG_WEBSERVICE:
                $outputFile .= 'callWebService_' . $sf->getTokenValue() . '.txt';
                break;
            case SF_LOG_DEBUG:
                $outputFile .= 'orders_debug_errors_on_' . $sf->getTokenValue() . '.txt';
                break;
            default:
                return;
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
            if (!file_exists($fileName)) {
                return;
            }

            $fileSizeMbs = filesize($fileName) / 1024 / 1024;
            if ($fileSizeMbs >= (self::$logRotateMegaBites)) {
                // Base file
                $baseFile = Tools::substr($fileName, 0, strrpos($fileName, '.'));
                // The file extension (.txt, .log...)
                $extension = Tools::substr($fileName, strrpos($fileName, '.'));
                // Compose the new name of the file
                $rotatedLogFileBase = $baseFile . '_' . self::$logRotateMegaBites . 'mb';

                // Get files and number of files already rotated
                $filesRotated = glob($rotatedLogFileBase."*".$extension);
                $nbAlreadyRotated = count($filesRotated);

                if ($nbAlreadyRotated >= self::$maxRotateIteration) {
                    // We exhausted the number of combination
                    
                    // We order the files by last modified
                    array_multisort(
                        array_map('filemtime', $filesRotated),
                        SORT_NUMERIC,
                        SORT_ASC,
                        $filesRotated
                    );

                    // We remove the oldest file
                    unlink($filesRotated[0]);
                }

                // Build the complete file name with the iteration and the extension
                $rotatedLogFile = $rotatedLogFileBase."_".date("Y-m-d-H-i-s").$extension;

                // Rename current log file
                rename($fileName, $rotatedLogFile);
            }
        }
    }
}
