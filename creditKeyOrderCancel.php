<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('./creditkey/init.php');

$paymenttoken = $_GET['id'];

$sqlUpdate = "UPDATE `ck_order` SET `order_status` = 0, `order_completed` = 0, `order_comment` = 'Canceled by customer' WHERE paymenttoken = '".$paymenttoken."' ";
$con->query($sqlUpdate);

$sqlSelect = "SELECT cancelurl FROM `ck_order` WHERE paymenttoken = '".$paymenttoken."' ";
$oresult = $con->query($sqlSelect);
if ($oresult->num_rows > 0) {
	while($row = $oresult->fetch_assoc()) {
		$logfile = "logs/$orderMerchantID/3DcartCancelOrderLog.txt";
		$ErrorMsg = '<!== log start for order invoice: '.$row['invoice'].', date: '.date('Y-m-d H:i:s').', Canceled by customer. ==!>';
		error_log($ErrorMsg."\n",3,$logfile);
		header('Location: '.$row['cancelurl']);
	}
}
?>