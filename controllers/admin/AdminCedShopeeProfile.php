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

include_once _PS_MODULE_DIR_ . 'cedshopee/classes/CedShopeeLibrary.php';
include_once _PS_MODULE_DIR_ . 'cedshopee/classes/CedShopeeProfile.php';

/**
* AdminCedShopeeProfileController file
*/
class AdminCedShopeeProfileController extends ModuleAdminController
{
	
	public function __construct()
	{
		$this->id_lang = Context::getContext()->language->id;
        $this->bootstrap = true;
        $this->table = 'cedshopee_profile';
        $this->className = 'CedShopeeProfile';
        $this->identifier = 'id';
        $this->list_no_link = true;
        $this->addRowAction('edit');
        $this->addRowAction('deleteProfile');
        parent::__construct();
        $this->fields_list = array(
            'id' => array(
                'title' => 'ID',
                'type' => 'text',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'title' => array(
                'title' => 'Profile Name',
                'type' => 'text',
            ),
            'status' => array(
                'title' => $this->l('Status'),
                'align' => 'text-center',
                'type' => 'bool',
                'class' => 'fixed-width-sm',
                'callback' => 'profileStatus',
                'orderby' => false
            ),
        );

        if (Tools::isSubmit('submitProfileSave')) {
            $this->saveProfile();
        }
        if (Tools::getIsset('created') && Tools::getValue('created')) {
            $this->confirmations[] = "Profile created successfully";
        }
        if (Tools::getIsset('updated') && Tools::getValue('updated')) {
            $this->confirmations[] = "Profile updated successfully";
        }
	}

	public function profileStatus($value)
    {
        $this->context->smarty->assign(array('status' => (string)$value));
        return $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedshopee/views/templates/admin/profile/profile_status.tpl'
        );
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_profile'] = array(
                'href' => self::$currentIndex . '&addcedshopee_profile&token=' . $this->token,
                'desc' => $this->l('Add New Profile', null, null, false),
                'icon' => 'process-icon-new'
            );
        } elseif ($this->display == 'edit' || $this->display == 'add') {
            $this->page_header_toolbar_btn['backtolist'] = array(
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Back To List', null, null, false),
                'icon' => 'process-icon-back'
            );
        }
        parent::initPageHeaderToolbar();
    }

    public function postProcess()
    {
        if (Tools::getIsset('deleteprofile') && Tools::getValue('deleteprofile')) {
            $id = Tools::getValue('deleteprofile');
            $res = $this->deleteProfile($id);
            if ($res) {
                $this->confirmations[] = "Profile ".$id." deleted successfully";
            } else {
                $this->errors[] = "Failed to delete Profile ".$id;
            }
        }
        parent::postProcess();
    }

    public function initContent() 
    {
        if(Tools::getIsset('ajax') && Tools::getValue('ajax')) {
            $this->ajax = true;
        }
        parent::initContent();
    }

    public function displayDeleteProfileLink($token = null, $id = null)
    {
        $tpl = $this->createTemplate('helpers/list/list_action_delete.tpl');
        if (!array_key_exists('Delete', self::$cache_lang)) {
            self::$cache_lang['Delete'] = 'Delete';
        }

        $tpl->assign(array(
            'href' => self::$currentIndex.'&'.$this->identifier.'='.$id.'&deleteprofile='.$id.
                '&token='.($token != null ? $token : $this->token),
            'action' => self::$cache_lang['Delete'],
            'id' => $id
        ));
        return $tpl->fetch();
    }

    public function renderForm()
    {
    	$db = Db::getInstance();
        $profileData = array();
        $general = array();
        $storeCategory = array();
        $profileAttributeMapping = array();
        $selectedLogistics = array();
        $selectedWholesale = array();
        $defaultMapping = array();
        $productManufacturer = array();
        $Languages = array();
        $Shops = array();
        $idProfile = '';
        $ShopeeAttributes = CedShopeeLibrary::getShopeeAttributes();
        $ShopeeDefaultValues = CedShopeeLibrary::getDefaultShopeeAttributes();
        // $storeAttributes = CedShopeeLibrary::getAttributes();
        $storeSystemValues = CedShopeeLibrary::getSystemAttributes();
        $shopeeLogistics = CedShopeeLibrary::getShopeeLogistics();
        $shopeeWholesale = CedShopeeLibrary::getShopeeWholesale();
        $productManufacturer = Manufacturer::getManufacturers(false, 0, true, false, false, false, true);
        $Languages = Language::getLanguages(true, false, false);
        $Shops = Shop::getShops(true, false, false);
        $logistics_list = CedShopeeLibrary::getLogistics();
        $shopeeCategories = CedShopeeLibrary::getShopeeCategories();
        $idProfile = Tools::getValue('id');

        $this->context->controller->addJqueryUi('ui.autocomplete');
        $this->context->controller->addCSS(_PS_MODULE_DIR_ . 'cedshopee/views/css/shopee_category_attribute.css');
        
        if (!empty($idProfile)) {
            $cedShopeeProfile = new CedShopeeProfile();
            $profileData = $cedShopeeProfile->getProfileDataById($idProfile);
            // echo '<pre>'; print_r($profileData); die;
            $general = $profileData['general'];
            $storeCategory = $profileData['store_category'];
            $profileAttributeMapping = $profileData['profileAttributeMapping'];
            $selectedLogistics = $profileData['logistics'];
            $selectedWholesale = $profileData['wholesale'];
            $defaultMapping = $profileData['defaultMapping'];
        }
        $this->context->smarty->assign(array('profileId' => $idProfile));
        $this->context->smarty->assign(array(
            'controllerUrl' => $this->context->link->getAdminLink('AdminCedShopeeProfile'),
            'token' => $this->token,
            'ShopeeAttributes' => $ShopeeAttributes,
            'ShopeeDefaultValues' => $ShopeeDefaultValues,
            //'storeAttributes' => $storeAttributes,
            'storeSystemAttributes' => $storeSystemValues,
            'shopeeLogistics' => $shopeeLogistics,
            'shopeeWholesale' => $shopeeWholesale,
            'productManufacturer' => $productManufacturer,
            'Languages' => $Languages,
            'Shops' => $Shops,
            'logistics_list' => $logistics_list,
            'shopeeCategories' => $shopeeCategories
        ));

        $this->context->smarty->assign(array(
            'general' => $general,
            'profileAttributeMapping' => $profileAttributeMapping,
            'selectedLogistics' => $selectedLogistics,
            'selectedWholesale' => $selectedWholesale,
            'defaultMapping' => $defaultMapping
        ));

        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true) {
            $tree_categories_helper = new HelperTreeCategories('categories-treeview');
            $tree_categories_helper->setRootCategory((Shop::getContext() == Shop::CONTEXT_SHOP ?
                Category::getRootCategory()->id_category : 0))
                ->setUseCheckBox(true);
        } else {
            if (Shop::getContext() == Shop::CONTEXT_SHOP) {
                $root_category = Category::getRootCategory();
                $root_category = array(
                    'id_category' => $root_category->id_category,
                    'name' => $root_category->name);
            } else {
                $root_category = array('id_category' => '0', 'name' => $this->l('Root'));
            }
            $tree_categories_helper = new Helper();
        }
        if (version_compare(_PS_VERSION_, '1.6.0', '>=') === true) {
            $tree_categories_helper->setUseSearch(true);
            $tree_categories_helper->setSelectedCategories($storeCategory);
            $this->context->smarty->assign(array(
                'storeCategories' => $tree_categories_helper->render()));
        } else {
            $this->context->smarty->assign(array(
                'storeCategories' => $tree_categories_helper->renderCategoryTree(
                    $root_category,
                    $storeCategory,
                    'categoryBox'
                )
            ));
        }

        $profileTemplate = $this->context->smarty->fetch(
            _PS_MODULE_DIR_ . 'cedshopee/views/templates/admin/profile/edit_profile.tpl'
        );
        parent::renderForm();
        return $profileTemplate;
    }

    public function saveProfile()
    {
        $db = Db::getInstance();
        $title = Tools::getValue('title');
        $storeCategory = Tools::getValue('categoryBox');
        $shopee_categories = Tools::getValue('shopee_category_id');
        $shopee_category = Tools::getValue('shopee_category_id');
        $profile_attribute_mapping = Tools::getValue('profile_attribute_mapping');
        $status = Tools::getValue('status');
        $logistics = Tools::getValue('shopeeLogistics');
        $wholesale = Tools::getValue('shopeeWholesale');
        $default_mapping = Tools::getValue('defaultMapping');
        $profile_store = Tools::getValue('profile_store');
        $product_manufacturer = Tools::getValue('product_manufacturer');
        $profile_language = Tools::getValue('profile_language');
        $shopee_category_name = Tools::getValue('shopee_category');
        $profileId = Tools::getValue('id');
       // echo '<pre>'; print_r(Tools::getAllValues()); die;
        if (empty(trim($title))
        ) {
            $this->errors[] = "Missing required fields";
        }
        if (empty($storeCategory)
        ) {
            $this->errors[] = "Missing profile categories";
        } else {
            try {
                if (!empty($profileId)) {
                    $res = $db->update(
                        'cedshopee_profile',
                        array(
                           'title' => pSQL($title),
                           'store_category' => pSQL(json_encode($storeCategory)),
                           'shopee_categories' => pSQL(json_encode($shopee_categories)),
                           'shopee_category' => pSQL($shopee_category),
                           'profile_attribute_mapping' => pSQL(json_encode($profile_attribute_mapping)),
                           'status' => (int)$status,
                           'logistics' => pSQL(json_encode($logistics)),
                           'wholesale' => pSQL(json_encode($wholesale)),
                           'default_mapping' => pSQL(json_encode($default_mapping)),
                           'profile_store' => pSQL(json_encode($profile_store)),
                           'product_manufacturer' => pSQL(json_encode($product_manufacturer)),
                           'profile_language' => (int)$profile_language,
                           'shopee_category_name' => pSQL($shopee_category_name)
                        ),
                        'id=' . (int)$profileId
                    );
                    if ($res && count($storeCategory)) {
                        $prod_result = $this->updateProfileProducts($profileId, $storeCategory, 'update');
                        if ($prod_result) {
                            $link = new LinkCore();
                            $controller_link = $link->getAdminLink('AdminCedShopeeProfile').'&updated=1';
                            Tools::redirectAdmin($controller_link);
                            $this->confirmations[] = "Profile updated successfully";
                        }
                    }
                } else {
                    $p_code = $db->getValue(
                        "SELECT `id` FROM `"._DB_PREFIX_."cedshopee_profile` 
                                  WHERE `title`='".pSQL($title)."'"
                    );
                    if (!$p_code) {
                        $res = $db->insert(
                            'cedshopee_profile',
                            array(
                               'title' => pSQL($title),
	                           'store_category' => pSQL(json_encode($storeCategory)),
	                           'shopee_categories' => pSQL(json_encode($shopee_categories)),
	                           'shopee_category' => pSQL($shopee_category),
	                           'profile_attribute_mapping' => pSQL(json_encode($profile_attribute_mapping)),
	                           'status' => (int)$status,
	                           'logistics' => pSQL(json_encode($logistics)),
	                           'wholesale' => pSQL(json_encode($wholesale)),
	                           'default_mapping' => pSQL(json_encode($default_mapping)),
	                           'profile_store' => pSQL(json_encode($profile_store)),
	                           'product_manufacturer' => pSQL(json_encode($product_manufacturer)),
                               'profile_language' => (int)$profile_language,
	                           'shopee_category_name' => pSQL($shopee_category_name)
                            )
                        );
                        $newProfileId = $db->Insert_ID();
                        if ($res && $newProfileId && count($storeCategory)) {
                            $prod_result = $this->updateProfileProducts($newProfileId, $storeCategory, 'new');
                            if ($prod_result) {
                                $link = new LinkCore();
                                 $controller_link = $link->getAdminLink('AdminCedShopeeProfile').'&created=1';
                                 Tools::redirectAdmin($controller_link);
                                $this->confirmations[] = "Profile created successfully";
                            }
                        }
                    } else {
                        $this->errors[] = "Profile Title must be unique. ".$title.
                           " is already assigned to profile Id ".$p_code;
                    }
                }
            } catch (\Exception $e) {
                $this->errors[] = $e->getMessage();
            }
        }
    }

    public function updateProfileProducts($profileId, $categories = array(), $type = '')
    {
        if ($profileId && count($categories)) {
            $db = Db::getInstance();
            $res = '';
            $productIds = array();
            $sql = "SELECT DISTINCT cp.`id_product` FROM `" . _DB_PREFIX_ . "category_product` cp
            JOIN `"._DB_PREFIX_."product` p ON (p.id_product = cp.`id_product`)
            WHERE `id_category` IN (" . implode(',', (array)$categories) . ")";
            $data = $db->executeS($sql);
            if (count($data)) {
                foreach ($data as $item) {
                    $productIds[] = $item['id_product'];
                }
            }
            $idsToDisable = array();
            if (count($productIds)) {
                $query = "SELECT `product_id` FROM `" . _DB_PREFIX_ . "cedshopee_profile_products` 
                WHERE `shopee_profile_id` != " . (int)$profileId . " AND `product_id` 
                IN (" . implode(',', (array)$productIds) . ")";
                $dbResult = $db->executeS($query);
                if (count($dbResult)) {
                    foreach ($dbResult as $re) {
                        $idsToDisable[] = $re['product_id'];
                    }
                }
                $query = "DELETE FROM `" . _DB_PREFIX_ . "cedshopee_profile_products` 
                WHERE `shopee_profile_id` != " . (int)$profileId . " AND `product_id` 
                IN (" . implode(',', (array)$productIds) . ")";
                $db->execute($query);
                if ($type == 'new') {
                } else {
                    $idsToDisableSameProfile = array();
                    $sqlQuery = "SELECT `product_id` FROM `" . _DB_PREFIX_ . "cedshopee_profile_products`
                     WHERE `shopee_profile_id` = " . (int)$profileId . " AND `product_id` 
                     NOT IN (" . implode(',', (array)$productIds) . ")";
                    $queryResult = $db->executeS($sqlQuery);
                    if (count($queryResult)) {
                        foreach ($queryResult as $res) {
                            $idsToDisableSameProfile[] = $res['product_id'];
                        }
                    }

                    $idsToDisable = array_merge($idsToDisable, $idsToDisableSameProfile);
                    $query = "DELETE FROM `" . _DB_PREFIX_ . "cedshopee_profile_products` 
                    WHERE `shopee_profile_id` = " . (int)$profileId . "";
                    $db->execute($query);
                }
                $sql = "INSERT INTO `" . _DB_PREFIX_ . "cedshopee_profile_products` (shopee_profile_id, product_id) values";
                foreach ($productIds as $id) {
                    $sql .= "(" . (int)$profileId . ", " . (int)$id . "),";
                }
                $sql = rtrim($sql, ',');
                $sql .= ";";
                $res = $db->execute($sql);
                if ($res) {
                    return true;
                }
            }
        }
        return true;
    }

    public function deleteProfile($id)
    {
        $db = Db::getInstance();
        if (!empty($id)) {
            $res = $db->delete(
                'cedshopee_profile_products',
                'shopee_profile_id='.(int)$id
            );
            if ($res) {
                $res =  $db->delete(
                    'cedshopee_profile',
                    'id='.(int)$id
                );
            }
            if ($res) {
                return true;
            }
        }
        return false;
    }

    public function ajaxProcessAttributesByCategory()
    {
        $db = Db::getInstance();
        $result = Tools::getAllValues();
    	$cedShopeeProfile = new cedShopeeProfile;
        $profile_id = Tools::getValue('id');
        $category_id = Tools::getIsset('category_id')?Tools::getValue('category_id'):0;
        $profile_id = Tools::getIsset('profile_id')?Tools::getValue('profile_id'):0;
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $html ='No Attribute Found , Please checkCategory.';
        if ($category_id) {
            ini_set('memory_limit','512M');
           
            $attributes_options = $cedShopeeProfile->getAttributesByCategory($category_id);
         
            $mapped_attributes_options = array();
            if ($profile_id) {
               $mapped_attributes_options = $cedShopeeProfile->getMappedAttributes($profile_id); 
            }
            // $results = $cedShopeeProfile->getAttributes();
            $results = Feature::getFeatures($default_lang,true);
            $store_options = $cedShopeeProfile->storeOptions();
            $options = $store_options['options'];
            $attributes = $cedShopeeProfile->getAttributesByCategory($category_id);
            $html ='';
            $required = array();
            foreach ($attributes as $attribute) {

                $key = $attribute['attribute_id'];
                $html .= '<tr>';
                $html .= '<td class="col-sm-12 col-md-3 col-lg-3 left">';
                if(isset($attribute['is_mandatory']) && $attribute['is_mandatory']){
                    $required[] = $attribute['attribute_id'];
                    $html .= '<span class="required">*</span>';
                    $html .= '<input type="hidden" name="profile_attribute_mapping['.$key.'][is_mandatory]" value="1"/>';
                } else {
                    $html .= '<input type="hidden" name="profile_attribute_mapping['.$key.'][is_mandatory]" value="0"/>';
                }
                $html .= '<input type="hidden" name="profile_attribute_mapping['.$key.'][attribute_type]" value="'.$attribute['attribute_type'].'"/>';
                $html .= '<input type="hidden" name="profile_attribute_mapping['.$key.'][input_type]" value="'.$attribute['input_type'].'"/>';
                $html .= '<select name="profile_attribute_mapping['.$key.'][shopee_attribute]" class="col-sm-12 col-md-8 col-lg-8">';

                $mapped_options = false;
                $store_selected_option = false;
                $default_values_selected = false;
                $default_values_id_selected = false;
                $shoppee_selected_option = false;
                if(isset($mapped_attributes_options[$attribute['attribute_id']]) && isset($mapped_attributes_options[$attribute['attribute_id']]['option']) && isset($mapped_attributes_options[$attribute['attribute_id']]['option'])) {
                    $mapped_options = $mapped_attributes_options[$attribute['attribute_id']]['option'];
// echo '<pre>'; print_r($mapped_options); die;
                    if(is_array($mapped_options) && !empty($mapped_options)){
                        $mapped_options = array_filter($mapped_options);
                        $mapped_options = array_values($mapped_options);
                    }
                    
                    if(isset($mapped_attributes_options[$attribute['attribute_id']]['store_attribute']) && $mapped_attributes_options[$attribute['attribute_id']]['store_attribute'])
                        $store_selected_option = $mapped_attributes_options[$attribute['attribute_id']]['store_attribute'];

                    if($mapped_attributes_options[$attribute['attribute_id']]['shopee_attribute'])
                        $shoppee_selected_option = $mapped_attributes_options[$attribute['attribute_id']]['shopee_attribute'];

                    if($mapped_attributes_options[$attribute['attribute_id']]['default_values'])
                        $default_values_selected = $mapped_attributes_options[$attribute['attribute_id']]['default_values'];

                    if($mapped_attributes_options[$attribute['attribute_id']]['default_value_id'])
                        $default_values_id_selected = $mapped_attributes_options[$attribute['attribute_id']]['default_value_id'];
                }

                if(!$attribute['is_mandatory'])
                $html .= '<option value=""></option>';
                foreach ($attributes_options as $attribute_option) {
                    if($shoppee_selected_option && ($attribute_option['attribute_id']==$shoppee_selected_option)) {
                        $html .= '<option selected="selected" value="'.$attribute_option['attribute_id'].'">';
                        $html .= $attribute_option['attribute_name'];
                        $html .= '</option>';
                    } else if($attribute['is_mandatory'] && ($attribute_option['attribute_id']==$attribute['attribute_id'])){
                        $html .= '<option selected="selected" value="'.$attribute_option['attribute_id'].'">';
                        $html .= $attribute_option['attribute_name'];
                        $html .= '</option>';
                    } else {
                        $html .= '<option value="'.$attribute_option['attribute_id'].'">';
                        $html .= $attribute_option['attribute_name'];
                        $html .= '</option>';
                    }
                }
                $html .= '</select>';
                $html .= '</td>';
                $html .= '<td>';
                $attribute['options'] = json_decode($attribute['options'], true);
                $html .= '<input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping['.$key.'][default_values]" onkeyup="getBrand(this)" data-id="'.$key.'" value ="'.$default_values_selected.'" >';
                $html .= '<input type="hidden" value="" class="text-left" name="profile_attribute_mapping['.$key.'][default_value_id]" value ="'.$default_values_id_selected.'" >';
                $html .= '</td>';
                $html .= '<td class="col-sm-12 col-md-3 col-lg-3 left">';
                if ( ( $key != 9463) && in_array($attribute['input_type'], array('DROP_DOWN', 'COMBO_BOX'))) {
                    $html .= '<select id="profile_attribute_mapping['.$key.'][store_attribute]" name="profile_attribute_mapping['.$key.'][store_attribute]" class="col-sm-12 col-md-8 col-lg-8">';
                    $html .= '<option value="">Select Mapping</option>';
                    $html .= '<optgroup label="Store Option">';
                    foreach ($options as $option) {
                        if($store_selected_option && ('option-'.$option['id_attribute_group']==$store_selected_option)) {
                            $html .= '<option show_option_mapping="1" selected="selected" value="option-'.$option['id_attribute_group'].'">';
                            $html .= $option['name'];
                            $html .= '</option>';
                        } else {
                            $html .= '<option show_option_mapping="1" value="option-'.$option['id_attribute_group'].'">';
                            $html .= $option['name'];
                            $html .= '</option>';
                        }

                    }
                    $html .= '</optgroup>';
                    $html .= '<optgroup label="Store Attributes">';
                    foreach ($results as $result) {
                        if($store_selected_option && ('attribute-'.$result['id_feature']==$store_selected_option)) {
                            $html .= '<option show_option_mapping="0" selected="selected" value="attribute-'.$result['id_feature'].'">';
                            $html .= $result['name'];
                            $html .= '</option>';
                        } else {
                            $html .= '<option show_option_mapping="0" value="attribute-'.$result['id_feature'].'">';
                            $html .= $result['name'];
                            $html .= '</option>';
                        }
                    }
                    $html .= '</optgroup>';
                    $product_fields = array(); 
                    try{
                        $columns = $db->executeS("SHOW COLUMNS FROM `"._DB_PREFIX_."product`;");
                        if(isset($columns) && count($columns)) {
                            $product_fields = $columns;
                        }
                        $this->array_sort_by_column($product_fields, 'Field');
                    }catch(Exception $e){
                        echo $e->getMessage();die;
                    }
                   
                    $html .= '<optgroup label="Product Fields">';
                    foreach ($product_fields as $result) {
                        $show_option_mapping = 0 ;
                        if(in_array($result['Field'],array('manufacturer_id')))
                            $show_option_mapping = 1 ;
                        if($store_selected_option && ('product-'.$result['Field']==$store_selected_option)) {
                            $html .= '<option show_option_mapping="'.$show_option_mapping.'" selected="selected" value="product-'.$result['Field'].'">';
                            $html .= ucfirst(str_replace('_', ' ', $result['Field']));
                            $html .= '</option>';
                        } else {
                            $html .= '<option show_option_mapping="'.$show_option_mapping.'" value="product-'.$result['Field'].'">';
                            $html .= ucfirst(str_replace('_', ' ', $result['Field']));
                            $html .= '</option>';
                        }
                    }
                    $html .= '</optgroup>';
                    $html .= '</select>';                  
                    $option_html = '';
                    $option_html .= '<a style="margin-left:1%; text-weight:bold;" class="center button" onclick="toggleOptions(' . $key . ')"> Map Option(s) </a><div style="display:none;" id="panel' . $key . '">';
                    $option_html .= '<table class="table table-bordered" id="option_mapping' . $key . '">';
                    $option_html .= '<thead>';
                    $option_html .= '<tr>';
                    $option_html .= '<td class="col-sm-12 col-md-4 col-lg-4 center">';
                    $option_html .= '<strong>Store Option</strong>';
                    $option_html .= '</td>';
                    $option_html .= '<td class="col-sm-12 col-md-4 col-lg-4 center">';
                    $option_html .= '<strong>Shopee Option</strong>';
                    $option_html .= '</td>';
                    $option_html .= '</tr>';
                    $option_html .= '</thead>';
                    $option_html .= '<tr>';
                    $option_html .= '<td>';
                    $option_html .= '<input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping['.$key.'][option][store_attribute]" value="" onkeyup="getStoreOptions(this)" data-id="'.$key.'"/>';
                    $option_html .= '<input type="hidden" class="text-left" name="profile_attribute_mapping['.$key.'][option][store_attribute_id]"/>';
                    $option_html .= '</td>';
                    $option_html .= '<td>';
                    $option_html .= '<input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping['.$key.'][option][shopee_attribute]" onkeyup="getOptions(this)" data-id="'.$key.'">';
                    $option_html .= '</td>';
                    $option_html .= '<td>';
                    $option_html .= '<button type="button" class="btn btn-primary pull-right" onclick="addAttribute(this,'.$key.');" >Add Mapping</button>';
                    $option_html .= '</td>';
                    $option_html .= '</tr>';
                    $option_html .= '</thead>';
                    if(!empty($mapped_options))
                    {
                        foreach ($mapped_options as $key_p => $value) {
                            $option_html .= '<tr id="attribute-row'.$key.$key_p.'">';
                            $option_html .= '<td>';
                            $option_html .= '<input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping['.$key.'][option]['.$key_p.'][store_attribute]" value="'.$value['store_attribute'].'"/>';
                            $option_html .= '<input type="hidden" class="text-left" name="profile_attribute_mapping['.$key.'][option]['.$key_p.'][store_attribute_id]" value="'.$value['store_attribute_id'].'"/>';
                            $option_html .= '</td>';
                            $option_html .= '<td>';
                            $option_html .= '<input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping['.$key.'][option]['.$key_p.'][shopee_attribute]" value="'.$value['shopee_attribute'].'">';
                            $option_html .= '</td>';
                            $option_html .= '<td>';
                            $option_html .= '<a type="button" onclick="$(\'#attribute-row'.$key.$key_p.'\').remove();" class="btn btn-danger pull-right"> Remove</a>';
                            $option_html .= '</td>';
                            $option_html .= '</tr>';
                        } 
                    }
                    $option_html .= '</table>';
                    $option_html .= '</div>';
                    $html .= $option_html;
                } else {
                    if (isset($mapped_attributes_options[$key]['store_attribute'])) {
                        $html .= '<input type="text" value="'.$mapped_attributes_options[$key]['store_attribute'].'" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping['.$key.'][store_attribute]" onkeyup="getBrand(this)" data-id="'.$key.'">';
                    } else {
                        $html .= '<input type="text" class="col-sm-12 col-md-8 col-lg-8 text-left" name="profile_attribute_mapping['.$key.'][store_attribute]" onkeyup="getBrand(this)" data-id="'.$key.'">';
                    }
                }
                $html .= '</td>';
                $html .= '</tr>';
            }
            die($html);
        } else {
        	die($html);
        }
    }

    public function ajaxProcessGetStoreOptions()
    {
        $returnResponse = array();
        $CedShopeeProfile = new CedShopeeProfile;
        $data = Tools::getAllValues();
        if (isset($data['filter_name']) && !empty($data['filter_name']) && isset($data['attribute_group_id']) && !empty($data['attribute_group_id']) && isset($data['catId']) && !empty($data['catId'])) {
            $attribute_group_id = $data['attribute_group_id']; 
            $type_array = explode('-', $attribute_group_id );
            if (isset($type_array['0']) && ($type_array['0']=='product')){
                // Manufacturer::getManufacturers(false, 0, true, false, false, false, true);
                $returnResponse = $CedShopeeProfile->getManufacturers(array('filter_name' => $data['filter_name']));
            } else if (isset($type_array['0']) && ($type_array['0']=='option')){
                $returnResponse = $CedShopeeProfile->getStoreOptions($data['catId'],$type_array['1'], $data['filter_name']);
            }
        }
        die(json_encode($returnResponse));
    }

    public function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) 
    {
        $sort_col = array();
        foreach ($arr as $key=> $row) {
            $sort_col[$key] = $row[$col];
        }
        array_multisort($sort_col, $dir, $arr);
    }

    public function ajaxProcessAutocomplete()
    {
        $CedShopeeLibrary = new CedShopeeLibrary;
        $json = array();
        $request = Tools::getAllValues();

        if (isset($request) && !empty($request)) {
            $filter_name = Tools::getIsset('filter_name')?Tools::getValue('filter_name'):'';
            $data = array('filter_name' => $filter_name);
            $results = $CedShopeeLibrary->getShopeeCategories($data);
            foreach ($results as $category) {
                $json[] = array(
                    'category_id' => $category['category_id'],
                    'name' => strip_tags(html_entity_decode($category['category_name'], ENT_QUOTES, 'UTF-8')),
                );
            }
        }
        die(json_encode($json));
    }

    public function ajaxProcessBrandAuto()
    {
        $CedShopeeProfile = new CedShopeeProfile;
        $returnResponse = array();
        $data = Tools::getAllValues();
        if (isset($data['filter_name']) && !empty($data['filter_name']) && isset($data['attribute_id']) && !empty($data['attribute_id']) && isset($data['catId']) && !empty($data['catId'])) {
            $attribute_id = $data['attribute_id']; 
            $returnResponse = $CedShopeeProfile->getBrands($data['catId'],$attribute_id, $data['filter_name']);
        }
        die(json_encode($returnResponse));
    }
}