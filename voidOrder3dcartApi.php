<?php 
require_once('./creditkey/init.php');
$json = file_get_contents('php://input');
file_put_contents('logs/3DcartPayloadCancel.txt', $json);   
$jsonDecord = json_decode($json);

$sqlSelectOrder = "SELECT * FROM `ck_order` WHERE orderid = '".$jsonDecord->orderid."' ";
$resultOrder = $con->query($sqlSelectOrder);
if ($resultOrder->num_rows > 0) {
	while($orow = $resultOrder->fetch_assoc()) {
		
		$merchantResult = getMerchantbyID($orow['merchant_id'], $con);
		$merchantId = $merchantResult['id'];
		$ShippedOrderStatusID = $merchantResult['ShippedOrderStatusID'];
		
		if( $orow['order_status'] == $ShippedOrderStatusID ){

			if( $orow['testmode'] ){ 
				\CreditKey\Api::configure(\CreditKey\Api::STAGING, $orow['username'], $orow['password']);
			}else{   
				\CreditKey\Api::configure(\CreditKey\Api::PRODUCTION, $orow['username'], $orow['password']);
			}

			$refundAmount = $jsonDecord->amounttotal/100;
			$orderRefund = \CreditKey\Orders::refund($jsonDecord->transactionid, strval($refundAmount)); 

			$msg = 'order invoice: '.$jsonDecord->invoice.', date: '.date('Y-m-d H:i:s').', order void successfully';

			$sqlUpdate = "UPDATE `ck_order` SET `order_comment` = '".$msg."', `type` = 'void' WHERE orderid = '".$jsonDecord->orderid."' AND merchant_id = '".$merchantId."' ";
			$con->query($sqlUpdate);

			$logfile = "logs/$merchantId/3DcartVoidOrderLog.txt";
			$ErrorMsg = '<!== log start for order invoice: '.$jsonDecord->invoice.', date: '.date('Y-m-d H:i:s').', order void successfully ==!>';
			error_log($ErrorMsg."\n",3,$logfile);
			$response = new \stdClass();
			$response->approved = 1;
			$response->transactionid = $jsonDecord->transactionid; 
			$response->errorcode = ""; 
			$response->errormessage = "";
			$response->randomkey = $jsonDecord->randomkey;
			$response->signature = md5($jsonDecord->randomkey.''.$privatekey.''.$jsonDecord->orderid.''.$jsonDecord->invoice.''.$jsonDecord->transactionid);  
			echo json_encode($response);
		}else{
			$logfile = "logs/$merchantId/3DcartVoidOrderLog.txt"; 
			$ErrorMsg = '<!== log start for order invoice: '.$jsonDecord->invoice.', date: '.date('Y-m-d H:i:s').', order void unsuccessful, Order is not shipped yet ==!>';
			error_log($ErrorMsg."\n",3,$logfile);
			$response = new \stdClass();
			$response->approved = 0;
			$response->transactionid = $jsonDecord->transactionid;
			$response->errorcode = "CreditKey:exception"; 
			$response->errormessage = "Only Shipped orders can void the transaction.";
			$response->randomkey = $jsonDecord->randomkey;
			$response->signature = md5($jsonDecord->randomkey.''.$privatekey.''.$jsonDecord->orderid.''.$jsonDecord->invoice.''.$jsonDecord->transactionid);  
			echo json_encode($response);
		}
	}
}
?>
