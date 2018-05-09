<?php
session_start();

require 'conf.php';

use chillerlan\QRCode\QRCode;

if (isset($_GET["logout"])) {
  session_destroy();
  header('Location: wallet.php');
}
elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["trans"])) {
      #Get variables and set them
      $anonymity = intval($_POST["mixin"]);
      $rec = $_POST["addr"];
      $rawfee = (float) $_POST["fee"];
      $rawamount = (float) $_POST["amount"];
      $fee = intval($rawfee * 100);
      $amount = intval($rawamount * 100);
      $pid = $_POST["pid"];
      $addresses = array($_SESSION["addr"]);
      $transfers = [
         [
            "address" => $rec,
            "amount"  => $amount
         ]
      ];
      if (strlen($pid) != 0) {
        $trans = $walletd->sendTransaction($anonymity, $transfers, $fee, $addresses, 0, null, $pid)->getBody()->getContents();
      }
      else {
        $trans = $walletd->sendTransaction($anonymity, $transfers, $fee, $addresses, 0, null)->getBody()->getContents();
      }
      #Decode
      $dectrans = json_decode($trans, true);
      #Check for errors
      if (isset($dectrans["error"])) {
        if ($dectrans["error"]["message"] == "Wrong amount") {
          die("<script>alert('Insufficient funds');</script>");
        }
        elseif ($dectrans["error"]["message"] == "Bad address"){
          die("<script>alert('The sender/receiver address is invalid');</script>");
        }
        else {
          die("<script>alert('" . $dectrans["error"]["message"] . "');</script>");
        }
      }
      else {
        echo "Transaction sent to blockchain: <a target='_blank' href='https://turtle-coin.com/?hash=" . $dectrans["result"]["transactionHash"] . "#blockchain_transaction'>Watch status</a>";
      }
    }
    else {
      $addr = $_POST["addr"];
      $pview = $_POST["privview"];
      $pubspend = $_POST["pubspend"];
      $pspend = $_POST["privspend"];

      $sql = $con->prepare("SELECT address, privview, pubspend, privspend FROM addresses WHERE address=? AND privview=? AND pubspend=? AND privspend=?");
      $sql->bind_param('ssss', $addr, $pview, $pubspend, $pspend);
      $suc = $sql->execute();
      if ($suc) {
        echo "You are logged in!";
        $_SESSION["addr"] = $addr;
        header('Location: wallet.php');
      }
      else {
        header('Location: index.php?error=The keys are not valid!');
      }
      $sql->close();
  }
}
elseif (!isset($_SESSION["addr"])) {
  logout();
}
function logout() {
  header('Location: index.php?error=Your session timed out!');
}
 ?>
