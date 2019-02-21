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

require_once _PS_MODULE_DIR_ . 'cedshopee/classes/CedShopeeLibrary.php';
require_once _PS_MODULE_DIR_ . 'cedshopee/classes/CedShopeeProduct.php';

class AdminCedShopeeProductController extends ModuleAdminController
{
    /**
     * @var string name of the tab to display
     */
    protected $tab_display;

    protected $object;

    protected $product_attributes;

    protected $position_identifier = 'id_product';

    protected $submitted_tabs;

    protected $id_current_category;
    
    public function __construct()
    {
        $this->db = Db::getInstance();
        $this->context = Context::getContext();
        $this->bootstrap = true;
        $this->table = 'product';
        $this->className = 'Product';
        $this->lang = false;
        $this->list_no_link = true;
        $this->explicitSelect = true;
        $this->addRowAction('edit');
        $this->bulk_actions = array(
            'upload' => array(
                'text' => ('Upload selected'),
                'icon' => 'icon-upload',
            ),
            'updateStock' => array(
                'text' => ('Update Quantity'),
                'icon' => 'icon-upload',
            ),
            'updatePrice' => array(
                'text' => ('Update Price'),
                'icon' => 'icon-upload',
            ),
            'remove' => array(
                'text' => ('Remove From Shopee'),
                'icon' => 'icon-trash',
            )
        );
        if (!Tools::getValue('id_product')) {
            $this->multishop_context_group = false;
        }
        parent::__construct();
        /* Join categories table */
        if ($id_category = (int)Tools::getValue('productFilter_cl!name')) {
            $this->_category = new Category((int)$id_category);
            $_POST['productFilter_cl!name'] = $this->_category->name[$this->context->language->id];
        } else {
            if ($id_category = (int)Tools::getValue('id_category')) {
                $this->id_current_category = $id_category;
                $this->context->cookie->id_category_products_filter = $id_category;
            } elseif ($id_category = $this->context->cookie->id_category_products_filter) {
                $this->id_current_category = $id_category;
            }
            if ($this->id_current_category) {
                $this->_category = new Category((int)$this->id_current_category);
            } else {
                $this->_category = new Category();
            }
        }
        $this->_join .= '
        LEFT JOIN `'._DB_PREFIX_.'stock_available` sav ON (sav.`id_product` = a.`id_product` 
        AND sav.`id_product_attribute` = 0
        '.StockAvailable::addSqlShopRestriction(null, null, 'sav').') ';

        $alias = 'sa';
        $alias_image = 'image_shop';

        $id_shop = Shop::isFeatureActive() && Shop::getContext() == Shop::CONTEXT_SHOP ?
            (int)$this->context->shop->id : 'a.id_shop_default';

        $CedShopeeProduct = new CedShopeeProduct();
        $mapped_categories = $CedShopeeProduct->getAllMappedCategories();

        // if ($mapped_categories) {
        //     $walmart_cat_filter = false;
        // } else {
        //     $walmart_cat_filter = true;
        // }
        $catgories =array();
        if ($mapped_categories) {
            // $CedShopeeProduct = new CedShopeeProduct();
            // $mapped_categories = $CedShopeeProduct->getAllMappedCategories();
            $mapped_categories = array_unique($mapped_categories);

            if (is_array($mapped_categories) && count($mapped_categories)) {
                $catgories = $mapped_categories;
                if (count($catgories)) {
                    $this->_join .= ' JOIN `'._DB_PREFIX_.'product_shop` sa ON (a.`id_product` = sa.`id_product` 
                    AND sa.id_shop = '.$id_shop.')

                LEFT JOIN `'._DB_PREFIX_.'product_lang` b ON (a.`id_product` = b.id_product 
                AND b.id_shop = '.$id_shop.' AND b.`id_lang`="'.(int)$this->context->language->id.'")

                LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON ('.$alias.'.`id_category_default` = cl.`id_category` 
                AND b.`id_lang` = cl.`id_lang` AND cl.id_shop = '.$id_shop.')
                LEFT JOIN `'._DB_PREFIX_.'shop` shop ON (shop.id_shop = '.$id_shop.')
                LEFT JOIN `'._DB_PREFIX_.'cedshopee_profile_products` wp ON (wp.product_id = a.`id_product`)
                LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop ON (image_shop.`id_product` = a.`id_product` 
                AND image_shop.`cover` = 1 AND image_shop.id_shop = '.$id_shop.')
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_image` = image_shop.`id_image`)
                LEFT JOIN `'._DB_PREFIX_.'product_download` pd ON (pd.`id_product` = a.`id_product` 
                AND pd.`active` = 1)';
                }
            } else {
                $this->_join .= ' JOIN `'._DB_PREFIX_.'product_shop` sa ON (a.`id_product` = sa.`id_product` 
                AND sa.id_shop = '.$id_shop.')

                LEFT JOIN `'._DB_PREFIX_.'product_lang` b ON (a.`id_product` = b.id_product 
                AND b.id_shop = '.$id_shop.' AND b.`id_lang`="'.(int)$this->context->language->id.'")

                LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON ('.$alias.'.`id_category_default` = cl.`id_category` 
                AND b.`id_lang` = cl.`id_lang` AND cl.id_shop = '.$id_shop.')
                LEFT JOIN `'._DB_PREFIX_.'shop` shop ON (shop.id_shop = '.$id_shop.')
                JOIN `'._DB_PREFIX_.'cedshopee_profile_products` wp ON (wp.product_id = a.`id_product`)
                LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop ON (image_shop.`id_product` = a.`id_product` 
                AND image_shop.`cover` = 1 AND image_shop.id_shop = '.$id_shop.')
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_image` = image_shop.`id_image`)
                LEFT JOIN `'._DB_PREFIX_.'product_download` pd ON (pd.`id_product` = a.`id_product` 
                AND pd.`active` = 1)';
            }
        } else {
            $this->_join .= ' JOIN `'._DB_PREFIX_.'product_shop` sa ON (a.`id_product` = sa.`id_product` 
            AND sa.id_shop = '.$id_shop.')

