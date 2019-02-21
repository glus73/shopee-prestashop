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
 * @package   Cedshopee
 */
  -->

<div class="row">
	<div class="col-lg-12">
		<div class="row">
			<div class="col-lg-6">
				<div class="panel" id="formAddPaymentPanel">
			        <div class="panel-heading">
			          <i class="icon-shopping-cart"></i>
			          Order Info
			        </div>
			        <div class="table-responsive">
			            <table class="table">
			              	<tbody>
			              		{foreach $order_info as $key => $value}
			              		  {foreach $value as $index => $val}
									<tr>
										<td>
											{$index|escape:'htmlall':'UTF-8'}
										</td>
										<td>
											{$val|escape:'htmlall':'UTF-8'}
										</td>
									</tr>
								  {/foreach}
								{/foreach}
			              	</tbody>
			            </table>
			        </div>
		        </div>
			</div>
			
			<div class="col-lg-6">
				<div class="panel" id="formAddPaymentPanel">
			        <div class="panel-heading">
			          <i class="icon-truck"></i>
			          Shipping Info
			        </div>
			        <div class="table-responsive">
			            <table class="table">
			              	<tbody>
			              		{foreach $shippingInfo as $index => $value}
								<tr>
									<td>
										{$index|escape:'htmlall':'UTF-8'}
									</td>
									<td>
										{$value|escape:'htmlall':'UTF-8'}
									</td>
								</tr>
								{/foreach}
			              	</tbody>
			            </table>
			        </div>
		        </div>
			</div>	
		</div>
	</div>
</div>
<div class="row">
	<div class="col-lg-12">
		<div class="row">
			<div class="panel" id="formAddPaymentPanel">
			    <div class="panel-heading">
			        <i class="icon-money"></i>
			          Products Info
			    </div>
			    <div class="table-responsive">
			    <input type="hidden" name="token" data-token="{$token|escape:'htmlall':'UTF-8'}" id="token" />
			        <table class="table">
			        	<thead>
			        		<th><strong>ProductSkuId</strong></th>
			        		<th><strong>VariationSkuId</strong></th>
			        		<th><strong>ProductName</strong></th>
			        		<th><strong>Quantity</strong></th>
			        		<th><strong>VariationName</strong></th>
			        		<th><strong>DiscountPrice</strong></th>
			        		<th><strong>OriginalPrice</strong></th>
			        	</thead>
			          	<tbody>
			          		{foreach $items as $index => $value}
			          		<tr>
			          		   {foreach $value as $ind => $val}
			          			  <td>{$val|escape:'htmlall':'UTF-8'}</td>
			          			{/foreach}
			          		</tr>
							{/foreach}
			           	</tbody>
			        </table>
			    </div>
		    </div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-lg-12">
		<div class="row">
			<div class="panel" id="formAddPaymentPanel">
			    <div class="panel-heading">
			        <i class="icon-truck"></i>
			          Ship Whole Order
			    </div>
			    <div class="table-responsive">
			        <table class="table">
			          	<tbody>
			          		<tr>
			          			<td>Order ID</td>
			          			<td><input type="text" name="ordersn" id="ship_ordersn" value="{$ordersn|escape:'htmlall':'UTF-8'}"></td>
			          		</tr>
			          		<tr>
			          			<td>Tracking No.</td>
			          			<td><input type="text" name="tracking_no" id="ship_tracking_no" value="{$tracking_no|escape:'htmlall':'UTF-8'}"></td>
			          		</tr>
			           	</tbody>
			           	<tfoot>
			           		<tr>
			           			<td colspan="2">
			           				<button onclick="shipCompleteOrder('{$ordersn|escape:'htmlall':'UTF-8'}','{$tracking_no|escape:'htmlall':'UTF-8'}')" class="btn btn-primary">Ship Whole Order</button>
			           			</td>
			           		</tr>
			           	</tfoot>
			        </table>
			        <div id="shopee_shipwhole_response">
			        	
			        </div>
			    </div>
		    </div>
		</div>
	</div>
</div>

