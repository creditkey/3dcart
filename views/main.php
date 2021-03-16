<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="./images/favicon.ico"/>
    <link rel="icon" type="image/png" sizes="32x32" href="./images/favicon-32x32.png"/>
    <link rel="icon" type="image/png" sizes="16x16" href="./images/favicon-16x16.png"/>
    <link rel="apple-touch-icon" sizes="180x180" href="./images/apple-touch-icon.png"/>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
    <title><?php echo $article['title']; ?></title>
  </head>
  <body>
  	<div class="container">
		<div class="py-5 text-center">
        	<img class="d-block mx-auto mb-4" src="./images/creditKey.svg" alt="CreditKey">
        	<p class="lead mb-4"><?php echo $article['subtitle']; ?></p>
        	<!-- <hr class="mb-4"> -->
        	<?php 
        		if ( isset($article['cancelurl']) ){
        			echo '<a href="'.$article['cancelurl'].'" class="btn btn-primary btn-lg btn-block">Back to checkout</a>';
        		}
        	?>
        	
      </div>
	</div>
 
    <!-- <h1><?php // echo $article['subtitle']; ?></h1> -->

    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script> -->
  </body>
</html>