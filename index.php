<?php
session_start();

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

    #Register!
    $sql = $con->prepare("INSERT INTO addresses (address, privview, pubspend, privspend) VALUES ('$naddr', '$privview', '$pubspend', '$privspend')");
    $result = $sql->execute();
    if ($result) {
      echo "Creation succeeded!<br>";
    }
    else {
      echo "Connection failed, please contact hello@notrait.com to insert your credentials";
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
    <title>Turtlecoin web wallet</title>
    <link href="https://fonts.googleapis.com/css?family=Muli|Titan+One" rel="stylesheet">
    <style media="screen">
      body {
        text-align: center;
        background-image: url("https://images8.alphacoders.com/465/465304.jpg");
        background-size: cover;
        background-repeat: no-repeat;
      }
      div {
        font-size: 50px;
        font-family: 'Titan One', cursive;
      }
      span {
        color: red;
        font-size: 25px;
        font-family: 'Muli', sans-serif;
      }
      h3 {
        color: white;
        font-family: 'Muli', sans-serif;
      }
    </style>
  </head>
  <body>
    <div id="intro">
      The simplest way to use Turtlecoin (beta)
    </div>
    <form id="login" action="wallet.php" method="post">
      <input type="text" name="addr" placeholder="Address"><br>
      <input type="text" name="pubspend" placeholder="Public spend key"><br>
      <input type="text" name="privspend" placeholder="Private spend key"><br>
      <input type="text" name="privview" placeholder="Private view key"><br>
      <input type="submit" value="Log me in!">
    </form></p>
    <form id="create" action="index.php" method="post">
      <input type="hidden" name="create" value="true">
      <input type="submit" value="Create account">
    </form>
    <?php
    if (isset($_GET["error"])) {
      echo "<span>" . htmlspecialchars(stripslashes(trim($_GET["error"]))) . "</span>";
    }
     ?>
  </body>
</html>