<div class="row">
	<div class="col-lg-12">
		<div class="row">
			<div class="panel" id="formAddPaymentPanel">
			    <div class="panel-heading">
			        <i class="icon-ban"></i>
			          Cancel Whole Order
			    </div>
			    <div class="table-responsive">
			        <table class="table">
			          	<tbody>
			          		<tr>
			          			<td>Order ID</td>
			          			<td><input type="text" name="ordersn" id="cancel_ordersn" value="{$ordersn|escape:'htmlall':'UTF-8'}"></td>
			          		</tr>
			          		<tr>
			          			<td>Cancel Reason No.</td>
			          			<td>
			          			<select name="cancel_reason" id="cancel_reason">
			          				<option value="OUT_OF_STOCK">OUT OF STOCK</option>
			          				<option value="CUSTOMER_REQUEST">CUSTOMER REQUEST</option>
			          				<option value="UNDELIVERABLE_AREA">UNDELIVERABLE AREA</option>
			          				<option value="COD_NOT_SUPPORTED">COD NOT SUPPORTED</option>
			          			</select>
			          			</td>
			          		</tr>
			           	</tbody>
			           	<tfoot>
			           		<tr>
			           			<td colspan="2">
			           				<button onclick="cancelOrder('{$ordersn|escape:'htmlall':'UTF-8'}',document.getElementById('cancel_reason').value,)" class="btn btn-primary">Cancel Whole Order</button>
			           			</td>
			           		</tr>
			           	</tfoot>
			        </table>
			        <div id="shopee_cancelwhole_response">
			        	
			        </div>
			    </div>
		    </div>
		</div>
	</div>
</div>

<script type="text/javascript">
	function shipCompleteOrder(ordersn, tracking_no) {
        $.ajax({
          	type: "POST",
          	data: {
          		ajax: true,
          		controller: 'AdminCedShopeeOrder',
          		action: 'shipOrder',
          		token: $('#token').attr('data-token'),
          		ordersn: ordersn,
          		tracking_number: tracking_no 
          	},
          	success: function(response){
          		console.log(response);
          		if(response){
          			response = JSON.parse(response);
	          		if(response.success)
	          			$('#shopee_shipwhole_response').html('<span style="color:green;font-size:14px;font-weight:bold;">'+response.message+'</span>').delay(5000).fadeOut();
	          		else
	          			$('#shopee_shipwhole_response').html('<span style="color:Red;font-size:14px;font-weight:bold;">'+response.message+'</span>').delay(5000).fadeOut();
          		}
          	},
          	statusCode: {
          	500: function(xhr) {
              if(window.console) console.log(xhr.responseText);
            },
          	400: function (response) {
             alert('<span style="color:Red;">Error While Uploading Please Check</span>');
          	},
          	404: function (response) {
             
            	alert('<span style="color:Red;">Error While Uploading Please Check</span>');
          		}
          	},
          	error: function(xhr, ajaxOptions, thrownError) {
            	if(window.console) console.log(xhr.responseText);
            	alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);

        	},
      	});
	}

	function cancelOrder(ordersn, cancel_reason) {
        $.ajax({
          	type: "POST",
          	data: {
          		ajax: true,
          		controller: 'AdminCedShopeeOrder',
          		action: 'cancelOrder',
          		token: $('#token').attr('data-token'),
          		ordersn: ordersn,
          		cancel_reason: document.getElementById('cancel_reason').value  
          	},
          	success: function(response){
          		console.log(response);
          		if(response){
          			response = JSON.parse(response);
	          		if(response.success)
	          			$('#shopee_cancelwhole_response').html('<span style="color:green;font-size:14px;font-weight:bold;">'+response.message+'</span>').delay(5000).fadeOut();
	          		else
	          			$('#shopee_cancelwhole_response').html('<span style="color:Red;font-size:14px;font-weight:bold;">'+response.message+'</span>').delay(5000).fadeOut();
          		}
          	},
          	statusCode: {
          	500: function(xhr) {
              if(window.console) console.log(xhr.responseText);
            },
          	400: function (response) {
             alert('<span style="color:Red;">Error While Uploading Please Check</span>');
          	},
          	404: function (response) {
             
            	alert('<span style="color:Red;">Error While Uploading Please Check</span>');
          		}
          	},
          	error: function(xhr, ajaxOptions, thrownError) {
            	if(window.console) console.log(xhr.responseText);
            	alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);

        	},
      	});
	}


</script>