                LEFT JOIN `'._DB_PREFIX_.'product_lang` b ON (a.`id_product` = b.id_product 
                AND b.id_shop = '.$id_shop.' AND b.`id_lang`="'.(int)$this->context->language->id.'")

                LEFT JOIN `'._DB_PREFIX_.'category_lang` cl ON ('.$alias.'.`id_category_default` = cl.`id_category` 
                AND b.`id_lang` = cl.`id_lang` AND cl.id_shop = '.$id_shop.')
                LEFT JOIN `'._DB_PREFIX_.'shop` shop ON (shop.id_shop = '.$id_shop.')
                JOIN `'._DB_PREFIX_.'cedshopee_profile_products` wp ON (wp.product_id = a.`id_product`)
                LEFT JOIN `'._DB_PREFIX_.'image_shop` image_shop ON (image_shop.`id_product` = a.`id_product` 
                AND image_shop.`cover` = 1 AND image_shop.id_shop = '.$id_shop.')
                LEFT JOIN `'._DB_PREFIX_.'image` i ON (i.`id_image` = image_shop.`id_image`)
                LEFT JOIN `'._DB_PREFIX_.'product_download` pd ON (pd.`id_product` = a.`id_product` 
                AND pd.`active` = 1)';
        }

        
        $this->_select .= 'shop.`name` AS `shopname`, a.`id_shop_default`, ';
        
        $this->_select .= $alias_image.'.`id_image` AS `id_image`, a.`id_product` as `id_temp`, cl.`name` 
        AS `name_category`, '.$alias.'.`price`, 0 
        AS `price_final`, a.`is_virtual`, pd.`nb_downloadable`, sav.`quantity` 
        AS `sav_quantity`, '.$alias.'.`active`, IF(sav.`quantity`<=0, 1, 0) AS `badge_danger`';

        if (!empty($catgories)) {
            $this->_where = 'AND cl.`id_category` IN ('.implode(',', (array)$catgories).')';
        }
        $this->_use_found_rows = true;

        $this->fields_list = array();
        $this->fields_list['id_product'] = array(
            'title' => ('ID'),
            'align' => 'center',
            'class' => 'fixed-width-xs',
            'type' => 'int'
        );
        $this->fields_list['image'] = array(
            'title' => ('Image'),
            'align' => 'center',
            'image' => 'p',
            'orderby' => false,
            'filter' => false,
            'search' => false
        );
        $this->fields_list['name'] = array(
            'title' => ('Name'),
            'filter_key' => 'b!name',
            'class' => 'fixed-width-sm',
        );
        $this->fields_list['reference'] = array(
            'title' => ('Reference'),
            'align' => 'left',
        );


       /* if (Shop::isFeatureActive() && Shop::getContext() != Shop::CONTEXT_SHOP) {
            $this->fields_list['shopname'] = array(
                'title' => ('Default shop'),
                'filter_key' => 'shop!name',
            );
        } else {
            $this->fields_list['name_category'] = array(
                'title' => ('Category'),
                'filter_key' => 'cl!name',
            );
        }*/
        $this->fields_list['price'] = array(
            'title' => ('Base price'),
            'type' => 'price',
            'align' => 'text-right',
            'filter_key' => 'a!price'
        );
        $this->fields_list['price_final'] = array(
            'title' => ('Final price'),
            'type' => 'price',
            'align' => 'text-right',
            'havingFilter' => true,
            'orderby' => false,
            'search' => false
        );
       
        if (Configuration::get('PS_STOCK_MANAGEMENT')) {
            $this->fields_list['sav_quantity'] = array(
                'title' => ('Quantity'),
                'type' => 'int',
                'align' => 'text-right',
                'filter_key' => 'sav!quantity',
                'orderby' => true,
                'badge_danger' => true,
                'hint' => ('This is the quantity available in the current shop/group.'),
            );
        }

        $this->fields_list['active'] = array(
            'title' => ('Status'),
            'active' => 'status',
            'filter_key' => $alias.'!active',
            'align' => 'text-center',
            'type' => 'bool',
            'class' => 'fixed-width-sm',
            'orderby' => false
        );
        
        $this->fields_list['shopee_status'] = array(
            'title' => ('Shopee Status'),
            'type' => 'text',
            'align' => 'text-right',
            'havingFilter' => true,
            'orderby' => true,
            'class' => 'fixed-width-xs',
            'search' => true

        );
        $this->fields_list['shopee_item_id'] = array(
            'title' => ('Shopee Item ID'),
            'type' => 'text',
            'align' => 'text-right',
            'havingFilter' => true,
            'orderby' => true,
            'class' => 'fixed-width-xs',
            'search' => true
        );

        $this->fields_list['error_message'] = array(
            'title' => ('View Details'),
            'align' =>'text-left',
            'search' => false,
            'class' => 'fixed-width-xs',
            'callback' => 'viewDetailsButton',
        );

        if (Tools::getIsset('submitBulkuploadproduct')) {
            if (Tools::getIsset('productBox') && count(Tools::getValue('productBox'))) {
                $this->processBulkUpload(Tools::getValue('productBox'));
            } else {
                $this->errors[] = Tools::displayError('Please Select Product');
            }
        }

        if (Tools::getIsset('submitBulkupdateStockproduct')) {
            if (Tools::getIsset('productBox') && count(Tools::getValue('productBox'))) {
                $this->processBulkUpdateStock(Tools::getValue('productBox'));
            } else {
                $this->errors[] = Tools::displayError('Please Select Product to Update Stock');
            }
        }

        if (Tools::getIsset('submitBulkupdatePriceproduct')) {
            if (Tools::getIsset('productBox') && count(Tools::getValue('productBox'))) {
                $this->processBulkUpdatePrice(Tools::getValue('productBox'));
            } else {
                $this->errors[] = Tools::displayError('Please Select Product to Update Price');
            }
        }

        if (Tools::getIsset('submitBulkremoveproduct')) {
            if (Tools::getIsset('productBox') && count(Tools::getValue('productBox'))) {
                $this->processBulkRemove(Tools::getValue('productBox'));
            } else {
                $this->errors[] = Tools::displayError('Please Select Product to Update Price');
            }
        }

        if (Tools::isSubmit('submitProductSave')) {
            $this->saveProduct();
        }
    }

    public function viewDetailsButton($data, $rowData)
    {
        $productID = isset($rowData['id_product'])?$rowData['id_product']: '';
        $this->context->smarty->assign(
            array(
                'product_id' => $productID,
                'token' => $this->token
            )
        );
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ .'cedshopee/views/templates/admin/product/product_validation_detail.tpl'
        );
    }

    public function ajaxProcessViewDetails()
    {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $productID = Tools::getValue('product_id');
        $json = array();
        if (!empty($productID)) {
            $shopee_item_id = $this->db->getValue("SELECT `shopee_item_id` FROM `". _DB_PREFIX_ ."cedshopee_uploaded_products` WHERE `product_id` = '". $productID ."' ");
            if (!empty($shopee_item_id)) {
                $url = 'item/get';
                $params = array(
                    'item_id' => $shopee_item_id
                    );
                $response = $CedShopeeLibrary->postRequest($url, $params);
                if (isset($response['item'])) {
                    $json = array('success' => true, 'message' => $response['item']);
                } elseif (isset($response['error'])) {
                    $json = array('success' => false, 'message' => $response['error']);
                } elseif (isset($response['msg'])) {
                    $json = array('success' => false, 'message' => $response['msg']);
                } else {
                    $json = array('success' => false, 'message' => 'Item Not Found On Shopee.');
                }
            } else {
                $json = array('success' => false, 'message' => 'Item Not Found On Shopee.');
            }
            die(json_encode($json));
        }
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['upload_all'] = array(
                'href' => $this->context->link->getAdminLink('AdminCedShopeeBulkUploadProduct'),
                'desc' => $this->l('Upload All', null, null, false),
                'icon' => 'process-icon-upload'
            );
            $this->page_header_toolbar_btn['fetchstatus'] = array(
                'href' => $this->context->link->getAdminLink('AdminCedShopeeUpdateStatus'),
                'desc' => 'Update Status',
                'icon' => 'process-icon-download'
            );
        }
        parent::initPageHeaderToolbar();
    }

    public function renderForm()
    {
        $selectedLogistics = array();
        $selectedWholesale = array();
        $CedShopeeLibrary = new CedShopeeLibrary;
        $product_id = Tools::getValue('id_product');
        $shopeeLogistics = $CedShopeeLibrary->getShopeeLogistics();
        $shopeeWholesale = $CedShopeeLibrary->getShopeeWholesale();
        $logistics_list = $CedShopeeLibrary->getLogistics();
        
        if (!empty($product_id)) {
            $productData = $this->getProductById($product_id);
            if (!empty($productData)) {
                $selectedLogistics = $productData['logistics'];
                $selectedWholesale = $productData['wholesale'];
                $this->context->smarty->assign(array(
                    'selectedLogistics' => $selectedLogistics,
                    'selectedWholesale' => $selectedWholesale
                ));
            }
        }

        $this->context->smarty->assign(array(
            'controllerUrl' => $this->context->link->getAdminLink('AdminCedShopeeProduct'),
            'token' => $this->token,
            'product_id' => $product_id,
            'shopeeLogistics' => $shopeeLogistics,
            'shopeeWholesale' => $shopeeWholesale,
            'logistics_list' => $logistics_list
        ));

        $productTemplate = $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedshopee/views/templates/admin/product/edit_product.tpl'
        );
        parent::renderForm();
        return $productTemplate;
    }

    public function getProductById($product_id)
    {
        $response = array();
        $result = $this->db->executeS("SELECT * FROM `". _DB_PREFIX_ ."cedshopee_uploaded_products` WHERE `product_id` = '". $product_id ."' ");
        if (!empty($result)) {
            $productData = $result[0];
            $response['logistics'] = json_decode($productData['logistics'], true);
            $response['wholesale'] = json_decode($productData['wholesale'], true);
            return $response;
        } else {
            return array();
        }
    }

    public function saveProduct()
    {
        $product_id = Tools::getValue('id_product');
        $logistics = Tools::getValue('shopeeLogistics');
        $wholesale = Tools::getValue('shopeeWholesale');
        if (!empty($product_id)) {
            $productExist = $this->db->getValue("SELECT `id` FROM `". _DB_PREFIX_ ."cedshopee_uploaded_products` WHERE `product_id` = '". $product_id ."' ");
            if (!empty($productExist)) {
                $res = $this->db->update(
                    'cedshopee_uploaded_products',
                    array(
                        'logistics' => pSQL(json_encode($logistics)),
                        'wholesale' => pSQL(json_encode($wholesale))
                        ),
                    'id=' . (int)$productExist
                );
                if ($res) {
                    $link = new LinkCore();
                    $controller_link = $link->getAdminLink('AdminCedShopeeProduct').'&updated=1';
                    Tools::redirectAdmin($controller_link);
                    $this->confirmations[] = "Product data updated successfully";
                }
            } else {
                $res = $this->db->insert(
                    'cedshopee_uploaded_products',
                    array(
                        'product_id' => (int)$product_id,
                        'logistics' => pSQL(json_encode($logistics)),
                        'wholesale' => pSQL(json_encode($wholesale))
                        )
                );
                if ($res) {
                    $link = new LinkCore();
                    $controller_link = $link->getAdminLink('AdminCedShopeeProduct').'&created=1';
                    Tools::redirectAdmin($controller_link);
                    $this->confirmations[] = "Product data updated successfully";
                }
            }
        }
    }

    protected function processBulkUpload($product_ids = array())
    {
        if (is_array($product_ids) && count($product_ids)) {
            $CedShopeeProduct = new CedShopeeProduct;
            $result = $CedShopeeProduct->uploadProducts($product_ids);
            if (isset($result['success']) && $result['success']) {
                $this->confirmations[] = json_encode($result['message']);
            } else {
                $this->errors[] = $result['message'];
            }
        }
    }

    public function processBulkUpdateStock($product_ids = array())
    {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $CedShopeeProduct = new CedShopeeProduct;
        try {
            if (is_array($product_ids) && count($product_ids)) {
                $updated = 0;
                $fail = 0;
                if (is_array($product_ids) && count($product_ids)) {
                    foreach ($product_ids as $product_id) {
                        $result = $CedShopeeProduct->updateInventory($product_id);
                        if (isset($result['item'])) {
                            $updated++;
                        } elseif (isset($result['error'])) {
                            $fail++;
                        }
                    }
                }
                if ($updated) {
                    if ($fail) {
                        $this->confirmations[] = $updated . ' Product(s) Updated and '.$fail . 'are failed to update ';
                    } else {
                        $this->confirmations[] = $updated . ' Product(s) Updated';
                    }
                } else {
                    $this->errors[] = 'Unable to update data.';
                }
            } else {
                $this->errors[] = 'Please Select Product to Update Inventory';
            }
        } catch (\Exception $e) {
            $CedShopeeLibrary->log(
                'AdminCedShopeeBulkUploadProductController::updateStock',
                'Exception',
                $e->getMessage(),
                $e->getMessage(),
                true
            );
            die(json_encode(array(
                'status' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    public function processBulkUpdatePrice($product_ids = array())
    {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $CedShopeeProduct = new CedShopeeProduct;
        try {
            if (is_array($product_ids) && count($product_ids)) {
                $updated = 0;
                $fail = 0;
                if (is_array($product_ids) && count($product_ids)) {
                    foreach ($product_ids as $product_id) {
                        $result = $CedShopeeProduct->updatePrice($product_id);
                        if (!isset($result['error'])) {
                            $updated++;
                        } else {
                            $fail++;
                        }
                    }
                }
                if ($updated) {
                    if ($fail) {
                        $this->confirmations[] = $updated . ' Product(s) Updated and '.$fail . 'are failed to update ';
                    } else {
                        $this->confirmations[] = $updated . ' Product(s) Updated';
                    }
                } else {
                    $this->errors[] = 'unable to update data.';
                }
            } else {
                $this->errors[] = 'Please Select Product to Update Price';
            }
        } catch (\Exception $e) {
            $CedShopeeLibrary->log(
                'AdminCedShopeeBulkUploadProductController::updatePrice',
                'Exception',
                $e->getMessage(),
                $e->getMessage(),
                true
            );
            die(json_encode(array(
                'status' => true,
                'message' => $e->getMessage()
            )));
        }
    }

    public function processBulkRemove($product_ids = array())
    {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $CedShopeeProduct = new CedShopeeProduct;
        try {
            if (is_array($product_ids) && count($product_ids)) {
                foreach ($product_ids as $product_id) {
                    $shopee_item_id = $CedShopeeProduct->getShopeeItemId($product_id);
                    $shopee_item_id = isset($shopee_item_id) ? $shopee_item_id : '0';
                    if (!empty($shopee_item_id)) {
                        $requestSent = $CedShopeeLibrary->postRequest('item/delete', array('item_id'=> (int)$shopee_item_id));
                        if (isset($requestSent['item_id'])) {
                            $requestSent['message'] = $requestSent['msg'];
                            $this->db->update(
                                'cedshopee_uploaded_products',
                                array(
                                    'shopee_item_id' => (int) $shopee_item_id,
                                    'shopee_status' => pSQL('Deleted')
                                    ),
                                'product_id="'. (int) $product_id .'"'
                            );
                            $this->confirmations[] = 'Product Deleted Successfully';
                        } else {
                            $this->errors[] = $requestSent['msg'];
                        }
                    } else {
                        $this->errors[] = 'Product Delete failed Item id not Found.';
                    }
                }
            }
        } catch (\Exception $e) {
            $CedShopeeLibrary->log(
                'AdminCedShopeeProductController::Remove',
                'Exception',
                $e->getMessage(),
                $e->getMessage(),
                true
            );
            die(json_encode(array(
                'status' => true,
                'message' => $e->getMessage()
            )));
        }
    }
}
