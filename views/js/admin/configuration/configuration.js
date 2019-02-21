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
 * @package   Cmxwalmart
 */

window.onload = function () {
    if (document.getElementById('CEDSHOPEE_LIVE_MODE')) {
        var liveMode = document.getElementById('CEDSHOPEE_LIVE_MODE').value;
        if (liveMode && (liveMode == 'live' || liveMode == '')) {
            // https://partner.shopeemobile.com/api/v1/shop/auth_partner

            document.getElementById('CEDSHOPEE_API_URL').value = 'https://partner.test.shopeemobile.com/api/v1/';
        } else if (liveMode && (liveMode == 'sandbox')) {
            // https://partner.uat.shopeemobile.com/api/v1/shop/auth_partner
            document.getElementById('CEDSHOPEE_API_URL').value = 'https://partner.shopeemobile.com/api/v1/';
        }

        document.getElementById('CEDSHOPEE_LIVE_MODE').addEventListener("change", modeLive);
    }
}

function modeLive()
{
    var mode = this.value;
    if (mode == 'live') {
         document.getElementById('CEDSHOPEE_API_URL').value = 'https://partner.shopeemobile.com/api/v1/';
    } else if (mode = 'sandbox') {
        document.getElementById('CEDSHOPEE_API_URL').value = 'https://partner.test.shopeemobile.com/api/v1/';
    }
}
