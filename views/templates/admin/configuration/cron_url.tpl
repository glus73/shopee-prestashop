<!--
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
 * @package   CedShopee
 */
 -->

<div class="row">
    <div class="col-sm-3 text-right">{l s='Shopee Cron Urls' mod='cedshopee'}</div>
    <div class="col-sm-8">
        <table class="table">
            <thead>
            <tr>
                <th><strong>{l s='Cron Name' mod='cedshopee'}</strong></th>
                <th><strong>{l s='Cron Url' mod='cedshopee'}</strong></th>
                <th><strong>{l s='Recommended Time' mod='cedshopee'}</strong></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Upload Product at Shopee</td>
                <td scope="row">{$base_url|escape:'htmlall':'UTF-8'}modules/cedshopee/uploadProduct.php?secure_key={$cron_secure_key|escape:'htmlall':'UTF-8'}</td>
                <td>ONCE A DAY</td>
            </tr>
            <tr>
                <td>Sync Inventory at Shopee</td>
                <td scope="row">{$base_url|escape:'htmlall':'UTF-8'}modules/cedshopee/updateInventory.php?secure_key={$cron_secure_key|escape:'htmlall':'UTF-8'}</td>
                <td>ONCE A DAY</td>
            </tr>
            <tr>
                <td>Sync Price at Shopee</td>
                <td scope="row">{$base_url|escape:'htmlall':'UTF-8'}modules/cedshopee/updatePrice.php?secure_key={$cron_secure_key|escape:'htmlall':'UTF-8'}</td>
                <td>ONCE A DAY</td>
            </tr>
            <tr>
                <td>Order Import</td>
                <td scope="row">{$base_url|escape:'htmlall':'UTF-8'}modules/cedshopee/fetchorder.php?secure_key={$cron_secure_key|escape:'htmlall':'UTF-8'}</td>
                <td>PER 1 HOUR</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>