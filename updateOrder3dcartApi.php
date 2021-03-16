<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('./creditkey/init.php'); 

$json = file_get_contents('php://input');
file_put_contents('logs/3DcartPayloadUpdate.txt', $json); 
$jsonDecord = json_decode($json);


$TokenKey = $_GET['key'];
$OrderID = $jsonDecord[0]->OrderID;
$OrderStatusID = $jsonDecord[0]->OrderStatusID;
$TransactionID = $jsonDecord[0]->TransactionList[0]->TransactionID;
$invoice = $jsonDecord[0]->InvoiceNumberPrefix."".$jsonDecord[0]->InvoiceNumber;
$statusName = '';

$itemsArray = $jsonDecord[0]->OrderItemList;
$itemPrice = 0;
$cartContents = [];
foreach ($itemsArray as $object) { 
	$itemPrice += $object->ItemUnitPrice * $object->ItemQuantity;
	$itemnameArray = explode("<br>",$object->ItemDescription);
    array_push($cartContents, new \CreditKey\Models\CartItem($object->ItemID, $itemnameArray[0], $object->ItemUnitPrice, $object->ItemID, $object->ItemQuantity, null, null)); 
}

$shippingArray = $jsonDecord[0]->ShipmentList;
$shippingPrice = 0; 
foreach ($shippingArray as $sobject) { 
	$shippingPrice += $sobject->ShipmentCost;

    $shippingContents = new \CreditKey\Models\Address($sobject->ShipmentFirstName, $sobject->ShipmentLastName, $sobject->ShipmentCompany, $jsonDecord[0]->BillingEmail, $sobject->ShipmentAddress, $sobject->ShipmentAddress2, $sobject->ShipmentCity, $sobject->ShipmentState, $sobject->ShipmentZipCode, $sobject->ShipmentPhone);
}

$tax = $jsonDecord[0]->SalesTax + $jsonDecord[0]->SalesTax2 + $jsonDecord[0]->SalesTax3;
$discountAmount = $jsonDecord[0]->OrderDiscount;
$charges = new \CreditKey\Models\Charges($itemPrice, $shippingPrice, $tax, $discountAmount, $jsonDecord[0]->OrderAmount);

$sqlSelectOrder = "SELECT * FROM `ck_order` WHERE orderid = '".$OrderID."' ";
$resultOrder = $con->query($sqlSelectOrder);
if ($resultOrder->num_rows > 0) {
	while($orow = $resultOrder->fetch_assoc()) {
		if( $orow['testmode'] ){ 
			\CreditKey\Api::configure(\CreditKey\Api::STAGING, $orow['username'], $orow['password']);
		}else{   
			\CreditKey\Api::configure(\CreditKey\Api::PRODUCTION, $orow['username'], $orow['password']);
		}
		$orderSID = $orow['order_status'];
		$orderMerchantID = $orow['merchant_id'];
		$randomkey = $orow['randomkey'];
		$notificationurl = $orow['notificationurl'];
	}
}

$sqlSelect = "SELECT * FROM `ck_merchant` WHERE TokenKey = '".$TokenKey."' ";
$result = $con->query($sqlSelect);
if ($result->num_rows > 0) {
	while($row = $result->fetch_assoc()) {
		if( $row['Action'] == "AUTHORIZE" && $row['ShippedOrderStatusID'] != $orderSID ){
			$statusArray = json_decode($row['OrderStatus']);
			foreach ($statusArray as $object) {
				if( $object->Visible && $object->OrderStatusID == $OrderStatusID ){
		  			echo $statusName = $object->StatusText;
		  		}   
		  	}	
			if( $row['CancelOrderStatusID'] == $OrderStatusID ){

				$orderCancel = \CreditKey\Orders::cancel($TransactionID);
				file_put_contents('logs/dataTrack.txt', print_r($orderCancel, true));
				$msg = 'order invoice: '.$invoice.', date: '.date('Y-m-d H:i:s').', StatusName: '.$statusName.', order cancelled successfully';
				$sqlUpdate = "UPDATE `ck_order` SET order_comment = '".$msg."', order_status = ".$OrderStatusID." WHERE orderid = '".$OrderID."' AND merchant_id = '".$orderMerchantID."' ";
				$logfile = "logs/$orderMerchantID/3DcartCancelOrderLog.txt";
				$ErrorMsg = '<!== log start for order invoice: '.$invoice.', date: '.date('Y-m-d H:i:s').', StatusName: '.$statusName.', order cancelled successfully ==!>';
				error_log($ErrorMsg."\n",3,$logfile); 

			}else if( $row['ShippedOrderStatusID'] == $OrderStatusID ){

				$orderConfirm = \CreditKey\Orders::confirm($TransactionID, $statusName, $invoice, $cartContents, $charges, $shippingContents);
				$msg = 'order invoice: '.$invoice.', date: '.date('Y-m-d H:i:s').', StatusName: '.$statusName.', order confirmed successfully';
				$sqlUpdate = "UPDATE `ck_order` SET order_comment = '".$msg."', order_status = ".$OrderStatusID." WHERE orderid = '".$OrderID."' AND merchant_id = '".$orderMerchantID."' "; 
				$logfile = "logs/$orderMerchantID/3DcartConfirmOrderLog.txt";
				$ErrorMsg = '<!== log start for order invoice: '.$invoice.', date: '.date('Y-m-d H:i:s').', StatusName: '.$statusName.', order confirmed successfully ==!>';
				error_log($ErrorMsg."\n",3,$logfile);

			}else{

				$orderUpdate = \CreditKey\Orders::update($TransactionID, $statusName, $invoice, $cartContents, $charges, $shippingContents);
				$msg = 'order invoice: '.$invoice.', date: '.date('Y-m-d H:i:s').', StatusName: '.$statusName.', order updated successfully';
				$sqlUpdate = "UPDATE `ck_order` SET order_comment = '".$msg."', order_status = ".$OrderStatusID." WHERE orderid = '".$OrderID."' AND merchant_id = '".$orderMerchantID."' "; 
				$logfile = "logs/$orderMerchantID/3DcartUpdateOrderLog.txt";
				$ErrorMsg = '<!== log start for order invoice: '.$invoice.', date: '.date('Y-m-d H:i:s').', StatusName: '.$statusName.', order updated successfully ==!>'; 
				error_log($ErrorMsg."\n",3,$logfile);
			}
    		$con->query($sqlUpdate);
    	}
	}
}
?>