<?php
require_once('./creditkey/connection.php');
$json = file_get_contents('php://input');
file_put_contents('logs/3DcartCallBackURL.txt', $json); 
$jsonDecord = json_decode($json); 
 
$sqlSelect = "SELECT * FROM `ck_merchant` WHERE SecureURL = '".$jsonDecord->SecureURL."' ";
$result = $con->query($sqlSelect);
if ($result->num_rows > 0) {
  if($jsonDecord->Action == "REMOVE"){
    $sqlUpdate = "UPDATE `ck_merchant` SET 
       `PublicKey` = '".$jsonDecord->PublicKey."', 
       `TimeStamp` = '".$jsonDecord->TimeStamp."', 
       `TokenKey` = '".$jsonDecord->TokenKey."', 
       `Action` = '".$jsonDecord->Action."',
       `WebhookID` = 0
     WHERE SecureURL = '".$jsonDecord->SecureURL."' ";  
  $con->query($sqlUpdate);
  }else{
  	$sqlUpdate = "UPDATE `ck_merchant` SET 
         `PublicKey` = '".$jsonDecord->PublicKey."', 
         `TimeStamp` = '".$jsonDecord->TimeStamp."', 
         `TokenKey` = '".$jsonDecord->TokenKey."', 
         `Action` = '".$jsonDecord->Action."'
       WHERE SecureURL = '".$jsonDecord->SecureURL."' ";  
  	$con->query($sqlUpdate); 
  }
} else {  
	$sqlInsert = "INSERT INTO `ck_merchant` (`PublicKey`, `TimeStamp`, `TokenKey`, `Action`, `SecureURL`) VALUES ('".$jsonDecord->PublicKey."', '".$jsonDecord->TimeStamp."', '".$jsonDecord->TokenKey."', '".$jsonDecord->Action."', '".$jsonDecord->SecureURL."')";  
	$con->query($sqlInsert);  
}

$sqlSelectID = "SELECT id FROM `ck_merchant` WHERE SecureURL = '".$jsonDecord->SecureURL."' ";
$resultID = $con->query($sqlSelectID);
if ($resultID->num_rows > 0) {
  while($row = $resultID->fetch_assoc()) {
    $merchantID = $row['id'];
    $fileArray = ['3DcartCancelOrderLog.txt', '3DcartConfirmOrderLog.txt', '3DcartUpdateOrderLog.txt', '3DcartVoidOrderLog.txt', '3DcartSuccessOrderLog.txt', '3DcartErrorOrderLog.txt'];
    if (!file_exists(dirname(__FILE__).'/logs/'.$merchantID)) {
        mkdir(dirname(__FILE__).'/logs/'.$merchantID, 0777, true);
        foreach($fileArray as $filename){
            echo $filename;
            $file = dirname(__FILE__).'/logs/'.$merchantID.'/'.$filename;
            $fp = fopen($file, 'w');
            fwrite($fp, '');
            fclose($fp);
            chmod($file, 0777);
        }
    }
  }
} 
?> 