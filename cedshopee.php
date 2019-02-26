<?php
/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement(EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://cedcommerce.com/license-agreement.txt
 *
 * @author    CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright Copyright CEDCOMMERCE(http://cedcommerce.com/)
 * @license   http://cedcommerce.com/license-agreement.txt
 * @category  Ced
 * @package   Cedshopee
 */

if (!defined('_PS_VERSION_')) {
    exit;
}
if (!function_exists('curl_version')) {
    exit;
}

include_once(_PS_MODULE_DIR_ . 'cedshopee/classes/CedShopeeLibrary.php');

class CedShopee extends Module
{
    public $fields_form = array();
    public function __construct()
    {
        $this->name = 'cedshopee';
        $this->tab = 'administration';
        $this->version = '0.0.1';
        $this->author = 'CedCommerce';
        $this->bootstrap = true;
        $this->need_instance = 1;
         $this->module_key = 'a5e9830e9ca4ef5b71ca0d3b7f5839ac';

        $this->controllers = array('validation');
        if (version_compare(_PS_VERSION_, '1.7.0', '>=') === true) {
            $this->secure_key = Tools::hash($this->name);
        } else {
            $this->secure_key = Tools::encrypt($this->name);
        }

        $this->is_eu_compatible = 1;
        $this->currencies = false;
        $this->displayName = $this->l('Shopee Integration');
        $this->description = $this->l(
            'Allow merchants to integrate their Prestashop shop with Shopee marketplace.'
        );
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        $this->ps_versions_compliancy = array('min' => '1.6.0.0', 'max' => _PS_VERSION_);
        parent::__construct();
    }

    public function install()
    {
        require_once _PS_MODULE_DIR_ . 'cedshopee/sql/installTables.php';
        if (!parent::install()
            || !$this->installTab(
                'AdminCedShopee',
                'Shopee Integration',
                0
            )
            || !$this->installTab(
                'AdminCedShopeeCategory',
                'Shopee Category',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->installTab(
                'AdminCedShopeeProfile',
                'Shopee Profile',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->installTab(
                'AdminCedShopeeProduct',
                'Shopee Products',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->installTab(
                'AdminCedShopeeBulkUploadProduct',
                'Shopee Bulk Upload',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->installTab(
                'AdminCedShopeeUpdateStatus',
                'Shopee Update Status',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->installTab(
                'AdminCedShopeeOrder',
                'Shopee Orders',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->installTab(
                'AdminCedShopeeFailedOrder',
                'Shopee Failed Orders',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->installTab(
                'AdminCedShopeeLogs',
                'Shopee Logs',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->installTab(
                'AdminCedShopeeLogistics',
                'Shopee Logistics',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->installTab(
                'AdminCedShopeeReturn',
                'Shopee Return',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->installTab(
                'AdminCedShopeeDiscount',
                'Shopee Discount',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->installTab(
                'AdminCedShopeeConfig',
                'Shopee Configuration',
                (int)Tab::getIdFromClassName('AdminCedShopee')
            )
            || !$this->registerHook('actionProductUpdate')
            || !$this->registerHook('actionProductDelete')
            || !$this->registerHook('actionUpdateQuantity')
            || !$this->registerHook('actionOrderStatusPostUpdate')
            || !$this->registerHook('displayBackOfficeHeader')
        ) {
            return false;
        }
        if (!Configuration::get('PS_ORDER_RETURN')) {
            Configuration::updateValue('PS_ORDER_RETURN', 1);
        }
        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()
            || !$this->uninstallTab('AdminCedShopee')
            || !$this->uninstallTab('AdminCedShopeeCategory')
            || !$this->uninstallTab('AdminCedShopeeProfile')
            || !$this->uninstallTab('AdminCedShopeeProduct')
            || !$this->uninstallTab('AdminCedShopeeBulkUploadProduct')
            || !$this->uninstallTab('AdminCedShopeeUpdateStatus')
            || !$this->uninstallTab('AdminCedShopeeOrder')
            || !$this->uninstallTab('AdminCedShopeeFailedOrder')
            || !$this->uninstallTab('AdminCedShopeeLogs')
            || !$this->uninstallTab('AdminCedShopeeLogistics')
            || !$this->uninstallTab('AdminCedShopeeReturn')
            || !$this->uninstallTab('AdminCedShopeeDiscount')
            || !$this->uninstallTab('AdminCedShopeeConfig')
            || !$this->unregisterHook('displayBackOfficeHeader')
            || !$this->unregisterHook('actionProductUpdate')
            || !$this->unregisterHook('actionProductDelete')
            || !$this->unregisterHook('actionUpdateQuantity')
            || !$this->unregisterHook('actionOrderStatusPostUpdate')
        ) {
            return false;
        }
        if (!Configuration::get('PS_ORDER_RETURN')) {
            Configuration::updateValue('PS_ORDER_RETURN', 1);
        }
        return true;
    }
    /* install tabs on basis of class name given
    * use tab name in frontend
    * install under the parent tab given
    */
    public function installTab($class_name, $tab_name, $parent)
    {
        $tab = new Tab();
        $tab->active = 1;
        if ($class_name == 'AdminCedShopeeBulkUploadProduct' || $class_name == 'AdminCedShopeeUpdateStatus') {
            $tab->active = 0;
        }
        $tab->class_name = $class_name;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tab_name;
        }
        if ($parent == 0 && _PS_VERSION_ >= '1.7') {
            $tab->id_parent = (int)Tab::getIdFromClassName('SELL');
            $tab->icon = 'C';
        } else {
            $tab->id_parent = $parent;
        }
        $tab->module = $this->name;
        return $tab->add();
    }
    /**
     * uninstall tabs created by module
     */
    public function uninstallTab($class_name)
    {
        $id_tab = (int)Tab::getIdFromClassName($class_name);
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        } else {
            return false;
        }
    }
    
    public function initContent()
    {
        if (Tools::getIsset('ajax') && Tools::getValue('ajax')) {
            $this->ajax = true;
        }
        parent::initContent();
    }
    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $output = null;
        if (Tools::isSubmit('submitCedshopeeModule')) {
            $form_values = $this->getConfigFormValues();
            foreach (array_keys($form_values) as $key) {
                if (Tools::getIsset($key)) {
                    Configuration::updateValue(trim($key), Tools::getValue($key));
                }
            }
            $partner_id = Configuration::get('CEDSHOPEE_PARTNER_ID');
            $shop_id = Configuration::get('CEDSHOPEE_SHOP_ID');
            $signature = Configuration::get('CEDSHOPEE_SIGNATURE');
            $price_type = Tools::getValue('CEDSHOPEE_PRICE_VARIANT_TYPE');
            $order_email = Tools::getValue('CEDSHOPEE_ORDER_EMAIL');
            if (isset($price_type)
                    && ($price_type == 'increase_fixed' || $price_type == 'decrease_fixed')
                ) {
                $fixed_price = Tools::getValue('CEDSHOPEE_PRICE_VARIANT_FIXED');
                if (empty($fixed_price) || !Validate::isFloat($fixed_price)) {
                    $output .= $this->displayError($this->l('The fixed price should be a valid number'));
                }
            } elseif (isset($price_type)
                    && ($price_type == 'increase_per' || $price_type == 'decrease_per')
                ) {
                $fixed_per = Tools::getValue('CEDSHOPEE_PRICE_VARIANT_PER');
                if (empty($fixed_per) || !Validate::isFloat($fixed_per)) {
                    $output .= $this->displayError($this->l('The fixed percentage price should be a valid number'));
                }
            } elseif (!Validate::isEmail($order_email)) {
                $output .= $this->displayError($this->l('Order email is invalid'));
            } else {
                if (empty($partner_id)
                    || empty($shop_id)
                    || empty($signature)
                ) {
                    $output .= $this->displayError($this->l('Invalid Api credentials. '));
                } else {
                    $output .= $this->displayConfirmation("Shopee configuration saved successfully");
                }
            }
        }
        return $output . $this->getConfigForm();
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'CEDSHOPEE_ENABLE' => Configuration::get('CEDSHOPEE_ENABLE') ?
                Configuration::get('CEDSHOPEE_ENABLE') : 0,
            'CEDSHOPEE_LIVE_MODE' => Configuration::get('CEDSHOPEE_LIVE_MODE') ?
                Configuration::get('CEDSHOPEE_LIVE_MODE') : 'sandbox',
            'CEDSHOPEE_API_URL' => Configuration::get('CEDSHOPEE_API_URL') ? Configuration::get('CEDSHOPEE_API_URL') : '',
            'CEDSHOPEE_PARTNER_ID' => Configuration::get('CEDSHOPEE_PARTNER_ID') ?
                Configuration::get('CEDSHOPEE_PARTNER_ID') : '',
            'CEDSHOPEE_SHOP_ID' => Configuration::get('CEDSHOPEE_SHOP_ID') ? Configuration::get('CEDSHOPEE_SHOP_ID') : '',
            'CEDSHOPEE_SIGNATURE' => Configuration::get('CEDSHOPEE_SIGNATURE') ? Configuration::get('CEDSHOPEE_SIGNATURE') : '',
            'CEDSHOPEE_VALIDATE_BUTTON' => Configuration::get('CEDSHOPEE_VALIDATE_BUTTON') ?
                Configuration::get('CEDSHOPEE_VALIDATE_BUTTON') : '',
            'CEDSHOPEE_ORDER_EMAIL' => Configuration::get('CEDSHOPEE_ORDER_EMAIL') ?
                Configuration::get('CEDSHOPEE_ORDER_EMAIL') : 'order@walmart.com',
            'CEDSHOPEE_REJECTED_ORDER' => Configuration::get('CEDSHOPEE_REJECTED_ORDER') ?
                Configuration::get('CEDSHOPEE_REJECTED_ORDER') : 0,
            'CEDSHOPEE_PRICE_VARIANT_TYPE' => Configuration::get('CEDSHOPEE_PRICE_VARIANT_TYPE') ?
                Configuration::get('CEDSHOPEE_PRICE_VARIANT_TYPE') : 0,
            'CEDSHOPEE_PRICE_VARIANT_FIXED' => Configuration::get('CEDSHOPEE_PRICE_VARIANT_FIXED') ?
                Configuration::get('CEDSHOPEE_PRICE_VARIANT_FIXED') : '',
            'CEDSHOPEE_PRICE_VARIANT_PER' => Configuration::get('CEDSHOPEE_PRICE_VARIANT_PER') ?
                Configuration::get('CEDSHOPEE_PRICE_VARIANT_PER') : '',
            'CEDSHOPEE_ENABLE_CRON_SYNC' => Configuration::get('CEDSHOPEE_ENABLE_CRON_SYNC') ?
                Configuration::get('CEDSHOPEE_ENABLE_CRON_SYNC') : 0,
            'CEDSHOPEE_UPDATE_INVENTORY_EDIT' => Configuration::get('CEDSHOPEE_UPDATE_INVENTORY_EDIT')
                ? Configuration::get('CEDSHOPEE_UPDATE_INVENTORY_EDIT') : 0,
            'CEDSHOPEE_UPDATE_PRICE_EDIT' => Configuration::get('CEDSHOPEE_UPDATE_PRICE_EDIT') ?
                Configuration::get('CEDSHOPEE_UPDATE_PRICE_EDIT') : 0,
            'CEDSHOPEE_UPDATE_WHOLE_INFO' => Configuration::get('CEDSHOPEE_UPDATE_WHOLE_INFO') ?
                Configuration::get('CEDSHOPEE_UPDATE_WHOLE_INFO') : 0,
            'CEDSHOPEE_DEBUG_MODE' => Configuration::get('CEDSHOPEE_DEBUG_MODE') ?
                Configuration::get('CEDSHOPEE_DEBUG_MODE') : '',
            'CEDSHOPEE_PRODUCT_UPLOAD' => Configuration::get('CEDSHOPEE_PRODUCT_UPLOAD') ?
                Configuration::get('CEDSHOPEE_PRODUCT_UPLOAD') : '',
            'CEDSHOPEE_SYNC_QUANTITY' => Configuration::get('CEDSHOPEE_SYNC_QUANTITY') ?
                Configuration::get('CEDSHOPEE_SYNC_QUANTITY') : '',
            'CEDSHOPEE_SYNC_PRICE' => Configuration::get('CEDSHOPEE_SYNC_PRICE') ?
                Configuration::get('CEDSHOPEE_SYNC_PRICE') : '',
            'CEDSHOPEE_FETCH_ORDER' => Configuration::get('CEDSHOPEE_FETCH_ORDER') ?
                Configuration::get('CEDSHOPEE_FETCH_ORDER') : '',
            'CEDSHOPEE_ORDER_STATE_IMPORT' => Configuration::get('CEDSHOPEE_ORDER_STATE_IMPORT') ?
                Configuration::get('CEDSHOPEE_ORDER_STATE_IMPORT') : '',
            'CEDSHOPEE_ORDER_STATE_ACKNOWLEDGE' => Configuration::get('CEDSHOPEE_ORDER_STATE_ACKNOWLEDGE') ?
                Configuration::get('CEDSHOPEE_ORDER_STATE_ACKNOWLEDGE') : '',
            'CEDSHOPEE_ORDER_STATE_CANCEL' => Configuration::get('CEDSHOPEE_ORDER_STATE_CANCEL') ?
                Configuration::get('CEDSHOPEE_ORDER_STATE_CANCEL') : '',
            'CEDSHOPEE_ORDER_STATE_SHIPPED' => Configuration::get('CEDSHOPEE_ORDER_STATE_SHIPPED') ?
                Configuration::get('CEDSHOPEE_ORDER_STATE_SHIPPED') : '',
            'CEDSHOPEE_ORDER_CARRIER' => Configuration::get('CEDSHOPEE_ORDER_CARRIER') ?
                Configuration::get('CEDSHOPEE_ORDER_CARRIER') : '',
            'CEDSHOPEE_CRON_SECURE_KEY' => Configuration::get('CEDSHOPEE_CRON_SECURE_KEY') ?
                Configuration::get('CEDSHOPEE_CRON_SECURE_KEY'): '',
        );
    }
    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    public function getConfigForm()
    {
        $fields_form = array();
        $fields_form[0]['form'] = $this->getGeneralSettingForm();
        $fields_form[1]['form'] = $this->getProductSettingForm();
        $fields_form[2]['form'] = $this->getOrderSettingForm();
        $fields_form[3]['form'] = $this->getCronInfoForm();
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get(
            'PS_BO_ALLOW_EMPLOYEE_FORM_LANG'
        ) ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCedshopeeModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) .
            '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );
        return $helper->generateForm($fields_form);
    }
    /*
    * General form details
    */
    public function getGeneralSettingForm()
    {
        $this->context->smarty->assign(array(
            'base_url' => Context::getContext()->shop->getBaseURL(true)
        ));
        $validate_credentials = $this->display(
            __FILE__,
            'views/templates/admin/configuration/validate_credentials.tpl'
        );

        return array(
            'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'type' => 'switch',
                    'label' => $this->l('Enable'),
                    'name' => 'CEDSHOPEE_ENABLE',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'col' => 6,
                    'type' => 'select',
                    'label' => $this->l('Mode'),
                    'name' => 'CEDSHOPEE_LIVE_MODE',
                    'required' => true,
                    'id' => 'CEDSHOPEE_LIVE_MODE',
                    'desc' => $this->l('Use this module in live mode'),
                    'default_value' => '',
                    'options' => array(
                        'query' => array(
                            array('value' => 'live', 'label' => 'Live'),
                            array('value' => 'sandbox', 'label' => 'Sandbox'),
                        ),
                        'id' => 'value',
                        'name' => 'label',
                    )
                ),
                array(
                    'col' => 6,
                    'type' => 'text',
                    'id' => 'CEDSHOPEE_API_URL',
                    'required' => true,
                    'readonly' => true,
                    'prefix' => '<i class="icon icon-link"></i>',
                    'name' => 'CEDSHOPEE_API_URL',
                    'label' => $this->l(' API URL'),
                ),
                array(
                    'col' => 6,
                    'type' => 'text',
                    'required' => true,
                    'name' => 'CEDSHOPEE_PARTNER_ID',
                    'desc' => $this->l('Must be a valid number.'),
                    'label' => $this->l('Partner ID'),
                ),
                array(
                    'col' => 6,
                    'type' => 'text',
                    'required' => true,
                    'name' => 'CEDSHOPEE_SHOP_ID',
                    'desc' => $this->l('Must be a valid number.'),
                    'label' => $this->l('Shop ID'),
                ),
                array(
                    'col' => 6,
                    'type' => 'text',
                    'required' => true,
                    'name' => 'CEDSHOPEE_SIGNATURE',
                    'label' => $this->l('Signature'),
                ),
                array(
                    'cols' => 6,
                    'type' => 'html',
                    'label' => $this->l('Click to Validate'),
                    'name' => $validate_credentials,
                ),
            ),
        );
    }
    /*
    * Product form details
    */
    public function getProductSettingForm()
    {
        $this->context->smarty->assign(array(
            'CEDSHOPEE_PRICE_VARIATION_TYPE' => Configuration::get('CEDSHOPEE_PRICE_VARIATION_TYPE')
        ));
        $price_variation_html = $this->context->smarty
            ->fetch(_PS_MODULE_DIR_ . 'cedshopee/views/templates/admin/configuration/price_variation.tpl');

        return array(
            'legend' => array(
                'title' => $this->l('Product Setting'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'col' => 6,
                    'type' => 'html',
                    'label' => $this->l('Price Variation'),
                    'name' => $price_variation_html,
                ),
                array(
                    'col' => 6,
                    'type' => 'text',
                    'id' => 'fixed_price',
                    'prefix' => '<i class="icon icon-money"></i>',
                    'desc' => $this->l('Enter the Fixed amount which is to be added 
                    in product default price while creating or updating product feed for Shopee.'),
                    'name' => 'CEDSHOPEE_PRICE_VARIANT_FIXED',
                    'label' => $this->l(' Fixed Amount'),
                ),
                array(
                    'col' => 6,
                    'type' => 'text',
                    'id' => 'fixed_per',
                    'prefix' => '<i class="icon icon-money"></i>',
                    'desc' => $this->l('Enter the Fixed percent which is to be added 
                    in product default price while creating or updating product feed for Shopee. 
                    Do not include any symbol like "%" etc.'),
                    'name' => 'CEDSHOPEE_PRICE_VARIANT_PER',
                    'label' => $this->l(' Fixed Percentage'),
                ),
                // array(
                //     'col' => 6,
                //     'type' => 'select',
                //     'label' => $this->l('Price Variation from Store'),
                //     'name' => 'CEDSHOPEE_PRICE_VARIANT_TYPE',
                //     'required' => false,
                //     'id' => 'CEDSHOPEE_PRICE_VARIANT_TYPE',
                //     'desc' => $this->l(''),
                //     'default_value' => '',
                //     'options' => array(
                //         'query' => array(
                //             array('value' => '1', 'label' => 'Default Price'),
                //             array('value' => '2', 'label' => 'Increase By Fixed Amount'),
                //             array('value' => '3', 'label' => 'Decrease By Fixed Amount'),
                //             array('value' => '4', 'label' => 'Increase By Fixed Percent'),
                //             array('value' => '5', 'label' => 'Decrease By Fixed Percent'),
                //         ),
                //         'id' => 'value',
                //         'name' => 'label',
                //     )
                // ),
                // array(
                //     'col' => 6,
                //     'type' => 'text',
                //     'required' => true,
                //     'name' => 'CEDSHOPEE_PRICE_VARIANT_AMOUNT',
                //     'label' => $this->l('Variant Amount'),
                // ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Enable Cron Sync'),
                    'name' => 'CEDSHOPEE_ENABLE_CRON_SYNC',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Update Inventory Edit'),
                    'name' => 'CEDSHOPEE_UPDATE_INVENTORY_EDIT',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Update Price on Edit'),
                    'name' => 'CEDSHOPEE_UPDATE_PRICE_EDIT',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Update Whole Info'),
                    'name' => 'CEDSHOPEE_UPDATE_WHOLE_INFO',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Debug Mode'),
                    'name' => 'CEDSHOPEE_DEBUG_MODE',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
            ),
        );
    }
    /*
    * Order form details
    */
    public function getOrderSettingForm()
    {

        $db = Db::getInstance();
        $id_lang = ((int)Configuration::get('CEDSHOPEE_LANGUAGE_STORE')) ?
            (int)Configuration::get('CEDSHOPEE_LANGUAGE_STORE')
            : (int)Configuration::get('PS_LANG_DEFAULT');

        $order_states = $db->ExecuteS("SELECT `id_order_state`,`name` 
       FROM `" . _DB_PREFIX_ . "order_state_lang` where `id_lang` = '" . (int)$id_lang . "'");

        $order_carriers = $db->ExecuteS("SELECT `id_carrier`,`name` 
        FROM `" . _DB_PREFIX_ . "carrier` where `active` = '1'");

        $payment_methods = array();

        $modules_list = Module::getPaymentModules();

        foreach ($modules_list as $module) {
            $module_obj = Module::getInstanceById($module['id_module']);
            if ($module_obj) {
                array_push($payment_methods, array('id' => $module_obj->name, 'name' => $module_obj->displayName));
            }
        }
        
        return array(
            'legend' => array(
                'title' => $this->l('Order Setting'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'col' => 6,
                    'type' => 'text',
                    'required' => false,
                    'name' => 'CEDSHOPEE_ORDER_EMAIL',
                    'label' => $this->l('Order Email'),
                ),
                array(
                    'type' => 'select',
                    'col' => 6,
                    'label' => $this->l('Order status when Import'),
                    'desc' => $this->l('Order Status While importing order.'),
                    'name' => 'CEDSHOPEE_ORDER_STATE_IMPORT',
                    'required' => false,
                    'default_value' => '',
                    'options' => array(
                        'query' => $order_states,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    )
                ),
                array(
                    'type' => 'select',
                    'col' => 6,
                    'label' => $this->l('Order status when Accepted At Shopee'),
                    'name' => 'CEDSHOPEE_ORDER_STATE_ACKNOWLEDGE',
                    'required' => false,
                    'default_value' => '',
                    'options' => array(
                        'query' => $order_states,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    )
                ),
                array(
                    'type' => 'select',
                    'col' => 6,
                    'label' => $this->l('Order status when cancelled at Shopee'),
                    'desc' => $this->l('Order Status after cancel order.'),
                    'name' => 'CEDSHOPEE_ORDER_STATE_CANCEL',
                    'required' => false,
                    'default_value' => '',
                    'options' => array(
                        'query' => $order_states,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    )
                ),
                array(
                    'type' => 'select',
                    'col' => 6,
                    'label' => $this->l('Order status when Shipped'),
                    'desc' => $this->l('Order Status after order Shipped.'),
                    'name' => 'CEDSHOPEE_ORDER_STATE_SHIPPED',
                    'required' => false,
                    'default_value' => '',
                    'options' => array(
                        'query' => $order_states,
                        'id' => 'id_order_state',
                        'name' => 'name',
                    )
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Auto Reject Order'),
                    'name' => 'CEDSHOPEE_REJECTED_ORDER',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'type' => 'select',
                    'col' => 6,
                    'label' => $this->l('Order Carrier'),
                    'desc' => $this->l('Order Carrier While importing order.'),
                    'name' => 'CEDSHOPEE_ORDER_CARRIER',
                    'required' => false,
                    'default_value' => '',
                    'options' => array(
                        'query' => $order_carriers,
                        'id' => 'id_carrier',
                        'name' => 'name',
                    )
                ),
            ),
        );
    }
    /*
    * Order form details
    */
    public function getCronInfoForm()
    {
        $this->context->smarty->assign(array(
            'base_url' => Context::getContext()->shop->getBaseURL(true),
            'cron_secure_key' => Configuration::get('CEDSHOPEE_CRON_SECURE_KEY')
        ));
        $cron_html = $this->display(
            __FILE__,
            'views/templates/admin/configuration/cron_url.tpl'
        );

        return array(
            'legend' => array(
                'title' => $this->l('Cron Url'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'col' => 6,
                    'type' => 'text',
                    'id' => 'CEDSHOPEE_CRON_SECURE_KEY',
                    'prefix' => '<i class="icon icon-envelope"></i>',
                    'name' => 'CEDSHOPEE_CRON_SECURE_KEY',
                    'label' => $this->l(' Cron Secure Key'),
                    'desc' => $this->l('This cron secure key need to set in 
                    the parameters of following cron urls'),
                ),
                array(
                    'col' => 12,
                    'type'  => 'html',
                    'label'  => $this->l(''),
                    'name' => $cron_html,
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );
    }

    public function ajaxProcessValidateApi()
    {
        $cedshopeeLib = new CedshopeeLibrary;
        $url ='shop/get';
        $params= array();
        $response = $cedshopeeLib->postRequest($url, $params);
        $cedshopeeLib->log('=====validate===', 6, true);
        $cedshopeeLib->log(json_encode($response), 6, true);

        if (isset($response['error']) && $response['error']) {
            Configuration::updateValue('CEDSHOPEE_VALIDATE_STATUS', 0);
            $msg   =   $response['msg'];
            if (empty($msg)) {
                $msg = 'Validation error : Please Check the above details';
            }
                die(json_encode(array('success'=> false, 'message' => $msg)));
        } else {
            Configuration::updateValue('CEDSHOPEE_VALIDATE_STATUS', 1);
            die(json_encode(array('success'=> true, 'message' => 'Woah You are all set. Details Validated Successfully')));
        }
    }


    public function hookDisplayBackOfficeHeader()
    {
        if (!Module::isEnabled($this->name)) {
            return false;
        }
        if (method_exists($this->context->controller, 'addCSS')) {
            $this->context->controller->addCSS($this->_path . 'views/css/tab.css');
        }
        if (method_exists($this->context->controller, 'addJS')) {
            $this->context->controller->addJS(
                _PS_MODULE_DIR_ . 'cedshopee/views/js/admin/configuration/configuration.js'
            );
        }
        // if (method_exists($this->context->controller, 'addJquery')) {
        //     $this->context->controller->addJquery();
        // }
    }
}
