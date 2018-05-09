<?php
session_start();

require 'conf.php';

use chillerlan\QRCode\QRCode;

if (isset($_GET["logout"])) {
    logout('Your session timed out!');
}
if (isset($_GET["del"])) {
    $addr = $_SESSION["addr"];
    $sql = "SELECT address, id FROM addresses WHERE address='$addr'";
    $result = $con->query($sql);
    if ($result->num_rows > 0) {
        while ($a = $result->fetch_assoc()) {
            $to = $a["id"];
            $del = $con->prepare("DELETE FROM addresses WHERE addresses.id = $to");
            $res = $del->execute();
            if ($res) {
                #Delete address
                $resp = $walletd->deleteAddress($addr)->getBody()->getContents();
                #Decode
                $decresp = json_decode($resp, true);
                #Check for errors
                if (isset($decresp["error"])) {
                    logout('We were able to delete your wallet from our db, but our payment gate was not able to delete it, please contact us');
                }
                else {
                    logout("Address deleted successfully");
                }
            }
            else {
                logout("Failed to delete address, try again later, or contact us.");
            }
        }
    }
    else {
        logout("This address doesn't exists");
    }
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
        header('Location: wallet.php?sent=true&tx=' . $dectrans["result"]["transactionHash"] . '');
      //  echo "Transaction sent to blockchain: <a target='_blank' href='https://turtle-coin.com/?hash=" . $dectrans["result"]["transactionHash"] . "#blockchain_transaction'>Watch status</a>";
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
  logout("Your session timed out!");
}
function logout($why) {
    session_destroy();
    header('Location: index.php?error=' . $why . '');
}
 ?>
