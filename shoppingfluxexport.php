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

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once(dirname(__FILE__).'/sfpayment.php');

class ShoppingFluxExport extends Module
{
    private $default_country = null;
    private $_html = '';
    
    private $debug = true;

    public function __construct()
    {
        $this->name = 'shoppingfluxexport';
        $this->tab = 'smart_shopping';
        $this->version = '4.2.0';
        $this->author = 'PrestaShop';
        $this->limited_countries = array('fr', 'us');
        $this->module_key = '08b3cf6b1a86256e876b485ff9bd4135';

        parent::__construct();

        $this->displayName = $this->l('Shopping Feed Official');
        $this->description = $this->l('Export all your products to Google Shopping, eBay, Amazon, Rakuten, etc...');
        $this->confirmUninstall = $this->l('Delete this plugin ?');

        $id_default_country = Configuration::get('PS_COUNTRY_DEFAULT');
        $this->default_country = new Country($id_default_country);
        
        // Set default passes if not existing
        $productsToBeTreated = Configuration::get('SHOPPING_FLUX_PASSES');
        if (empty($productsToBeTreated) || !isset($productsToBeTreated)) {
            Configuration::updateValue('SHOPPING_FLUX_PASSES', '200');
        }
    }

    public function install()
    {
        return (parent::install() && $this->_initHooks() && $this->_initConfig());
    }

    /* REGISTER HOOKS */
    private function _initHooks()
    {
        if (!$this->registerHook('postUpdateOrderStatus') ||
                !$this->registerHook('backOfficeTop') ||
                !$this->registerHook('actionProductAdd') ||
                !$this->registerHook('actionObjectAddAfter') ||
                !$this->registerHook('top')) {
            return false;
        }

        return true;
    }

    /* SET DEFAULT CONFIGURATION */
    private function _initConfig()
    {
        //Avoid servers IPs
        Db::getInstance()->Execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'customer_ip` (
            `id_customer_ip` int(10) unsigned NOT null AUTO_INCREMENT,
            `id_customer` int(10) unsigned NOT null,
            `ip` varchar(32) DEFAULT null,
            PRIMARY KEY (`id_customer_ip`),
            KEY `idx_id_customer` (`id_customer`)
            ) ENGINE='._MYSQL_ENGINE_.'  DEFAULT CHARSET=utf8;');


        if (version_compare(_PS_VERSION_, '1.5', '>') && Shop::isFeatureActive()) {
            foreach (Shop::getShops() as $shop) {
                if (method_exists('ImageType', 'getFormatedName')) {
                    if (!Configuration::updateValue('SHOPPING_FLUX_TOKEN', md5(rand()), false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_CANCELED', Configuration::get('PS_OS_CANCELED'), false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_SHIPPED', Configuration::get('PS_OS_SHIPPING'), false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_IMAGE', ImageType::getFormatedName('thickbox'), false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_CARRIER', Configuration::get('PS_CARRIER_DEFAULT'), false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_TRACKING', 'checked', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_ORDERS', 'checked', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_STATUS_SHIPPED', 'checked', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_STATUS_CANCELED', '', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_FDG', '', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_REF', '', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_LOGIN', '', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_INDEX', 'http://' . $shop['domain'] . $shop['uri'], false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_STOCKS', '', false, null, $shop['id_shop'] ||
                        !Configuration::updateValue('SHOPPING_FLUX_PASSES', '300', false, null, $shop['id_shop']))
                    ) {
                        return false;
                    }
                } else {
                    if (!Configuration::updateValue('SHOPPING_FLUX_TOKEN', md5(rand()), false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_CANCELED', Configuration::get('PS_OS_CANCELED'), false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_SHIPPED', Configuration::get('PS_OS_SHIPPING'), false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_IMAGE', '', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_CARRIER', Configuration::get('PS_CARRIER_DEFAULT'), false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_TRACKING', 'checked', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_ORDERS', 'checked', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_STATUS_SHIPPED', 'checked', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_STATUS_CANCELED', '', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_FDG', '', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_REF', '', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_LOGIN', '', false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_INDEX', 'http://' . $shop['domain'] . $shop['uri'], false, null, $shop['id_shop']) ||
                        !Configuration::updateValue('SHOPPING_FLUX_STOCKS', '', false, null, $shop['id_shop'] ||
                        !Configuration::updateValue('SHOPPING_FLUX_PASSES', '300', false, null, $shop['id_shop']))
                    ) {
                        return false;
                    }
                }
            }
        } else {
            if (method_exists('ImageType', 'getFormatedName')) {
                if (!Configuration::updateValue('SHOPPING_FLUX_TOKEN', md5(rand())) ||
                    !Configuration::updateValue('SHOPPING_FLUX_CANCELED', Configuration::get('PS_OS_CANCELED')) ||
                    !Configuration::updateValue('SHOPPING_FLUX_SHIPPED', Configuration::get('PS_OS_SHIPPING')) ||
                    !Configuration::updateValue('SHOPPING_FLUX_IMAGE', ImageType::getFormatedName('thickbox')) ||
                    !Configuration::updateValue('SHOPPING_FLUX_CARRIER', Configuration::get('PS_CARRIER_DEFAULT')) ||
                    !Configuration::updateValue('SHOPPING_FLUX_TRACKING', 'checked') ||
                    !Configuration::updateValue('SHOPPING_FLUX_ORDERS', 'checked') ||
                    !Configuration::updateValue('SHOPPING_FLUX_STATUS_SHIPPED', 'checked') ||
                    !Configuration::updateValue('SHOPPING_FLUX_STATUS_CANCELED', '') ||
                    !Configuration::updateValue('SHOPPING_FLUX_LOGIN', '') ||
                    !Configuration::updateValue('SHOPPING_FLUX_FDG', '') ||
                    !Configuration::updateValue('SHOPPING_FLUX_REF', '') ||
                    !Configuration::updateValue('SHOPPING_FLUX_INDEX', 'http://'.$shop['domain'].$shop['uri']) ||
                    !Configuration::updateValue('SHOPPING_FLUX_STOCKS') ||
                    !Configuration::updateValue('SHOPPING_FLUX_PASSES', '300')) {
                    return false;
                }
            } else {
                if (!Configuration::updateValue('SHOPPING_FLUX_TOKEN', md5(rand())) ||
                    !Configuration::updateValue('SHOPPING_FLUX_CANCELED', Configuration::get('PS_OS_CANCELED')) ||
                    !Configuration::updateValue('SHOPPING_FLUX_SHIPPED', Configuration::get('PS_OS_SHIPPING')) ||
                    !Configuration::updateValue('SHOPPING_FLUX_IMAGE', '') ||
                    !Configuration::updateValue('SHOPPING_FLUX_CARRIER', Configuration::get('PS_CARRIER_DEFAULT')) ||
                    !Configuration::updateValue('SHOPPING_FLUX_TRACKING', 'checked') ||
                    !Configuration::updateValue('SHOPPING_FLUX_ORDERS', 'checked') ||
                    !Configuration::updateValue('SHOPPING_FLUX_STATUS_SHIPPED', 'checked') ||
                    !Configuration::updateValue('SHOPPING_FLUX_STATUS_CANCELED', '') ||
                    !Configuration::updateValue('SHOPPING_FLUX_LOGIN', '') ||
                    !Configuration::updateValue('SHOPPING_FLUX_FDG', '') ||
                    !Configuration::updateValue('SHOPPING_FLUX_REF', '') ||
                    !Configuration::updateValue('SHOPPING_FLUX_INDEX', 'http://'.$shop['domain'].$shop['uri']) ||
                    !Configuration::updateValue('SHOPPING_FLUX_STOCKS') ||
                    !Configuration::updateValue('SHOPPING_FLUX_PASSES', '300')) {
                    return false;
                }
            }
        }