<!DOCTYPE html>
<html>
 <head>
   <meta charset="utf-8">
   <title>You wallet</title>
   <link href="https://fonts.googleapis.com/css?family=Muli|Titan+One" rel="stylesheet">
   <style media="screen">
     form, input, select {
       text-align: center;
       font-family: 'Muli', sans-serif;
     }
     div {
       text-align: center;
     }
     body {
       background-color: #5dcc63;
       color: white;
       font-family: 'Muli', sans-serif;
     }
   </style>
   <script>
   function copy(){var copyText = document.getElementById('addr'); copyText.select(); document.execCommand('Copy'); document.getElementById('btn').innerHTML = 'Copied!'}</script>
 </head>
 <body>
   <a href="wallet.php?logout=true">Logout</a></p>
   <div>
   <?php
   if (isset($_SESSION["addr"])) {
     bal($_SESSION["addr"], $walletd);
     status($walletd);
   }
   function bal($addr, $walletd) {
     $bal = $walletd->getBalance($addr)->getBody()->getContents();
     $decbal = json_decode($bal, true);
     $balance = intval($decbal["result"]["availableBalance"]) / 100;
     $lbalance = intval($decbal["result"]["lockedAmount"]) / 100;
     echo "Your wallet address: <br><input id='addr' size='100%' type='text' value=" . $addr . " readonly><button id='btn' onclick='copy()'>Copy</button><br><img style='background-color: #fff;' src=" . (new QRCode)->render($addr) . "><br>" . "Balance: " . $balance . " TRTL,  Locked: " . $lbalance . " TRTL</p>";
   }
   function status($walletd) {
     $status = $walletd->getStatus()->getBody()->getContents();
     $decstats = json_decode($status, true);
     $sblocks = $decstats["result"]["blockCount"];
     $bcount = $decstats["result"]["knownBlockCount"];
     $rem = $bcount - $sblocks;
     if ($rem == 1) {
       echo "Syncing your wallet: " . $rem . " block remaining<br>You can make transactions";
     }
     elseif ($rem > 1) {
       echo "Syncing your wallet: " . $rem . " blocks remaining<br>No transactions allowed, until synced";
     }
     else {
       echo "Wallet synced: You can make transactions";
     }
   }
    ?>
  </div>
  </p>
    <form action="wallet.php" method="post">
      <input type="hidden" name="trans" value="true">
      <input type="text" name="addr" placeholder="Address"><br>
      <input type="text" name="amount" placeholder="Amount"><br>
      Fee: <select name="fee">
        <option value="0.1">0.1 TRTL (slow)</option>
        <option value="1">1 TRTL (medium)</option>
        <option value="10">10 TRTL (fast)</option>
        <option value="50">50 TRTL (very fast)</option>
      </select><br>
      Anonymity: &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<select name="mixin">
        <option value="0">0</option>
        <option value="5">5</option>
        <option value="7">7</option>
        <option value="10">10</option>
        <option value="20">20</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select><br>
      <input type="text" name="pid" placeholder="PaymentID(optional)"><br>
      <input type="submit" value="Create transaction">
    </form>
    <?php
    echo "</p><div>Transactions<br>";
    $addr = array($_SESSION["addr"]);
    $status = $walletd->getStatus()->getBody()->getContents();
    $decstats = json_decode($status, true);
    $bcount = intval($decstats["result"]["knownBlockCount"]);
    $fbi = 1;
    $ltrans = $walletd->getTransactions($bcount, $fbi, null, $addr)->getBody()->getContents();
    $decltrans = json_decode($ltrans, true);
    $pcount = count($decltrans["result"]["items"]);
    for ($i=0; $i < $pcount; $i++) {
      $test = count($decltrans["result"]["items"][$i]["transactions"]);
      for ($h=0; $h < $test; $h++) {
        $tcount = count($decltrans["result"]["items"][$i]["transactions"][$h]["transfers"]);
        for ($j=0; $j < $tcount; $j++) {
              if ($addr[0] == $decltrans["result"]["items"][$i]["transactions"][0]["transfers"][$j]["address"]) {
                if ($decltrans["result"]["items"][$i]["transactions"][0]["transfers"][$j]["amount"] < 0) {
                  echo "Outgoing: " . "<a target='_blank' href='https://turtle-coin.com/?hash=" . $decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"] . "#blockchain_transaction'>" . substr($decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"], 0, -30) . "...</a> &nbsp;&nbsp;&nbsp;" . $decltrans["result"]["items"][$i]["transactions"][0]["transfers"][$j]["amount"] / 100 . " TRTL &nbsp;&nbsp;&nbsp; Fee: " . $decltrans["result"]["items"][$i]["transactions"][0]["fee"] / 100 . " TRTL<br>";
                }
                else {
                  echo "Incoming: " . "<a target='_blank' href='https://turtle-coin.com/?hash=" . $decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"] . "#blockchain_transaction'>" . substr($decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"], 0, -30) . "...</a> &nbsp;&nbsp;&nbsp;+" . $decltrans["result"]["items"][$i]["transactions"][0]["transfers"][$j]["amount"] / 100 . " TRTL &nbsp;&nbsp;&nbsp; Fee: " . $decltrans["result"]["items"][$i]["transactions"][0]["fee"] / 100 . " TRTL<br>";
                }
            }
          }
      }
    }
     ?>
   </div>
 </body>
</html>
