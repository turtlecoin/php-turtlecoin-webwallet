<?php
	session_start();

	if (!isset($_SESSION['username'])) {
		$_SESSION['msg'] = "You must log in first";
		header('location: login.php');
	}

	if (isset($_GET['logout'])) {
		session_destroy();
		unset($_SESSION['username']);
		header("location: login.php");
	}

	if (isset($_SESSION["addr"])) {
	  header('Location: wallet.php');
	}
	elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
	  require 'conf.php';
	  #Check if Connection Succeeded
	  if ($con->connect_error) {
	    die("Fatal error!");
	  }
	  if (isset($_POST["create"])) {
	    #Generate new address
	    $gen = $walletd->createAddress()->getBody()->getContents();
	    #Decode
	    $decgen = json_decode($gen, true);
	    $naddr = $decgen["result"]["address"];
	    #Get keys
	    $spendkey = $walletd->getSpendKeys($naddr)->getBody()->getContents();
	    $vkey = $walletd->getViewKey()->getBody()->getContents();
	    #Decode
	    $decvkey = json_decode($vkey, true);
	    $decspendkey = json_decode($spendkey, true);

	    $privview = $decvkey["result"]["viewSecretKey"];
	    $pubspend = $decspendkey["result"]["spendPublicKey"];
	    $privspend = $decspendkey["result"]["spendSecretKey"];
			$username = $_SESSION['username'];

	    #Register!
	    $sql = $con->prepare("INSERT INTO addresses (username, address, privview, pubspend, privspend) VALUES ('$username', '$naddr', '$privview', '$pubspend', '$privspend')");
	    $result = $sql->execute();
	    if ($result) {
	      echo "Creation succeeded!<br>";
	    }
	    else {
	      echo "Connection failed, please contact turtlecoin.lol@protonmail.com to insert your credentials";
	    }
	    $con->close();

	    echo "<h3>These are your login creds, store them safely!<br>";
	    echo "Address: " . $naddr . "<br>";
	    echo "Public spend key: " . $pubspend . "<br>";
	    echo "Private spend key: " . $privspend . "<br>";
	    echo "Private view key: " . $privview . "<br></h3>";
	  }
	}
	 ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
<script src="http://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<title>TurtleCoinWebWallet</title>
<link href="css/loginmodule.css" rel="stylesheet" type="text/css" />

</head>
<body class="fff">
	
<center>
<h1><img src="logo.png"/></h1>
<h1>&nbsp;</h1>
<h1>&nbsp;</h1>
<h1>&nbsp;</h1>
<p>&nbsp;</p>
	<div class="header">
		<h2>Home Page</h2>
	</div>
	<div class="content">

		<!-- notification message -->
		<?php if (isset($_SESSION['success'])) : ?>
			<div class="error success" >
				<h3>
					<?php
						echo $_SESSION['success'];
						unset($_SESSION['success']);
					?>
				</h3>
			</div>
		<?php endif ?>

		<!-- logged in user information -->
		<?php  if (isset($_SESSION['username'])) : ?>
			<h1><p>Welcome <strong><?php echo $_SESSION['username']; ?></strong></p>
				<form id="create" action="index.php" method="post">
      <input type="hidden" name="create" value="true">
      <input type="submit" value="Create account">
    </form>
		<a href="wallet.php">Go to wallet</a>
    <?php
    if (isset($_GET["error"])) {
      echo "<span>" . htmlspecialchars(stripslashes(trim($_GET["error"]))) . "</span>";
    }
     ?>
			<p> <a href="index.php?logout='1'" style="color: red;">logout</a> </p>
		<?php endif ?>
	</div>

</body>
</html>
