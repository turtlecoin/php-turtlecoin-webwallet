<?php
session_start();

if (isset($_SESSION["addr"])) {
  header('Location: wallet.php');
}
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
  require '../vendor/autoload.php';
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
      echo "Fail: " . $con->error;
    }
    $con->close();

    echo "These are your login creds, store them safely!<br>";
    echo "Address: " . $naddr . "<br>";
    echo "Public spend key: " . $pubspend . "<br>";
    echo "Private spend key: " . $privspend . "<br>";
    echo "Private view key: " . $privview . "<br>";
  }
}
 ?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Turtlecoin web wallet</title>
  </head>
  <body>
    <div id="intro">
      Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
    </div>
    <form id="login" action="wallet.php" method="post">
      <input type="text" name="addr" placeholder="Address">
      <input type="text" name="pubspend" placeholder="Public spend key">
      <input type="text" name="privspend" placeholder="Private spend key">
      <input type="text" name="privview" placeholder="Private view key">
      <input type="submit" value="Log me in!">
    </form>
    <form id="create" action="index.php" method="post">
      <input type="hidden" name="create" value="true">
      <input type="submit" value="Create account">
    </form>
  </body>
</html>
