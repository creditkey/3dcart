<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once('./creditkey/init.php');
 
$paymenttoken = $_POST["paymenttoken"];
$sqlSelect = "SELECT * FROM `ck_order` WHERE paymenttoken = '".$paymenttoken."' ";
$result = $con->query($sqlSelect);
if ($result->num_rows > 0) {
  while($row = $result->fetch_assoc()) {
     
    if( $row['testmode'] ){ 
    	\CreditKey\Api::configure(\CreditKey\Api::STAGING, $row['username'], $row['password']);
    }else{   
    	\CreditKey\Api::configure(\CreditKey\Api::PRODUCTION, $row['username'], $row['password']);
    }

    $billingAddressObject = $row['billing'];
    $billingAddressArray = json_decode($billingAddressObject);
    $billingAddress = new \CreditKey\Models\Address($billingAddressArray->firstname, $billingAddressArray->lastname, $billingAddressArray->company, $row['email'], $billingAddressArray->address, $billingAddressArray->address2, $billingAddressArray->city, $billingAddressArray->state, $billingAddressArray->zip, $billingAddressArray->phone);

    $shippingAddressObject = $row['shipping'];
    $shippingAddressArray = json_decode($shippingAddressObject);
    $shippingAddress = new \CreditKey\Models\Address($shippingAddressArray->firstname, $shippingAddressArray->lastname, $shippingAddressArray->company, $row['email'], $shippingAddressArray->address, $shippingAddressArray->address2, $shippingAddressArray->city, $shippingAddressArray->state, $shippingAddressArray->zip, $shippingAddressArray->phone);
 
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
    $isDisplayed = \CreditKey\Checkout::isDisplayedInCheckout($cartContents, NULL);
    if( $isDisplayed ){
        $redirectUrl = \CreditKey\Checkout::beginCheckout($cartContents, $billingAddress, $shippingAddress, $charges, $row['paymenttoken'], NULL, 'https://3dcart.creditkey.com/creditKeyOrderSuccess.php?id='.$paymenttoken.'&ckOrderId=%CKKEY%', 'https://3dcart.creditkey.com/creditKeyOrderCancel.php?id='.$paymenttoken.'', 'redirect');
        $sqlUpdate = "UPDATE `ck_order` SET `creditkey_order_id` = '".$redirectUrl->id."' WHERE paymenttoken = '".$paymenttoken."' ";
		$con->query($sqlUpdate);
        header('Location: '.$redirectUrl->checkout_url);
        
    }else{
        $sqlUpdate = "UPDATE `ck_order` SET `order_comment` = '{is_displayed_in_checkout: false}' WHERE paymenttoken = '".$paymenttoken."' ";
        $con->query($sqlUpdate);
    	$article = array(    
        'title' => 'CreditKey not available',
        'subtitle' => 'CreditKey is not available at this moment. Please try after sometime.',
        'cancelurl' => $row['cancelurl']
        );
        include("./views/main.php"); 
    }      

  }
} else {
   $article = array(    
    'title' => 'No Result Found',
    'subtitle' => 'No Result Found. Please try again.'
    );
    include("./views/main.php"); 
} 
?>