<!DOCTYPE html>
<html>
 <head>
   <meta charset="utf-8">
   <title>Your wallet</title>
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
   function copy(){
       let copyText = document.getElementById('addr');
       copyText.select(); document.execCommand('Copy');
       document.getElementById('btn').innerHTML = 'Copied!'
   }
   function copy2(){
       let copyText = document.getElementById('payid');
       copyText.select(); document.execCommand('Copy');
       document.getElementById('btn2').innerHTML = 'Copied!'
   }
   </script>
 </head>
 <body>
   <a href="wallet.php?logout=true">Logout</a></p>
   <div>
   <?php
   if (isset($_SESSION["addr"])) {
     bal($_SESSION["addr"], $walletd);
     status($walletd);
   }
   if (isset($_GET["sent"])) {
       showtx($_GET["tx"]);
   }
   function bal($addr, $walletd) {
     $bal = $walletd->getBalance($addr)->getBody()->getContents();
     $decbal = json_decode($bal, true);
     $balance = intval($decbal["result"]["availableBalance"]) / 100;
     $lbalance = intval($decbal["result"]["lockedAmount"]) / 100;
     echo "Your wallet address: <br><input id='addr' size='100%' type='text' value=" . $addr . " readonly><button id='btn' onclick='copy()'>Copy</button>";
     if (isset($_GET["genpid"])) {
         try {
             $bytes = random_bytes(32);
         } catch (Exception $e) {
             echo "Failed to generate payment id";
         }
         $hex = bin2hex($bytes);
         echo "<br>Your randomly generated payment id:<br><input id='payid' type='text' size='61%' value=" . $hex . "><button id='btn2' onclick='copy2()'>Copy</button><a href='wallet.php'><button>Stop generating</button></a>";
         echo "<br><img style='background-color: #fff;' src=" . (new QRCode)->render('address:' . $addr . 'paymentic:' . $hex) . "><br>" . "Balance: " . $balance . " TRTL,  Locked: " . $lbalance . " TRTL</p>";
     }
     else {
         echo "<a href='wallet.php?genpid=true'><button>Generate payment id</button></a>";
         echo "<br><img style='background-color: #fff;' src=" . (new QRCode)->render($addr) . "><br>" . "Balance: " . $balance . " TRTL,  Locked: " . $lbalance . " TRTL</p>";
     }
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
   function showtx($tx) {
     echo "<br>Transaction sent to blockchain: <a target='_blank' href='https://turtle-coin.com/?hash=" . $tx . "#blockchain_transaction'>Watch status</a>";
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
    if ($pcount != 0) {
        for ($i = $pcount - 1; $i > 0; $i--) {
            $test = count($decltrans["result"]["items"][$i]["transactions"]);
            for ($h = 0; $h < $test; $h++) {
                $tcount = count($decltrans["result"]["items"][$i]["transactions"][$h]["transfers"]);
                for ($j = 0; $j < $tcount; $j++) {
                    if ($addr[0] == $decltrans["result"]["items"][$i]["transactions"][0]["transfers"][$j]["address"]) {
                        if ($decltrans["result"]["items"][$i]["transactions"][0]["transfers"][$j]["amount"] < 0) {
                                if ($decltrans["result"]["items"][$i]["transactions"][0]["paymentId"] != "") {
                                    echo "Outgoing: " . "<a target='_blank' href='https://turtle-coin.com/?hash=" . $decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"] . "#blockchain_transaction'>" . substr($decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"], 0, -30) . "...</a> &nbsp;&nbsp;&nbsp;" . $decltrans["result"]["items"][$i]["transactions"][0]["transfers"][$j]["amount"] / 100 . " TRTL &nbsp;&nbsp;&nbsp; Fee: " . $decltrans["result"]["items"][$i]["transactions"][0]["fee"] / 100 . " TRTL <br> PaymentId: " . $decltrans["result"]["items"][$i]["transactions"][0]["paymentId"] . "</p>";
                                }
                                else {
                                    echo "Outgoing: " . "<a target='_blank' href='https://turtle-coin.com/?hash=" . $decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"] . "#blockchain_transaction'>" . substr($decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"], 0, -30) . "...</a> &nbsp;&nbsp;&nbsp;" . $decltrans["result"]["items"][$i]["transactions"][0]["transfers"][$j]["amount"] / 100 . " TRTL &nbsp;&nbsp;&nbsp; Fee: " . $decltrans["result"]["items"][$i]["transactions"][0]["fee"] / 100 . "</p>";
                                }
                            }
                            else {
                                if ($decltrans["result"]["items"][$i]["transactions"][0]["paymentId"] != "") {
                                    echo "Incoming: " . "<a target='_blank' href='https://turtle-coin.com/?hash=" . $decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"] . "#blockchain_transaction'>" . substr($decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"], 0, -30) . "...</a> &nbsp;&nbsp;&nbsp;+" . $decltrans["result"]["items"][$i]["transactions"][0]["transfers"][$j]["amount"] / 100 . " TRTL &nbsp;&nbsp;&nbsp; Fee: " . $decltrans["result"]["items"][$i]["transactions"][0]["fee"] / 100 . " TRTL <br> PaymentId: " . $decltrans["result"]["items"][$i]["transactions"][0]["paymentId"] . "</p>";
                                }
                                else {
                                    echo "Incoming: " . "<a target='_blank' href='https://turtle-coin.com/?hash=" . $decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"] . "#blockchain_transaction'>" . substr($decltrans["result"]["items"][$i]["transactions"][0]["transactionHash"], 0, -30) . "...</a> &nbsp;&nbsp;&nbsp;+" . $decltrans["result"]["items"][$i]["transactions"][0]["transfers"][$j]["amount"] / 100 . " TRTL &nbsp;&nbsp;&nbsp; Fee: " . $decltrans["result"]["items"][$i]["transactions"][0]["fee"] / 100 . " TRTL <br>" . "</p>";
                                }
                        }
                    }
                }
            }
        }
    }
    else {
        echo "<span style='color: red'>No transactions found!</span>";
    }
     ?>
   </p>
   <a href="wallet.php?del=true"><button>Delete this address</button></a><br>
 !DELETED ADDRESSES ARE NOT RECOVERABLE WITHOUT KEYS!
 </body>
</html>
