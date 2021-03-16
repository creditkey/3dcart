<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('./creditkey/init.php');

$paymenttoken = $_GET['id'];

$sqlSelect = "SELECT * FROM `ck_order` WHERE paymenttoken = '".$paymenttoken."' ";
$oresult = $con->query($sqlSelect);
if ($oresult->num_rows > 0) {
	while($row = $oresult->fetch_assoc()) {
		$creditkeyOrderId = $row['creditkey_order_id'];
		$orderMerchantID = $row['merchant_id'];
		if( $row['testmode'] ){ 
	    	\CreditKey\Api::configure(\CreditKey\Api::STAGING, $row['username'], $row['password']);
	    }else{   
	    	\CreditKey\Api::configure(\CreditKey\Api::PRODUCTION, $row['username'], $row['password']);
	    }
 
	    $isCompleteCheckout = \CreditKey\Checkout::completeCheckout($creditkeyOrderId);

	    if($isCompleteCheckout->success){
	    	$url = $row['notificationurl'];
			$ch = curl_init($url);
			$data = array(
			    'orderid' => $row['orderid'],
			    'invoice' => $row['invoice'],
			    'amount' => $row['amounttotal'],
			    'approved' => 1,
			    'transactionid' => $creditkeyOrderId,
			    'errorcode' => '',
			    'errormessage' => '',
			    'randomkey' => $row['randomkey'],
			    'signature' => md5($row['randomkey'].''.$privatekey.''.$row['orderid'].''.$row['invoice'].''.$creditkeyOrderId)
			);
			$payload = json_encode($data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json')); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$notificationResult = curl_exec($ch);
			curl_close($ch);
			$response = json_decode($notificationResult, true);
			if($response['processed'] == 1){
				$sqlUpdate = "UPDATE `ck_order` SET `order_status` = 1, `order_completed` = 1, `order_comment` = 'Order Placed successfully' WHERE paymenttoken = '".$paymenttoken."' ";
				$con->query($sqlUpdate); 

				$itemsArray = json_decode($row['items']);
			    $cartContents = [];
			    foreach ($itemsArray as $object) { 
			    	$itemnameArray = explode("<br>",$object->itemname);
			        array_push($cartContents, new \CreditKey\Models\CartItem($object->itemid, $itemnameArray[0], $object->unitprice/100, $object->itemid, $object->quantity, null, null));
			    }

			    $shipping = $row['shippingtotal']/100;
			    $tax = $row['taxtotal']/100;
			    $discountAmount = $row['discount']/100;
			    $grandTotal = $row['amounttotal']/100;
			    $total = $grandTotal - $shipping - $tax - $discountAmount; 
			    $charges = new \CreditKey\Models\Charges($total, $shipping, $tax, $discountAmount, $grandTotal);

			    $shippingAddressObject = $row['shipping'];
			    $shippingAddressArray = json_decode($shippingAddressObject);
			    $shippingAddress = new \CreditKey\Models\Address($shippingAddressArray->firstname, $shippingAddressArray->lastname, $shippingAddressArray->company, $row['email'], $shippingAddressArray->address, $shippingAddressArray->address2, $shippingAddressArray->city, $shippingAddressArray->state, $shippingAddressArray->zip, $shippingAddressArray->phone);

				$orderUpdate = \CreditKey\Orders::update($creditkeyOrderId, "new", $row['invoice'], $cartContents, $charges, $shippingAddress);

				$logfile = "logs/$orderMerchantID/3DcartSuccessOrderLog.txt";
				$ErrorMsg = '<!== log start for order invoice: '.$row['invoice'].', date: '.date('Y-m-d H:i:s').', StatusName: New, order placed successfully ==!>';
				error_log($ErrorMsg."\n",3,$logfile);
				header('Location: '.$row['returnurl']);
			}else{

				$logfile = "logs/$orderMerchantID/3DcartErrorOrderLog.txt";
				$ErrorMsg = '<!== log start for order invoice: '.$row['invoice'].', date: '.date('Y-m-d H:i:s').', order responce failed from 3Dcart.  ==!>';
				error_log($ErrorMsg."\n",3,$logfile);

				$sqlUpdate = "UPDATE `ck_order` SET `order_comment` = '{3Dcart:notificationurl: false}' WHERE paymenttoken = '".$paymenttoken."' ";
		        $con->query($sqlUpdate);
		    	$article = array(    
		        'title' => 'CreditKey order placed but failed in 3Dcart',
		        'subtitle' => 'CreditKey order placed but failed in 3Dcart.',
		        'cancelurl' => $row['cancelurl']
		        );
		        include("./views/main.php"); 
			}
	    }else{

	    	$logfile = "logs/$orderMerchantID/3DcartErrorOrderLog.txt";
			$ErrorMsg = '<!== log start for order invoice: '.$row['invoice'].', date: '.date('Y-m-d H:i:s').', {CreditKey:complete_checkout: false}.  ==!>';
			error_log($ErrorMsg."\n",3,$logfile);

	    	$sqlUpdate = "UPDATE `ck_order` SET `order_comment` = '{complete_checkout: false}' WHERE paymenttoken = '".$paymenttoken."' ";
	        $con->query($sqlUpdate);
	    	$article = array(    
	        'title' => 'CreditKey order not completed',
	        'subtitle' => 'CreditKey order not completed.',
	        'cancelurl' => $row['cancelurl']
	        );
	        include("./views/main.php"); 
	    }
	    
	}   
} 
?>