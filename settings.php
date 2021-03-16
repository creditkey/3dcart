<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('./creditkey/init.php');

$paymenttoken = $_GET['token'];
$successMessage = '';   

if(isset($_POST['submit'])){
 	$selectCancelStatus= $_POST['selectStatus'];
 	$selectShippedStatus = $_POST['selectShippedStatus'];
 	$sqlUpdate = "UPDATE `ck_merchant` SET `CancelOrderStatusID` = '".$selectCancelStatus."', `ShippedOrderStatusID` = '".$selectShippedStatus."' WHERE TokenKey = '".$paymenttoken."' ";
	$con->query($sqlUpdate);
	$successMessage = "Order status is saved successfully";
} 

$sqlSelect = "SELECT * FROM `ck_merchant` WHERE TokenKey = '".$paymenttoken."' ";
$oresult = $con->query($sqlSelect);
if ($oresult->num_rows > 0) {
	while($row = $oresult->fetch_assoc()) {
		$SecureURL = $row['SecureURL'];
		$CancelOrderStatusID = $row['CancelOrderStatusID'];
		$ShippedOrderStatusID = $row['ShippedOrderStatusID'];
		 
		if( $row['Action'] == "AUTHORIZE" && $row['WebhookID'] == 0 ){
			$curl = curl_init();
			curl_setopt_array($curl, array(
			  CURLOPT_URL => "https://apirest.3dcart.com/3dCartWebAPI/v1/Webhooks",
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => "",
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 30,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => "POST",
			  CURLOPT_POSTFIELDS => "{\r\n  \"Name\": \"CreditKey Order Update\",\r\n  \"Url\": \"https://3dcart.creditkey.com/updateOrder3dcartApi.php?key=$paymenttoken\",\r\n  \"EventType\": 2,\r\n  \"Enabled\": 1,\r\n  \"Format\": \"JSON\"\r\n}",
			  CURLOPT_HTTPHEADER => array(
			    "accept: application/json",
			    "cache-control: no-cache",
			    "content-type: application/json",
			    "privatekey: $privatekey",
			    "secureurl: $SecureURL",
			    "token: $paymenttoken"
			  ),
			));
			$responseW = curl_exec($curl);
			$errW = curl_error($curl);
			curl_close($curl);
			if ($errW) {
			  echo "cURL Error #:" . $errW;
			} else {
			  $responseArray = json_decode($responseW, true);
			  $sqlUpdateWebhook = "UPDATE `ck_merchant` SET `WebhookID` = '".$responseArray[0]['Value']."'WHERE TokenKey = '".$paymenttoken."' ";
			  $con->query($sqlUpdateWebhook);
			}
		}

		$curl = curl_init();
		curl_setopt_array($curl, array(
		  CURLOPT_URL => "https://apirest.3dcart.com/3dCartWebAPI/v2/OrderStatus",
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => "",
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "GET",
		  CURLOPT_HTTPHEADER => array(
		    "accept: application/json",
		    "cache-control: no-cache",
		    "content-type: application/xml",
		    "privatekey: $privatekey",
		    "secureurl: $SecureURL",
		    "token: $paymenttoken"
		  ),
		));

		$response = curl_exec($curl);
		$err = curl_error($curl);
		curl_close($curl);

		if ($err) {
		  echo "cURL Error #:" . $err;
		} else {
			$responseArray = json_decode($response, true);
			$sqlUpdateStatus = "UPDATE `ck_merchant` SET `OrderStatus` = '".$response."'WHERE TokenKey = '".$paymenttoken."' ";
			$con->query($sqlUpdateStatus);
		}
	}
}
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <title>CreditKey Settings</title>
  </head>
  <body>
  	<div class="container">
		<div class="py-5 text-center">
        	<img class="d-block mx-auto mb-4" src="./images/creditKey.svg" alt="CreditKey">
        	<form method="post" action="" style="max-width: 450px;margin: 0 auto;">
        		<?php 
        		if($successMessage!=''){
        			echo '<div class="alert alert-success" role="alert">'.$successMessage.'</div>';
        		}
        		?>
        		
        		<div class="mb-3 text-start">
				    <label for="selectShippedStatus" class="form-label">Please Select Shipped order status</label>
				    <select class="form-select" name="selectShippedStatus" id="selectShippedStatus" aria-label="Select Shipped order status">
					  	<option value="0" <?php echo ($ShippedOrderStatusID == 0) ? "Selected" : "" ; ?> >Please select one</option>
					  	<?php 
					  	foreach ($responseArray as $object) {
					  		if( $object['Visible'] ){
					  			?>
					  			<option value="<?php echo $object['OrderStatusID'] ?>" <?php echo ($ShippedOrderStatusID == $object['OrderStatusID']) ? "Selected" : "" ; ?> ><?php echo $object['StatusText'] ?></option>
					  			<?php
					  		}
					  	}	
					  	?>
					</select>
				    <div id="emailHelp" class="form-text">This order status will be used to identify completed/shipped order in CreditKey. Please note that Shipped/Completed orders will not be canceled from CreditKey.</div>
			  	</div>
        		<div class="mb-3 text-start">
				    <label for="selectStatus" class="form-label">Please Select Cancel order status</label>
				    <select class="form-select" name="selectStatus" id="selectStatus" aria-label="Select Cancel order status">
					  	<option value="0" <?php echo ($CancelOrderStatusID == 0) ? "Selected" : "" ; ?> >Please select one</option>
					  	<?php 
					  	foreach ($responseArray as $object) {
					  		if( $object['Visible'] ){
					  			?>
					  			<option value="<?php echo $object['OrderStatusID'] ?>" <?php echo ($CancelOrderStatusID == $object['OrderStatusID']) ? "Selected" : "" ; ?> ><?php echo $object['StatusText'] ?></option>
					  			<?php
					  		}
					  	}	
					  	?>
					</select>
				    <div id="emailHelp" class="form-text">This order status will be used to cancel order in CreditKey.</div>
			  	</div>
        		
				<button type="submit" name="submit" class="btn btn-primary">Submit</button>
        	</form>
        	
      </div>
	</div>
  </body>
</html>
