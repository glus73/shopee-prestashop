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
require_once _PS_MODULE_DIR_ . 'cedshopee/classes/CedShopeeCategory.php';
class AdminCedShopeeCategoryController extends ModuleAdminController
{
	public function __construct()
    {
        $this->db         = Db::getInstance();
        $this->bootstrap  = true;
        $this->table      = 'cedshopee_category';
        $this->identifier = 'category_id';
        $this->list_no_link = true;
        // $this->addRowAction('mapcategory');
        $this->fields_list = array(
            'category_id'       => array(
                'title' => 'Category ID',
                'type'  => 'text',
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'category_name'     => array(
                'title' => 'Category Name',
                'type'  => 'text',
            ),
            'parent_id'     => array(
                'title' => 'Parent ID',
                'type'  => 'text',
            ),
        );

        if (Tools::getIsset('method') && 
            (trim(Tools::getValue('method'))) == 'fetchCategory'
        ) {
            $this->fetchCategory();
        }
        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['fetch_category'] = array(
                'href' => self::$currentIndex . '&method=fetchCategory&token=' . $this->token,
                'desc' => $this->l('Fetch Category', null, null, false),
                'icon' => 'process-icon-download'
            );
        }
        parent::initPageHeaderToolbar();
    }

    public function fetchCategory() 
    {
        $CedshopeeLibrary = new CedshopeeLibrary;
        $response = $CedshopeeLibrary->postRequest('item/categories/get', array());
        if (!isset($response['error'])) {
            if (!empty($response['categories'])) {
                $catResponse = $this->addShopeeCategories($response['categories']);
                if (isset($catResponse) && $catResponse) {
                    $this->confirmations[] = 'Categories fetched successfully';
                } else {
                    $this->errors[] = 'Error while fetching categories';
                }
            } else {
                $this->errors[] = 'No response from Shopee';
            }
        } else if (isset($response['msg'])){
            $this->errors[] = $response['msg'];
        } else if (isset($response['error'])){
            $this->errors[] = $response['error'];
        } else {
            $this->errors[] = 'No response from Shopee';
        }
    }

    public function addShopeeCategories($data) 
    {
        $this->db->Execute("DELETE FROM " . _DB_PREFIX_ . "cedshopee_category");
        $flag = 0;
        foreach ($data as $category) {
            if(isset($category['category_id']) && $category['category_id']) {
                $query = $this->db->ExecuteS("SELECT `category_name` FROM " . _DB_PREFIX_ . "cedshopee_category WHERE category_id = '" . (int)$category['parent_id'] . "'");
             
                if(!empty($query) && isset($query['0']['category_name'])) {
                    $this->db->Execute("INSERT INTO " . _DB_PREFIX_ . "cedshopee_category SET category_id = '" . (int)$category['category_id'] . "', parent_id = '" . (int)$category['parent_id'] . "', has_children = '" . (int)$category['has_children'] . "', category_name = '" . pSQL($query['0']['category_name'].' > '.$category['category_name']) . "'");
                } else {
                    $this->db->Execute("INSERT INTO " . _DB_PREFIX_ . "cedshopee_category SET category_id = '" . (int)$category['category_id'] . "', parent_id = '" . (int)$category['parent_id'] . "', has_children = '" . (int)$category['has_children'] . "', category_name = '" . pSQL($category['category_name']) . "'");
                }
            $flag ++;
            } 
        }
        return $flag;
    }
}