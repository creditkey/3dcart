<?php 
// Custom Functions 
function getMerchant($url, $con){
	$sqlSelectM = "SELECT * FROM `ck_merchant` WHERE SecureURL = '".$url."' ";
	$resultMerchant = $con->query($sqlSelectM);
	if ($resultMerchant->num_rows > 0) {
	    return mysqli_fetch_assoc( $resultMerchant);
	}else{
		return 0;
	}
}

function getMerchantbyID($id, $con){
	$sqlSelectM = "SELECT * FROM `ck_merchant` WHERE id = '".$id."' ";
	$resultMerchant = $con->query($sqlSelectM);
	if ($resultMerchant->num_rows > 0) {
	    return mysqli_fetch_assoc( $resultMerchant);
	}else{
		return 0;
	}
}
?>