        return true;
    }

    public function uninstall()
    {
        if (!Configuration::deleteByName('SHOPPING_FLUX_TOKEN') ||
                !Configuration::deleteByName('SHOPPING_FLUX_CANCELED') ||
                !Configuration::deleteByName('SHOPPING_FLUX_SHIPPED') ||
                !Configuration::deleteByName('SHOPPING_FLUX_IMAGE') ||
                !Configuration::deleteByName('SHOPPING_FLUX_TRACKING') ||
                !Configuration::deleteByName('SHOPPING_FLUX_ORDERS') ||
                !Configuration::deleteByName('SHOPPING_FLUX_STATUS_SHIPPED') ||
                !Configuration::deleteByName('SHOPPING_FLUX_STATUS_CANCELED') ||
                !Configuration::deleteByName('SHOPPING_FLUX_LOGIN') ||
                !Configuration::deleteByName('SHOPPING_FLUX_FDG') ||
                !Configuration::deleteByName('SHOPPING_FLUX_REF') ||
                !Configuration::deleteByName('SHOPPING_FLUX_INDEX') ||
                !Configuration::deleteByName('SHOPPING_FLUX_STOCKS') ||
                !Configuration::deleteByName('SHOPPING_FLUX_SHIPPING_MATCHING') ||
                !Configuration::deleteByName('SHOPPING_FLUX_PASSES') ||
                !parent::uninstall()) {
            return false;
        }

        return true;
    }

    public function getContent()
    {
        $this->setREF();
        $this->setFDG();
        
        $status_xml = $this->_checkToken();
        $status = is_object($status_xml) ? $status_xml->Response->Status : '';
        $price = is_object($status_xml) ? (float)$status_xml->Response->Price : 0;

        switch ($status) {
            case 'Client':
                $this->_html .= $this->_clientView();
                //we do this here for retro compatibility
                $this->_setShoppingFeedId();
                break;
            case 'Prospect':
                $this->_html .= $this->displayConfirmation($this->l('You are now registered for your free trial. Our team will contact you soon.'));
                // No break, we want the code below to be executed
            case 'New':
            default:
                $this->_html .= $this->_defaultView($price);
                break;
        }

        if (!in_array('curl', get_loaded_extensions())) {
            $this->_html .= '<br/><strong>'.$this->l('You have to install Curl extension to use this plugin. Please contact your IT team.').'</strong>';
        } else {
            Configuration::updateValue('SHOPPINGFLUXEXPORT_CONFIGURED', true); // SHOPPINGFLUXEXPORT_CONFIGURATION_OK
        }
        
        foreach (Shop::getShops() as $shop) {
            $lockFile = dirname(__FILE__).'/cron_'.$shop['id_shop'].'.lock';
            if (file_exists($lockFile)) {
                // Remove lock
                $fp = fopen($lockFile, 'r+');
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
        
        
        return $this->_html;
    }

    /* Check wether the Token is known by Shopping Flux */
    private function _checkToken()
    {
        return $this->_callWebService('IsClient');
    }

    /* Default view when site isn't in Shopping Flux DB */
    private function _defaultView($price = 0)
    {
        //uri feed
        if (version_compare(_PS_VERSION_, '1.5', '>') && Shop::isFeatureActive()) {
            $shop = Context::getContext()->shop;
            $base_uri = Tools::getCurrentUrlProtocolPrefix().$shop->domain.$shop->physical_uri.$shop->virtual_uri;
            $uri = Tools::getCurrentUrlProtocolPrefix().$shop->domain.$shop->physical_uri.$shop->virtual_uri.'modules/shoppingfluxexport/flux.php?token='.Configuration::get('SHOPPING_FLUX_TOKEN');
        } else {
            $base_uri = Tools::getCurrentUrlProtocolPrefix().Tools::getHttpHost().__PS_BASE_URI__;
            $uri = Tools::getCurrentUrlProtocolPrefix().Tools::getHttpHost().__PS_BASE_URI__.'modules/shoppingfluxexport/flux.php?token='.Configuration::get('SHOPPING_FLUX_TOKEN');
        }

        //uri images
        $uri_img = Tools::getCurrentUrlProtocolPrefix().Tools::getHttpHost().__PS_BASE_URI__.'modules/shoppingfluxexport/views/img/';
        //owner object
        $owner = new Employee($this->context->cookie->id_employee);
        //post process
        $send_mail = Tools::getValue('send_mail');
        if (isset($send_mail) && $send_mail != null) {
            $this->sendMail();
        }

        //get fieldset depending on the country
        $country = $this->default_country->iso_code == 'FR' ? 'fr' : 'us';

        $html = '<p style="text-align:center"><img style="margin:20px; width: 250px" src="'.Tools::safeOutput($base_uri).'modules/shoppingfluxexport/views/img/logo_'.$country.'.jpg" /></p>';

        $html .= '<div style="width:50%; float:left"><fieldset>
        <legend>'.$this->l('Information(s)').'</legend>
        <p style="text-align:center; margin:10px 0 30px 0; font-weight: bold" ><a style="padding:10px 20px; font-size:1.5em" target="_blank" href="https://register.shopping-feed.com/sign/prestashop?phone='.urlencode(Tools::safeOutput(Configuration::get('PS_SHOP_PHONE'))).'&email='.Tools::safeOutput(Configuration::get('PS_SHOP_EMAIL')).'&feed='.Tools::safeOutput($uri).'&lang='.Tools::strtolower($this->default_country->iso_code).'" onclick="" value="'.$this->l('Send').'" class="button">'.$this->l('Register Now!').'</a></p>
        <h2><b>'.$this->l('Shopping Feed exports your products to the largest marketplaces in the world, all from a single intuitive platform.').'</b> '.$this->l('Through our free setup and expert support, we help thousands of storefronts increase their sales and visibility.').'</h2>
        <br/><p style="line-height:2em;">
        <b>'.$this->l('Put your feeds to work:').' </b>'.$this->l('A single platform to manage your products and sales on the world\'s marketplaces.').'<br />
        <b>'.$this->l('Set it and forget it:').' </b>'.$this->l('Automated order processes for each marketplace channel you sell on, quadrupling your revenue, and not your workload.').'<br />
        <b>'.$this->l('Try Before You Buy:'). '</b> '.$this->l('Expert Channel Setup is always free on Shopping Feed, giving you risk-free access to your brand new channel before becoming a member.').' <br />
        </p><br/>
        <ol style="line-height:2em; list-style-type:circle; padding: 0 0 0 20px">
        <li>'.$this->l('Optimize your channels, and calculate realtime Return On Investment for all the leading comparison shopping engines like Google Shopping, Ratuken, shopping.com, NextTag, ShopZilla and more.').'</li>
        <li>'.$this->l('Connect your storefront to all the major marketplaces like eBay, Amazon, Sears and 11 Main, while managing your pricing, inventory, and merchandising through a single, intuitive platform.').'</li>
        <li>'.$this->l('Prepare for an evolving ecosystem: New features, tools, and integrations are being created every month, at no extra cost.').'</li>
        <li>'.$this->l('Be seen: With over 50 different marketplaces and shopping engines under one roof, Shopping Feed helps you find your right audience.').'</li>
        </ol><br/>
        <h3>'.$this->l('With over 1400 Members worldwide, helping them achieve over $13 Million in monthly revenue,').' <b>'.$this->l('Lets us help you put your feeds to work.').'</b></h3>
        </fieldset></div>';

        $html .= '<div style="width:45%; float: left; padding: 10px 0 0 10px"><img src="http://'.Tools::getHttpHost().__PS_BASE_URI__.'modules/shoppingfluxexport/views/img/ad.png"></div>';

        $html .= '<div style="clear:both"></div>';

        return $html;
    }

    /* View when site is client */
    private function _clientView()
    {
        $this->_treatForm();

        $configuration = Configuration::getMultiple(array('SHOPPING_FLUX_TOKEN', 'SHOPPING_FLUX_TRACKING',
                    'SHOPPING_FLUX_ORDERS', 'SHOPPING_FLUX_STATUS_SHIPPED', 'SHOPPING_FLUX_STATUS_CANCELED', 'SHOPPING_FLUX_LOGIN',
                    'SHOPPING_FLUX_STOCKS', 'SHOPPING_FLUX_INDEX', 'PS_LANG_DEFAULT', 'SHOPPING_FLUX_CARRIER', 'SHOPPING_FLUX_IMAGE',
                    'SHOPPING_FLUX_SHIPPED', 'SHOPPING_FLUX_CANCELED', 'SHOPPING_FLUX_SHIPPING_MATCHING', 'SHOPPING_FLUX_PASSES'));
        
        
        // Retrieve custom fields from override that can be in products
        $fields = $this->getOverrideFields();
        foreach ($fields as $key => $fieldname) {
            $configuration['SHOPPING_FLUX_CUSTOM_'.$fieldname] = Configuration::get('SHOPPING_FLUX_CUSTOM_'.$fieldname);
        }

        $html = $this->_getFeedContent();
        $html .= $this->_getParametersContent($configuration);
        $html .= $this->_getAdvancedParametersContent($configuration);
        $html .= $this->defaultAdvancedParameterInformationView($configuration);
        $html .= $this->defaultInformationView($configuration);

        return $html;
    }

    /* Fieldset for params */
    private function _getParametersContent($configuration)
    {
        return '<form method="post" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'">
                    <fieldset>
                        <legend>'.$this->l('Parameters').'</legend>
                        <p><label>Login '.$this->l('Shopping Feed').' : </label><input type="text" name="SHOPPING_FLUX_LOGIN" value="'.Tools::safeOutput($configuration['SHOPPING_FLUX_LOGIN']).'"/></p>
                        <p><label>Token '.$this->l('Shopping Feed').' : </label><input type="text" name="SHOPPING_FLUX_TOKEN" value="'.Tools::safeOutput($configuration['SHOPPING_FLUX_TOKEN']).'" style="width:auto"/></p>
                        <p><label>'.$this->l('Order tracking').' : </label><input type="checkbox" name="SHOPPING_FLUX_TRACKING" '.Tools::safeOutput($configuration['SHOPPING_FLUX_TRACKING']).'/> '.$this->l('orders coming from shopbots will be tracked').'.</p>
                        <p><label>'.$this->l('Order importation').' : </label><input type="checkbox" name="SHOPPING_FLUX_ORDERS" '.Tools::safeOutput($configuration['SHOPPING_FLUX_ORDERS']).'/> '.$this->l('orders coming from marketplaces will be imported').'.</p>
                        <p><label>'.$this->l('Order shipment').' : </label><input type="checkbox" name="SHOPPING_FLUX_STATUS_SHIPPED" '.Tools::safeOutput($configuration['SHOPPING_FLUX_STATUS_SHIPPED']).'/> '.$this->l('orders shipped on your Prestashop will be shipped on marketplaces').'.</p>
                        <p><label>'.$this->l('Order cancellation').' : </label><input type="checkbox" name="SHOPPING_FLUX_STATUS_CANCELED" '.Tools::safeOutput($configuration['SHOPPING_FLUX_STATUS_CANCELED']).'/> '.$this->l('orders shipped on your Prestashop will be canceled on marketplaces').'.</p>
                        <p><label>'.$this->l('Sync stock and orders').' : </label><input type="checkbox" name="SHOPPING_FLUX_STOCKS" '.Tools::safeOutput($configuration['SHOPPING_FLUX_STOCKS']).'/> '.$this->l('every stock and price movement will be transfered to marletplaces').'.</p>
                        <p><label>'.$this->l('Default carrier').' : </label>'.$this->_getCarriersSelect($configuration, $configuration['SHOPPING_FLUX_CARRIER']).'</p>
                        <p><label>'.$this->l('Default image type').' : </label>'.$this->_getImageTypeSelect($configuration).'</p>
                        <p><label>'.$this->l('Call marketplace for shipping when order state become').' : </label>'.$this->_getOrderStateShippedSelect($configuration).'</p>
                        <p style="margin-top:20px"><label>'.$this->l('Call marketplace for cancellation when order state become').' : </label>'.$this->_getOrderStateCanceledSelect($configuration).'</p>'
                         .$this->getOverrideFieldsContent($configuration).'
                        <p style="margin-top:20px"><input type="submit" value="'.$this->l('Update').'" name="rec_config" class="button"/></p>
                    </fieldset>
                </form>';
    }

    /**
     * Bloc that displays in configurato
     * @param unknown $configuration
     */
    private function _getAdvancedParametersContent($configuration)
    {
        if (!in_array('curl', get_loaded_extensions())) {
            return;
        }

        $sf_carriers_xml = $this->_callWebService('GetCarriers');

        if (!isset($sf_carriers_xml->Response->Carriers->Carrier[0])) {
            return;
        }

        $sf_carriers = array();

        foreach ($sf_carriers_xml->Response->Carriers->Carrier as $carrier) {
            $sf_carriers[] = (string)$carrier;
        }

        $html = '<h3>'.$this->l('Advanced Parameters').'</h3>
            <form method="post" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'">
                <fieldset>
                    <legend>'.$this->l('Carriers Matching').'</legend>
                    <p>'.$this->l('Please see below carriers coming from your markeplaces managed on Shopping Feed. You can match them to your Prestashop carriers').'</p>';

        $actual_configuration = unserialize($configuration['SHOPPING_FLUX_SHIPPING_MATCHING']);

        foreach ($sf_carriers as $sf_carrier) {
            $actual_value = isset($actual_configuration[base64_encode(Tools::safeOutput($sf_carrier))]) ? $actual_configuration[base64_encode(Tools::safeOutput($sf_carrier))] : $configuration['SHOPPING_FLUX_CARRIER'];
            $html .= '<p><label>'.Tools::safeOutput($sf_carrier).' : </label>'.$this->_getCarriersSelect($configuration, $actual_value, 'MATCHING['.base64_encode(Tools::safeOutput($sf_carrier)).']').'</p>';
        }

        $html .= '<p style="margin-top:20px"><input type="submit" value="'.$this->l('Update').'" name="rec_shipping_config" class="button"/></p>
                </fieldset>
            </form>';

        return $html;
    }

    private function _getCarriersSelect($configuration, $actual_value, $name = 'SHOPPING_FLUX_CARRIER')
    {
        $html = '<select name="'.Tools::safeOutput($name).'">';

        foreach (Carrier::getCarriers($configuration['PS_LANG_DEFAULT'], true, false, false, null, 5) as $carrier) {
            $selected = (int)$actual_value === (int)$carrier['id_reference'] ? 'selected = "selected"' : '';
            $html .= '<option value="'.(int)$carrier['id_reference'].'" '.$selected.'>'.Tools::safeOutput($carrier['name']).'</option>';
        }

        $html .= '</select>';

        return $html;
    }

    private function _getImageTypeSelect($configuration)
    {
        $html = '<select name="SHOPPING_FLUX_IMAGE">';

        foreach (ImageType::getImagesTypes() as $imagetype) {
            $selected = $configuration['SHOPPING_FLUX_IMAGE'] == $imagetype['name'] ? 'selected = "selected"' : '';
            $html .= '<option value="'.$imagetype['name'].'" '.$selected.'>'.Tools::safeOutput($imagetype['name']).'</option>';
        }

        $html .= '</select>';

        return $html;
    }

    private function _getOrderStateShippedSelect($configuration)
    {
        $html = '<select name="SHOPPING_FLUX_SHIPPED">';

        foreach (OrderState::getOrderStates($configuration['PS_LANG_DEFAULT']) as $orderState) {
            $selected = (int)$configuration['SHOPPING_FLUX_SHIPPED'] === (int)$orderState['id_order_state'] ? 'selected = "selected"' : '';
            $html .= '<option value="'.$orderState['id_order_state'].'" '.$selected.'>'.Tools::safeOutput($orderState['name']).'</option>';
        }

        $html .= '</select>';

        return $html;
    }

    private function _getOrderStateCanceledSelect($configuration)
    {
        $html = '<select name="SHOPPING_FLUX_CANCELED">';

        foreach (OrderState::getOrderStates($configuration['PS_LANG_DEFAULT']) as $orderState) {
            $selected = (int)$configuration['SHOPPING_FLUX_CANCELED'] === (int)$orderState['id_order_state'] ? 'selected = "selected"' : '';
            $html .= '<option value="'.$orderState['id_order_state'].'" '.$selected.'>'.Tools::safeOutput($orderState['name']).'</option>';
        }

        $html .= '</select>';

        return $html;
    }

    /* Fieldset for feed URI */
    private function _getFeedContent()
    {
        //uri feed
        if (version_compare(_PS_VERSION_, '1.5', '>') && Shop::isFeatureActive()) {
            $shop = Context::getContext()->shop;
            $base_uri = Tools::getCurrentUrlProtocolPrefix().$shop->domain.$shop->physical_uri.$shop->virtual_uri;
        } else {
            $base_uri = Tools::getCurrentUrlProtocolPrefix().Tools::getHttpHost().__PS_BASE_URI__;
        }

        $uri = $base_uri.'modules/shoppingfluxexport/flux.php?token='.Configuration::get('SHOPPING_FLUX_TOKEN');
        $logo = $this->default_country->iso_code == 'FR' ? 'fr' : 'us';

        return '
        <img style="margin:10px; width:250px" src="'.Tools::safeOutput($base_uri).'modules/shoppingfluxexport/views/img/logo_'.$logo.'.jpg" />
        <fieldset>
            <legend>'.$this->l('Your feeds').'</legend>
            <p>
                <a href="'.Tools::safeOutput($uri).'" target="_blank">
                    '.Tools::safeOutput($uri).'
                </a>
            </p>
        </fieldset>
        <br/>';
    }

    /* Form record */
    private function _treatForm()
    {
        $rec_config = Tools::getValue('rec_config');
        $rec_shipping_config = Tools::getValue('rec_shipping_config');
        
        $rec_config_adv = Tools::getValue('rec_config_adv');

        if ((isset($rec_config) && $rec_config != null)) {
            $configuration = Configuration::getMultiple(array('SHOPPING_FLUX_TRACKING',
                        'SHOPPING_FLUX_ORDERS', 'SHOPPING_FLUX_STATUS_SHIPPED', 'SHOPPING_FLUX_STATUS_CANCELED',
                        'SHOPPING_FLUX_LOGIN', 'SHOPPING_FLUX_STOCKS', 'SHOPPING_FLUX_CARRIER', 'SHOPPING_FLUX_IMAGE',
                        'SHOPPING_FLUX_CANCELED', 'SHOPPING_FLUX_SHIPPED'));

            foreach ($configuration as $key => $val) {
                    $value = Tools::getValue($key, '');
                    Configuration::updateValue($key, $value == 'on' ? 'checked' : $value);
            }
            
            // Check if they are custom fields (Product class override)
            $theyAreCustomFields = false;
            foreach ($_POST as $field => $value) {
                if (strpos($field, 'SHOPPING_FLUX_CUSTOM_') !== false) {
                    $theyAreCustomFields = true;
                    break;
                }
            }
            
            // If they are custom field, save their configuration
            if ($theyAreCustomFields) {
                $fields = $this->getOverrideFields();
                foreach ($fields as $key => $fieldname) {
                    $valueName = 'SHOPPING_FLUX_CUSTOM_'.$fieldname;
                    Configuration::updateValue($valueName, Tools::getValue($valueName) == '1' ? '1' : '0');
                }
            }
        } elseif (isset($rec_shipping_config) && $rec_shipping_config != null) {
            Configuration::updateValue('SHOPPING_FLUX_SHIPPING_MATCHING', serialize(Tools::getValue('MATCHING')));
        } elseif (isset($rec_config_adv) && $rec_config_adv != null) {
            $configuration = Configuration::getMultiple(array('SHOPPING_FLUX_PASSES'));
            
            $passes = Tools::getValue('SHOPPING_FLUX_PASSES');
            if (empty($passes) ||
                $passes == 0) {
                    Configuration::updateValue('SHOPPING_FLUX_PASSES', '200');
            } elseif (!empty($passes) ||
                is_int($passes)) {
                $passValue = (int)Tools::getValue('SHOPPING_FLUX_PASSES');
                if ($passValue == 0) {
                    $passValue = 200;
                }
                Configuration::updateValue('SHOPPING_FLUX_PASSES', $passValue);
            }
        }
    }

    /* Send mail to PS and Shopping Flux */
    private function sendMail()
    {
        $this->_html .= $this->displayConfirmation($this->l('You are now registered for your free trial. Our team will contact you soon.')).'
            <img src="http://www.prestashop.com/partner/shoppingflux/image.php?site='.Tools::safeOutput(Tools::getValue('site')).'&nom='.Tools::safeOutput(Tools::getValue('nom')).'&prenom='.Tools::safeOutput(Tools::getValue('prenom')).'&email='.Tools::safeOutput(Tools::getValue('email')).'&telephone='.Tools::safeOutput(Tools::getValue('telephone')).'&flux='.Tools::safeOutput(Tools::getValue('flux')).'" border="0" />';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<AddProspect>';
        $xml .= '<LastName><![CDATA['.Tools::safeOutput(Tools::getValue('nom')).']]></LastName>';
        $xml .= '<FirstName><![CDATA['.Tools::safeOutput(Tools::getValue('prenom')).']]></FirstName>';
        $xml .= '<Site><![CDATA['.Tools::safeOutput(Tools::getValue('site')).']]></Site>';
        $xml .= '<Email><![CDATA['.Tools::safeOutput(Tools::getValue('email')).']]></Email>';
        $xml .= '<Phone><![CDATA['.Tools::safeOutput(Tools::getValue('telephone')).']]></Phone>';
        $xml .= '<Feed><![CDATA['.Tools::safeOutput(Tools::getValue('flux')).']]></Feed>';
        $xml .= '<Code><![CDATA['.Tools::safeOutput(Tools::getValue('code')).']]></Code>';
        $xml .= '<Lang><![CDATA['.$this->default_country->iso_code.']]></Lang>';
        $xml .= '</AddProspect>';

        if (in_array('curl', get_loaded_extensions())) {
            $this->_callWebService('AddProspectPrestashop', $xml);
        }
    }

    /* Clean XML tags */
    private function clean($string)
    {
        return str_replace("\r\n", '', strip_tags($string));
    }

    /* Feed content */
    
    private function getSimpleProducts($id_lang, $limit_from, $limit_to)
    {
        if (version_compare(_PS_VERSION_, '1.5', '>')) {
            $context = Context::getContext();

            if (!in_array($context->controller->controller_type, array('front', 'modulefront'))) {
                $front = false;
            } else {
                $front = true;
            }

            $sql = 'SELECT p.`id_product`, pl.`name`
                FROM `'._DB_PREFIX_.'product` p
                '.Shop::addSqlAssociation('product', 'p').'
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product` '.Shop::addSqlRestrictionOnLang('pl').')
                WHERE pl.`id_lang` = '.(int)$id_lang.' AND product_shop.`active`= 1 
                AND product_shop.`available_for_order`= 1 AND p.`cache_is_pack` = 0
                '.($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '').'
                ORDER BY pl.`name`';

            if ($limit_from !== false) {
                $sql .= ' LIMIT '.(int)$limit_from.', '.(int)$limit_to;
            }
        } else {
            $sql = 'SELECT p.`id_product`, pl.`name`
                FROM `'._DB_PREFIX_.'product` p
                LEFT JOIN `'._DB_PREFIX_.'product_lang` pl ON (p.`id_product` = pl.`id_product`)
                WHERE pl.`id_lang` = '.(int)($id_lang).' 
                AND p.`active`= 1 AND p.`available_for_order`= 1 AND p.`cache_is_pack` = 0
                ORDER BY pl.`name`';
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    private function countProducts()
    {
        if (version_compare(_PS_VERSION_, '1.5', '>')) {
            $context = Context::getContext();

            if (!in_array($context->controller->controller_type, array('front', 'modulefront'))) {
                $front = false;
            } else {
                $front = true;
            }

            $sql_association = Shop::addSqlAssociation('product', 'p');
            $table = $sql_association ? 'product'.'_shop' : 'p';

            $sql = 'SELECT COUNT(p.`id_product`)
                FROM `'._DB_PREFIX_.'product` p
                '.$sql_association.'
                WHERE '.$table.'.`active`= 1 AND '.$table.'.`available_for_order`= 1 AND p.`cache_is_pack` = 0
                '.($front ? ' AND '.$table.'.`visibility` IN ("both", "catalog")' : '');
        } else {
            $sql = 'SELECT COUNT(p.`id_product`)
                FROM `'._DB_PREFIX_.'product` p
                WHERE p.`active`= 1 AND p.`available_for_order`= 1 AND p.`cache_is_pack` = 0';
        }

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    public function generateFeed()
    {
        $token = Tools::getValue('token');
        $tokenInConfig = Configuration::get('SHOPPING_FLUX_TOKEN');
        if ($token == '' || $token != $tokenInConfig) {
            die("<?xml version='1.0' encoding='utf-8'?><error>Invalid Token</error>");
        }
        
        $configuration = Configuration::getMultiple(array('PS_TAX_ADDRESS_TYPE', 'PS_CARRIER_DEFAULT', 'PS_COUNTRY_DEFAULT',
            'PS_LANG_DEFAULT', 'PS_SHIPPING_FREE_PRICE', 'PS_SHIPPING_HANDLING', 'PS_SHIPPING_METHOD', 'PS_SHIPPING_FREE_WEIGHT',
            'SHOPPING_FLUX_IMAGE', 'SHOPPING_FLUX_REF'));
    
        $no_breadcrumb = Tools::getValue('no_breadcrumb');
    
        $lang = Tools::getValue('lang');
        $configuration['PS_LANG_DEFAULT'] = !empty($lang) ? Language::getIdByIso($lang) : $configuration['PS_LANG_DEFAULT'];
        $carrier = Carrier::getCarrierByReference((int)Configuration::get('SHOPPING_FLUX_CARRIER'));
    
        //manage case PS_CARRIER_DEFAULT is deleted
        $carrier = is_object($carrier) ? $carrier : new Carrier((int)Configuration::get('SHOPPING_FLUX_CARRIER'));
        $products = $this->getSimpleProducts($configuration['PS_LANG_DEFAULT'], false, 0);
        $link = new Link();
    
        echo '<?xml version="1.0" encoding="utf-8"?>';
        echo '<products version="'.$this->version.'" country="'.$this->default_country->iso_code.'">';
    
        foreach ($products as $productArray) {
            $product = new Product((int)($productArray['id_product']), true, $configuration['PS_LANG_DEFAULT']);
    
            echo '<'.$this->_translateField('product').'>';
            echo $this->_getBaseData($product, $configuration, $link, $carrier);
            echo $this->_getImages($product, $configuration, $link);
            echo $this->_getUrlCategories($product, $configuration, $link);
            echo $this->_getFeatures($product, $configuration);
            echo $this->_getCombinaisons($product, $configuration, $link, $carrier);
    
            if (empty($no_breadcrumb)) {
                echo $this->_getFilAriane($product, $configuration);
            }
    
            echo '<manufacturer><![CDATA['.$product->manufacturer_name.']]></manufacturer>';
            echo '<supplier><![CDATA['.$product->supplier_name.']]></supplier>';
    
            if (is_array($product->specificPrice)) {
                echo '<from><![CDATA['.$product->specificPrice['from'].']]></from>';
                echo '<to><![CDATA['.$product->specificPrice['to'].']]></to>';
            } else {
                echo '<from/>';
                echo '<to/>';
            }

            // The flux must give the specific price in the future when adding the parameter &discount=1
            if (Tools::getValue('discount') == 1) {
                $specificPrices = SpecificPrice::getIdsByProductId($product->id);
                $specificPricesInFuture = array();
                foreach ($specificPrices as $idSpecificPrice) {
                    $specificPrice = new SpecificPrice($idSpecificPrice['id_specific_price']);

                    if (new DateTime($specificPrice->from) > new DateTime()) {
                        $specificPricesInFuture[] = $specificPrice;
                    }
                }

                echo '<discounts>';
                $priceComputed = $product->getPrice(true, null, 2, null, false, true, 1);
                foreach ($specificPricesInFuture as $currentSpecificPrice) {
                    echo '<discount>';
                    // Reduction calculation
                    $reduc = 0;
                    if ($currentSpecificPrice->price == -1) {
                        if ($currentSpecificPrice->reduction_type == 'amount') {
                            $reduction_amount = $currentSpecificPrice->reduction;
                            $reduc = $reduction_amount;
                        } else {
                            $reduc = $priceComputed * $currentSpecificPrice->reduction;
                        }
                        $priceComputed -= $reduc;
                        $priceComputed = round($priceComputed, 2);
                    } else {
                        $priceComputed = $currentSpecificPrice->price;
                    }

                    echo '<from><![CDATA['.$currentSpecificPrice->from.']]></from>';
                    echo '<to><![CDATA['.$currentSpecificPrice->to.']]></to>';
                    echo '<price><![CDATA['.$priceComputed.']]></price>';
                    echo '</discount>';
                }
                echo '</discounts>';
            }
    
            echo '<'.$this->_translateField('supplier_link').'><![CDATA['.$link->getSupplierLink($product->id_supplier, null, $configuration['PS_LANG_DEFAULT']).']]></'.$this->_translateField('supplier_link').'>';
            echo '<'.$this->_translateField('manufacturer_link').'><![CDATA['.$link->getManufacturerLink($product->id_manufacturer, null, $configuration['PS_LANG_DEFAULT']).']]></'.$this->_translateField('manufacturer_link').'>';
            echo '<'.$this->_translateField('on_sale').'>'.(int)$product->on_sale.'</'.$this->_translateField('on_sale').'>';
            echo '</'.$this->_translateField('product').'>';
        }
    
        echo '</products>';
    }
    
    public function initFeed()
    {
        $id_shop = $this->context->shop->id;
        $lockFile = dirname(__FILE__).'/cron_'.$id_shop.'.lock';
        if (!file_exists($lockFile)) {
            $fp = fopen($lockFile, 'w+');
        } else {
            $fp = fopen($lockFile, 'r+');
        }
        
        
        
        // Avoid simultaneous calls
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, "lock");
        } else {
            $this->logDebug('Simultaneous CRON, lock activated, we stop execution');
            die();
        }
        
        // Write time when init for first time
        $today =  date('Y-m-d H:i:s');
        Configuration::updateValue('PS_SHOPPINGFLUX_CRON_TIME', $today, false, null, $id_shop);
        
        $this->emptyLog();
        
        $file = fopen(dirname(__FILE__).'/feed_tmp.xml', 'w+');
        fwrite($file, '<?xml version="1.0" encoding="utf-8"?><products version="'.$this->version.'" country="'.$this->default_country->iso_code.'">');
        fclose($file);

        $totalProducts = $this->countProducts();
        
        $this->logDebug('Starting generation of '.$totalProducts.' products');
        $this->writeFeed($totalProducts);
        
        // Release lock
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    public function writeFeed($total, $current = 0)
    {
        $token = Tools::getValue('token');
        $tokenInConfig = Configuration::get('SHOPPING_FLUX_TOKEN');
        if ($token == '' || $token != $tokenInConfig) {
            die("<?xml version='1.0' encoding='utf-8'?><error>Invalid Token</error>");
        }
        
        $shop_id = $this->context->shop->id;
        if (!is_file(dirname(__FILE__).'/feed_tmp.xml')) {
            die("<?xml version='1.0' encoding='utf-8'?><error>File error</error>");
        }

        $file = fopen(dirname(__FILE__).'/feed_tmp.xml', 'a+');

        $configuration = Configuration::getMultiple(
            array(
                'PS_TAX_ADDRESS_TYPE', 'PS_CARRIER_DEFAULT', 'PS_COUNTRY_DEFAULT',
                'PS_LANG_DEFAULT', 'PS_SHIPPING_FREE_PRICE', 'PS_SHIPPING_HANDLING',
                'PS_SHIPPING_METHOD', 'PS_SHIPPING_FREE_WEIGHT', 'SHOPPING_FLUX_IMAGE',
                'SHOPPING_FLUX_REF'
            )
        );

        $no_breadcrumb = Tools::getValue('no_breadcrumb');

        $lang = Tools::getValue('lang');
        $configuration['PS_LANG_DEFAULT'] = !empty($lang) ? Language::getIdByIso($lang) : $configuration['PS_LANG_DEFAULT'];
        $carrier = Carrier::getCarrierByReference((int)Configuration::get('SHOPPING_FLUX_CARRIER'));
      
        $passes = Tools::getValue('passes');
        if (! ini_get('allow_url_fopen')) {
            // Max 20 redirections, technical restriction on mutualized hostings
            $configuration['PASSES'] = !empty($passes) ? $passes : (int)($total / 20) + 1;
        } else {
            if ($total < 100) {
                // If less than 100 products, make a product generation for each URL call
                $configuration['PASSES'] = !empty($passes) ? $passes : 1;
            } else {
                // else treat the number of products defined in $productsToBeTreated
                $productsToBeTreated = Configuration::get('SHOPPING_FLUX_PASSES');
                $configuration['PASSES'] = !empty($passes) ? $passes : $productsToBeTreated;
            }
        }

        //manage case PS_CARRIER_DEFAULT is deleted
        $carrier = is_object($carrier) ? $carrier : new Carrier((int)Configuration::get('SHOPPING_FLUX_CARRIER'));
        $products = $this->getSimpleProducts($configuration['PS_LANG_DEFAULT'], $current, $configuration['PASSES']);
        $link = new Link();

        $i = 0;
        $this->logDebug('Last url - '.Configuration::get('PS_SHOPPINGFLUX_LAST_URL'));
        $logMessage = '-- URL call for products from '.($current+1).'/'.$total.' to ';
        $logMessage .= ($current+1+$configuration['PASSES']).'/'.$total.', current URL is: '.$_SERVER['REQUEST_URI'];
        $this->logDebug($logMessage);
        
        foreach ($products as $productArray) {
            $i++;
            $logMessage = '----- Product generation '.$i.' / '.$configuration['PASSES'];
            $logMessage .= '(for this URL call) (id_product = '.$productArray['id_product'].')';
            $this->logDebug($logMessage);
            
            $str = '';
            $product = new Product((int)($productArray['id_product']), true, $configuration['PS_LANG_DEFAULT']);

            
            $str .= '<'.$this->_translateField('product').'>';
            $str .= $this->_getBaseData($product, $configuration, $link, $carrier);
            $str .= $this->_getImages($product, $configuration, $link);
            $str .= $this->_getUrlCategories($product, $configuration, $link);
            $str .= $this->_getFeatures($product, $configuration);
            fwrite($file, $str);

            // This function writes directly to the file to avoid performances issues
            $this->_getCombinaisons($product, $configuration, $link, $carrier, $file);

            $str = '';
            if (empty($no_breadcrumb)) {
                $str .= $this->_getFilAriane($product, $configuration);
            }

            $str .= '<manufacturer><![CDATA['.$product->manufacturer_name.']]></manufacturer>';
            $str .= '<supplier><![CDATA['.$product->supplier_name.']]></supplier>';

            if (is_array($product->specificPrice)) {
                $str .= '<from><![CDATA['.$product->specificPrice['from'].']]></from>';
                $str .= '<to><![CDATA['.$product->specificPrice['to'].']]></to>';
            } else {
                $str .= '<from/>';
                $str .= '<to/>';
            }

            // The flux must give the specific price in the future when adding the parameter &discount=1
            $discounts = Tools::getValue('discount');
            if ($discounts == 1) {
                $str .= '<discounts>';
                $priceComputed = $product->getPrice(true, null, 2, null, false, true, 1);

                $specificPrices = SpecificPrice::getIdsByProductId($product->id);
                $specificPricesInFuture = array();
                foreach ($specificPrices as $idSpecificPrice) {
                    $specificPrice = new SpecificPrice($idSpecificPrice['id_specific_price']);
                     
                     
                    if (new DateTime($specificPrice->from) > new DateTime()) {
                        $specificPricesInFuture[] = $specificPrice;
                    }
                }
                
                foreach ($specificPricesInFuture as $currentSpecificPrice) {
                    $str .= '<discount>';
                    // Reduction calculation
                    $reduc = 0;
                    if ($currentSpecificPrice->price == -1) {
                        if ($currentSpecificPrice->reduction_type == 'amount') {
                            $reduction_amount = $currentSpecificPrice->reduction;
                            $reduc = $reduction_amount;
                        } else {
                            $reduc = $priceComputed * $currentSpecificPrice->reduction;
                        }
                        $priceComputed -= $reduc;
                        $priceComputed = round($priceComputed, 2);
                    } else {
                        $priceComputed = $currentSpecificPrice->price;
                    }
                     
                    $str .= '<from><![CDATA['.$currentSpecificPrice->from.']]></from>';
                    $str .= '<to><![CDATA['.$currentSpecificPrice->to.']]></to>';
                    $str .= '<price><![CDATA['.$priceComputed.']]></price>';
                    $str .= '</discount>';
                }
                $str .= '</discounts>';
            }
            
            $str .= '<'.$this->_translateField('supplier_link').'><![CDATA['.$link->getSupplierLink($product->id_supplier, null, $configuration['PS_LANG_DEFAULT']).']]></'.$this->_translateField('supplier_link').'>';
            $str .= '<'.$this->_translateField('manufacturer_link').'><![CDATA['.$link->getManufacturerLink($product->id_manufacturer, null, $configuration['PS_LANG_DEFAULT']).']]></'.$this->_translateField('manufacturer_link').'>';
            $str .= '<'.$this->_translateField('on_sale').'>'.(int)$product->on_sale.'</'.$this->_translateField('on_sale').'>';
            $str .= '</'.$this->_translateField('product').'>';

            fwrite($file, $str);
        }
        fclose($file);
       
        if ($current + $configuration['PASSES'] >= $total) {
            $this->closeFeed();
            
            // Remove previous feed an place the newly generated one
            $shop_id = $this->context->shop->id;
            @unlink(dirname(__FILE__).'/feed.xml');
            rename(dirname(__FILE__).'/feed_tmp.xml', dirname(__FILE__).'/feed.xml');
            
            // Notify end of cron execution
            $this->logDebug('EXPORT SUCCESSFULL');

            // Empty last known url
            Configuration::updateValue('PS_SHOPPINGFLUX_LAST_URL', '0');
        } else {
            $protocol_link = Tools::getCurrentUrlProtocolPrefix();
            $next_uri = $protocol_link.Tools::getHttpHost().__PS_BASE_URI__;
            $next_uri .= 'modules/shoppingfluxexport/cron.php?token='.Configuration::get('SHOPPING_FLUX_TOKEN');
            $next_uri .= '&current='.($current + $configuration['PASSES']).'&total='.$total;
            $next_uri .= '&passes='.$configuration['PASSES'].(!empty($no_breadcrumb) ? '&no_breadcrumb=true' : '');
            $this->logDebug('-- going to call URL: '.$next_uri);
        
            // Disconnect DB to avoid reaching max connections
            DB::getInstance()->disconnect();

            Tools::redirect($next_uri);
        }
        
    }

    private function closeFeed()
    {
        $file = fopen(dirname(__FILE__).'/feed_tmp.xml', 'a+');
        fwrite($file, '</products>');
    }

    public function setFDG()
    {
        $id = Tools::getValue('fdg');

        if ($id == 'del') {
            Configuration::updateValue('SHOPPING_FLUX_FDG', '');
            return 'ko';
        } elseif (is_numeric($id)) {
            Configuration::updateValue('SHOPPING_FLUX_FDG', (int)$id);
            return 'ok';
        }
    }
    
    public function setREF()
    {
        $ref = Tools::getValue('ref');

        if ($ref == 'true') {
            Configuration::updateValue('SHOPPING_FLUX_REF', 'true');
            return 'ok';
        } elseif ($ref == 'false') {
            Configuration::updateValue('SHOPPING_FLUX_REF', 'false');
            return 'ko';
        } else {
            return 'ko';
        }
    }

    /* Default data, in Product Class */
    private function _getBaseData($product, $configuration, $link, $carrier)
    {
        $ret = '';

        $titles = array(
            0 => 'id',
            1 => $this->_translateField('name'),
            2 => $this->_translateField('link'),
            4 => 'description',
            5 => $this->_translateField('short_description'),
            6 => $this->_translateField('price'),
            7 => $this->_translateField('old_price'),
            8 => $this->_translateField('shipping_cost'),
            9 => $this->_translateField('shipping_delay'),
            10 => $this->_translateField('brand'),
            11 => $this->_translateField('category'),
            13 => $this->_translateField('quantity'),
            14 => 'ean',
            15 => $this->_translateField('weight'),
            16 => $this->_translateField('ecotax'),
            17 => $this->_translateField('vat'),
            18 => $this->_translateField('mpn'),
            19 => $this->_translateField('supplier_reference'),
            20 => 'upc',
            21 => 'wholesale-price'
        );

        $data = array();
        $data[0] = ($configuration['SHOPPING_FLUX_REF'] != 'true') ? $product->id : $product->reference;
        $data[1] = $product->name;
        $data[2] = $link->getProductLink($product);
        $data[4] = $product->description;
        $data[5] = $product->description_short;
        $data[6] = $product->getPrice(true, null, 2, null, false, true, 1);
        $data[7] = $product->getPrice(true, null, 2, null, false, false, 1);
        $data[8] = $this->_getShipping($product, $configuration, $carrier);
        $data[9] = $carrier->delay[$configuration['PS_LANG_DEFAULT']];
        $data[10] = $product->manufacturer_name;
        $data[11] = $this->_getCategories($product, $configuration);
        $data[13] = $product->quantity;
        $data[14] = $product->ean13;
        $data[15] = $product->weight;
        $data[16] = $product->ecotax;
        $data[17] = $product->tax_rate;
        $data[18] = $product->reference;
        $data[19] = $product->supplier_reference;
        $data[20] = $product->upc;
        $data[21] = $product->wholesale_price;

        foreach ($titles as $key => $balise) {
            $ret .= '<'.$balise.'><![CDATA['.htmlentities($data[$key], ENT_QUOTES, 'UTF-8').']]></'.$balise.'>';
        }

        return $ret;
    }

    /* Shipping prices */
    private function _getShipping($product, $configuration, $carrier, $attribute_id = null, $attribute_weight = null)
    {
        $default_country = new Country($configuration['PS_COUNTRY_DEFAULT'], $configuration['PS_LANG_DEFAULT']);
        $id_zone = (int)$default_country->id_zone;
        $this->id_address_delivery = 0;
        $carrier_tax = Tax::getCarrierTaxRate((int)$carrier->id, (int)$this->{$configuration['PS_TAX_ADDRESS_TYPE']});

        $shipping = 0;

        $product_price = $product->getPrice(true, $attribute_id, 2, null, false, true, 1);
        $shipping_free_price = $configuration['PS_SHIPPING_FREE_PRICE'];
        $shipping_free_weight = isset($configuration['PS_SHIPPING_FREE_WEIGHT']) ? $configuration['PS_SHIPPING_FREE_WEIGHT'] : 0;

        if (!(((float)$shipping_free_price > 0) && ($product_price >= (float)$shipping_free_price)) &&
                !(((float)$shipping_free_weight > 0) && ($product->weight + $attribute_weight >= (float)$shipping_free_weight))) {
            if (isset($configuration['PS_SHIPPING_HANDLING']) && $carrier->shipping_handling) {
                $shipping = (float)($configuration['PS_SHIPPING_HANDLING']);
            }

            if ($carrier->getShippingMethod() == Carrier::SHIPPING_METHOD_WEIGHT) {
                $shipping += $carrier->getDeliveryPriceByWeight($product->weight, $id_zone);
            } else {
                $shipping += $carrier->getDeliveryPriceByPrice($product_price, $id_zone);
            }

            $shipping *= 1 + ($carrier_tax / 100);
            $shipping = (float)(Tools::ps_round((float)($shipping), 2));
        }

        return (float)$shipping + (float)$product->additional_shipping_cost;
    }

    /* Product category */
    private function _getCategories($product, $configuration)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT cl.`name`
            FROM `'._DB_PREFIX_.'product` p
            '.Shop::addSqlAssociation('product', 'p').'
            LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (product_shop.`id_category_default` = cl.`id_category`)
            WHERE p.`id_product` = '.(int)$product->id.'
            AND cl.`id_lang` = '.(int)$configuration['PS_LANG_DEFAULT']);
    }

    /* Images URIs */
    private function getImages($id_product, $id_lang)
    {
        return Db::getInstance()->ExecuteS('
            SELECT i.`cover`, i.`id_image`, il.`legend`, i.`position`
            FROM `'._DB_PREFIX_.'image` i
            LEFT JOIN `'._DB_PREFIX_.'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = '.(int)($id_lang).')
            WHERE i.`id_product` = '.(int)($id_product).'
            ORDER BY i.cover DESC, i.`position` ASC ');
    }

    private function _getImages($product, $configuration, $link)
    {
        $images = $this->getImages($product->id, $configuration['PS_LANG_DEFAULT']);
        $ret = '<images>';

        if ($images != false) {
            foreach ($images as $image) {
                $ids = $product->id.'-'.$image['id_image'];
                $ret .= '<image><![CDATA['.Tools::getCurrentUrlProtocolPrefix().$link->getImageLink($product->link_rewrite, $ids, $configuration['SHOPPING_FLUX_IMAGE']).']]></image>';
            }
        }
        $ret .= '</images>';
        return $ret;
    }

    /* Categories URIs */
    private function _getUrlCategories($product, $configuration, $link)
    {
        $ret = '<uri-categories>';

        foreach ($this->_getProductCategoriesFull($product->id, $configuration['PS_LANG_DEFAULT']) as $key => $categories) {
            $ret .= '<uri><![CDATA['.$link->getCategoryLink($key, null, $configuration['PS_LANG_DEFAULT']).']]></uri>';
        }

        $ret .= '</uri-categories>';
        return $ret;
    }

    /* All product categories */
    private function _getProductCategoriesFull($id_product, $id_lang)
    {
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            SELECT cp.`id_category`, cl.`name`, cl.`link_rewrite` FROM `'._DB_PREFIX_.'category_product` cp
            LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (cp.`id_category` = cl.`id_category`)
            WHERE cp.`id_product` = '.(int)$id_product.'
            AND cl.`id_lang` = '.(int)$id_lang.'
            ORDER BY cp.`position` DESC');

        $ret = array();

        foreach ($row as $val) {
            $ret[$val['id_category']] = $val;
        }

        return $ret;
    }

    /* Features */
    private function _getFeatures($product, $configuration)
    {
        $ret = '<caracteristiques>';
        foreach ($product->getFrontFeatures($configuration['PS_LANG_DEFAULT']) as $feature) {
            $feature['name'] = $this->_clean($feature['name']);
            
            if (!empty($feature['name'])) {
                $ret .= '<'.$feature['name'].'><![CDATA['.$feature['value'].']]></'.$feature['name'].'>';
            }
        }

        $ret .= '<meta_title><![CDATA['.$product->meta_title.']]></meta_title>';
        $ret .= '<meta_description><![CDATA['.$product->meta_description.']]></meta_description>';
        $ret .= '<meta_keywords><![CDATA['.$product->meta_keywords.']]></meta_keywords>';

        $tabTags = Tag::getProductTags($product->id);
        
        if (empty($tabTags[$configuration['PS_LANG_DEFAULT']])) {
            $ret .= '<tags></tags>';
        } else {
            $ret .= '<tags><![CDATA['.implode(" ", $tabTags[$configuration['PS_LANG_DEFAULT']]).']]></tags>';
        }
        

        $ret .= '<width><![CDATA['.$product->width.']]></width>';
        $ret .= '<depth><![CDATA['.$product->depth.']]></depth>';
        $ret .= '<height><![CDATA['.$product->height.']]></height>';

        $ret .= '<state><![CDATA['.$product->condition.']]></state>';
        $ret .= '<available_for_order><![CDATA['.$product->available_for_order.']]></available_for_order>';
        $ret .= '<out_of_stock><![CDATA['.$product->out_of_stock.']]></out_of_stock>';
        
        
        // Add the overrided fields if any
        $fields = $this->getOverrideFields();
        foreach ($fields as $key => $fieldname) {
            if (Configuration::get('SHOPPING_FLUX_CUSTOM_'.$fieldname) == 1) {
                $ret .= '<'.$fieldname.'><![CDATA['.$product->$fieldname.']]></'.$fieldname.'>';
            }
        }

        $ret .= '</caracteristiques>';
        return $ret;
    }

    /* Product attributes */
    private function _getAttributeImageAssociations($id_product_attribute)
    {
        $combinationImages = array();
        $data = Db::getInstance()->ExecuteS('
            SELECT pai.`id_image`
            FROM `'._DB_PREFIX_.'product_attribute_image` pai
            LEFT JOIN `'._DB_PREFIX_.'image` i ON pai.id_image = i.id_image
            WHERE pai.`id_product_attribute` = '.(int)($id_product_attribute).'
            ORDER BY i.cover DESC, i.position ASC
        ');

        foreach ($data as $row) {
            $combinationImages[] = (int)($row['id_image']);
        }

        return $combinationImages;
    }

    private function _getCombinaisons($product, $configuration, $link, $carrier, $fileToWrite = 0)
    {
        $combinations = array();

        $ret = '<declinaisons>';

        if ($fileToWrite) {
            fwrite($fileToWrite, $ret);
        }
        
        foreach ($product->getAttributeCombinations($configuration['PS_LANG_DEFAULT']) as $combinaison) {
            $combinations[$combinaison['id_product_attribute']]['attributes'][$combinaison['group_name']] = $combinaison['attribute_name'];
            $combinations[$combinaison['id_product_attribute']]['ean13'] = $combinaison['ean13'];
            $combinations[$combinaison['id_product_attribute']]['upc'] = $combinaison['upc'];
            $combinations[$combinaison['id_product_attribute']]['quantity'] = $combinaison['quantity'];
            $combinations[$combinaison['id_product_attribute']]['poids'] = $combinaison['weight'] + $product->weight;
            $combinations[$combinaison['id_product_attribute']]['weight'] = $combinaison['weight'];
            $combinations[$combinaison['id_product_attribute']]['reference'] = $combinaison['reference'];
        }

        $j = 0;
        foreach ($combinations as $id => $combination) {
            // Add time limit to php execution in case of multiple combination
            set_time_limit(60);
            $j++;
            $logMessage = '---------- Attribute generation '.$j.' / '.count($combinations);
            $logMessage .= ' (id_product = '.$product->id.')';
            $this->logDebug($logMessage);
            
            if ($fileToWrite) {
                $ret = '';
            }
            
            if ($configuration['SHOPPING_FLUX_REF'] != 'true') {
                $ref = $id;
            } else {
                $ref = $combination['reference'];
            }

            $ret .= '<declinaison>';
            $ret .= '<id><![CDATA['.$ref.']]></id>';
            $ret .= '<ean><![CDATA['.$combination['ean13'].']]></ean>';
            $ret .= '<upc><![CDATA['.$combination['upc'].']]></upc>';
            $ret .= '<'.$this->_translateField('quantity').'><![CDATA['.$combination['quantity'].']]></'.$this->_translateField('quantity').'>';
            $ret .= '<'.$this->_translateField('weight').'><![CDATA['.$combination['poids'].']]></'.$this->_translateField('weight').'>';
            $ret .= '<'.$this->_translateField('price').'><![CDATA['.$product->getPrice(true, $id, 2, null, false, true, 1).']]></'.$this->_translateField('price').'>';
            $ret .= '<'.$this->_translateField('old_price').'><![CDATA['.$product->getPrice(true, $id, 2, null, false, false, 1).']]></'.$this->_translateField('old_price').'>';
            $ret .= '<'.$this->_translateField('shipping_cost').'><![CDATA['.$this->_getShipping($product, $configuration, $carrier, $id, $combination['weight']).']]></'.$this->_translateField('shipping_cost').'>';
            $ret .= '<images>';

            $image_child = true;

            foreach ($this->_getAttributeImageAssociations($id) as $image) {
                if (empty($image)) {
                    $image_child = false;
                    break;
                }
                $ret .= '<image><![CDATA['.Tools::getCurrentUrlProtocolPrefix().$link->getImageLink($product->link_rewrite, $product->id.'-'.$image, $configuration['SHOPPING_FLUX_IMAGE']).']]></image>';
            }

            if (!$image_child) {
                foreach ($product->getImages($configuration['PS_LANG_DEFAULT']) as $images) {
                    $ids = $product->id.'-'.$images['id_image'];
                    $ret .= '<image><![CDATA['.Tools::getCurrentUrlProtocolPrefix().$link->getImageLink($product->link_rewrite, $ids, $configuration['SHOPPING_FLUX_IMAGE']).']]></image>';
                }
            }

            $ret .= '</images>';
            $ret .= '<attributs>';

            asort($combination['attributes']);
            foreach ($combination['attributes'] as $attributeName => $attributeValue) {
                $attributeName = $this->_clean($attributeName);
                
                if (!empty($attributeName)) {
                    $ret .= '<'.$attributeName.'><![CDATA['.$attributeValue.']]></'.$attributeName.'>';
                }
            }

            $ret .= '<'.$this->_translateField('mpn').'><![CDATA['.$combination['reference'].']]></'.$this->_translateField('mpn').'>';
            $ret .= '<'.$this->_translateField('combination_link').'><![CDATA['.$link->getProductLink($product).$product->getAnchor($id, true).']]></'.$this->_translateField('combination_link').'>';

            $ret .= '</attributs>';
            $ret .= '</declinaison>';

            if ($fileToWrite) {
                fwrite($fileToWrite, $ret);
            }
        }

        if ($fileToWrite) {
            $ret = '</declinaisons>';
            fwrite($fileToWrite, $ret);
            return;
        } else {
            $ret .= '</declinaisons>';
            return $ret;
        }
    }

    /* Category tree XML */
    private function _getFilAriane($product, $configuration)
    {
        $category = '';
        $ret = '<'.$this->_translateField('category_breadcrumb').'>';

        foreach ($this->_getProductFilAriane($product->id, $configuration['PS_LANG_DEFAULT']) as $categories) {
            $category .= $categories.' > ';
        }

        $ret .= '<![CDATA['.Tools::substr($category, 0, -3).']]></'.$this->_translateField('category_breadcrumb').'>';
        return $ret;
    }

    /* Category tree */
    private function _getProductFilAriane($id_product, $id_lang, $id_category = 0, $id_parent = 0, $name = 0)
    {
        $ret = array();
        $id_parent = '';
    
        if ($id_category) {
            $ret[$id_category] = $name;
            $id_parent = $id_parent;
            $id_category = $id_category;
        } else {
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
			SELECT cl.`name`, p.`id_category_default` as id_category, c.`id_parent` FROM `'._DB_PREFIX_.'product` p
			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (p.`id_category_default` = cl.`id_category`)
			LEFT JOIN `'._DB_PREFIX_.'category` c ON (p.`id_category_default` = c.`id_category`)
			WHERE p.`id_product` = '.(int)$id_product.'
			AND cl.`id_lang` = '.(int)$id_lang);
    
            foreach ($row as $val) {
                $ret[$val['id_category']] = $val['name'];
                $id_parent = $val['id_parent'];
                $id_category = $val['id_category'];
            }
        }
    
        while ($id_parent != 0 && $id_category != $id_parent) {
            $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
				SELECT cl.`name`, c.`id_category`, c.`id_parent` FROM `'._DB_PREFIX_.'category_lang` cl
				LEFT JOIN `'._DB_PREFIX_.'category` c ON (c.`id_category` = '.(int)$id_parent.')
				WHERE cl.`id_category` = '.(int)$id_parent.'
				AND cl.`id_lang` = '.(int)$id_lang);
    
            if (! sizeof($row)) {
                // There is a problem with the category parent, let's try another category
                $productCategory = Db::getInstance(_PS_USE_SQL_SLAVE_)->ExecuteS('
            			SELECT DISTINCT c.`id_category`, cl.`name`, 
                            c.`id_parent` FROM `'._DB_PREFIX_.'category_product` cp
            			LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON (cp.`id_category` = cl.`id_category`)
            			LEFT JOIN `'._DB_PREFIX_.'category` c ON (cp.`id_category` = c.`id_category`)
            			WHERE cp.`id_product` = '.(int)$id_product.'
			            AND cp.`id_category` NOT IN ('.$id_category.')
            			AND cl.`id_lang` = '.(int)$id_lang.'
			            ORDER BY level_depth DESC');
                 
                if (! sizeof($productCategory)) {
                    return array();
                }
                 
                return $this->_getProductFilAriane($id_product, $id_lang, $productCategory[0]['id_category'], $productCategory[0]['id_parent'], $productCategory[0]['name']);
            }
            
            foreach ($row as $val) {
                $ret[$val['id_category']] = $val['name'];
                $id_parent = $val['id_parent'];
                $id_category = $val['id_category'];
            }
        }
    
        $ret = array_reverse($ret);
        return $ret;
    }

    public function hookbackOfficeTop($no_cron = true)
    {
        $minTimeDiff = 60;
        $now = time();
        $lastHookCalledTime = Configuration::get('SHOPPING_BACKOFFICE_CALL');
        $moreThanOneMinute = $now - $lastHookCalledTime;

        if ($moreThanOneMinute > $minTimeDiff) {
            $controller = Tools::strtolower(Tools::getValue('controller'));
            $ordersConfig = Configuration::get('SHOPPING_FLUX_ORDERS');
            if (($controller == 'adminorders' &&
                $ordersConfig != '' &&
                in_array('curl', get_loaded_extensions())) ||
                $no_cron == false) {
                $ordersXML = $this->_callWebService('GetOrders');
    
                if (count($ordersXML->Response->Orders) == 0) {
                    return;
                }
    
                foreach ($ordersXML->Response->Orders->Order as $order) {
                    try {
                        if ((Tools::strtolower($order->Marketplace) == 'rdc' || Tools::strtolower($order->Marketplace) == 'rueducommerce') && strpos($order->ShippingMethod, 'Mondial Relay') !== false) {
                            $num = explode(' ', $order->ShippingMethod);
                            $order->Other = end($num);
                            $order->ShippingMethod = 'Mondial Relay';
                        }
    
                        $orderExists = Db::getInstance()->getRow('SELECT m.id_message  FROM '._DB_PREFIX_.'message m
                            WHERE m.message LIKE "%Numéro de commande '.pSQL($order->Marketplace).' :'.pSQL($order->IdOrder).'%"');
    
                        if (isset($orderExists['id_message'])) {
                            $this->_validOrders((string)$order->IdOrder, (string)$order->Marketplace);
                            continue;
                        }
    
                        $check = $this->checkData($order);
                        if ($check !== true) {
                            $this->_validOrders((string)$order->IdOrder, (string)$order->Marketplace, false, $check);
                            continue;
                        }
    
                        $mail = (string)$order->BillingAddress->Email;
                        $email = (empty($mail)) ? pSQL($order->IdOrder.'@'.$order->Marketplace.'.sf') : pSQL($mail);
    
                        $id_customer = $this->_getCustomer($email, (string)$order->BillingAddress->LastName, (string)$order->BillingAddress->FirstName);
                        //avoid update of old orders by the same merchant with different addresses
                        $id_address_billing = $this->_getAddress($order->BillingAddress, $id_customer, 'Billing-'.(string)$order->IdOrder);
                        $id_address_shipping = $this->_getAddress($order->ShippingAddress, $id_customer, 'Shipping-'.(string)$order->IdOrder, $order->Other);
                        $products_available = $this->_checkProducts($order->Products);
    
                        $current_customer = new Customer((int)$id_customer);
    
                        if ($products_available && $id_address_shipping && $id_address_billing && $id_customer) {
                            $cart = $this->_getCart($id_customer, $id_address_billing, $id_address_shipping, $order->Products, (string)$order->Currency, (string)$order->ShippingMethod, $order->TotalFees);
    
                            if ($cart) {
                                //compatibylity with socolissmo
                                $this->context->cart = $cart;
    
                                Db::getInstance()->autoExecute(_DB_PREFIX_.'customer', array('email' => 'do-not-send@alerts-shopping-flux.com'), 'UPDATE', '`id_customer` = '.(int)$id_customer);
    
                                $customerClear = new Customer();
    
                                if (method_exists($customerClear, 'clearCache')) {
                                    $customerClear->clearCache(true);
                                }
    
                                $payment = $this->_validateOrder($cart, $order->Marketplace);
                                $id_order = $payment->currentOrder;
    
                                //we valid there
                                $this->_validOrders((string)$order->IdOrder, (string)$order->Marketplace, $id_order);
    
                                $reference_order = $payment->currentOrderReference;
    
                                Db::getInstance()->autoExecute(_DB_PREFIX_.'customer', array('email' => pSQL($email)), 'UPDATE', '`id_customer` = '.(int)$id_customer);
    
                                Db::getInstance()->autoExecute(_DB_PREFIX_.'message', array('id_order' => (int)$id_order, 'message' => 'Numéro de commande '.pSQL($order->Marketplace).' :'.pSQL($order->IdOrder), 'date_add' => date('Y-m-d H:i:s')), 'INSERT');
                                $this->_updatePrices($id_order, $order, $reference_order);
                            }
                        }
    
                        $cartClear = new Cart();
    
                        if (method_exists($cartClear, 'clearCache')) {
                            $cartClear->clearCache(true);
                        }
    
                        $addressClear = new Address();
    
                        if (method_exists($addressClear, 'clearCache')) {
                            $addressClear->clearCache(true);
                        }
    
                        $customerClear = new Customer();
    
                        if (method_exists($customerClear, 'clearCache')) {
                            $customerClear->clearCache(true);
                        }
                    } catch (PrestaShopException $pe) {
                        $this->_validOrders((string)$order->IdOrder, (string)$order->Marketplace, false, $pe->getMessage());
                    }
                }
            }
        }
    }

    //Check Data to avoid errors
    private function checkData($order)
    {
        $id_shop = $this->context->shop->id;
        foreach ($order->Products->Product as $product) {
            if (Configuration::get('SHOPPING_FLUX_REF') == 'true') {
                $ids = $this->getIDs($product->SKU);
            } else {
                $ids = explode('_', $product->SKU);
            }
            if (!$ids[1]) {
                $p = new Product($ids[0]);
                if (empty($p->id)) {
                    return 'Product ID don\'t exist';
                }

                $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                SELECT active, available_for_order
                FROM '._DB_PREFIX_.'product_shop
                WHERE id_product ='.(int)$ids[0].' AND id_shop = '.(int)$id_shop);

                if ($res['active'] != 1 || $res['available_for_order'] != 1) {
                    return 'Product is not active or not available for order';
                }

                $minimalQuantity = $p->minimal_quantity;
            } else {
                $exist = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                SELECT id_product_attribute
                FROM '._DB_PREFIX_.'product_attribute
                WHERE id_product = '.(int)$ids[0].'
                AND id_product_attribute ='.(int)$ids[1]);

                if ($exist === false) {
                    return 'Product ID don\'t exist';
                }

                $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow('
                SELECT active, available_for_order
                FROM '._DB_PREFIX_.'product_shop
                WHERE id_product ='.(int)$ids[0].' AND id_shop = '.(int)$id_shop);

                if ($res['active'] != 1 || $res['available_for_order'] != 1) {
                    return 'Product is not active or not available for order';
                }

                $minimalQuantity = (int)Attribute::getAttributeMinimalQty((int)$ids[1]);
            }


            if ($minimalQuantity > $product->Quantity) {
                return 'Minimal quantity for product '.$product->SKU.' is '.$minimalQuantity.'.';
            }
        }

        return true;
    }

    public function hookPostUpdateOrderStatus($params)
    {
        $order = new Order((int)$params['id_order']);

        //set the shop related to the order in the context to avoid empty configurations
        $this->context->shop = new Shop((int)$order->id_shop);

        if ((Configuration::get('SHOPPING_FLUX_STATUS_SHIPPED') != '' &&
                Configuration::get('SHOPPING_FLUX_SHIPPED') == '' &&
                $this->_getOrderStates(Configuration::get('PS_LANG_DEFAULT'), 'shipped') == $params['newOrderStatus']->name) &&
                $order->module == 'sfpayment' ||
                (Configuration::get('SHOPPING_FLUX_STATUS_SHIPPED') != '' &&
                (int)Configuration::get('SHOPPING_FLUX_SHIPPED') == $params['newOrderStatus']->id && $order->module == 'sfpayment')) {
            $shipping = $order->getShipping();
            $carrier = new Carrier((int)$order->id_carrier);
            $url = str_replace('http://http://', 'http://', $carrier->url);
            $url = str_replace('@', $order->shipping_number, $url);


            $message = $order->getFirstMessage();
            $id_order_marketplace = explode(':', $message);
            $id_order_marketplace[1] = trim($id_order_marketplace[1]) == 'True' ? '' : $id_order_marketplace[1];

            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<UpdateOrders>';
            $xml .= '<Order>';
            $xml .= '<IdOrder>'.$id_order_marketplace[1].'</IdOrder>';
            $xml .= '<Marketplace>'.$order->payment.'</Marketplace>';
            $xml .= '<MerchantIdOrder>'.(int)$params['id_order'].'</MerchantIdOrder>';
            $xml .= '<Status>Shipped</Status>';

            if (isset($shipping[0])) {
                $xml .= '<TrackingNumber><![CDATA['.$shipping[0]['tracking_number'].']]></TrackingNumber>';
                $xml .= '<CarrierName><![CDATA['.$shipping[0]['state_name'].']]></CarrierName>';
                $xml .= '<TrackingUrl><![CDATA['.$url.']]></TrackingUrl>';
            }

            $xml .= '</Order>';
            $xml .= '</UpdateOrders>';

            $responseXML = $this->_callWebService('UpdateOrders', $xml);

            if (!$responseXML->Response->Error) {
                Db::getInstance()->autoExecute(_DB_PREFIX_.'message', array('id_order' => pSQL((int)$order->id), 'message' => 'Statut mis à jour sur '.pSQL((string)$order->payment).' : '.pSQL((string)$responseXML->Response->Orders->Order->StatusUpdated), 'date_add' => date('Y-m-d H:i:s')), 'INSERT');
            } else {
                Db::getInstance()->autoExecute(_DB_PREFIX_.'message', array('id_order' => pSQL((int)$order->id), 'message' => 'Statut mis à jour sur '.pSQL((string)$order->payment).' : '.pSQL((string)$responseXML->Response->Error->Message), 'date_add' => date('Y-m-d H:i:s')), 'INSERT');
            }
        } elseif ((Configuration::get('SHOPPING_FLUX_STATUS_CANCELED') != '' &&
                Configuration::get('SHOPPING_FLUX_CANCELED') == '' &&
                $this->_getOrderStates(Configuration::get('PS_LANG_DEFAULT'), 'order_canceled') == $params['newOrderStatus']->name) &&
                $order->module == 'sfpayment' ||
                (Configuration::get('SHOPPING_FLUX_STATUS_CANCELED') != '' &&
                (int)Configuration::get('SHOPPING_FLUX_CANCELED') == $params['newOrderStatus']->id && $order->module == 'sfpayment')) {
            $order = new Order((int)$params['id_order']);
            $shipping = $order->getShipping();

            $message = $order->getFirstMessage();
            $id_order_marketplace = explode(':', $message);
            $id_order_marketplace[1] = trim($id_order_marketplace[1]) == 'True' ? '' : $id_order_marketplace[1];

            $xml = '<?xml version="1.0" encoding="UTF-8"?>';
            $xml .= '<UpdateOrders>';
            $xml .= '<Order>';
            $xml .= '<IdOrder>'.$id_order_marketplace[1].'</IdOrder>';
            $xml .= '<Marketplace>'.$order->payment.'</Marketplace>';
            $xml .= '<MerchantIdOrder>'.(int)$params['id_order'].'</MerchantIdOrder>';
            $xml .= '<Status>Canceled</Status>';
            $xml .= '</Order>';
            $xml .= '</UpdateOrders>';

            $responseXML = $this->_callWebService('UpdateOrders', $xml);

            if (!$responseXML->Response->Error) {
                Db::getInstance()->autoExecute(_DB_PREFIX_.'message', array('id_order' => (int)$order->id, 'message' => 'Statut mis à jour sur '.pSQL((string)$order->payment).' : '.pSQL((string)$responseXML->Response->Orders->Order->StatusUpdated), 'date_add' => date('Y-m-d H:i:s')), 'INSERT');
            } else {
                Db::getInstance()->autoExecute(_DB_PREFIX_.'message', array('id_order' => (int)$order->id, 'message' => 'Statut mis à jour sur '.pSQL((string)$order->payment).' : '.pSQL((string)$responseXML->Response->Error->Message), 'date_add' => date('Y-m-d H:i:s')), 'INSERT');
            }
        }
    }

    public function hookTop()
    {
        
        $ip = $this->getIp();
        if ((int)Db::getInstance()->getValue('SELECT `id_customer_ip` FROM `'._DB_PREFIX_.'customer_ip` 
            WHERE `id_customer` = '.(int)$this->context->cookie->id_customer) > 0) {
            $updateIp = array('ip' => pSQL($ip));
            $sql = 'UPDATE `'._DB_PREFIX_.'customer_ip`
					SET `ip` = '.(int)$updateIp['ip'].'
					WHERE `id_customer` = '.(int)(int)$this->context->cookie->id_customer;
            Db::getInstance()->execute($sql);
        } else {
            $insertIp = array('id_customer' => (int)$this->context->cookie->id_customer, 'ip' => pSQL($ip));

            $sql = 'INSERT INTO `'._DB_PREFIX_.'customer_ip` (`id_customer`, `ip`)
					VALUES
					('.(int)$insertIp['id_customer'].',
					'.(int)$insertIp['ip'].')';
            Db::getInstance()->execute($sql);
        }
    }

    /* Clean XML strings */
    private function _clean($string)
    {
        $string = str_replace("\r\n", '', strip_tags($string));
        $string = str_replace(" ", '_', strip_tags($string));
        $string = str_replace(array('(', ')', '°', '&', '+', '/', "'", ':', ';', ','), '', strip_tags($string));
        
        //Check if first char is a number
        if (preg_match('#[0-9]#', substr($string, 0, 1))) {
            $string = str_replace(substr($string, 0, 1), '_', strip_tags($string));
        }
        
        return $string;
    }

    /* Call Shopping Flux Webservices */
    private function _callWebService($call, $xml = false)
    {
        $token = Configuration::get('SHOPPING_FLUX_TOKEN');
        if (empty($token)) {
            return false;
        }

        $service_url = 'https://ws.shopping-feed.com';

        $curl_post_data = array(
            'TOKEN' => Configuration::get('SHOPPING_FLUX_TOKEN'),
            'CALL' => $call,
            'MODE' => 'Production',
            'REQUEST' => $xml
        );
        
        // Log datas
        $this->logCallWebservice('');
        $this->logCallWebservice('------- Start Call Webservice -------');
        $this->logCallWebservice($service_url.'?'.http_build_query($curl_post_data, '', '&amp;'));
        $this->logCallWebservice('XML Infos');
        $this->logCallWebservice($xml);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $service_url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        $curl_response = curl_exec($curl);

        // Log datas
        $this->logCallWebservice('XML Infos Curl Response');
        $this->logCallWebservice($curl_response);
        $this->logCallWebservice('------- End Call Webservice -------');
        $this->logCallWebservice('');

        curl_close($curl);
        return @simplexml_load_string($curl_response);
    }

    private function _getOrderStates($id_lang, $type)
    {
        return Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue('
            SELECT osl.name
            FROM `'._DB_PREFIX_.'order_state` os
            LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl
            ON (os.`id_order_state` = osl.`id_order_state`
            AND osl.`id_lang` = '.(int)$id_lang.')
            WHERE `template` = "'.pSQL($type).'"');
    }

    private function _getAddress($addressNode, $id_customer, $type, $other)
    {
        //alias is limited
        $type = Tools::substr($type, 0, 32);

        $id_address = (int)Db::getInstance()->getValue('SELECT `id_address`
            FROM `'._DB_PREFIX_.'address` WHERE `id_customer` = '.(int)$id_customer.' AND `alias` = \''.pSQL($type).'\'');

        if ($id_address) {
            $address = new Address((int)$id_address);
        } else {
            $address = new Address();
        }

        $customer = new Customer((int)$id_customer);

        $street1 = '';
        $street2 = '';
        $line2 = false;
        $streets = explode(' ', (string)$addressNode->Street);

        foreach ($streets as $street) {
            if (Tools::strlen($street1) + Tools::strlen($street) + 1 < 32 && !$line2) {
                $street1 .= $street.' ';
            } else {
                $line2 = true;
                $street2 .= $street.' ';
            }
        }

        $lastname = (string)$addressNode->LastName;
        $firstname = (string)$addressNode->FirstName;

        $lastname = preg_replace('/\-?\d+/', '', $lastname);
        $firstname = preg_replace('/\-?\d+/', '', $firstname);

        $address->id_customer = (int)$id_customer;
        $address->id_country = (int)Country::getByIso(trim($addressNode->Country));
        $address->alias = pSQL($type);
        $address->lastname = (!empty($lastname)) ? pSQL($lastname) : $customer->lastname;
        $address->firstname = (!empty($firstname)) ? pSQL($firstname) : $customer->firstname;
        $address->address1 = pSQL($street1);
        $address->address2 = pSQL($street2);
        $address->company = pSQL($addressNode->Company);
        $address->other = pSQL($other);
        $address->postcode = pSQL($addressNode->PostalCode);
        $address->city = pSQL($addressNode->Town);
        if (empty($addressNode->Phone)) {
            $address->phone = Tools::substr(pSQL($addressNode->PhoneMobile), 0, 16);
        } else {
            $address->phone = Tools::substr(pSQL($addressNode->Phone), 0, 16);
        }

        if (empty($addressNode->PhoneMobile)) {
            $address->phone_mobile = Tools::substr(pSQL($addressNode->Phone), 0, 16);
        } else {
            $address->phone_mobile = Tools::substr(pSQL($addressNode->PhoneMobile), 0, 16);
        }

        if ($id_address) {
            $address->update();
        } else {
            $address->add();
        }

        return $address->id;
    }

    private function _getCustomer($email, $lastname, $firstname)
    {
        $id_customer = (int)Db::getInstance()->getValue('SELECT `id_customer`
            FROM `'._DB_PREFIX_.'customer` WHERE `email` = \''.pSQL($email).'\'');

        if ($id_customer) {
            return $id_customer;
        }

        $lastname = preg_replace('/\-?\d+/', '', $lastname);
        $firstname = preg_replace('/\-?\d+/', '', $firstname);

        $customer = new Customer();
        $customer->lastname = (!empty($lastname)) ? pSQL($lastname) : '-';
        $customer->firstname = (!empty($firstname)) ? pSQL($firstname) : '-';
        $customer->passwd = md5(pSQL(_COOKIE_KEY_.rand()));
        $customer->id_default_group = 1;
        $customer->email = pSQL($email);
        $customer->add();

        return $customer->id;
    }

    private function getIDs($ref)
    {
        $row = Db::getInstance()->getRow('SELECT pa.id_product, pa.id_product_attribute  FROM '._DB_PREFIX_.'product_attribute pa
            WHERE pa.reference = "'.  pSQL($ref).'"');

        if (isset($row['id_product_attribute'])) {
            return array($row['id_product'], $row['id_product_attribute']);
        }

        $row2 = Db::getInstance()->getRow('SELECT p.id_product  FROM '._DB_PREFIX_.'product p
            WHERE p.reference = "'.  pSQL($ref).'"');

        return array($row2['id_product'], 0);
    }

    private function _updatePrices($id_order, $order, $reference_order)
    {
        $tax_rate = 0;

        foreach ($order->Products->Product as $product) {
            if (Configuration::get('SHOPPING_FLUX_REF') == 'true') {
                $skus = $this->getIDs($product->SKU);
            } else {
                $skus = explode('_', $product->SKU);
            }

            $row = Db::getInstance()->getRow('SELECT t.rate, od.id_order_detail  FROM '._DB_PREFIX_.'tax t
                LEFT JOIN '._DB_PREFIX_.'order_detail_tax odt ON t.id_tax = odt.id_tax
                LEFT JOIN '._DB_PREFIX_.'order_detail od ON odt.id_order_detail = od.id_order_detail
                WHERE od.id_order = '.(int)$id_order.' AND product_id = '.(int)$skus[0].' AND product_attribute_id = '.(int)$skus[1]);

            // Tax 0 for FDG product
            if (Configuration::get('SHOPPING_FLUX_FDG') == (int)$skus[0]) {
                $tax_rate = 0;
            } else {
                $tax_rate = $row['rate'];
            }
            $id_order_detail = $row['id_order_detail'];

            $updateOrderDetail = array(
                'product_price' => (float)((float)$product->Price / (1 + ($tax_rate / 100))),
                'reduction_percent' => 0,
                'reduction_amount' => 0,
                'ecotax' => 0,
                'total_price_tax_incl' => (float)((float)$product->Price * $product->Quantity),
                'total_price_tax_excl' => (float)(((float)$product->Price / (1 + ($tax_rate / 100))) * $product->Quantity),
                'unit_price_tax_incl' => (float)$product->Price,
                'unit_price_tax_excl' => (float)((float)$product->Price / (1 + ($tax_rate / 100))),
            );

            Db::getInstance()->autoExecute(_DB_PREFIX_.'order_detail', $updateOrderDetail, 'UPDATE', '`id_order` = '.(int)$id_order.' AND `product_id` = '.(int)$skus[0].' AND `product_attribute_id` = '.(int)$skus[1]);

            $updateOrderDetailTax = array(
                'unit_amount' => (float)((float)$product->Price - ((float)$product->Price / (1 + ($tax_rate / 100)))),
                'total_amount' => (float)(((float)$product->Price - ((float)$product->Price / (1 + ($tax_rate / 100)))) * $product->Quantity),
            );

            Db::getInstance()->autoExecute(_DB_PREFIX_.'order_detail_tax', $updateOrderDetailTax, 'UPDATE', '`id_order_detail` = '.(int)$id_order_detail);
        }

        if ((float)$order->TotalFees > 0 && Configuration::get('SHOPPING_FLUX_FDG') != '') {
            $row = Db::getInstance()->getRow('SELECT t.rate, od.id_order_detail  FROM '._DB_PREFIX_.'tax t
                LEFT JOIN '._DB_PREFIX_.'order_detail_tax odt ON t.id_tax = odt.id_tax
                LEFT JOIN '._DB_PREFIX_.'order_detail od ON odt.id_order_detail = od.id_order_detail
                WHERE od.id_order = '.(int)$id_order.' AND product_id = '.(int)Configuration::get('SHOPPING_FLUX_FDG').' AND product_attribute_id = 0');

            $tax_rate = $row['rate'];
            $id_order_detail = $row['id_order_detail'];

            $updateOrderDetail = array(
                'product_price' => (float)($order->TotalFees),
                'reduction_percent' => 0,
                'reduction_amount' => 0,
                'total_price_tax_incl' => (float)($order->TotalFees),
                'total_price_tax_excl' => (float)($order->TotalFees),
                'unit_price_tax_incl' => (float)($order->TotalFees),
                'unit_price_tax_excl' => (float)($order->TotalFees),
            );

            Db::getInstance()->autoExecute(_DB_PREFIX_.'order_detail', $updateOrderDetail, 'UPDATE', '`id_order` = '.(int)$id_order.' AND `product_id` = '.(int)Configuration::get('SHOPPING_FLUX_FDG').' AND `product_attribute_id` = 0');

            $updateOrderDetailTax = array(
                'unit_amount' => 0,
                'total_amount' => 0,
            );

            Db::getInstance()->autoExecute(_DB_PREFIX_.'order_detail_tax', $updateOrderDetailTax, 'UPDATE', '`id_order_detail` = '.(int)$id_order_detail);
        }

        $actual_configuration = unserialize(Configuration::get('SHOPPING_FLUX_SHIPPING_MATCHING'));

        $carrier_to_load = isset($actual_configuration[base64_encode(Tools::safeOutput((string)$order->ShippingMethod))]) ?
                (int)$actual_configuration[base64_encode(Tools::safeOutput((string)$order->ShippingMethod))] :
                (int)Configuration::get('SHOPPING_FLUX_CARRIER');

        $carrier = Carrier::getCarrierByReference($carrier_to_load);

        //manage case PS_CARRIER_DEFAULT is deleted
        $carrier = is_object($carrier) ? $carrier : new Carrier($carrier_to_load);

        if ((float)$order->TotalFees > 0) {
            $updateOrder = array(
                'total_paid' => (float)($order->TotalAmount),
                'total_paid_tax_incl' => (float)($order->TotalAmount),
                'total_paid_tax_excl' => (float)((float)$order->TotalAmount / (1 + ($tax_rate / 100))),
                'total_paid_real' => (float)($order->TotalAmount),
                'total_products' => (float)(Db::getInstance()->getValue('SELECT SUM(`product_price`)*`product_quantity` FROM `'._DB_PREFIX_.'order_detail` WHERE `id_order` = '.(int)$id_order)),
                'total_products_wt' => (float)((float)$order->TotalProducts + (float)$order->TotalFees),
                'total_shipping' => (float)($order->TotalShipping),
                'total_shipping_tax_incl' => (float)($order->TotalShipping),
                'total_shipping_tax_excl' => (float)((float)$order->TotalShipping / (1 + ($tax_rate / 100))),
                'id_carrier' => $carrier->id
            );
        } else {
            $updateOrder = array(
                'total_paid' => (float)($order->TotalAmount),
                'total_paid_tax_incl' => (float)($order->TotalAmount),
                'total_paid_tax_excl' => (float)((float)$order->TotalAmount / (1 + ($tax_rate / 100))),
                'total_paid_real' => (float)($order->TotalAmount),
                'total_products' => (float)(Db::getInstance()->getValue('SELECT SUM(`product_price`)*`product_quantity` FROM `'._DB_PREFIX_.'order_detail` WHERE `id_order` = '.(int)$id_order)),
                'total_products_wt' => (float)($order->TotalProducts),
                'total_shipping' => (float)($order->TotalShipping),
                'total_shipping_tax_incl' => (float)($order->TotalShipping),
                'total_shipping_tax_excl' => (float)((float)$order->TotalShipping / (1 + ($tax_rate / 100))),
                'id_carrier' => $carrier->id
            );
        }

        Db::getInstance()->autoExecute(_DB_PREFIX_.'orders', $updateOrder, 'UPDATE', '`id_order` = '.(int)$id_order);

        if ((float)$order->TotalFees > 0) {
            $updateOrderInvoice = array(
                'total_paid_tax_incl' => (float)($order->TotalAmount),
                'total_paid_tax_excl' => (float)((float)$order->TotalAmount / (1 + ($tax_rate / 100))),
                'total_products' => (float)(Db::getInstance()->getValue('SELECT SUM(`product_price`)*`product_quantity` FROM `'._DB_PREFIX_.'order_detail` WHERE `id_order` = '.(int)$id_order)),
                'total_products_wt' => (float)((float)$order->TotalProducts + (float)$order->TotalFees),
                'total_shipping_tax_incl' => (float)($order->TotalShipping),
                'total_shipping_tax_excl' => (float)((float)$order->TotalShipping / (1 + ($tax_rate / 100))),
            );
        } else {
            $updateOrderInvoice = array(
                'total_paid_tax_incl' => (float)($order->TotalAmount),
                'total_paid_tax_excl' => (float)((float)$order->TotalAmount / (1 + ($tax_rate / 100))),
                'total_products' => (float)(Db::getInstance()->getValue('SELECT SUM(`product_price`)*`product_quantity` FROM `'._DB_PREFIX_.'order_detail` WHERE `id_order` = '.(int)$id_order)),
                'total_products_wt' => (float)($order->TotalProducts),
                'total_shipping_tax_incl' => (float)($order->TotalShipping),
                'total_shipping_tax_excl' => (float)((float)$order->TotalShipping / (1 + ($tax_rate / 100))),
            );
        }

        Db::getInstance()->autoExecute(_DB_PREFIX_.'order_invoice', $updateOrderInvoice, 'UPDATE', '`id_order` = '.(int)$id_order);

        $updateOrderTracking = array(
            'shipping_cost_tax_incl' => (float)($order->TotalShipping),
            'shipping_cost_tax_excl' => (float)((float)$order->TotalShipping / (1 + ($tax_rate / 100))),
            'id_carrier' => $carrier->id
        );

        Db::getInstance()->autoExecute(_DB_PREFIX_.'order_carrier', $updateOrderTracking, 'UPDATE', '`id_order` = '.(int)$id_order);
        $updatePayment = array('amount' => (float)$order->TotalAmount);
        Db::getInstance()->autoExecute(_DB_PREFIX_.'order_payment', $updatePayment, 'UPDATE', '`order_reference` = "'.$reference_order.'"');
    }

    private function _validateOrder($cart, $marketplace)
    {
        $payment = new sfpayment();
        $payment->name = 'sfpayment';
        $payment->active = true;

        //we need to flush the cart because of cache problems
        $cart->getPackageList(true);
        $cart->getDeliveryOptionList(null, true);
        $cart->getDeliveryOption(null, false, false);

        $payment->validateOrder((int)$cart->id, 2, (float)Tools::ps_round(Tools::convertPrice($cart->getOrderTotal(), new Currency($cart->id_currency)), 2), Tools::strtolower($marketplace), null, array(), $cart->id_currency, false, $cart->secure_key);
        return $payment;
    }

    /*
     * Fake cart creation
     */

    private function _getCart($id_customer, $id_address_billing, $id_address_shipping, $productsNode, $currency, $shipping_method, $fees)
    {
        $cart = new Cart();
        $cart->id_customer = $id_customer;
        $cart->id_address_invoice = $id_address_billing;
        $cart->id_address_delivery = $id_address_shipping;
        $cart->id_currency = Currency::getIdByIsoCode((string)$currency == '' ? 'EUR' : (string)$currency);
        $cart->id_lang = Configuration::get('PS_LANG_DEFAULT');
        $cart->recyclable = 0;
        $cart->secure_key = md5(uniqid(rand(), true));

        $actual_configuration = unserialize(Configuration::get('SHOPPING_FLUX_SHIPPING_MATCHING'));

        $carrier_to_load = isset($actual_configuration[base64_encode(Tools::safeOutput($shipping_method))]) ?
            (int)$actual_configuration[base64_encode(Tools::safeOutput($shipping_method))] :
            (int)Configuration::get('SHOPPING_FLUX_CARRIER');

        $carrier = Carrier::getCarrierByReference($carrier_to_load);

        //manage case PS_CARRIER_DEFAULT is deleted
        $carrier = is_object($carrier) ? $carrier : new Carrier($carrier_to_load);

        $cart->id_carrier = $carrier->id;
        $cart->add();

        foreach ($productsNode->Product as $product) {
            if (Configuration::get('SHOPPING_FLUX_REF') == 'true') {
                $skus = $this->getIDs($product->SKU);
            } else {
                $skus = explode('_', $product->SKU);
            }

            $p = new Product((int)($skus[0]), false, Configuration::get('PS_LANG_DEFAULT'), Context::getContext()->shop->id);

            if (!Validate::isLoadedObject($p)) {
                return false;
            }

            $added = $cart->updateQty((int)($product->Quantity), (int)($skus[0]), ((isset($skus[1])) ? $skus[1] : null));

            if ($added < 0 || $added === false) {
                return false;
            }
        }

        if (isset($fees) && $fees > 0 && Configuration::get('SHOPPING_FLUX_FDG') != '') {
            if (!$cart->updateQty(1, Configuration::get('SHOPPING_FLUX_FDG'), null)) {
                return false;
            }
        }

        $cart->update();
        return $cart;
    }

    private function _checkProducts($productsNode)
    {
        $available = true;

        foreach ($productsNode->Product as $product) {
            if (Configuration::get('SHOPPING_FLUX_REF') == 'true') {
                $skus = $this->getIDs($product->SKU);
            } else {
                $skus = explode('_', $product->SKU);
            }

            if ($skus[1] !== false) {
                $quantity = StockAvailable::getQuantityAvailableByProduct((int)$skus[0], (int)$skus[1]);

                if ($quantity - $product->Quantity < 0) {
                    StockAvailable::updateQuantity((int)$skus[0], (int)$skus[1], (int)$product->Quantity);
                }
            } else {
                $quantity = StockAvailable::getQuantityAvailableByProduct((int)$product->SKU);

                if ($quantity - $product->Quantity < 0) {
                    StockAvailable::updateQuantity((int)$product->SKU, 0, (int)$product->Quantity);
                }
            }
        }

        return $available;
    }

    private function _validOrders($id_order, $marketplace, $id_order_merchant = false, $error = false)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<ValidOrders>';
        $xml .= '<Order>';
        $xml .= '<IdOrder>'.$id_order.'</IdOrder>';
        $xml .= '<Marketplace>'.$marketplace.'</Marketplace>';

        if ($id_order_merchant) {
            $xml .= '<MerchantIdOrder>'.$id_order_merchant.'</MerchantIdOrder>';
        }

        if ($error) {
            $xml .= '<ErrorOrder><![CDATA['.$error.']]></ErrorOrder>';
        }

        $xml .= '</Order>';
        $xml .= '</ValidOrders>';

        $this->_callWebService('ValidOrders', $xml);
    }

    private function _setShoppingFeedId()
    {
        $login = Configuration::get('SHOPPING_FLUX_LOGIN');
        $id = Configuration::get('SHOPPING_FLUX_ID');

        if (empty($login) || !empty($id)) {
            return;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<GetClientId>';
        $xml .= '<Login>'.$login.'</Login>';
        $xml .= '</GetClientId>';

        $getClientId = $this->_callWebService('GetClientId', $xml);

        if (!is_object($getClientId)) {
            return;
        }

        Configuration::updateValue('SHOPPING_FLUX_ID', (string)$getClientId->Response->ID);
    }

    private function _translateField($field)
    {
        $translations = array(
            'FR' => array(
                'product' => 'produit',
                'supplier_link' => 'url-fournisseur',
                'manufacturer_link' => 'url-fabricant',
                'on_sale' => 'solde',
                'name' => 'nom',
                'link' => 'url',
                'short_description' => 'description-courte',
                'price' => 'prix',
                'old_price' => 'prix-barre',
                'shipping_cost' => 'frais-de-port',
                'shipping_delay' => 'delai-livraison',
                'brand' => 'marque',
                'category' => 'rayon',
                'quantity' => 'quantite',
                'weight' => 'poids',
                'ecotax' => 'ecotaxe',
                'vat' => 'tva',
                'mpn' => 'ref-constructeur',
                'supplier_reference' => 'ref-fournisseur',
                'category_breadcrumb' => 'fil-ariane',
                'combination_link' => 'url-declinaison',
                'total_weight' => 'poids-total',
            )
        );

        $iso_code = $this->default_country->iso_code;

        if (isset($translations[$iso_code][$field])) {
            return $translations[$iso_code][$field];
        }

        return $field;
    }

    /**
     * Get Tthe user's IP handling if there is a proxy
     * @param String $ip optionnal IP comming from the order
     */
    private function getIp($ip = null)
    {
        if (empty($ip)) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }
        return $ip;
    }
    
    /**
     * Get additional fields from Product.php override
     */
    private function getOverrideFields()
    {
        // Load core Product info
        
        static $definition;
        
        // Load override Product info
        $overrideProductFields = Product::$definition['fields'];
        $overrideFields = array();
        
        $newFields = array();
        
        $productCoreFields = ProductCore::$definition['fields'];
        $coreFields = array();
        
        foreach ($productCoreFields as $key => $value) {
            $coreFields[] = $key;
        }
        
        foreach ($overrideProductFields as $key => $value) {
            if (!in_array($key, $coreFields)) {
                $newFields[] = $key;
            }
        }
        
        return $newFields;
    }
    
    /**
     * Function to display fields found in Product class override
     * @param $configuration
     */
    private function getOverrideFieldsContent($configuration)
    {
        $fields = $this->getOverrideFields();
        $message = '';
        if (count($fields) == 0) {
            $message = '<span style="display: block; padding: 3px 0 0 0;">';
            $message .= $this->l('No additional field found on your website').'</span>';
        }
        
        $html = '';
        $html .= '<p></p>';
        $html .= '<p><label>'.$this->l('Select additional fields to export').' :</label>'.$message.'</p>';
        $html .= '<p style="clear: both"></p>';

        foreach ($fields as $key => $field) {
            $html .= '<p><label>'.$field.' : </label>';
            $html .= '<input type="checkbox" name="SHOPPING_FLUX_CUSTOM_'.$field.'" ';
            $html .= 'value="1" '.($configuration['SHOPPING_FLUX_CUSTOM_'.$field] == 1 ? 'checked="checked"' : '');
            $html .= '/></p>';
        }
        return $html;
    }
    
    
    /**
     * Function to create new CDiscount fees product
     *
     * @param $configuration
     */
    private function getFDGContent($configuration)
    {
        $html = '';
        $html .= '<p style="clear: both"><label>'.$this->l('FDG');
        $html .= ' :</label><span style="display: block; padding: 3px 0 0 0;">'.$this->getOrCreateFdgProduct().'</span></p>';
        $html .= '<p style="clear: both"></p>';
        return $html;
    }
    
    /**
     * Returns the FDG product, if not existing we create it
     */
    private function getOrCreateFdgProduct()
    {
        $fdg = Configuration::get('SHOPPING_FLUX_FDG');
        $languages = Language::getLanguages(false);
    
        if (empty($fdg)) {
            // Create new FDG Product, not visible in front office
            $product = new Product();
            foreach ($languages as $language) {
                $product->name[$language['id_lang']] = 'CDiscount fees';
                $product->link_rewrite[$language['id_lang']] = 'fdg';
            }
            $product->id_category_default = Configuration::get('PS_HOME_CATEGORY');
            $product->active = 1;
            $product->visibility = 'none';
            $product->price = 0;
            $product->out_of_stock = 1;
            $product->reference = 'FDG';
            $product->add();
    
            // Retrieve FDG product id after save
            Configuration::updateValue('SHOPPING_FLUX_FDG', $product->id);
    
            $id_stock_available = (int)StockAvailable::getStockAvailableIdByProductId($product->id, 0, 1);
            $stock_available = new StockAvailable($id_stock_available);
            $stock_available->out_of_stock = 1;
            $stock_available->update();
        } else {
            $product = new Product($fdg);
    
            if (Validate::isLoadedObject($product)) {
                // Poruct exists, we check if it can be ordered when out of stock
                if ($product->out_of_stock == 0 || $product->out_of_stock == 2) {
                    $product->out_of_stock = 1;
                    $product->reference = 'FDG';
                    $product->update();
    
                    $id_stock_available = (int)StockAvailable::getStockAvailableIdByProductId($product->id, 0, 1);
                    $stock_available = new StockAvailable($id_stock_available);
                    $stock_available->out_of_stock = 1;
                    $stock_available->update();
                }
            } else {
                // Create new FDG Product, not visible in front office since it does not exist
                $product = new Product();
                foreach ($languages as $language) {
                    $product->name[$language['id_lang']] = 'CDiscount fees';
                    $product->link_rewrite[$language['id_lang']] = 'fdg';
                }
                $product->id_category_default = Configuration::get('PS_HOME_CATEGORY');
                $product->active = 1;
                $product->visibility = 'none';
                $product->price = 0;
                $product->out_of_stock = 1;
                $product->reference = 'FDG';
                $product->add();
    
                $id_stock_available = (int)StockAvailable::getStockAvailableIdByProductId($product->id, 0, 1);
                $stock_available = new StockAvailable($id_stock_available);
                $stock_available->out_of_stock = 1;
                $stock_available->update();
    
                // Retrieve FDG product id after save
                Configuration::updateValue('SHOPPING_FLUX_FDG', $product->id);
            }
        }
    
        return Configuration::get('SHOPPING_FLUX_FDG');
    }
    
    /**
     * Function to display cron information
     * @param $configuration
     */
    private function getCronDetails($configuration)
    {
        $id_shop = $this->context->shop->id;
        $html = '';
        $html .= '<p style="clear: both"><label>';
        $html .= '<p style="clear: both"><label>'.$this->l('Cron last generated date');
        $html .= ' :</label><span style="display: block; padding: 3px 0 0 0;">';
        if (Configuration::get('PS_SHOPPINGFLUX_CRON_TIME', null, null, $id_shop) != '') {
            $cronTime = Configuration::get('PS_SHOPPINGFLUX_CRON_TIME', null, null, $id_shop);
            $html .= Tools::displayDate($cronTime, $configuration['PS_LANG_DEFAULT'], true, '/');
        } else {
            $html .= 'Jamais';
        }
        $html .= '</span></p>';
        $html .= '<p style="clear: both"><label>';
        $html .= $this->l('Number of products to treat at each CRON\'s URL call (do not modify)');
        $html .= ' : </label><input type="text" name="SHOPPING_FLUX_PASSES" value="';
        $html .= Tools::safeOutput($configuration['SHOPPING_FLUX_PASSES']).'"/></p>';
        return $html;
    }
    
    /**
     * log a debug trace into a log file
     *
     * @param string $toLog the string to log
     */
    public function logDebug($toLog)
    {
        if ($this->debug) {
            $outputFile = _PS_MODULE_DIR_ . 'shoppingfluxexport/logs/cronexport_'.Configuration::get('SHOPPING_FLUX_TOKEN').'.txt';
            $fp = fopen($outputFile, 'a');
            fwrite($fp, chr(10) . date('d/m/Y h:i:s A') . ' - ' . $toLog);
            fclose($fp);
        }
    }
    
    /**
     * empty log file
     */
    private function emptyLog()
    {
        if ($this->debug) {
            $outputFile = _PS_MODULE_DIR_ . 'shoppingfluxexport/logs/cronexport_'.Configuration::get('SHOPPING_FLUX_TOKEN').'.txt';
            unlink($outputFile);
        }
    }
    
    /**
     * log a debug trace into a log file
     *
     * @param string $toLog the string to log
     */
    public function logCallWebservice($toLog)
    {
        if ($this->debug) {
            $outputFile = _PS_MODULE_DIR_ . 'shoppingfluxexport/logs/callWebService_'.Configuration::get('SHOPPING_FLUX_TOKEN').'.txt';
            $fp = fopen($outputFile, 'a');
            fwrite($fp, chr(10) . date('d/m/Y h:i:s A') . ' - ' . $toLog);
            fclose($fp);
        }
    }
    
    /**
     * Function to display curl information
     *
     * @param $configuration
     *
     */
    private function defaultAdvancedParameterInformationView($configuration)
    {
        $html = '<form method="post" action="'.Tools::safeOutput($_SERVER['REQUEST_URI']).'">';
        $html .= '<fieldset>';
        $html .= '<legend>'.$this->l('Advanced settings').'</legend>';
        $html .= $this->getFDGContent($configuration);
        $html .= $this->getCronDetails($configuration);
        $html .= '<p style="margin-top:20px"><input type="submit" value="'.$this->l('Update');
        $html .= '" name="rec_config_adv" class="button"/></p>';
        $html .= '</fieldset>';
        $html .= '</form>';
    
        return $html;
    }
    
    /**
     * Function to display curl information
     *
     * @param $configuration
     *
     */
    private function defaultInformationView($configuration)
    {
        $html = '<fieldset>';
        $html .= '<legend>'.$this->l('Prerequisites').'</legend>';
        $html .= '<p style="clear: both"><label>'.$this->l('CURL Technology').' : </label>';
        $html .= '<span style="display: block; padding: 3px 0 0 0;">'.$this->isCurlInstalled().'</span></p>';
        $html .= '<p><label>'.$this->l('Open URL').' : </label><span style="display: block; padding: 3px 0 0 0;">';
        $html .= $this->isFopenAllowed().'</span></p>';
        $html .= '</fieldset>';
        
        return $html;
    }
    
    
    /**
     * Function to check if curl is installed
     */
    private function isCurlInstalled()
    {
        if (in_array('curl', get_loaded_extensions())) {
            $response = $this->l('Active (correct)');
        } else {
            $response = $this->l('Not installed (incorrect)');
        }
        return $response;
    }

    /**
     * Function to check if fopen is allowed
     */
    private function isFopenAllowed()
    {
        if (ini_get('allow_url_fopen')) {
            $response = $this->l('Active (correct)');
        } else {
            $response = $this->l('Not installed (incorrect)');
        }
        return $response;
    }
    
    /**
     * On order creation, send XML notification to ShoppingFlux
     */
    public function hookActionObjectAddAfter($params)
    {
        if ($params['object'] instanceof Order) {
            $ip = Db::getInstance()->getValue('SELECT `ip` FROM `'._DB_PREFIX_.'customer_ip` WHERE `id_customer` = 
                '.(int)$params['object']->id_customer);
            $ip = $this->getIp($ip);
            
            if (Configuration::get('SHOPPING_FLUX_TRACKING') != '' && Configuration::get('SHOPPING_FLUX_ID') != ''
                && $params['object']->module != 'sfpayment') {
                Tools::file_get_contents('https://tag.shopping-flux.com/order/'
                    .base64_encode(Configuration::get('SHOPPING_FLUX_ID').'|'.$params['object']->id.'|'
                        .$params['object']->total_paid).'?ip='.$ip);
            }
            
            if (Configuration::get('SHOPPING_FLUX_STOCKS') != '' && $params['object']->module != 'sfpayment') {
                foreach ($params['cart']->getProducts() as $product) {
                    $id = (isset($product['id_product_attribute'])) ? (int)$product['id_product']
                    .'_'.(int)$product['id_product_attribute'] : (int)$product['id_product'];
                    $qty = (int)$product['stock_quantity'] - (int)$product['quantity'];
            
                    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
                    $xml .= '<UpdateProduct>';
                    $xml .= '<Product>';
                    $xml .= '<SKU>'.$id.'</SKU>';
                    $xml .= '<Quantity>'.$qty.'</Quantity>';
                    $xml .= '</Product>';
                    $xml .= '</UpdateProduct>';
            
                    $this->_callWebService('UpdateProduct', $xml);
                }
            }
        }
    }
}
