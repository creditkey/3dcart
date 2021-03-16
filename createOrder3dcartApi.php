<?php 
require_once('./creditkey/connection.php');
$json = file_get_contents('php://input');
file_put_contents('logs/3DcartPayload.txt', $json); 
$jsonDecord = json_decode($json); 

$paymenttoken = md5(uniqid(rand(), true));

$urlC = parse_url($jsonDecord->cancelurl);
$merchantURL = $urlC['scheme']."://".$urlC['host'];
$sqlSelectMID = "SELECT * FROM `ck_merchant` WHERE SecureURL = '".$merchantURL."' ";
$resultMID = $con->query($sqlSelectMID);
if ($resultMID->num_rows > 0) {
      while($MIDrow = $resultMID->fetch_assoc()) {
            $merchantID = $MIDrow['id'];
      }
}
 
$sqlSelect = "SELECT * FROM `ck_order` WHERE orderid = '".$jsonDecord->orderid."' ";
$result = $con->query($sqlSelect);
if ($result->num_rows > 0) { 
	$sqlUpdate = "UPDATE `ck_order` SET 
       `username` = '".$jsonDecord->username."',
       `password` = '".$jsonDecord->password."',     
       `email` = '".$jsonDecord->email."', 
       `amounttotal` = '".$jsonDecord->amounttotal."', 
       `taxtotal` = '".$jsonDecord->taxtotal."', 
       `shippingtotal` = '".$jsonDecord->shippingtotal."', 
       `discount` = '".$jsonDecord->discount."',
       `cancelurl` = '".$jsonDecord->cancelurl."',
       `errorurl` = '".$jsonDecord->errorurl."',
       `notificationurl` = '".$jsonDecord->notificationurl."',
       `returnurl` = '".$jsonDecord->returnurl."',
       `randomkey` = '".$jsonDecord->randomkey."',
       `signature` = '".$jsonDecord->signature."',
       `billing` = '".addslashes(json_encode($jsonDecord->billing))."',
       `shipping` = '".addslashes(json_encode($jsonDecord->shipping))."',
       `items` = '".addslashes(json_encode($jsonDecord->items))."',
       `testmode` = '".$jsonDecord->testmode."',
       `paymenttoken` = '".$paymenttoken."'
  	 WHERE orderid = '".$jsonDecord->orderid."' ";  
	$con->query($sqlUpdate); 
} else {  
	$sqlInsert = "INSERT INTO `ck_order` (`merchant_id`, `type`, `orderid`, `invoice`, `email`, `amounttotal`, `taxtotal`, `shippingtotal`, `discount`, `username`, `password`, `cancelurl`, `errorurl`, `notificationurl`, `returnurl`, `randomkey`, `signature`, `billing`, `shipping`, `items`, `testmode`, `paymenttoken`) VALUES ('".$merchantID."', '".$jsonDecord->type."', '".$jsonDecord->orderid."', '".$jsonDecord->invoice."', '".$jsonDecord->email."', '".$jsonDecord->amounttotal."', '".$jsonDecord->taxtotal."', '".$jsonDecord->shippingtotal."', '".$jsonDecord->discount."', '".$jsonDecord->username."', '".$jsonDecord->password."', '".$jsonDecord->cancelurl."', '".$jsonDecord->errorurl."', '".$jsonDecord->notificationurl."', '".$jsonDecord->returnurl."', '".$jsonDecord->randomkey."', '".$jsonDecord->signature."', '".addslashes(json_encode($jsonDecord->billing))."', '".addslashes(json_encode($jsonDecord->shipping))."', '".addslashes(json_encode($jsonDecord->items))."', '".$jsonDecord->testmode."', '".$paymenttoken."' )"; 
	$con->query($sqlInsert);  
}
  
$response->paymenttoken = $paymenttoken;
$response->redirecturl = "https://3dcart.creditkey.com/createOrderCreditKeyApi.php"; 
$response->redirectmethod = "POST";
$response->errorcode = '';
$response->errormessage = '';
$response->randomkey = $jsonDecord->randomkey;
$response->signature = md5($jsonDecord->randomkey.''.$privatekey.''.$paymenttoken.'https://3dcart.creditkey.com/createOrderCreditKeyApi.php');  
echo json_encode($response);
?>